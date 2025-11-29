<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class IES_Loader {

    public function __construct() {
        // Boş bırakabiliriz, run() içinde işler yapılıyor
    }

    public function run() {

        // ============================
        //  MODÜL DOSYALARINI YÜKLE
        // ============================
        require_once IES_PATH . 'includes/modules/class-ies-wallet.php';
        require_once IES_PATH . 'includes/modules/class-ies-wallet-admin.php';
        require_once IES_PATH . 'includes/modules/class-ies-topup.php';
        require_once IES_PATH . 'includes/modules/class-ies-popup.php';
        require_once IES_PATH . 'includes/modules/class-ies-category.php';
        require_once IES_PATH . 'includes/modules/class-ies-homepage.php';
        require_once IES_PATH . 'includes/modules/class-ies-ticket.php';
        require_once IES_PATH . 'includes/modules/class-ies-reviews.php';
        require_once IES_PATH . 'includes/modules/class-ies-epin.php';
		require_once IES_PATH . 'includes/class-ies-admin.php'; 


        // Modül sınıflarını çalıştır (hepsidir "new" olanlar)
       new IES_Wallet();
	   new IES_Topup();
	   new IES_Popup();
	   new IES_Category();
	   new IES_Homepage();
	   new IES_Ticket();
	   new IES_Reviews();
	   new IES_EPIN_Manager();
	   new IES_Admin();
	   new IES_Wallet_Admin();
// IES_Wallet_Admin kendi dosyasında new IES_Wallet_Admin() ile çalışıyor, istersen onu da loader'a taşıyıp buradan yönetebilirsin.


        // Assetler
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );


    }

    /**
     * FRONTEND CSS / JS
     */
    public function frontend_assets() {

        // Sadece gerçekten ihtiyaç olan sayfalarda yükleyelim
        if ( is_account_page() || is_product() || is_tax( 'product_cat' ) || is_front_page() ) {
            wp_enqueue_style(
                'ies-frontend',
                IES_URL . 'assets/css/frontend.css',
                array(),
                IES_VERSION
            );

            wp_enqueue_script(
                'ies-frontend-js',
                IES_URL . 'assets/js/frontend.js',
                array( 'jquery' ),
                IES_VERSION,
                true
            );
        }
    }

    /**
     * ADMIN CSS
     */
    public function admin_assets( $hook ) {
        // Şimdilik global yükleyebiliriz, istersen ileride $hook ile filtrelersin
        wp_enqueue_style(
            'ies-admin',
            IES_URL . 'assets/css/admin.css',
            array(),
            IES_VERSION
        );
    }
}

