<?php

namespace App\Services\Coolify;

use App\Services\CoolifyApiService;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class WordpressComposeFilebrowserMerger
{
    public const FILEBROWSER_VOLUME = 'wordpress-files';

    public const FILEBROWSER_META_VOLUME = 'filebrowser-meta';

    public function __construct(
        protected CoolifyApiService $coolify
    ) {}

    public function merge(string $wordpressServiceType): string
    {
        $wordpressServiceType = strtolower(trim($wordpressServiceType));
        if ($wordpressServiceType === '') {
            throw new RuntimeException('نوع خدمة WordPress غير محدد.');
        }

        $wpYaml = $this->coolify->getServiceTemplateComposeYaml($wordpressServiceType);
        if ($wpYaml === null) {
            throw new RuntimeException('تعذّر تحميل قالب WordPress: '.$wordpressServiceType);
        }

        $fbYaml = $this->coolify->getServiceTemplateComposeYaml('filebrowser');
        if ($fbYaml === null) {
            $fbYaml = $this->fallbackFilebrowserComposeYaml();
        }

        try {
            /** @var array<string, mixed> $wp */
            $wp = Yaml::parse($wpYaml);
            /** @var array<string, mixed> $fb */
            $fb = Yaml::parse($fbYaml);
        } catch (ParseException $e) {
            throw new RuntimeException('فشل تحليل قالب compose: '.$e->getMessage(), 0, $e);
        }

        $fbServices = $fb['services'] ?? null;
        if (! is_array($fbServices) || ! isset($fbServices['filebrowser']) || ! is_array($fbServices['filebrowser'])) {
            throw new RuntimeException('قالب filebrowser لا يحتوي على خدمة filebrowser.');
        }

        if (! isset($wp['services']) || ! is_array($wp['services'])) {
            $wp['services'] = [];
        }

        $filebrowser = $fbServices['filebrowser'];
        $filebrowser['volumes'] = [
            self::FILEBROWSER_VOLUME.':/srv',
            self::FILEBROWSER_META_VOLUME.':/database.db',
        ];

        $filebrowser['command'] = [
            '--root=/srv',
            '--database=/database.db',
            '--address=0.0.0.0',
            '--port=80',
        ];

        $filebrowser['environment'] = $this->normalizeEnvironmentList(
            $filebrowser['environment'] ?? [],
            ['SERVICE_FQDN_FILEBROWSER_80']
        );

        $wp['services']['filebrowser'] = $filebrowser;

        if (! isset($wp['volumes']) || ! is_array($wp['volumes'])) {
            $wp['volumes'] = [];
        }
        $wp['volumes'][self::FILEBROWSER_META_VOLUME] = null;

        return Yaml::dump($wp, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
    }

    /**
     * @param  array<int|string, mixed>  $environment
     * @param  array<int, string>  $required
     * @return array<int, string>
     */
    protected function normalizeEnvironmentList(array $environment, array $required): array
    {
        $lines = [];
        foreach ($environment as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $lines[] = $value;
            } elseif (is_string($key)) {
                $lines[] = $key.'='.$value;
            }
        }

        foreach ($required as $env) {
            $found = false;
            foreach ($lines as $line) {
                if (str_starts_with($line, $env)) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $lines[] = $env;
            }
        }

        return array_values($lines);
    }

    protected function fallbackFilebrowserComposeYaml(): string
    {
        return <<<'YAML'
services:
  filebrowser:
    image: 'filebrowser/filebrowser:latest'
    environment:
      - SERVICE_FQDN_FILEBROWSER_80
    healthcheck:
      test:
        - CMD
        - wget
        - '-q'
        - '--spider'
        - 'http://127.0.0.1:80/health'
      interval: 2s
      timeout: 10s
      retries: 15
YAML;
    }
}
