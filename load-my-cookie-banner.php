<?php

/**
 * Plugin Name: My Cookie Banner (loader)
 * Description: Chargeur mu-plugin — à copier à la racine de wp-content/mu-plugins/ (sous-répertoires non chargés).
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once WPMU_PLUGIN_DIR . '/my-cookie-banner/my-cookie-banner.php';
