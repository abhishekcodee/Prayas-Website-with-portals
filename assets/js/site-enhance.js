/* PRAYAS — presentational enhancements for the inner pages.
   Adds header shadow on scroll, scroll-reveal animations and loads photos
   into the "photo-slot" boxes when the matching file exists in /images. */
(function () {
  var doc = document.documentElement;

  // Header shadow once the page scrolls
  var header = document.querySelector('header');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Fill photo slots from images/<data-img> when the file is present
  var base = (document.querySelector('script[src*="site-enhance.js"]') || {}).getAttribute
    ? document.querySelector('script[src*="site-enhance.js"]').getAttribute('src').replace(/assets\/js\/site-enhance\.js.*$/, '')
    : '';
  document.querySelectorAll('.photo-slot[data-img]').forEach(function (slot) {
    if (slot.querySelector('img')) return;
    var img = new Image();
    img.alt = slot.getAttribute('data-alt') || '';
    img.onload = function () { slot.appendChild(img); slot.classList.add('filled'); };
    img.onerror = function () { slot.classList.add('empty'); };
    img.src = base + 'images/' + slot.getAttribute('data-img');
  });

  // Closing call-to-action band: last centred block holding a button
  var secs = document.querySelectorAll('main section');
  var last = secs[secs.length - 1];
  if (last) {
    var c = last.querySelector('[style*="text-align:center"]');
    if (c && c.querySelector('.btn') && !c.closest('.card')) c.classList.add('cta-band');
  }

  // Scroll reveal
  if (!('IntersectionObserver' in window)) return;
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var targets = document.querySelectorAll(
    'main section h2, main section .lead, .card, .checklist li, .flow, .split-media, .chal, .calc, ' +
    '.contact-form, .info-card, .win, .sides, .legal-body, .tabs'
  );
  if (!targets.length) return;
  doc.classList.add('js-reveal');
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (e.isIntersecting) {
        var t = e.target;
        t.classList.add('in');
        io.unobserve(t);
        // hand control back to hover effects once the entrance has played
        setTimeout(function () { t.classList.remove('reveal', 'in'); t.style.transitionDelay = ''; }, 1200);
      }
    });
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
  targets.forEach(function (el, i) {
    el.classList.add('reveal');
    // light stagger for siblings in a row
    var idx = Array.prototype.indexOf.call(el.parentNode.children, el);
    el.style.transitionDelay = Math.min(idx, 5) * 70 + 'ms';
    io.observe(el);
  });
})();
