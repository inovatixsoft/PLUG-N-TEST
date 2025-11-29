<?php
/**
 * Plugin Name: Inovatix EPIN Suite
 * Description: EPIN / Oyun kodu siteleri için gelişmiş cüzdan, kategori, anasayfa, ticket, otomatik yorum ve kod teslim sistemi.
 * Version: 1.0.0
 * Author: Inovatix Soft Bilişim Teknolojileri LTD. ŞTİ.
 * Text Domain: https://inovatixsoft.com.tr
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'IES_VERSION', '1.0.0' );
define( 'IES_PATH', plugin_dir_path( __FILE__ ) );
define( 'IES_URL',  plugin_dir_url( __FILE__ ) );

/**
 * Loader sınıfını yükle
 */
require_once IES_PATH . 'includes/modules/class-ies-loader.php';

/**
 * Plugin çalıştır
 */
function ies_run_epin_suite() {
    // WooCommerce yüklü mü kontrol et
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>Inovatix EPIN Suite</strong> eklentisi için WooCommerce gereklidir.</p></div>';
        } );
        return;
    }

    $loader = new IES_Loader();
    $loader->run();
}
add_action( 'plugins_loaded', 'ies_run_epin_suite', 20 );

/**
 * Aktivasyon: DB tablolarını oluştur, rewrite flush et
 */
function ies_epin_suite_activate() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_tickets  = $wpdb->prefix . 'ies_tickets';
    $table_messages = $wpdb->prefix . 'ies_ticket_messages';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Ticket ana tablo
    $sql_tickets = "CREATE TABLE {$table_tickets} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'open',
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status)
    ) {$charset_collate};";

    // Ticket mesajları
    $sql_messages = "CREATE TABLE {$table_messages} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        ticket_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED DEFAULT 0,
        is_admin TINYINT(1) NOT NULL DEFAULT 0,
        message LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY ticket_id (ticket_id),
        KEY user_id (user_id)
    ) {$charset_collate};";

    dbDelta( $sql_tickets );
    dbDelta( $sql_messages );

    // endpoint'ler için flush (destek-talepleri vs.)
    ies_run_epin_suite();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ies_epin_suite_activate' );

/**
 * Deaktivasyon: rewrite flush
 */
function ies_epin_suite_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ies_epin_suite_deactivate' );


