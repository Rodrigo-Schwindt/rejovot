<?php

namespace App\Support;

use App\Models\Metadata;

class Seo
{
    public static function forCurrentRoute(array $overrides = []): array
    {
        $routeName = optional(request()->route())->getName();

        $title = self::title();
        $description = 'Autopartes y repuestos para todas las marcas. Catálogo mayorista Rejovot.';
        $keywords = 'rejovot, autopartes, repuestos, mayorista';
        $robots = 'index, follow';
        $metadata = null;

        if ($routeName === 'home') {
            $metadata = Metadata::getForSection('home');
        } elseif ($routeName === 'productos') {
            $metadata = Metadata::getForSection('productos');
            $title = self::title('Productos');
        } elseif (request()->is('admin*') || $routeName === 'login') {
            $title = self::title('Panel administrativo');
            $description = 'Panel administrativo Rejovot.';
            $keywords = 'rejovot, admin';
            $robots = 'noindex, nofollow';
        }

        if ($metadata) {
            $description = $metadata->description ?: $description;
            $keywords = $metadata->keywords ?: $keywords;
        }

        return [
            'title'       => $overrides['title'] ?? $title,
            'description' => $overrides['description'] ?? self::clean($description),
            'keywords'    => $overrides['keywords'] ?? self::clean($keywords),
            'robots'      => $overrides['robots'] ?? $robots,
            'canonical'   => $overrides['canonical'] ?? url()->current(),
            'image'       => $overrides['image'] ?? asset('og-image.png'),
            'type'        => $overrides['type'] ?? 'website',
            'site_name'   => self::brand(),
        ];
    }

    public static function brand(): string
    {
        $name = config('app.name');

        return $name && $name !== 'Laravel' ? $name : 'Rejovot';
    }

    public static function title(?string $page = null): string
    {
        return filled($page) ? trim($page) . ' | ' . self::brand() : self::brand();
    }

    private static function clean($value): string
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text ?? '');
    }
}
