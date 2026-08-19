<?php

namespace App\Console\Commands;

use App\Models\EvolutionInstance;
use App\Services\WhatsApp\Evolution\EvolutionApiException;
use App\Services\WhatsApp\Evolution\EvolutionInstanceState;
use App\Services\WhatsApp\Evolution\EvolutionService;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Console\Command;

/**
 * Read-only. Prints what Evolution actually answers for every registered instance, so
 * "connected on Evolution but close in the panel" can be diagnosed without guessing:
 * usually the stored name is not the name the server knows, or the instance points at a
 * different Evolution server than the one the QR was scanned on.
 *
 * Writes nothing — safe to run on production at any time.
 */
class DiagnoseEvolutionInstancesCommand extends Command
{
    protected $signature = 'whatsapp:evolution-diagnose
                            {instance? : اسم instance محدّد (الافتراضي: كل المسجّلين)}
                            {--json : خرج JSON}';

    protected $description = 'فحص حالة Evolution API لكل instance وعرض الردود الخام (قراءة فقط، لا يكتب شيئاً)';

    public function handle(EvolutionService $service, WhatsAppSettingsService $settingsService): int
    {
        $settings = $settingsService->getSettings();
        $json = (bool) $this->option('json');

        $report = [
            'global' => [
                'base_url' => (string) ($settings['evolution_base_url'] ?? ''),
                'api_key' => self::mask((string) ($settings['evolution_api_key'] ?? '')),
                'default_instance_name' => (string) ($settings['evolution_instance_name'] ?? ''),
            ],
            'instances' => [],
        ];

        if (! $json) {
            $this->info('الإعدادات العامة');
            $this->line('  Base URL : '.($report['global']['base_url'] ?: '(غير مضبوط)'));
            $this->line('  API Key  : '.($report['global']['api_key'] ?: '(غير مضبوط)'));
            $this->line('  الافتراضي : '.($report['global']['default_instance_name'] ?: '(غير محدّد)'));
            $this->newLine();
        }

        $name = trim((string) $this->argument('instance'));

        $query = EvolutionInstance::query()->orderBy('instance_name');
        if ($name !== '') {
            $query->where('instance_name', $name);
        }

        $instances = $query->get();

        if ($instances->isEmpty()) {
            $this->warn($name !== ''
                ? 'لا يوجد instance مسجّل بالاسم: '.$name
                : 'لا توجد instances مسجّلة.');

            return self::FAILURE;
        }

        $problems = 0;

        foreach ($instances as $instance) {
            $entry = $this->inspect($service, $instance);
            $report['instances'][] = $entry;

            if ($entry['verdict'] !== 'ok') {
                $problems++;
            }

            if (! $json) {
                $this->render($entry);
            }
        }

        if ($json) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return $problems > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function inspect(EvolutionService $service, EvolutionInstance $instance): array
    {
        $config = $instance->hasCustomCredentials()
            ? $instance->resolveApiConfig()
            : ['base_url' => '(عام)', 'api_key' => '(عام)'];

        $entry = [
            'instance_name' => $instance->instance_name,
            'stored_state' => $instance->connection_status,
            'stored_number' => $instance->phone_number,
            'credentials' => $instance->hasCustomCredentials() ? 'خاص' : 'عام',
            'base_url' => $config['base_url'],
            'api_key' => self::mask((string) $config['api_key']),
            'list_error' => null,
            'state_error' => null,
            'remote_names' => null,
            'found' => null,
            'remote_state' => null,
            'remote_owner_jid' => null,
            'remote_number' => null,
            'verdict' => 'unknown',
            'hint' => null,
        ];

        $client = $service->clientFor($instance);

        try {
            $list = $client->fetchInstances();
            $entry['remote_names'] = EvolutionInstanceState::names($list);
            $row = EvolutionInstanceState::findRow($list, $instance->instance_name);
            $entry['found'] = $row !== null;

            if ($row !== null) {
                $entry['remote_state'] = EvolutionInstanceState::readConnectionState($row);
                $entry['remote_owner_jid'] = EvolutionInstanceState::ownerJid($row);
                $entry['remote_number'] = EvolutionInstanceState::phoneNumber($row);
            }
        } catch (\Throwable $e) {
            $entry['list_error'] = EvolutionApiException::resolveUserMessage($e);
        }

        try {
            $entry['remote_state'] = EvolutionInstanceState::readConnectionState(
                $client->getConnectionState($instance->instance_name)
            ) ?? $entry['remote_state'];
        } catch (\Throwable $e) {
            $entry['state_error'] = EvolutionApiException::resolveUserMessage($e);
        }

        [$entry['verdict'], $entry['hint']] = $this->verdict($entry);

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array{0: string, 1: ?string}
     */
    private function verdict(array $entry): array
    {
        if ($entry['found'] === false) {
            $names = $entry['remote_names'] ?: [];

            return ['name_mismatch', $names !== []
                ? 'الاسم غير موجود على هذا السيرفر. الموجود فعلاً: «'.implode('»، «', $names).'».'
                    .' صحّح الاسم ليطابق أحدها حرفياً (بنفس المسافات وحالة الأحرف).'
                : 'السيرفر لا يُرجع أي instance. تأكد أن الرابط يشير إلى نفس السيرفر الذي مسحت QR عليه.',
            ];
        }

        if ($entry['list_error'] !== null && $entry['remote_state'] === null) {
            return ['unreachable', 'تعذّر الوصول: '.$entry['list_error']];
        }

        if ($entry['remote_state'] === null) {
            return ['unreadable', 'السيرفر أجاب لكن بصيغة لا تحتوي على الحالة. أرسل خرج --json للمراجعة.'];
        }

        if ($entry['remote_state'] !== EvolutionInstanceState::OPEN) {
            return ['not_connected', 'السيرفر نفسه يقول «'.$entry['remote_state'].'» — الجهاز غير مرتبط على هذا السيرفر.'
                .' إن كان مرتبطاً على Evolution Manager، فالرابط أعلاه يشير إلى سيرفر آخر.'];
        }

        if ($entry['stored_state'] !== EvolutionInstanceState::OPEN) {
            return ['stale', 'السيرفر يقول open والقاعدة تقول «'.$entry['stored_state'].'». اضغط «مزامنة الحالة».'];
        }

        return ['ok', null];
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function render(array $entry): void
    {
        $this->line('── '.$entry['instance_name'].' ──');
        $this->line('  الاعتمادات      : '.$entry['credentials'].'  |  '.$entry['base_url'].'  |  '.$entry['api_key']);
        $this->line('  في القاعدة      : الحالة='.($entry['stored_state'] ?? 'null').'  الرقم='.($entry['stored_number'] ?? '—'));
        $this->line('  على السيرفر     : موجود='.match ($entry['found']) {
            true => 'نعم', false => 'لا', default => 'غير معروف',
        }.'  الحالة='.($entry['remote_state'] ?? 'غير مقروءة')
            .'  JID='.($entry['remote_owner_jid'] ?? '—')
            .'  الرقم='.($entry['remote_number'] ?? '—'));

        if (is_array($entry['remote_names'])) {
            $this->line('  أسماء السيرفر   : '.($entry['remote_names'] !== [] ? implode(' | ', $entry['remote_names']) : '(لا شيء)'));
        }
        if ($entry['list_error'] !== null) {
            $this->line('  خطأ القائمة     : '.$entry['list_error']);
        }
        if ($entry['state_error'] !== null) {
            $this->line('  خطأ الحالة      : '.$entry['state_error']);
        }

        $line = '  النتيجة         : '.$entry['verdict'].($entry['hint'] !== null ? ' — '.$entry['hint'] : '');
        $entry['verdict'] === 'ok' ? $this->info($line) : $this->warn($line);
        $this->newLine();
    }

    private static function mask(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return strlen($value) <= 8
            ? str_repeat('*', strlen($value))
            : substr($value, 0, 4).str_repeat('*', 6).substr($value, -2);
    }
}
