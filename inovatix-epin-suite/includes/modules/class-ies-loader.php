<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class IES_Loader {

    public function __construct() {
        // Şimdilik burada ekstra bir şey yok
    }

    public function run() {

        // ============================
        //  MODÜL DOSYALARINI YÜKLE
        // ============================
        require_once IES_PATH . 'includes/modules/class-ies-popup.php';
        require_once IES_PATH . 'includes/modules/class-ies-category.php';
        require_once IES_PATH . 'includes/modules/class-ies-homepage.php';
        require_once IES_PATH . 'includes/modules/class-ies-ticket.php';
        require_once IES_PATH . 'includes/modules/class-ies-reviews.php';
        require_once IES_PATH . 'includes/modules/class-ies-epin.php';
        require_once IES_PATH . 'includes/class-ies-admin.php';

        // YENİ: Anasayfa 9 bölüm sistemi
        require_once IES_PATH . 'includes/frontend/class-ies-home-sections.php';

        // ============================
        //  MODÜLLERİ ÇALIŞTIR
        // ============================
         new IES_Popup();
        new IES_Category();
        new IES_Homepage();
        new IES_Ticket();
        new IES_Reviews();
        new IES_EPIN_Manager();
        new IES_Admin();

        // Anasayfa 9 bölüm sistemi kısa kod ve assetleri başlat
        $home_sections = new IES_Home_Sections();
        $home_sections->init();

        // Assetler
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
    }

       public function frontend_assets() {

        // Sadece gerçekten ihtiyaç olan sayfalarda yükleyelim␊
        if ( $this->should_enqueue_frontend_assets() ) {

            // Genel frontend CSS
            wp_enqueue_style(
                'ies-frontend',
                IES_URL . 'assets/css/frontend.css',
                array(),
                IES_VERSION
            );

            // Genel frontend JS
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
     * Frontend assetlerinin yüklenmesi gereken sayfaları güvenli şekilde kontrol et.
     */
    protected function should_enqueue_frontend_assets() {
        $is_account_page = function_exists( 'is_account_page' ) && is_account_page();
        $is_product_page = function_exists( 'is_product' ) && is_product();
        $is_product_tax  = function_exists( 'is_tax' ) && is_tax( 'product_cat' );
        $is_front        = function_exists( 'is_front_page' ) && is_front_page();

        return $is_account_page || $is_product_page || $is_product_tax || $is_front;
    }
    /**
     * ADMIN CSS
     */
    public function admin_assets( $hook ) {
        // Şimdilik tüm admin sayfalarında
        wp_enqueue_style(
            'ies-admin',
            IES_URL . 'assets/css/admin.css',
            array(),
            IES_VERSION
        );
    }
}


