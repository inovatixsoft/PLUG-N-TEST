<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class IES_Topup {

    protected $wallet;

    public function __construct() {
        // Wallet sınıfına erişim
        if ( class_exists( 'IES_Wallet' ) ) {
            $this->wallet = new IES_Wallet();
        }

        // Müşteri paneline endpoint
        add_action( 'init', array( $this, 'add_endpoint' ) );
        add_filter( 'woocommerce_account_menu_items', array( $this, 'account_menu' ) );
        add_action( 'woocommerce_account_bakiye-yukle_endpoint', array( $this, 'account_page' ) );

        // Form işlemleri
        add_action( 'template_redirect', array( $this, 'handle_form' ) );

        // Sanal POS için sepet ürün fiyatını ayarla
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'adjust_pos_product_price' ), 20, 1 );
    }

    /* ============================================================
     *  MÜŞTERİ PANELİ ENDPOINT
     * ============================================================ */

    public function add_endpoint() {
        add_rewrite_endpoint( 'bakiye-yukle', EP_ROOT | EP_PAGES );
    }

    public function account_menu( $items ) {
        $logout = $items['customer-logout'];
        unset( $items['customer-logout'] );

        // Bakiye menüsü yoksa ekleyelim
        $items['bakiye-yukle'] = 'Bakiye Yükle';

        $items['customer-logout'] = $logout;

        return $items;
    }

    public function account_page() {
        if ( ! is_user_logged_in() ) {
            echo '<p>Bu sayfayı görmek için giriş yapmalısınız.</p>';
            return;
        }

        $user_id = get_current_user_id();
        $balance = 0;
        $ref     = '';

        if ( $this->wallet ) {
            $balance = $this->wallet->get_balance( $user_id );
            $ref     = $this->wallet->get_reference_code( $user_id );
        }

        // Sanal POS aktif mi? (ayar: yes/no)
        $pos_enabled   = get_option( 'ies_pos_enabled', 'no' ) === 'yes';
        $pos_commission = floatval( get_option( 'ies_pos_commission', 0 ) ); // %5 gibi

        // Basit banka hesapları (istersen ileride admin panelinden ayar yaparsın)
        $banks = get_option( 'ies_bank_accounts', array(
            array(
                'name'    => 'Banka 1',
                'iban'    => 'TR00 0000 0000 0000 0000 0000 01',
                'holder'  => 'INOVATIX SOFT BİLİŞİM',
            ),
            array(
                'name'    => 'Banka 2',
                'iban'    => 'TR00 0000 0000 0000 0000 0000 02',
                'holder'  => 'INOVATIX SOFT BİLİŞİM',
            ),
        ) );

        wc_get_template(
            'wallet/topup.php',
            array(
                'balance'        => $balance,
                'reference_code' => $ref,
                'banks'          => $banks,
                'pos_enabled'    => $pos_enabled,
                'pos_commission' => $pos_commission,
            ),
            '',
            IES_PATH . 'templates/'
        );
    }

    /* ============================================================
     *  FORM İŞLEMLERİ
     * ============================================================ */

    public function handle_form() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        if ( ! isset( $_POST['ies_topup_submit'] ) ) {
            return;
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ies_topup_nonce' ) ) {
            wc_add_notice( 'Güvenlik hatası. Lütfen tekrar deneyin.', 'error' );
            wp_safe_redirect( wc_get_account_endpoint_url( 'bakiye-yukle' ) );
            exit;
        }

        $user_id = get_current_user_id();
        $amount  = isset( $_POST['ies_topup_amount'] ) ? floatval( $_POST['ies_topup_amount'] ) : 0;
        $method  = isset( $_POST['ies_topup_method'] ) ? sanitize_text_field( $_POST['ies_topup_method'] ) : 'havale';

        if ( $amount <= 0 ) {
            wc_add_notice( 'Geçerli bir bakiye tutarı giriniz.', 'error' );
            wp_safe_redirect( wc_get_account_endpoint_url( 'bakiye-yukle' ) );
            exit;
        }

        // Varsayılan: Havale/EFT isteği → log kaydı
        if ( $method === 'havale' ) {
            $desc = 'Havale/EFT ile bakiye yükleme isteği (onay bekliyor). Tutar: ' . wc_price( $amount );

            if ( $this->wallet ) {
                // Log'a sadece pending olarak yazıyoruz, bakiyeyi admin onayından sonra admin panelden ekleyeceksiniz.
                $this->wallet->insert_log( $user_id, $amount, 'pending_bank', $desc );
            }

            wc_add_notice( 'Havale/EFT ile bakiye yükleme talebiniz alınmıştır. Lütfen açıklama kısmına referans numaranızı yazarak ödeme yapınız.', 'success' );
            wp_safe_redirect( wc_get_account_endpoint_url( 'bakiye-yukle' ) );
            exit;
        }

        // Sanal POS ile ödeme
        if ( $method === 'pos' ) {
            $pos_enabled = get_option( 'ies_pos_enabled', 'no' ) === 'yes';
            if ( ! $pos_enabled ) {
                wc_add_notice( 'Şu anda sanal POS ile bakiye yükleme aktif değildir.', 'error' );
                wp_safe_redirect( wc_get_account_endpoint_url( 'bakiye-yukle' ) );
                exit;
            }

            if ( ! class_exists( 'WC' ) || ! WC()->cart ) {
                wc_add_notice( 'Sepet oluşturulamadı. Lütfen tekrar deneyin.', 'error' );
                wp_safe_redirect( wc_get_account_endpoint_url( 'bakiye-yukle' ) );
                exit;
            }

            // POS komisyonu
            $commission = floatval( get_option( 'ies_pos_commission', 0 ) ); // %
            $total_price = $amount;

            if ( $commission > 0 ) {
                $total_price = $amount * ( 1 + ( $commission / 100 ) );
            }

            // POS ürünü ID
            $product_id = 0;
            if ( $this->wallet ) {
                $product_id = $this->wallet->get_pos_product_id();
            }

            if ( ! $product_id ) {
                wc_add_notice( 'Bakiye yükleme ürünü bulunamadı.', 'error' );
                wp_safe_redirect( wc_get_account_endpoint_url( 'bakiye-yukle' ) );
                exit;
            }

            // Session'da tutar ve fiyatı sakla, sepette bu ürüne özel fiyat set edeceğiz
            WC()->session->set( 'ies_pos_topup_amount', $amount );
            WC()->session->set( 'ies_pos_topup_price', $total_price );

            // Sepeti boşaltıp sadece bu ürünü ekleyelim
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart( $product_id, 1 );

            wc_add_notice( 'Bakiye yükleme işlemini tamamlamak için ödeme sayfasına yönlendiriliyorsunuz.', 'success' );
            wp_safe_redirect( wc_get_checkout_url() );
            exit;
        }

        // Bilinmeyen yöntem
        wc_add_notice( 'Geçersiz ödeme yöntemi.', 'error' );
        wp_safe_redirect( wc_get_account_endpoint_url( 'bakiye-yukle' ) );
        exit;
    }

    /**
     * Sepetteki "Bakiye Yükleme (POS)" ürününe, kullanıcıdan gelen tutara göre fiyat ver.
     */
    public function adjust_pos_product_price( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        if ( ! $cart || ! method_exists( $cart, 'get_cart' ) ) {
            return;
        }

        if ( ! class_exists( 'WC' ) || ! WC()->session ) {
            return;
        }

        $price = WC()->session->get( 'ies_pos_topup_price' );
        if ( ! $price || $price <= 0 ) {
            return;
        }

        $product_id = 0;
        if ( $this->wallet ) {
            $product_id = $this->wallet->get_pos_product_id();
        }

        if ( ! $product_id ) {
            return;
        }

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( isset( $cart_item['product_id'] ) && intval( $cart_item['product_id'] ) === intval( $product_id ) ) {
                if ( isset( $cart_item['data'] ) && is_object( $cart_item['data'] ) && method_exists( $cart_item['data'], 'set_price' ) ) {
                    $cart_item['data']->set_price( $price );
                }
            }
        }
    }
}
