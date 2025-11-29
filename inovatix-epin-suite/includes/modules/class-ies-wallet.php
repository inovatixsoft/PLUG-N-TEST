<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class IES_Wallet {

    protected $wallet_table;
    protected $log_table;

    public function __construct() {
        global $wpdb;
        $this->wallet_table = $wpdb->prefix . 'ies_wallets';
        $this->log_table    = $wpdb->prefix . 'ies_wallet_logs';

        // Yeni kayıt olan kullanıcıya otomatik wallet + referans no
        add_action( 'user_register', array( $this, 'create_wallet_for_user' ), 10, 1 );

        // Giriş yapan eski kullanıcılar için eksikse cüzdan oluştur
        add_action( 'wp_login', array( $this, 'ensure_wallet_on_login' ), 10, 2 );
    }

    /**
     * Yeni kullanıcı kaydı → cüzdan oluştur + referans no ata
     */
    public function create_wallet_for_user( $user_id ) {
        $user_id = intval( $user_id );
        if ( ! $user_id ) return;

        global $wpdb;

        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$this->wallet_table} WHERE user_id = %d LIMIT 1",
            $user_id
        ) );

        if ( $existing ) {
            return;
        }

        $reference = $this->generate_unique_reference_code();

        $wpdb->insert(
            $this->wallet_table,
            array(
                'user_id'       => $user_id,
                'balance'       => 0,
                'reference_code'=> $reference,
                'created_at'    => current_time( 'mysql' ),
            ),
            array( '%d', '%f', '%s', '%s' )
        );
    }

    /**
     * Eski kullanıcı giriş yaptığında, eğer cüzdanı yoksa oluştur.
     */
    public function ensure_wallet_on_login( $user_login, $user ) {
        if ( ! $user || empty( $user->ID ) ) return;
        $this->create_wallet_for_user( $user->ID );
    }

    /**
     * Kullanıcının referans numarasını getir (yoksa üret).
     */
    public function get_reference_code( $user_id ) {
        $user_id = intval( $user_id );
        if ( ! $user_id ) return '';

        global $wpdb;

        $reference = $wpdb->get_var( $wpdb->prepare(
            "SELECT reference_code FROM {$this->wallet_table} WHERE user_id = %d LIMIT 1",
            $user_id
        ) );

        if ( $reference ) {
            return $reference;
        }

        // Yoksa cüzdanı da referans no'yu da yeniden oluştur
        $this->create_wallet_for_user( $user_id );

        $reference = $wpdb->get_var( $wpdb->prepare(
            "SELECT reference_code FROM {$this->wallet_table} WHERE user_id = %d LIMIT 1",
            $user_id
        ) );

        return $reference ? $reference : '';
    }

    /**
     * Benzersiz referans no üret (ör: 9 haneli sayısal).
     */
    protected function generate_unique_reference_code() {
        global $wpdb;

        do {
            // 9 haneli random sayısal referans: 100000000–999999999
            $code = (string) wp_rand( 100000000, 999999999 );

            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$this->wallet_table} WHERE reference_code = %s LIMIT 1",
                $code
            ) );
        } while ( $exists );

        return $code;
    }

    /**
     * Kullanıcının mevcut bakiyesini getir.
     */
    public function get_balance( $user_id ) {
        $user_id = intval( $user_id );
        if ( ! $user_id ) return 0;

        global $wpdb;

        $balance = $wpdb->get_var( $wpdb->prepare(
            "SELECT balance FROM {$this->wallet_table} WHERE user_id = %d LIMIT 1",
            $user_id
        ) );

        return $balance !== null ? floatval( $balance ) : 0;
    }

    /**
     * Bakiye ekle (pozitif tutar). Log kaydı oluştur.
     * $type: 'credit' (yükleme) / 'adjustment' vb.
     */
    public function add_balance( $user_id, $amount, $description = '', $type = 'credit' ) {
        $user_id = intval( $user_id );
        $amount  = floatval( $amount );

        if ( ! $user_id || $amount <= 0 ) {
            return false;
        }

        global $wpdb;

        $current = $this->get_balance( $user_id );
        $new     = $current + $amount;

        $updated = $wpdb->update(
            $this->wallet_table,
            array( 'balance' => $new ),
            array( 'user_id' => $user_id ),
            array( '%f' ),
            array( '%d' )
        );

        if ( $updated !== false ) {
            $this->insert_log( $user_id, $amount, $type, $description );
            return true;
        }

        return false;
    }

    /**
     * Bakiye düş (pozitif tutar). Negatif bakiyeye düşmesine izin verme.
     * $type: 'debit' (harcama) vb.
     */
    public function deduct_balance( $user_id, $amount, $description = '', $type = 'debit' ) {
        $user_id = intval( $user_id );
        $amount  = floatval( $amount );

        if ( ! $user_id || $amount <= 0 ) {
            return false;
        }

        global $wpdb;

        $current = $this->get_balance( $user_id );
        if ( $current <= 0 ) {
            return false;
        }

        $new = max( 0, $current - $amount );

        $updated = $wpdb->update(
            $this->wallet_table,
            array( 'balance' => $new ),
            array( 'user_id' => $user_id ),
            array( '%f' ),
            array( '%d' )
        );

        if ( $updated !== false ) {
            $this->insert_log( $user_id, -$amount, $type, $description );
            return true;
        }

        return false;
    }

    /**
     * Bakiye log kaydı ekle.
     */
    public function insert_log( $user_id, $amount, $type = 'credit', $description = '' ) {
        global $wpdb;

        $wpdb->insert(
            $this->log_table,
            array(
                'user_id'    => intval( $user_id ),
                'amount'     => floatval( $amount ),
                'type'       => sanitize_text_field( $type ),
                'description'=> $description,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%f', '%s', '%s', '%s' )
        );
    }

    /**
     * Bakiye loglarını getir (isteğe bağlı admin tarafında kullanılır).
     */
    public function get_logs( $user_id, $limit = 50 ) {
        $user_id = intval( $user_id );
        if ( ! $user_id ) return array();

        global $wpdb;
        $limit = intval( $limit );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->log_table}
             WHERE user_id = %d
             ORDER BY created_at DESC
             LIMIT %d",
            $user_id,
            $limit
        ) );

        return $rows ? $rows : array();
    }

    /**
     * SANAL POS İÇİN ÖZEL BAKİYE ÜRÜNÜ
     *
     * Bu ürün, ileride PayTR / Tami POS gibi sanal pos ile bakiye yüklemede kullanılacak.
     * Burada sadece ürün varsa geri döner, yoksa otomatik oluşturur.
     */
    public function get_pos_product_id() {
        $product_id = get_option( 'ies_pos_product_id', 0 );
        if ( $product_id && get_post_status( $product_id ) ) {
            return $product_id;
        }

        // Yoksa otomatik oluştur
        $new_id = wp_insert_post( array(
            'post_title'  => 'Bakiye Yükleme (POS)',
            'post_type'   => 'product',
            'post_status' => 'publish',
        ) );

        if ( $new_id && ! is_wp_error( $new_id ) ) {
            // Sanal, indirilebilir olmayan ürün
            update_post_meta( $new_id, '_virtual', 'yes' );
            update_post_meta( $new_id, '_downloadable', 'no' );
            update_post_meta( $new_id, '_regular_price', '0' );
            update_post_meta( $new_id, '_price', '0' );
            update_post_meta( $new_id, '_sold_individually', 'yes' );
            // Bu ürünün "bakiye yükleme" bayrağı
            update_post_meta( $new_id, '_ies_wallet_topup', '1' );

            update_option( 'ies_pos_product_id', $new_id );

            return $new_id;
        }

        return 0;
    }
}