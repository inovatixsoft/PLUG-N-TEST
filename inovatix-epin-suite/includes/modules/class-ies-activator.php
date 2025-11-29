<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class IES_Activator {

    public static function activate() {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();
        $sql     = array();

        // WALLET TABLOLARI
        $sql[] = "CREATE TABLE {$wpdb->prefix}ies_wallets (
            id BIGINT UNSIGNED AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            balance DECIMAL(16,2) DEFAULT 0,
            reference_code VARCHAR(32),
            created_at DATETIME,
            PRIMARY KEY(id),
            KEY user_id (user_id),
            UNIQUE KEY reference_code (reference_code)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ies_wallet_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(16,2) NOT NULL,
            type VARCHAR(20) NOT NULL,
            description TEXT,
            created_at DATETIME,
            PRIMARY KEY(id),
            KEY user_id (user_id),
            KEY type (type),
            KEY created_at (created_at)
        ) $charset;";

        // TICKET TABLOLARI
        $sql[] = "CREATE TABLE {$wpdb->prefix}ies_tickets (
            id BIGINT UNSIGNED AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            subject VARCHAR(255) NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME,
            PRIMARY KEY(id),
            KEY user_id (user_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset;";

        $sql[] = "CREATE TABLE {$wpdb->prefix}ies_ticket_messages (
            id BIGINT UNSIGNED AUTO_INCREMENT,
            ticket_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED,
            is_admin TINYINT(1) DEFAULT 0,
            message LONGTEXT,
            created_at DATETIME,
            PRIMARY KEY(id),
            KEY ticket_id (ticket_id),
            KEY user_id (user_id)
        ) $charset;";

        // EPIN KOD TABLOSU
        $sql[] = "CREATE TABLE {$wpdb->prefix}ies_epin_codes (
            id BIGINT UNSIGNED AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            code VARCHAR(255) NOT NULL,
            used TINYINT(1) DEFAULT 0,
            order_id BIGINT UNSIGNED,
            user_id BIGINT UNSIGNED,
            created_at DATETIME,
            PRIMARY KEY(id),
            KEY product_id (product_id),
            KEY used (used),
            KEY order_id (order_id),
            KEY user_id (user_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ( $sql as $query ) {
            dbDelta( $query );
        }

        // Cron event: auto review için event zaten runtime'da planlanıyor, burada ekstra gerek yok

        flush_rewrite_rules();
    }
}
