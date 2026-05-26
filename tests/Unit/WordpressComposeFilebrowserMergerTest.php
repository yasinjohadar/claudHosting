<?php

use App\Services\Coolify\WordpressComposeFilebrowserMerger;
use App\Services\CoolifyApiService;

const FILEBROWSER_TEMPLATE_YAML = <<<'YAML'
services:
  filebrowser:
    image: 'filebrowser/filebrowser:latest'
    command: '--root=/srv'
    environment:
      - SERVICE_FQDN_FILEBROWSER_80
    volumes:
      - type: bind
        source: ./srv
        target: /srv
      - type: bind
        source: ./database.db
        target: /database.db
        content: ''
      - type: bind
        source: ./filebrowser.json
        target: /.filebrowser.json
        read_only: true
        content: "{\"address\": \"0.0.0.0\", \"port\": 80}\n"
volumes:
  filebrowser-meta:
YAML;

const WORDPRESS_TEMPLATE_YAML = <<<'YAML'
services:
  wordpress:
    image: 'wordpress:latest'
  mariadb:
    image: 'mariadb:11'
volumes:
  wordpress-files:
YAML;

test('merged compose uses coolify filebrowser template with shared wordpress volume', function () {
    $coolify = Mockery::mock(CoolifyApiService::class);
    $coolify->shouldReceive('getServiceTemplateComposeYaml')
        ->with('wordpress-with-mariadb')
        ->andReturn(WORDPRESS_TEMPLATE_YAML);
    $coolify->shouldReceive('getServiceTemplateComposeYaml')
        ->with('filebrowser')
        ->andReturn(FILEBROWSER_TEMPLATE_YAML);

    $merger = new WordpressComposeFilebrowserMerger($coolify);
    $yaml = $merger->merge('wordpress-with-mariadb');

    expect($yaml)
        ->toContain('wordpress-files:/srv')
        ->toContain('filebrowser.json')
        ->toContain('database.db')
        ->not->toContain('filebrowser-meta')
        ->not->toMatch('/^\s*command:/m');
});

test('merge does not duplicate filebrowser when already present', function () {
    $coolify = Mockery::mock(CoolifyApiService::class);
    $coolify->shouldReceive('getServiceTemplateComposeYaml')
        ->once()
        ->with('wordpress-with-mariadb')
        ->andReturn("services:\n  filebrowser:\n    image: test\n");

    $merger = new WordpressComposeFilebrowserMerger($coolify);
    $yaml = $merger->merge('wordpress-with-mariadb');

    expect(substr_count($yaml, 'filebrowser:'))->toBe(1);
});
