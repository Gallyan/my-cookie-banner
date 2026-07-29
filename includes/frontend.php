<?php

if (! defined('ABSPATH')) {
    exit;
}

function mcb_enqueue_assets(): void
{
    $settings = mcb_get_settings();

    if (! $settings['enabled']) {
        return;
    }

    wp_enqueue_style('my-cookie-banner', mcb_url('assets/banner.css'), [], MCB_VERSION);
    wp_add_inline_style('my-cookie-banner', mcb_inline_css($settings));

    wp_enqueue_script('my-cookie-banner', mcb_url('assets/banner.js'), [], MCB_VERSION, false);

    $texts = mcb_texts_for(mcb_current_language());

    $config = [
        'cookieName' => MCB_COOKIE_NAME,
        'cookieDays' => (int) $settings['cookie_days'],
        'texts'      => [
            'placeholder'       => $texts['placeholder'],
            'placeholderButton' => $texts['placeholder_button'],
        ],
    ];

    wp_add_inline_script(
        'my-cookie-banner',
        'var mcbConfig = ' . wp_json_encode($config) . ';',
        'before'
    );
}
add_action('wp_enqueue_scripts', 'mcb_enqueue_assets');

/** @param array<string, mixed> $settings */
function mcb_inline_css(array $settings): string
{
    $css = ':root {';

    foreach ($settings['colors'] as $key => $value) {
        $key = str_replace('_', '-', $key);
        $css .= "--mcb-{$key}: {$value};";
    }

    $css .= '}';

    if ($settings['custom_css'] !== '') {
        $css .= "\n" . $settings['custom_css'];
    }

    return $css;
}

function mcb_render_banner(): void
{
    $settings = mcb_get_settings();

    if (! $settings['enabled']) {
        return;
    }

    $texts = mcb_texts_for(mcb_current_language());
    $privacyUrl = $texts['privacy_url'] !== '' ? $texts['privacy_url'] : get_privacy_policy_url();
    ?>
    <div id="mcb-banner" class="mcb-banner mcb-pos-<?php echo esc_attr($settings['position']); ?>"
         role="region" aria-live="polite" aria-label="Cookies" tabindex="-1" hidden>
        <div class="mcb-message">
            <?php echo wp_kses_post($texts['message']); ?>
            <?php if ($privacyUrl) : ?>
                <a class="mcb-privacy"
                   href="<?php echo esc_url($privacyUrl); ?>"><?php echo esc_html($texts['privacy_label']); ?></a>
            <?php endif; ?>
        </div>
        <div class="mcb-actions">
            <button type="button" class="mcb-btn mcb-btn-refuse"
                    data-mcb-refuse><?php echo esc_html($texts['refuse']); ?></button>
            <button type="button" class="mcb-btn mcb-btn-accept"
                    data-mcb-accept><?php echo esc_html($texts['accept']); ?></button>
        </div>
    </div>
    <?php
    if (! $settings['show_revoke_button']) {
        return;
    }
    ?>
    <button type="button" id="mcb-revoke" class="mcb-revoke" data-mcb-revoke hidden
            title="<?php echo esc_attr($texts['revoke']); ?>"
            aria-label="<?php echo esc_attr($texts['revoke']); ?>">🍪</button>
    <?php
}
add_action('wp_footer', 'mcb_render_banner');
