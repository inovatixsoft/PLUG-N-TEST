<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Kategori Tasarım Modülü
 *
 * - WooCommerce ürün kategorilerini (product_cat) özel EPIN tasarımına yönlendirir.
 * - WoodMart + koyu tema + #6347d6 uyumlu olacak şekilde body class ekler.
 * - Asıl görünüm "templates/category/archive-product-cat.php" dosyasında çizilir.
 */
class IES_Category {

    public function __construct() {
        // Kategori arşiv şablonunu override et
        add_filter( 'template_include', array( $this, 'maybe_override_category_template' ), 99 );

        // Body class ekleyelim (CSS için)
        add_filter( 'body_class', array( $this, 'body_classes' ) );
    }

    /**
     * Ürün kategorisi sayfasında kendi şablonumuzu kullan.
     */
    public function maybe_override_category_template( $template ) {
        if ( is_tax( 'product_cat' ) ) {

            // Özel şablonumuz
            $custom = IES_PATH . 'templates/category/archive-product-cat.php';

            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }

        return $template;
    }

    /**
     * EPIN kategori sayfalarında body class ekleyelim.
     */
    public function body_classes( $classes ) {
        if ( is_tax( 'product_cat' ) ) {
            $classes[] = 'ies-epin-category';
        }

        return $classes;
    }

}
