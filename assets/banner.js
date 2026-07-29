(function () {
    'use strict';

    var cfg = window.mcbConfig || {};
    var cookieName = cfg.cookieName || 'mcb_consent';
    var texts = cfg.texts || {};

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : null;
    }

    function setCookie(name, value, days) {
        var date = new Date();
        date.setTime(date.getTime() + days * 864e5);
        document.cookie = name + '=' + encodeURIComponent(value) +
            '; expires=' + date.toUTCString() +
            '; path=/; SameSite=Lax' +
            (location.protocol === 'https:' ? '; Secure' : '');
    }

    function eraseCookie(name) {
        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
    }

    function getStatus() {
        var value = getCookie(cookieName);
        return value === 'accepted' || value === 'refused' ? value : 'pending';
    }

    function fire(status) {
        window.mcbConsentStatus = status;
        document.dispatchEvent(new CustomEvent('mcb:consent', {
            detail: { status: status, accepted: status === 'accepted' }
        }));
    }

    function syncWpConsentApi(status) {
        if (typeof window.wp_set_consent !== 'function') {
            return;
        }
        var value = status === 'accepted' ? 'allow' : 'deny';
        ['preferences', 'statistics', 'statistics-anonymous', 'marketing'].forEach(function (category) {
            window.wp_set_consent(category, value);
        });
        window.wp_set_consent('functional', 'allow');
    }

    function unblockIframes() {
        Array.prototype.forEach.call(document.querySelectorAll('iframe[data-mcb-src]'), function (iframe) {
            var src = iframe.getAttribute('data-mcb-src');
            iframe.removeAttribute('data-mcb-src');
            iframe.src = src;

            var placeholder = iframe.closest('.mcb-placeholder');
            if (placeholder) {
                placeholder.parentNode.insertBefore(iframe, placeholder);
                placeholder.parentNode.removeChild(placeholder);
            }
        });
    }

    function unblockScripts() {
        Array.prototype.forEach.call(document.querySelectorAll('script[data-mcb-block]'), function (blocked) {
            var script = document.createElement('script');
            Array.prototype.forEach.call(blocked.attributes, function (attr) {
                if (attr.name === 'type' || attr.name === 'data-mcb-block') {
                    return;
                }
                script.setAttribute(attr.name, attr.value);
            });
            script.text = blocked.text;
            blocked.parentNode.replaceChild(script, blocked);
        });
    }

    function unblockAll() {
        unblockIframes();
        unblockScripts();
    }

    function buildPlaceholders() {
        Array.prototype.forEach.call(document.querySelectorAll('iframe[data-mcb-src]'), function (iframe) {
            if (iframe.closest('.mcb-placeholder')) {
                return;
            }

            var placeholder = document.createElement('div');
            placeholder.className = 'mcb-placeholder';

            var width = parseInt(iframe.getAttribute('width'), 10);
            var height = parseInt(iframe.getAttribute('height'), 10);
            if (width > 0 && height > 0) {
                placeholder.style.aspectRatio = width + ' / ' + height;
                placeholder.style.width = width + 'px';
            }

            var inner = document.createElement('div');
            inner.className = 'mcb-placeholder-inner';

            var message = document.createElement('p');
            message.textContent = texts.placeholder || '';

            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'mcb-btn mcb-btn-accept';
            button.setAttribute('data-mcb-accept', '');
            button.textContent = texts.placeholderButton || '';

            inner.appendChild(message);
            inner.appendChild(button);

            iframe.parentNode.insertBefore(placeholder, iframe);
            placeholder.appendChild(iframe);
            placeholder.appendChild(inner);
        });
    }

    function render() {
        var status = getStatus();
        var banner = document.getElementById('mcb-banner');
        var revoke = document.getElementById('mcb-revoke');

        if (banner) {
            banner.hidden = status !== 'pending';
        }
        if (revoke) {
            revoke.hidden = status === 'pending';
        }
    }

    function setConsent(status) {
        setCookie(cookieName, status, cfg.cookieDays || 180);
        if (status === 'accepted') {
            unblockAll();
        }
        render();
        syncWpConsentApi(status);
        fire(status);
    }

    window.myCookieBanner = {
        getStatus: getStatus,
        hasConsent: function () {
            return getStatus() === 'accepted';
        },
        accept: function () {
            setConsent('accepted');
        },
        refuse: function () {
            setConsent('refused');
        },
        revoke: function () {
            eraseCookie(cookieName);
            syncWpConsentApi('refused');
            render();
            fire('pending');
        },
        onChange: function (callback) {
            document.addEventListener('mcb:consent', function (event) {
                callback(event.detail);
            });
        }
    };

    document.addEventListener('click', function (event) {
        if (!event.target.closest) {
            return;
        }
        var target = event.target.closest('[data-mcb-accept], [data-mcb-refuse], [data-mcb-revoke]');
        if (!target) {
            return;
        }

        event.preventDefault();

        if (target.hasAttribute('data-mcb-accept')) {
            window.myCookieBanner.accept();
        } else if (target.hasAttribute('data-mcb-refuse')) {
            window.myCookieBanner.refuse();
        } else {
            window.myCookieBanner.revoke();
        }
    });

    function init() {
        var status = getStatus();
        if (status === 'accepted') {
            unblockAll();
        } else {
            buildPlaceholders();
        }
        render();
        fire(status);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
