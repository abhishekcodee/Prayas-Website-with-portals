(function () {
  'use strict';

  var STORAGE_KEY = 'prayas_lang';
  var SUPPORTED = ['en', 'hi', 'mr'];
  var LABELS = {
    en: { code: 'EN', name: 'English', aria: 'Language: English' },
    hi: { code: 'HI', name: 'हिंदी', aria: 'भाषा: हिंदी' },
    mr: { code: 'MR', name: 'मराठी', aria: 'भाषा: मराठी' }
  };

  window.PRAYAS_I18N = window.PRAYAS_I18N || {};

  function isValidLang(lang) {
    return SUPPORTED.indexOf(lang) !== -1;
  }

  function getQueryLang() {
    try {
      var params = new URLSearchParams(window.location.search);
      var q = (params.get('lang') || '').toLowerCase();
      if (isValidLang(q)) return q;
    } catch (e) {}
    return null;
  }

  function getCookieLang() {
    try {
      var m = document.cookie.match(/(?:^|;\s*)prayas_lang=(en|hi|mr)(?:;|$)/);
      if (m && isValidLang(m[1])) return m[1];
    } catch (e) {}
    return null;
  }

  function getSavedLang() {
    var q = getQueryLang();
    if (q) return q;
    try {
      var ls = localStorage.getItem(STORAGE_KEY);
      if (isValidLang(ls)) return ls;
    } catch (e) {}
    var ck = getCookieLang();
    if (ck) return ck;
    return 'en';
  }

  function saveLang(lang) {
    try {
      localStorage.setItem(STORAGE_KEY, lang);
    } catch (e) {}
    try {
      document.cookie = STORAGE_KEY + '=' + lang + ';path=/;max-age=31536000;SameSite=Lax';
    } catch (e) {}
  }

  function t(key, fallback) {
    var lang = currentLang;
    var dict = window.PRAYAS_I18N[lang] || {};
    if (Object.prototype.hasOwnProperty.call(dict, key) && dict[key] !== '') {
      return dict[key];
    }
    var enDict = window.PRAYAS_I18N.en || {};
    if (Object.prototype.hasOwnProperty.call(enDict, key)) {
      return enDict[key];
    }
    return fallback !== undefined ? fallback : key;
  }

  var currentLang = getSavedLang();

  function applyTranslations(lang) {
    if (!isValidLang(lang)) lang = 'en';
    currentLang = lang;
    document.documentElement.setAttribute('lang', lang);

    // Text content
    var els = document.querySelectorAll('[data-i18n]');
    for (var i = 0; i < els.length; i++) {
      var el = els[i];
      var key = el.getAttribute('data-i18n');
      var val = t(key, null);
      if (val !== null) {
        if (el.tagName === 'TITLE') {
          document.title = val;
        } else {
          el.textContent = val;
        }
      }
    }

    // HTML content (for inline tags like <b>, <em>, <a>, <span class="chev">)
    var htmlEls = document.querySelectorAll('[data-i18n-html]');
    for (var j = 0; j < htmlEls.length; j++) {
      var hel = htmlEls[j];
      var hkey = hel.getAttribute('data-i18n-html');
      var hval = t(hkey, null);
      if (hval !== null) {
        hel.innerHTML = hval;
      }
    }

    // Meta description / content attributes
    var contentEls = document.querySelectorAll('[data-i18n-content]');
    for (var k = 0; k < contentEls.length; k++) {
      var cel = contentEls[k];
      var cval = t(cel.getAttribute('data-i18n-content'), null);
      if (cval !== null) cel.setAttribute('content', cval);
    }

    // Placeholders
    var phEls = document.querySelectorAll('[data-i18n-placeholder]');
    for (var p = 0; p < phEls.length; p++) {
      var pel = phEls[p];
      var pval = t(pel.getAttribute('data-i18n-placeholder'), null);
      if (pval !== null) pel.setAttribute('placeholder', pval);
    }

    // aria-label
    var ariaEls = document.querySelectorAll('[data-i18n-aria]');
    for (var a = 0; a < ariaEls.length; a++) {
      var ael = ariaEls[a];
      var aval = t(ael.getAttribute('data-i18n-aria'), null);
      if (aval !== null) ael.setAttribute('aria-label', aval);
    }

    // data-t and data-d for [data-flow] interactive steps
    var flowBtns = document.querySelectorAll('[data-i18n-t], [data-i18n-d]');
    for (var f = 0; f < flowBtns.length; f++) {
      var fb = flowBtns[f];
      if (fb.hasAttribute('data-i18n-t')) {
        var tv = t(fb.getAttribute('data-i18n-t'), null);
        if (tv !== null) fb.setAttribute('data-t', tv);
      }
      if (fb.hasAttribute('data-i18n-d')) {
        var dv = t(fb.getAttribute('data-i18n-d'), null);
        if (dv !== null) fb.setAttribute('data-d', dv);
      }
    }

    // Refresh active [data-flow] panel
    var flows = document.querySelectorAll('[data-flow]');
    for (var fl = 0; fl < flows.length; fl++) {
      var flow = flows[fl];
      var activeBtn = flow.querySelector('.node.on');
      var panel = flow.querySelector('.panel, .ans');
      if (activeBtn && panel) {
        var h4 = panel.querySelector('h4');
        var pEl = panel.querySelector('p');
        if (h4 && activeBtn.dataset.t !== undefined) h4.textContent = activeBtn.dataset.t;
        if (pEl && activeBtn.dataset.d !== undefined) pEl.textContent = activeBtn.dataset.d;
      }
    }

    // Refresh #bridge-btn text
    var bt = document.getElementById('bridge-btn');
    if (bt) {
      var sides = document.querySelector('.sides');
      var isBuilt = sides && sides.classList.contains('built');
      bt.textContent = isBuilt ? t('js.bridge_reset', 'Reset') : t('js.bridge_build', 'Build the bridge →');
    }

    // Refresh switcher UI
    updateSwitcherUI(lang);
  }

  function updateSwitcherUI(lang) {
    var switchers = document.querySelectorAll('.lang-switcher');
    for (var i = 0; i < switchers.length; i++) {
      var sw = switchers[i];
      var codeEl = sw.querySelector('.lang-current');
      var btn = sw.querySelector('.lang-btn');
      if (codeEl) codeEl.textContent = LABELS[lang].code;
      if (btn) btn.setAttribute('aria-label', LABELS[lang].aria);
      var opts = sw.querySelectorAll('.lang-option');
      for (var j = 0; j < opts.length; j++) {
        var optLang = opts[j].getAttribute('data-lang');
        opts[j].setAttribute('aria-selected', optLang === lang ? 'true' : 'false');
      }
    }
  }

  function announceLanguage(lang) {
    var live = document.getElementById('prayas-i18n-live');
    if (!live) {
      live = document.createElement('div');
      live.id = 'prayas-i18n-live';
      live.className = 'sr-only-i18n';
      live.setAttribute('aria-live', 'polite');
      document.body.appendChild(live);
    }
    live.textContent = LABELS[lang].name;
  }

  function setLang(lang) {
    if (!isValidLang(lang)) return;
    saveLang(lang);
    applyTranslations(lang);
    announceLanguage(lang);
  }

  function bindSwitchers() {
    var switchers = document.querySelectorAll('.lang-switcher');
    for (var i = 0; i < switchers.length; i++) {
      (function (sw) {
        if (sw.dataset.bound) return;
        sw.dataset.bound = '1';
        var btn = sw.querySelector('.lang-btn');
        var opts = sw.querySelectorAll('.lang-option');
        if (btn) {
          btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = sw.classList.toggle('open');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
          });
        }
        for (var j = 0; j < opts.length; j++) {
          opts[j].addEventListener('click', function (e) {
            e.stopPropagation();
            var chosen = this.getAttribute('data-lang');
            setLang(chosen);
            sw.classList.remove('open');
            if (btn) btn.setAttribute('aria-expanded', 'false');
          });
        }
      })(switchers[i]);
    }

    document.addEventListener('click', function () {
      var openSwitchers = document.querySelectorAll('.lang-switcher.open');
      for (var i = 0; i < openSwitchers.length; i++) {
        openSwitchers[i].classList.remove('open');
        var b = openSwitchers[i].querySelector('.lang-btn');
        if (b) b.setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        var openSwitchers = document.querySelectorAll('.lang-switcher.open');
        for (var i = 0; i < openSwitchers.length; i++) {
          openSwitchers[i].classList.remove('open');
          var b = openSwitchers[i].querySelector('.lang-btn');
          if (b) {
            b.setAttribute('aria-expanded', 'false');
            b.focus();
          }
        }
      }
    });
  }

  function init() {
    bindSwitchers();
    applyTranslations(currentLang);
  }

  window.PrayasI18n = {
    t: t,
    setLang: setLang,
    getLang: function () { return currentLang; },
    apply: function () { applyTranslations(currentLang); },
    init: init
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
