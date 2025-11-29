<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class IES_Deactivator {

    public static function deactivate() {
        // Cron eventlerini iptal etmek istersen:
        // $timestamp = wp_next_scheduled( 'ies_auto_review_event' );
        // if ( $timestamp ) {
        //     wp_unschedule_event( $timestamp, 'ies_auto_review_event' );
        // }

        flush_rewrite_rules();
    }
}
