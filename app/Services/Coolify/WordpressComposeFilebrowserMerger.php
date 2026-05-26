<?php

namespace App\Services\Coolify;

use App\Services\CoolifyApiService;
use RuntimeException;

class WordpressComposeFilebrowserMerger
{
    public const FILEBROWSER_VOLUME = 'wordpress-files';

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

        if (preg_match('/^\s*filebrowser\s*:/m', $wpYaml)) {
            return $wpYaml;
        }

        if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            return $this->mergeWithSymfonyYaml($wpYaml);
        }

        return $this->mergeWithStringAppend($wpYaml);
    }

    /**
     * Coolify API يتطلب docker_compose_raw بصيغة base64 ولا يقبل type معه في نفس الطلب.
     */
    public function mergeForCoolifyApi(string $wordpressServiceType): string
    {
        return base64_encode($this->merge($wordpressServiceType));
    }

    protected function mergeWithSymfonyYaml(string $wpYaml): string
    {
        $yamlClass = \Symfony\Component\Yaml\Yaml::class;

        $filebrowser = $this->loadFilebrowserServiceFromTemplate();
        $filebrowser = $this->patchFilebrowserService($filebrowser);

        try {
            /** @var array<string, mixed> $wp */
            $wp = $yamlClass::parse($wpYaml);
        } catch (\Throwable $e) {
            throw new RuntimeException('فشل تحليل قالب WordPress: '.$e->getMessage(), 0, $e);
        }

        if (! isset($wp['services']) || ! is_array($wp['services'])) {
            $wp['services'] = [];
        }

        $wp['services']['filebrowser'] = $filebrowser;

        if (isset($wp['volumes']) && is_array($wp['volumes'])) {
            unset($wp['volumes']['filebrowser-meta']);
            if ($wp['volumes'] === []) {
                unset($wp['volumes']);
            }
        }

        return $yamlClass::dump($wp, 6, 2, $yamlClass::DUMP_MULTI_LINE_LITERAL_BLOCK);
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadFilebrowserServiceFromTemplate(): array
    {
        $fbYaml = $this->coolify->getServiceTemplateComposeYaml('filebrowser');
        if ($fbYaml === null) {
            return $this->parseFilebrowserServiceFromYaml($this->fallbackFilebrowserComposeYaml());
        }

        return $this->parseFilebrowserServiceFromYaml($fbYaml);
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseFilebrowserServiceFromYaml(string $yaml): array
    {
        if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            /** @var array<string, mixed> $parsed */
            $parsed = \Symfony\Component\Yaml\Yaml::parse($yaml);
            $services = $parsed['services'] ?? null;
            if (is_array($services) && isset($services['filebrowser']) && is_array($services['filebrowser'])) {
                return $services['filebrowser'];
            }
        }

        throw new RuntimeException('قالب filebrowser لا يحتوي على خدمة filebrowser.');
    }

    /**
     * يبقي إعداد Coolify الأصلي (filebrowser.json + database.db) ويستبدل mount الملفات فقط.
     *
     * @param  array<string, mixed>  $filebrowser
     * @return array<string, mixed>
     */
    protected function patchFilebrowserService(array $filebrowser): array
    {
        unset($filebrowser['command']);

        $volumes = [];
        $srvReplaced = false;

        foreach ($filebrowser['volumes'] ?? [] as $volume) {
            if (is_string($volume)) {
                if (str_contains($volume, ':/srv') || str_ends_with($volume, ':/srv')) {
                    $volumes[] = self::FILEBROWSER_VOLUME.':/srv';
                    $srvReplaced = true;

                    continue;
                }
                $volumes[] = $volume;

                continue;
            }

            if (! is_array($volume)) {
                continue;
            }

            $target = (string) ($volume['target'] ?? '');
            if ($target === '/srv') {
                $volumes[] = self::FILEBROWSER_VOLUME.':/srv';
                $srvReplaced = true;

                continue;
            }

            $volumes[] = $volume;
        }

        if (! $srvReplaced) {
            array_unshift($volumes, self::FILEBROWSER_VOLUME.':/srv');
        }

        $filebrowser['volumes'] = $volumes;

        $filebrowser['environment'] = $this->normalizeEnvironmentList(
            $filebrowser['environment'] ?? [],
            ['SERVICE_FQDN_FILEBROWSER_80']
        );

        return $filebrowser;
    }

    protected function mergeWithStringAppend(string $wpYaml): string
    {
        $block = "\n".$this->filebrowserServiceYamlBlock();

        return rtrim($wpYaml).$block;
    }

    protected function filebrowserServiceYamlBlock(): string
    {
        return <<<'YAML'
  filebrowser:
    image: 'filebrowser/filebrowser:latest'
    environment:
      - SERVICE_FQDN_FILEBROWSER_80
    volumes:
      - 'wordpress-files:/srv'
      -
        type: bind
        source: ./database.db
        target: /database.db
        isDirectory: false
        content: ''
      -
        type: bind
        source: ./filebrowser.json
        target: /.filebrowser.json
        read_only: true
        content: "{\n  \"address\": \"0.0.0.0\",\n  \"port\": 80\n}\n"
YAML;
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
        return "services:\n".$this->filebrowserServiceYamlBlock();
    }
}
