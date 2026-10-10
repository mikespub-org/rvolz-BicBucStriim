/**
 * BicBucStriim - Installation check behaviour
 *
 * Page-specific progressive enhancement for installcheck.php /
 * templates/installcheck.twig. Replaces the info popups that jQuery Mobile
 * used to provide: any <a data-rel="popup" href="#someId"> opens the matching
 * .bbs-popup over a dimmed backdrop.
 *
 * Loaded after js/script.js, which creates the shared .bbs-drawer-backdrop
 * element this file reuses.
 */
(function () {
  'use strict';

  function onReady(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  onReady(function () {
    var backdrop = document.querySelector('.bbs-drawer-backdrop');
    if (!backdrop) {
      // js/script.js may not have run (or was not loaded); create our own.
      backdrop = document.createElement('div');
      backdrop.className = 'bbs-drawer-backdrop';
      document.body.appendChild(backdrop);
    }

    var anyPopupOpen = function () {
      return document.querySelector('.bbs-popup.open') !== null;
    };

    function closeAllPopups() {
      var open = document.querySelectorAll('.bbs-popup.open');
      for (var i = 0; i < open.length; i++) {
        open[i].classList.remove('open');
      }
      if (!document.querySelector('.bbs-panel.open, section[data-role="panel"].open')) {
        backdrop.classList.remove('open');
      }
    }

    function openPopup(popup) {
      closeAllPopups();
      popup.classList.add('open');
      backdrop.classList.add('open');
    }

    // Open: <a data-rel="popup" href="#popupId">
    var triggers = document.querySelectorAll('a[data-rel="popup"]');
    for (var i = 0; i < triggers.length; i++) {
      triggers[i].addEventListener('click', function (e) {
        var href = this.getAttribute('href');
        if (!href || href.indexOf('#') !== 0) {
          return;
        }
        var popup = document.getElementById(href.substring(1));
        if (!popup || popup.className.indexOf('bbs-popup') === -1) {
          return;
        }
        e.preventDefault();
        openPopup(popup);
      });
    }

    // Close: the × button inside each popup
    var closeBtns = document.querySelectorAll('.bbs-popup a[data-rel="back"]');
    for (var j = 0; j < closeBtns.length; j++) {
      closeBtns[j].addEventListener('click', function (e) {
        e.preventDefault();
        closeAllPopups();
      });
    }

    // Close: backdrop click and Escape key
    backdrop.addEventListener('click', closeAllPopups);
    document.addEventListener('keydown', function (e) {
      if ((e.keyCode === 27 || e.key === 'Escape') && anyPopupOpen()) {
        closeAllPopups();
      }
    });
  });
})();
