<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $sitemap = rtrim(config('app.url'), '/').'/sitemap.xml';

        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /admin/',
            'Disallow: /client',
            'Disallow: /client/',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /password/',
            '',
            'Sitemap: '.$sitemap,
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
