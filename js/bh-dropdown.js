'use strict';

/**
 * Shared viewport-aware Actions dropdown for Brew Haven tables.
 *
 * Menus use position:fixed so they are not clipped by .bh-table-wrap overflow.
 * When there is not enough room below the trigger, the menu opens upward.
 * Exposed as window.bhToggleMenu / window.bhCloseAllMenus for inline handlers
 * and security-confirm.js.
 */
(function () {
    var GAP = 4;
    var PAD = 8;
    var openMenu = null;
    var openBtn = null;

    function clearInlinePos(menu) {
        menu.style.top = '';
        menu.style.left = '';
        menu.style.maxHeight = '';
        menu.style.visibility = '';
        menu.classList.remove('bh-dropdown-up');
    }

    function bhCloseAllMenus() {
        document.querySelectorAll('.bh-dropdown-menu.open').forEach(function (m) {
            m.classList.remove('open');
            clearInlinePos(m);
        });
        openMenu = null;
        openBtn = null;
    }

    function positionMenu(btn, menu) {
        // Measure while open but invisible so height is accurate (incl. zoom).
        menu.style.visibility = 'hidden';
        menu.style.top = '0px';
        menu.style.left = '0px';
        menu.style.maxHeight = '';
        menu.classList.add('open');
        menu.classList.remove('bh-dropdown-up');

        var btnRect = btn.getBoundingClientRect();
        var menuRect = menu.getBoundingClientRect();
        var menuH = menuRect.height;
        var menuW = Math.max(menuRect.width, 200);
        var vh = window.innerHeight;
        var vw = window.innerWidth;
        var spaceBelow = vh - btnRect.bottom - PAD;
        var spaceAbove = btnRect.top - PAD;

        // Prefer downward; flip up when below is short and above has more room.
        var openUp = (spaceBelow < menuH && spaceAbove > spaceBelow);

        // If the menu is taller than the viewport, cap height and scroll inside.
        var maxH = vh - (PAD * 2);
        if (menuH > maxH) {
            menu.style.maxHeight = maxH + 'px';
            menuH = maxH;
            // Re-decide flip with capped height.
            openUp = (spaceBelow < menuH && spaceAbove > spaceBelow);
        }

        var top;
        if (openUp) {
            top = btnRect.top - GAP - menuH;
            menu.classList.add('bh-dropdown-up');
        } else {
            top = btnRect.bottom + GAP;
        }
        top = Math.max(PAD, Math.min(top, vh - menuH - PAD));

        var left = btnRect.right - menuW;
        left = Math.max(PAD, Math.min(left, vw - menuW - PAD));

        menu.style.top = Math.round(top) + 'px';
        menu.style.left = Math.round(left) + 'px';
        menu.style.visibility = '';
        openMenu = menu;
        openBtn = btn;
    }

    function bhToggleMenu(btn) {
        if (!btn) return;
        var menu = btn.nextElementSibling;
        if (!menu || !menu.classList.contains('bh-dropdown-menu')) return;
        var wasOpen = menu.classList.contains('open');
        bhCloseAllMenus();
        if (!wasOpen) {
            positionMenu(btn, menu);
        }
    }

    function repositionOpen() {
        if (openMenu && openBtn && openMenu.classList.contains('open')) {
            positionMenu(openBtn, openMenu);
        }
    }

    // Close on scroll (capture) so nested scroll containers also dismiss,
    // but ignore scrolling inside the open menu itself (tall menus at high zoom).
    window.addEventListener('scroll', function (e) {
        if (openMenu && (e.target === openMenu || (e.target && e.target.nodeType === 1 && openMenu.contains(e.target)))) {
            return;
        }
        bhCloseAllMenus();
    }, true);
    window.addEventListener('resize', function () {
        // Keep the open menu on-screen across zoom/resize when possible.
        if (openMenu && openBtn) {
            repositionOpen();
        }
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.bh-dropdown')) {
            bhCloseAllMenus();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            bhCloseAllMenus();
        }
    });

    window.bhCloseAllMenus = bhCloseAllMenus;
    window.bhToggleMenu = bhToggleMenu;
})();
