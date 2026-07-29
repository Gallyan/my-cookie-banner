<?php

/**
 * Plugin Name: My Cookie Banner
 * Description: Bannière de cookies : consentement, révocation, multilingue (WPML/Polylang), blocage des contenus tiers.
 * Version: 1.0.0
 * Author: Guillaume
 * License: MIT
 * Requires at least: 5.4
 * Requires PHP: 7.4
 * Update URI: false
 */

if (! defined('ABSPATH')) {
    exit;
}

define('MCB_VERSION', '1.0.0');
define('MCB_DIR', __DIR__);
define('MCB_COOKIE_NAME', 'mcb_consent');

require MCB_DIR . '/includes/settings.php';
require MCB_DIR . '/includes/frontend.php';
require MCB_DIR . '/includes/blocker.php';

function mcb_url(string $path): string
{
    return plugins_url($path, __FILE__);
}

function mcb_consent_status(): string
{
    $value = $_COOKIE[MCB_COOKIE_NAME] ?? '';

    if ($value === 'accepted' || $value === 'refused') {
        return $value;
    }

    return 'pending';
}

function mcb_has_consent(): bool
{
    return mcb_consent_status() === 'accepted';
}

/**
 * @param array<string, string>|string $atts
 */
function mcb_revoke_shortcode($atts = []): string
{
    $atts = shortcode_atts(['label' => ''], (array) $atts, 'mcb_revoke');

    $label = $atts['label'] !== ''
        ? $atts['label']
        : mcb_texts_for(mcb_current_language())['revoke'];

    return '<a href="#" class="mcb-revoke-link" data-mcb-revoke>' . esc_html($label) . '</a>';
}
add_shortcode('mcb_revoke', 'mcb_revoke_shortcode');
