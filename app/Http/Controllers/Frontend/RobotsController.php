<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\GlobalSeoService;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(GlobalSeoService $globalSeo): Response
    {
        $settings = $globalSeo->robotsSettings();
        $lines = ['User-agent: *'];

        foreach ($globalSeo->robotsDisallowPaths() as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        if ($settings['enable_sitemap_line'] ?? true) {
            $lines[] = '';
            $lines[] = 'Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml';
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
