<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Motifs d'URL bloqués tant que le consentement n'est pas donné.
 *
 * @return array<int, string>
 */
function mcb_blocked_hosts(): array
{
    static $hosts = null;

    if ($hosts !== null) {
        return $hosts;
    }

    $parsed = array_filter(array_map('trim', explode("\n", mcb_get_settings()['blocked_hosts'])));

    $hosts = apply_filters('mcb_blocked_hosts', array_values($parsed));

    return $hosts;
}

function mcb_src_is_blocked(string $src): bool
{
    foreach (mcb_blocked_hosts() as $host) {
        if (stripos($src, $host) !== false) {
            return true;
        }
    }

    return false;
}

function mcb_maybe_start_buffer(): void
{
    if (is_admin() || is_feed() || is_embed()) {
        return;
    }

    if (wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }

    $settings = mcb_get_settings();

    if (! $settings['enabled'] || ! $settings['blocking_enabled']) {
        return;
    }

    if (mcb_blocked_hosts() === []) {
        return;
    }

    ob_start('mcb_filter_output');
}
add_action('template_redirect', 'mcb_maybe_start_buffer', 999);

/**
 * Réécrit le HTML complet de la page : les iframes bloqués perdent leur src
 * (conservé dans data-mcb-src) et les scripts bloqués passent en text/plain.
 * La restauration après consentement est faite en JavaScript, sans rechargement,
 * ce qui rend le mécanisme compatible avec le cache de pages.
 */
function mcb_filter_output(string $html): string
{
    if ($html === '') {
        return $html;
    }

    if (stripos($html, '<iframe') === false && stripos($html, '<script') === false) {
        return $html;
    }

    $html = preg_replace_callback('#<iframe\b[^>]*>#is', 'mcb_block_iframe_tag', $html) ?? $html;
    $html = preg_replace_callback(
        '#<script\b[^>]*\bsrc\s*=\s*["\'][^"\']+["\'][^>]*>#is',
        'mcb_block_script_tag',
        $html
    ) ?? $html;

    return $html;
}

/** @param array<int, string> $matches */
function mcb_block_iframe_tag(array $matches): string
{
    $tag = $matches[0];

    if (strpos($tag, 'data-mcb-src') !== false) {
        return $tag;
    }

    if (! preg_match('#\ssrc\s*=\s*["\']([^"\']+)["\']#i', $tag, $src)) {
        return $tag;
    }

    if (! mcb_src_is_blocked($src[1])) {
        return $tag;
    }

    return str_replace(
        $src[0],
        ' src="about:blank" data-mcb-src="' . esc_url($src[1]) . '"',
        $tag
    );
}

/** @param array<int, string> $matches */
function mcb_block_script_tag(array $matches): string
{
    $tag = $matches[0];

    if (strpos($tag, 'data-mcb-block') !== false) {
        return $tag;
    }

    if (! preg_match('#\bsrc\s*=\s*["\']([^"\']+)["\']#i', $tag, $src)) {
        return $tag;
    }

    if (! mcb_src_is_blocked($src[1])) {
        return $tag;
    }

    $tag = preg_replace('#\stype\s*=\s*["\'][^"\']*["\']#i', '', $tag);

    return preg_replace('#^<script#i', '<script type="text/plain" data-mcb-block="1"', $tag, 1);
}
