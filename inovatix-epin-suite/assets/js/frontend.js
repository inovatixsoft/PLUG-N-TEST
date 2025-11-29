document.addEventListener('DOMContentLoaded', function () {

    /* =========================================================
     * SEO TABLARI (Kategori sayfası altındaki 3 tab)
     * ======================================================= */
    var tabButtons = document.querySelectorAll('.ies-seo-tab-btn');
    var tabPanes   = document.querySelectorAll('.ies-seo-tab-pane');

    if (tabButtons.length && tabPanes.length) {
        tabButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-target');

                // butonları resetle
                tabButtons.forEach(function (b) {
                    b.classList.remove('is-active');
                });
                btn.classList.add('is-active');

                // panelleri resetle
                tabPanes.forEach(function (pane) {
                    if (pane.id === targetId) {
                        pane.classList.add('is-active');
                    } else {
                        pane.classList.remove('is-active');
                    }
                });
            });
        });
    }

    /* =========================================================
     * ADD TO CART POPUP (ID / ZONE / Sunucu / Not)
     * ======================================================= */

    var overlay = document.querySelector('.ies-cart-popup-overlay');
    var form    = overlay ? overlay.querySelector('.ies-cart-popup-form') : null;
    var closeBtn= overlay ? overlay.querySelector('.ies-cart-popup-close') : null;
    var cancelBtn = overlay ? overlay.querySelector('.ies-cart-popup-cancel') : null;

    var hiddenProductId = overlay ? overlay.querySelector('#ies_popup_product_id') : null;
    var hiddenAddToCart = overlay ? overlay.querySelector('#ies_popup_add_to_cart') : null;

    var currentCartUrl = null;

    function openPopup(productId, cartUrl) {
        if (!overlay || !form || !hiddenProductId || !hiddenAddToCart) return;

        currentCartUrl = cartUrl;
        hiddenProductId.value = productId;
        hiddenAddToCart.value = productId;

        // Alanları temizle
        var player = form.querySelector('#ies_player_id');
        var zone   = form.querySelector('#ies_zone_id');
        var server = form.querySelector('#ies_server');
        var note   = form.querySelector('#ies_note');

        if (player) player.value = '';
        if (zone)   zone.value = '';
        if (server) server.value = '';
        if (note)   note.value = '';

        overlay.classList.add('is-active');
    }

    function closePopup() {
        if (!overlay) return;
        overlay.classList.remove('is-active');
        currentCartUrl = null;
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closePopup();
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closePopup();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                closePopup();
            }
        });
    }

    // Sepete ekle butonlarını yakalayalım
    // Özellikle kategori sayfasındaki .add_to_cart_button ve single product butonları.
    var cartButtons = document.querySelectorAll('.add_to_cart_button, .product_type_simple.add_to_cart_button_ajax');

    if (cartButtons.length && overlay) {
        cartButtons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                // Normal AJAX add-to-cart işlemini durdur
                e.preventDefault();

                var productId = btn.getAttribute('data-product_id') || btn.value || null;
                if (!productId) {
                    // WooCommerce bazen URL üzerinden add-to-cart yapar.
                    // URL'den ?add-to-cart=ID parametresini çekmeyi deneyelim:
                    var href = btn.getAttribute('href') || '';
                    var match = href.match(/add-to-cart=([0-9]+)/);
                    if (match && match[1]) {
                        productId = match[1];
                    }
                }

                if (!productId) {
                    // Ürün ID yoksa normal davranışı devam ettir
                    var href = btn.getAttribute('href');
                    if (href) {
                        window.location.href = href;
                    }
                    return;
                }

                var cartUrl = btn.getAttribute('href') || window.location.href;
                openPopup(productId, cartUrl);
            });
        });
    }

    // Popup formu submit edildiğinde gerçek add-to-cart isteği gönder
    if (form && overlay) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!hiddenProductId || !hiddenAddToCart) return;

            // Form action'ı: butonun href'i varsa onu kullan, yoksa mevcut sayfa
            var action = currentCartUrl || window.location.href;
            form.setAttribute('action', action);

            // Son olarak formu normal şekilde gönderelim
            form.submit();
        });
    }
});
