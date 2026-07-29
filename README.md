# My Cookie Banner

Bannière de consentement aux cookies ultra simple pour WordPress, sans pub, sans compte, sans dépendance.

- Consentement global **Accepter / Refuser**, révocable à tout moment
- Textes et couleurs personnalisables depuis **Réglages → Cookie Banner**
- Multilingue : anglais, français et espagnol par défaut, compatible **WPML** et **Polylang** (les langues actives du site apparaissent automatiquement dans les réglages)
- **Blocage automatique** des iframes et scripts tiers (YouTube, Google Maps, Vimeo…) tant que le consentement n'est pas donné, avec placeholder « Accepter les cookies et afficher »
- Variable et événements **JavaScript** + helpers **PHP** pour déclencher n'importe quoi selon le consentement
- Pont automatique vers la **WP Consent API** si elle est présente

## Installation

### En mu-plugin (recommandé)

WordPress ne charge pas les sous-répertoires de `mu-plugins`, il faut donc un fichier chargeur :

1. Copier le dossier `my-cookie-banner/` dans `wp-content/mu-plugins/`
2. Déplacer `load-my-cookie-banner.php` à la racine de `wp-content/mu-plugins/` :

```
wp-content/mu-plugins/
├── load-my-cookie-banner.php
└── my-cookie-banner/
    ├── my-cookie-banner.php
    ├── includes/
    └── assets/
```

### En plugin classique

Le dossier peut aussi être copié tel quel dans `wp-content/plugins/` et activé normalement (le chargeur est alors inutile).

## Fonctionnement

Un seul cookie est posé : `mcb_consent`, valeur `accepted` ou `refused` (durée configurable, 180 jours par défaut). Tant qu'il n'existe pas, le statut est `pending` et la bannière s'affiche.

Le blocage réécrit le HTML de la page côté serveur : les iframes dont l'URL correspond à un hôte bloqué perdent leur `src` (conservé dans `data-mcb-src`) et les scripts passent en `type="text/plain"`. Après acceptation, le JavaScript restaure tout **sans recharger la page**. La page rendue est identique pour tous les visiteurs : le mécanisme est **compatible avec le cache de pages**.

## API JavaScript

```js
// Variable globale simple
window.mcbConsentStatus; // 'accepted' | 'refused' | 'pending'

// API complète
window.myCookieBanner.hasConsent(); // true / false
window.myCookieBanner.getStatus();  // 'accepted' | 'refused' | 'pending'
window.myCookieBanner.accept();
window.myCookieBanner.refuse();
window.myCookieBanner.revoke();     // efface le choix et réaffiche la bannière
```

### Événement

`mcb:consent` est émis sur `document` au chargement de la page (état initial) et à chaque changement :

```js
document.addEventListener('mcb:consent', function (event) {
    if (event.detail.accepted) {
        // charger une carte, un tracker, une vidéo…
    }
});

// Ou via le raccourci :
window.myCookieBanner.onChange(function (detail) {
    console.log(detail.status);
});
```

### Bloquer un script maison manuellement

Tout script rendu inerte avec `type="text/plain"` et `data-mcb-block` est exécuté automatiquement après acceptation :

```html
<script type="text/plain" data-mcb-block="1">
    console.log('Exécuté seulement après consentement');
</script>
```

## API PHP

```php
mcb_has_consent();     // bool — attention : faux avec un cache de pages, préférer le JS
mcb_consent_status();  // 'accepted' | 'refused' | 'pending'
```

### Shortcode de révocation

À placer par exemple dans le footer ou la page de politique de confidentialité :

```
[mcb_revoke]
[mcb_revoke label="Gérer mes cookies"]
```

Tout élément portant l'attribut `data-mcb-revoke` (ou les attributs `data-mcb-accept` / `data-mcb-refuse`) déclenche l'action correspondante au clic.

### Filtres

```php
// Modifier la liste des hôtes bloqués
add_filter('mcb_blocked_hosts', fn (array $hosts) => [...$hosts, 'open.spotify.com']);

// Modifier la liste des langues affichées dans les réglages
add_filter('mcb_languages', fn (array $langs) => [...$langs, 'de']);
```

## Multilingue

Les textes sont stockés **par langue** dans une seule option (`mcb_settings`). La langue courante est détectée via WPML (`wpml_current_language`), Polylang (`pll_current_language`) ou, à défaut, la locale du site. Aucune configuration WPML String Translation n'est nécessaire. Un champ laissé vide dans une langue retombe sur le texte anglais.

## Limites connues

- Les iframes injectés en JavaScript après le chargement de la page (lazy-load maison, constructeurs de pages) ne sont pas interceptés par la réécriture serveur — utiliser l'événement `mcb:consent` pour ces cas.
- Après une révocation, les contenus tiers déjà chargés restent affichés jusqu'au prochain chargement de page (aucun cookie tiers supplémentaire n'est cependant déposé ensuite).
- La détection des hôtes est une recherche de sous-chaîne dans l'URL du `src`.

## Licence

[MIT](LICENSE)
