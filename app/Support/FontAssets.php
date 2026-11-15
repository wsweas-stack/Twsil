<?php

namespace App\Support;

use Illuminate\Support\Facades\Vite;

class FontAssets
{
    /**
     * Return the Vite font CSS with browser-resolvable URLs.
     *
     * The Vite fonts CSS inlines @font-face rules whose url() values are
     * relative to the built CSS file (public/build/assets). When that CSS is
     * inlined into a page rendered under a nested path (/admin, /track/...),
     * browsers resolve the URLs against the page URL and the fonts 404.
     * This rewrites them to absolute /build/assets URLs served by the app.
     */
    public static function inlineCss(): string
    {
        $css = (string) Vite::fonts();
        $base = 'url("' . asset('build/assets') . '/';

        $css = str_replace(
            ['url("./', 'url(./'],
            [$base, $base],
            $css,
        );

        if (app()->environment('production')
            || str_starts_with((string) config('app.url'), 'https://')) {
            // Behind the HTTPS reverse proxy, browsers block http:// font
            // requests as mixed content. Trust Proxies already make asset()
            // emit https://; this is a fallback for proxies that omit
            // X-Forwarded-Proto. Also covers platforms that run with a
            // https:// APP_URL outside the production environment.
            $css = str_replace('http://', 'https://', $css);
        }

        // Vite::fonts() preloads every font family/weight combination from
        // fonts-manifest.json (3 families x 4 weights) whether the page uses
        // them or not, which produces "preloaded but not used" warnings.
        // The @font-face rules below still load fonts on demand, so drop the
        // redundant preload links.
        $css = preg_replace('/<link\s[^>]*rel="preload"[^>]*>\s*/i', '', $css);

        return $css;
    }
}
