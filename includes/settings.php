<?php

if (! defined('ABSPATH')) {
    exit;
}

/** @return array<string, array<string, string>> */
function mcb_default_texts(): array
{
    return [
        'en' => [
            'message'            => 'We use cookies to improve your experience and to embed third-party content'
                . ' (YouTube videos, Google Maps…). You can accept or decline.',
            'accept'             => 'Accept',
            'refuse'             => 'Decline',
            'revoke'             => 'Cookie preferences',
            'placeholder'        => 'This content is blocked because you have not accepted cookies.',
            'placeholder_button' => 'Accept cookies and display',
            'privacy_label'      => 'Privacy policy',
            'privacy_url'        => '',
        ],
        'fr' => [
            'message'            => 'Nous utilisons des cookies pour améliorer votre expérience et intégrer'
                . ' des contenus tiers (vidéos YouTube, cartes Google Maps…). Vous pouvez accepter ou refuser.',
            'accept'             => 'Accepter',
            'refuse'             => 'Refuser',
            'revoke'             => 'Préférences cookies',
            'placeholder'        => 'Ce contenu est bloqué car vous n\'avez pas accepté les cookies.',
            'placeholder_button' => 'Accepter les cookies et afficher',
            'privacy_label'      => 'Politique de confidentialité',
            'privacy_url'        => '',
        ],
        'es' => [
            'message'            => 'Utilizamos cookies para mejorar su experiencia e integrar contenidos'
                . ' de terceros (vídeos de YouTube, mapas de Google Maps…). Puede aceptar o rechazar.',
            'accept'             => 'Aceptar',
            'refuse'             => 'Rechazar',
            'revoke'             => 'Preferencias de cookies',
            'placeholder'        => 'Este contenido está bloqueado porque no ha aceptado las cookies.',
            'placeholder_button' => 'Aceptar las cookies y mostrar',
            'privacy_label'      => 'Política de privacidad',
            'privacy_url'        => '',
        ],
    ];
}

/** @return array<string, mixed> */
function mcb_default_settings(): array
{
    return [
        'enabled'            => 1,
        'cookie_days'        => 180,
        'position'           => 'bottom',
        'show_revoke_button' => 1,
        'blocking_enabled'   => 1,
        'blocked_hosts'      => implode("\n", [
            // Vidéo
            'youtube.com',
            'youtube-nocookie.com',
            'youtu.be',
            'player.vimeo.com',
            'i.vimeocdn.com',
            'dailymotion.com/embed',
            'player.twitch.tv',
            // Cartes
            'google.com/maps',
            'maps.google.com',
            'maps.googleapis.com',
            'openstreetmap.org',
            // Réseaux sociaux
            'facebook.com/plugins',
            'connect.facebook.net',
            'instagram.com',
            'platform.twitter.com',
            'static.ads-twitter.com',
            'platform.linkedin.com',
            'pinterest.com/js/pinit.js',
            'tiktok.com',
            'snapchat.com',
            // Audio
            'open.spotify.com',
            'w.soundcloud.com',
            // Mesure d'audience et publicité
            'googletagmanager.com',
            'google-analytics.com',
            'googlesyndication.com',
            'doubleclick.net',
            'static.hotjar.com',
            // Divers
            'disqus.com/embed',
        ]),
        'colors'             => [
            'banner_bg'        => '#1f2937',
            'banner_text'      => '#f9fafb',
            'accept_bg'        => '#22c55e',
            'accept_text'      => '#052e16',
            'refuse_bg'        => '#4b5563',
            'refuse_text'      => '#f9fafb',
            'placeholder_bg'   => '#e5e7eb',
            'placeholder_text' => '#1f2937',
        ],
        'custom_css'         => '',
        'texts'              => mcb_default_texts(),
    ];
}

/** @return array<string, mixed> */
function mcb_get_settings(): array
{
    $saved = get_option('mcb_settings', []);

    if (! is_array($saved)) {
        $saved = [];
    }

    return array_replace_recursive(mcb_default_settings(), $saved);
}

function mcb_current_language(): string
{
    $lang = apply_filters('wpml_current_language', null);

    if (is_string($lang) && $lang !== '' && $lang !== 'all') {
        return $lang;
    }

    if (function_exists('pll_current_language')) {
        $lang = pll_current_language();

        if (is_string($lang) && $lang !== '') {
            return $lang;
        }
    }

    return substr(determine_locale(), 0, 2);
}

/** @return array<int, string> */
function mcb_languages(): array
{
    $settings = mcb_get_settings();
    $langs = array_keys($settings['texts']);

    $wpml = apply_filters('wpml_active_languages', null);

    if (is_array($wpml)) {
        $langs = array_merge($langs, array_keys($wpml));
    }

    if (function_exists('pll_languages_list')) {
        $langs = array_merge($langs, (array) pll_languages_list());
    }

    $langs = array_values(array_unique(array_filter($langs)));

    return apply_filters('mcb_languages', $langs);
}

/**
 * Textes pour une langue, avec repli sur l'anglais champ par champ.
 *
 * @return array<string, string>
 */
function mcb_texts_for(string $lang): array
{
    $texts = mcb_get_settings()['texts'];
    $base = $texts['en'] ?? reset($texts);

    if (! isset($texts[$lang])) {
        return $base;
    }

    $overlay = array_filter($texts[$lang], static fn ($value) => $value !== '');

    return array_merge($base, $overlay);
}

function mcb_register_settings(): void
{
    register_setting('mcb_settings_group', 'mcb_settings', [
        'type'              => 'array',
        'sanitize_callback' => 'mcb_sanitize_settings',
    ]);
}
add_action('admin_init', 'mcb_register_settings');

function mcb_admin_menu(): void
{
    add_options_page(
        'Cookie Banner',
        'Cookie Banner',
        'manage_options',
        'my-cookie-banner',
        'mcb_render_settings_page'
    );
}
add_action('admin_menu', 'mcb_admin_menu');

/**
 * @param mixed $input
 * @return array<string, mixed>
 */
function mcb_sanitize_settings($input): array
{
    $defaults = mcb_default_settings();

    if (! is_array($input)) {
        return $defaults;
    }

    $clean = [];
    $clean['enabled'] = empty($input['enabled']) ? 0 : 1;
    $clean['show_revoke_button'] = empty($input['show_revoke_button']) ? 0 : 1;
    $clean['blocking_enabled'] = empty($input['blocking_enabled']) ? 0 : 1;
    $clean['cookie_days'] = max(1, (int) ($input['cookie_days'] ?? $defaults['cookie_days']));

    $positions = ['bottom', 'top', 'bottom-left', 'bottom-right'];
    $clean['position'] = in_array($input['position'] ?? '', $positions, true)
        ? $input['position']
        : $defaults['position'];

    $clean['blocked_hosts'] = implode("\n", array_filter(array_map(
        'sanitize_text_field',
        explode("\n", (string) ($input['blocked_hosts'] ?? ''))
    )));

    $clean['custom_css'] = wp_strip_all_tags((string) ($input['custom_css'] ?? ''));

    $clean['colors'] = [];

    foreach ($defaults['colors'] as $key => $default) {
        $color = sanitize_hex_color((string) ($input['colors'][$key] ?? ''));
        $clean['colors'][$key] = $color ?: $default;
    }

    $texts = is_array($input['texts'] ?? null) ? $input['texts'] : [];

    $newLang = substr(sanitize_key((string) ($input['new_lang'] ?? '')), 0, 10);

    if ($newLang !== '' && ! isset($texts[$newLang])) {
        $texts[$newLang] = [];
    }

    $clean['texts'] = [];

    foreach ($texts as $lang => $fields) {
        $lang = substr(sanitize_key((string) $lang), 0, 10);

        if ($lang === '' || ! is_array($fields)) {
            continue;
        }

        $clean['texts'][$lang] = [
            'message'            => wp_kses_post((string) ($fields['message'] ?? '')),
            'accept'             => sanitize_text_field((string) ($fields['accept'] ?? '')),
            'refuse'             => sanitize_text_field((string) ($fields['refuse'] ?? '')),
            'revoke'             => sanitize_text_field((string) ($fields['revoke'] ?? '')),
            'placeholder'        => sanitize_text_field((string) ($fields['placeholder'] ?? '')),
            'placeholder_button' => sanitize_text_field((string) ($fields['placeholder_button'] ?? '')),
            'privacy_label'      => sanitize_text_field((string) ($fields['privacy_label'] ?? '')),
            'privacy_url'        => esc_url_raw((string) ($fields['privacy_url'] ?? '')),
        ];
    }

    if ($clean['texts'] === []) {
        $clean['texts'] = $defaults['texts'];
    }

    return $clean;
}

function mcb_render_settings_page(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $settings = mcb_get_settings();
    $languages = mcb_languages();

    $colorLabels = [
        'banner_bg'        => 'Fond de la bannière',
        'banner_text'      => 'Texte de la bannière',
        'accept_bg'        => 'Fond du bouton Accepter',
        'accept_text'      => 'Texte du bouton Accepter',
        'refuse_bg'        => 'Fond du bouton Refuser',
        'refuse_text'      => 'Texte du bouton Refuser',
        'placeholder_bg'   => 'Fond des contenus bloqués',
        'placeholder_text' => 'Texte des contenus bloqués',
    ];

    $textLabels = [
        'message'            => 'Message de la bannière',
        'accept'             => 'Bouton Accepter',
        'refuse'             => 'Bouton Refuser',
        'revoke'             => 'Libellé de révocation',
        'placeholder'        => 'Message des contenus bloqués',
        'placeholder_button' => 'Bouton des contenus bloqués',
        'privacy_label'      => 'Libellé du lien de confidentialité',
        'privacy_url'        => 'URL de la politique de confidentialité',
    ];
    ?>
    <div class="wrap">
        <h1>Cookie Banner</h1>

        <form method="post" action="options.php">
            <?php settings_fields('mcb_settings_group'); ?>

            <h2>Général</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Activer la bannière</th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="mcb_settings[enabled]"
                                   value="1"
                                   <?php checked($settings['enabled']); ?>>
                            Afficher la bannière et appliquer le blocage sur le site
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mcb-position">Position</label></th>
                    <td>
                        <select id="mcb-position" name="mcb_settings[position]">
                            <option value="bottom" <?php selected($settings['position'], 'bottom'); ?>>
                                Barre en bas
                            </option>
                            <option value="top" <?php selected($settings['position'], 'top'); ?>>
                                Barre en haut
                            </option>
                            <option value="bottom-left" <?php selected($settings['position'], 'bottom-left'); ?>>
                                Carte en bas à gauche
                            </option>
                            <option value="bottom-right" <?php selected($settings['position'], 'bottom-right'); ?>>
                                Carte en bas à droite
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mcb-cookie-days">Durée du consentement (jours)</label></th>
                    <td>
                        <input type="number" id="mcb-cookie-days" name="mcb_settings[cookie_days]" min="1"
                               value="<?php echo esc_attr($settings['cookie_days']); ?>" class="small-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Bouton de révocation flottant</th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="mcb_settings[show_revoke_button]"
                                   value="1"
                                   <?php checked($settings['show_revoke_button']); ?>>
                            Afficher un bouton 🍪 flottant pour modifier son choix
                            (sinon, utiliser le shortcode <code>[mcb_revoke]</code>)
                        </label>
                    </td>
                </tr>
            </table>

            <h2>Textes</h2>
            <p class="description">
                Un bloc par langue. Les champs vides utilisent le texte anglais.
                Avec WPML ou Polylang, les langues actives du site sont listées automatiquement.
            </p>
            <?php foreach ($languages as $index => $lang) :
                $texts = array_merge(
                    array_fill_keys(array_keys($textLabels), ''),
                    $settings['texts'][$lang] ?? []
                );
                ?>
                <details
                    <?php echo $index === 0 ? 'open' : ''; ?>
                    style="margin-bottom: 1em; border: 1px solid #c3c4c7; padding: 0.5em 1em; background: #fff;">
                    <summary style="cursor: pointer; font-weight: 600; text-transform: uppercase;">
                        <?php echo esc_html($lang); ?>
                    </summary>
                    <table class="form-table" role="presentation">
                        <?php foreach ($textLabels as $key => $label) :
                            $fieldId = "mcb-{$lang}-{$key}";
                            $fieldName = "mcb_settings[texts][{$lang}][{$key}]";
                            ?>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($fieldId); ?>">
                                        <?php echo esc_html($label); ?>
                                    </label>
                                </th>
                                <td>
                                    <?php if ($key === 'message') : ?>
                                        <textarea
                                            id="<?php echo esc_attr($fieldId); ?>"
                                            name="<?php echo esc_attr($fieldName); ?>"
                                            rows="3"
                                            class="large-text"><?php echo esc_textarea($texts[$key]); ?></textarea>
                                    <?php elseif ($key === 'privacy_url') : ?>
                                        <input type="url"
                                               id="<?php echo esc_attr($fieldId); ?>"
                                               name="<?php echo esc_attr($fieldName); ?>"
                                               value="<?php echo esc_attr($texts[$key]); ?>"
                                               class="regular-text"
                                               placeholder="<?php echo esc_attr(get_privacy_policy_url()); ?>">
                                    <?php else : ?>
                                        <input type="text"
                                               id="<?php echo esc_attr($fieldId); ?>"
                                               name="<?php echo esc_attr($fieldName); ?>"
                                               value="<?php echo esc_attr($texts[$key]); ?>"
                                               class="regular-text">
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </details>
            <?php endforeach; ?>

            <p>
                <label for="mcb-new-lang">Ajouter une langue (code ISO, ex. <code>de</code>) :</label>
                <input type="text" id="mcb-new-lang" name="mcb_settings[new_lang]"
                       value="" class="small-text" maxlength="10">
            </p>

            <h2>Couleurs</h2>
            <table class="form-table" role="presentation">
                <?php foreach ($colorLabels as $key => $label) : ?>
                    <tr>
                        <th scope="row">
                            <label for="mcb-color-<?php echo esc_attr($key); ?>">
                                <?php echo esc_html($label); ?>
                            </label>
                        </th>
                        <td>
                            <input type="color" id="mcb-color-<?php echo esc_attr($key); ?>"
                                   name="mcb_settings[colors][<?php echo esc_attr($key); ?>]"
                                   value="<?php echo esc_attr($settings['colors'][$key]); ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <h2>Blocage des contenus tiers</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Blocage automatique</th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="mcb_settings[blocking_enabled]"
                                   value="1"
                                   <?php checked($settings['blocking_enabled']); ?>>
                            Bloquer les iframes et scripts des hôtes listés tant que les cookies ne sont pas acceptés
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mcb-blocked-hosts">Hôtes bloqués</label></th>
                    <td>
                        <textarea
                            id="mcb-blocked-hosts"
                            name="mcb_settings[blocked_hosts]"
                            rows="8"
                            class="large-text code"><?php echo esc_textarea($settings['blocked_hosts']); ?></textarea>
                        <p class="description">
                            Un motif par ligne, recherché dans l'URL des iframes et scripts
                            (ex. <code>youtube.com</code>, <code>google.com/maps</code>).
                        </p>
                    </td>
                </tr>
            </table>

            <h2>CSS personnalisé</h2>
            <textarea
                name="mcb_settings[custom_css]"
                rows="8"
                class="large-text code"
                placeholder=".mcb-banner { font-size: 15px; }"
            ><?php echo esc_textarea($settings['custom_css']); ?></textarea>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
