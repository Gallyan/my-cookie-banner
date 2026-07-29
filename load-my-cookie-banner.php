<?php
/**
 * Plugin Name: My Cookie Banner (loader)
 * Description: Chargeur mu-plugin — WordPress ne charge pas les sous-répertoires de mu-plugins, ce fichier doit être copié à la racine de wp-content/mu-plugins/.
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once WPMU_PLUGIN_DIR . '/my-cookie-banner/my-cookie-banner.php';
