<?php

namespace App\Support;

class FontAssets
{
    /**
     * Return the Vite font @font-face CSS with root-relative URLs.
     *
     * We read the built CSS file directly from fonts-manifest.json instead of
     * calling Vite::fonts(). That helper calls asset() internally to build
     * <link rel="preload"> href values, and asset() emits absolute http://
     * URLs when the reverse-proxy trust is not configured — even when APP_URL
     * is https:// — causing 24 Mixed Content errors in production.
     *
     * By reading the CSS file ourselves and rewriting every relative path to
     * a root-relative /build/assets/ URL we produce output that:
     *   • carries no scheme, so it can never become http:// mixed content,
     *   • resolves correctly from every page path (/admin, /track/…, /),
     *   • is unaffected by APP_URL, ASSET_URL, proxy headers, or OPcache
     *     stale state on the production server.
     *
     * All 24 @font-face rules (3 families × 4 weights × woff2+woff), all
     * Arabic font families, and the font-display setting are preserved as-is.
     */
    public static function inlineCss(): string
    {
        // ── 1. Locate the fonts CSS file via fonts-manifest.json ─────────────
        $manifestPath = public_path('build/fonts-manifest.json');

        if (! is_file($manifestPath)) {
            return '';
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        // "style.file" is e.g. "assets/fonts-CiWWb1kQ.css"
        $cssFile = $manifest['style']['file'] ?? null;

        if (! $cssFile) {
            return '';
        }

        $cssPath = public_path('build/' . $cssFile);

        if (! is_file($cssPath)) {
            return '';
        }

        // ── 2. Read the raw CSS ───────────────────────────────────────────────
        $css = file_get_contents($cssPath);

        // ── 3. Rewrite relative url() values to root-relative paths ──────────
        //
        // The CSS file lives at public/build/assets/fonts-*.css.
        // Its url() values are relative to that file, e.g.:
        //   url("./noto-naskh-arabic-400-normal-Dajb6q3W.woff2")
        //
        // We rewrite them all to root-relative:
        //   url("/build/assets/noto-naskh-arabic-400-normal-Dajb6q3W.woff2")
        //
        // Both quoted ('url("./')' and unquoted ('url(./') forms are handled.
        $base = 'url("/build/assets/';

        $css = str_replace(
            ['url("./', 'url(./'],
            [$base, $base],
            $css,
        );

        // Safety net: if a future build bakes absolute URLs into the CSS
        // (e.g. built while APP_URL was http://), normalise them too.
        $css = preg_replace(
            '#(["\']?)https?://[^/"\'  ]+/build/assets/#i',
            '$1/build/assets/',
            $css,
        );

        // ── 4. Wrap in a <style> block ────────────────────────────────────────
        return '<style>' . "\n" . trim($css, "\n") . "\n" . '</style>';
    }
}
