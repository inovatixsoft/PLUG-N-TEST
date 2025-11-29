<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class IES_Wallet_Admin {

    protected $wallet_table;
    protected $log_table;

    public function __construct() {
        global $wpdb;
        $this->wallet_table = $wpdb->prefix . 'ies_wallets';
        $this->log_table    = $wpdb->prefix . 'ies_wallet_logs';

        add_action( 'admin_menu', array( $this, 'register_menu' ), 30 );
        add_action( 'admin_post_ies_wallet_adjust', array( $this, 'handle_wallet_adjust' ) );
    }

    /**
     * WooCommerce altında "EPIN Cüzdan" sayfası
     */
    public function register_menu() {
        add_submenu_page(
            'woocommerce',
            'EPIN Cüzdan',
            'EPIN Cüzdan',
            'manage_woocommerce',
            'ies-wallets',
            array( $this, 'render_page' )
        );
    }

    /**
     * Ana sayfa: Liste vs. Detay
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Bu sayfaya erişim yetkiniz yok.' );
        }

        $user_id = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;

        echo '<div class="wrap ies-admin-page">';
        echo '<h1>EPIN Cüzdan Yönetimi</h1>';

        if ( $user_id ) {
            $this->render_user_wallet( $user_id );
        } else {
            $this->render_wallet_list();
        }

        echo '</div>';
    }

    /**
     * Tüm cüzdanları listele (en son oluşturulan en üstte)
     */
    protected function render_wallet_list() {
        global $wpdb;

        $wallets = $wpdb->get_results(
            "SELECT w.*, u.user_login, u.user_email
             FROM {$this->wallet_table} w
             LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID
             ORDER BY w.created_at DESC
             LIMIT 100"
        );

        echo '<div class="ies-card">';
        echo '<h2 style="margin-top:0;">Son 100 Cüzdan</h2>';
        echo '<p class="description">Burada en son oluşturulan veya güncellenen cüzdanları görebilirsiniz. Detay görmek için satıra tıklayın.</p>';

        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>
                <th>ID</th>
                <th>Kullanıcı</th>
                <th>E-posta</th>
                <th>Bakiye</th>
                <th>Referans No</th>
                <th>Oluşturulma</th>
                <th>İşlem</th>
              </tr></thead>';
        echo '<tbody>';

        if ( ! $wallets ) {
            echo '<tr><td colspan="7">Henüz cüzdan kaydı bulunmuyor.</td></tr>';
        } else {
            foreach ( $wallets as $w ) {
                $detail_url = esc_url( admin_url( 'admin.php?page=ies-wallets&user_id=' . intval( $w->user_id ) ) );
                echo '<tr>';
                echo '<td>' . intval( $w->id ) . '</td>';
                echo '<td>' . esc_html( $w->user_login ) . '</td>';
                echo '<td>' . esc_html( $w->user_email ) . '</td>';
                echo '<td><strong>' . number_format_i18n( $w->balance, 2 ) . ' TL</strong></td>';
                echo '<td><code>' . esc_html( $w->reference_code ) . '</code></td>';
                echo '<td>' . esc_html( $w->created_at ) . '</td>';
                echo '<td><a href="' . $detail_url . '" class="button">Detay</a></td>';
                echo '</tr>';
            }
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    /**
     * Belirli kullanıcının cüzdan detay sayfası
     */
    protected function render_user_wallet( $user_id ) {
        global $wpdb;

        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            echo '<p>Kullanıcı bulunamadı.</p>';
            return;
        }

        $wallet = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->wallet_table} WHERE user_id = %d LIMIT 1",
            $user_id
        ) );

        if ( ! $wallet ) {
            echo '<div class="ies-card">';
            echo '<p>Bu kullanıcı için cüzdan kaydı bulunamadı. Kullanıcı siteye giriş yaptığında otomatik oluşturulacaktır.</p>';
            echo '</div>';
            return;
        }

        // Loglar
        $logs = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->log_table}
             WHERE user_id = %d
             ORDER BY created_at DESC
             LIMIT 50",
            $user_id
        ) );

        echo '<div class="ies-card">';
        echo '<h2 style="margin-top:0;">Kullanıcı Cüzdanı</h2>';
        echo '<p><strong>Kullanıcı:</strong> ' . esc_html( $user->user_login ) . ' (' . esc_html( $user->user_email ) . ')</p>';
        echo '<p><strong>Güncel Bakiye:</strong> <span style="font-size:16px;">' . number_format_i18n( $wallet->balance, 2 ) . ' TL</span></p>';
        echo '<p><strong>Referans No:</strong> <code>' . esc_html( $wallet->reference_code ) . '</code></p>';
        echo '</div>';

        // Bakiye düzeltme formu
        echo '<div class="ies-card">';
        echo '<h2 style="margin-top:0;">Bakiye Güncelle</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'ies_wallet_adjust_' . $user_id );
        echo '<input type="hidden" name="action" value="ies_wallet_adjust">';
        echo '<input type="hidden" name="user_id" value="' . intval( $user_id ) . '">';

        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th scope="row"><label for="ies_amount">Tutar</label></th>';
        echo '<td><input name="ies_amount" id="ies_amount" type="number" step="0.01" min="0" required> TL</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">İşlem Tipi</th>';
        echo '<td>';
        echo '<label><input type="radio" name="ies_type" value="credit" checked> Bakiye Ekle</label> &nbsp; ';
        echo '<label><input type="radio" name="ies_type" value="debit"> Bakiye Düş</label>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="ies_desc">Açıklama</label></th>';
        echo '<td><input name="ies_desc" id="ies_desc" type="text" class="regular-text" placeholder="Örn: Manuel bakiye düzeltme"></td>';
        echo '</tr>';

        echo '</table>';

        submit_button( 'Güncelle' );
        echo '</form>';
        echo '</div>';

        // Log tablosu
        echo '<div class="ies-card">';
        echo '<h2 style="margin-top:0;">Son 50 Hareket</h2>';

        if ( ! $logs ) {
            echo '<p>Bu cüzdan için henüz hareket kaydı yok.</p>';
        } else {
            echo '<table class="widefat fixed striped">';
            echo '<thead><tr>
                    <th>Tarih</th>
                    <th>Tutar</th>
                    <th>Tür</th>
                    <th>Açıklama</th>
                  </tr></thead>';
            echo '<tbody>';
            foreach ( $logs as $log ) {
                $amount = floatval( $log->amount );
                $color  = $amount >= 0 ? '#3c7d3c' : '#c0392b';

                echo '<tr>';
                echo '<td>' . esc_html( $log->created_at ) . '</td>';
                echo '<td><span style="color:' . esc_attr( $color ) . ';">' . number_format_i18n( $amount, 2 ) . ' TL</span></td>';
                echo '<td>' . esc_html( $log->type ) . '</td>';
                echo '<td>' . esc_html( $log->description ) . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        }

        echo '</div>';
    }

    /**
     * Admin tarafında bakiye güncelleme formunu işler.
     */
    public function handle_wallet_adjust() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Yetkiniz yok.' );
        }

        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
        $amount  = isset( $_POST['ies_amount'] ) ? floatval( $_POST['ies_amount'] ) : 0;
        $type    = isset( $_POST['ies_type'] ) ? sanitize_text_field( $_POST['ies_type'] ) : 'credit';
        $desc    = isset( $_POST['ies_desc'] ) ? sanitize_text_field( $_POST['ies_desc'] ) : '';

        if ( ! $user_id || $amount <= 0 ) {
            wp_die( 'Geçersiz veri.' );
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ies_wallet_adjust_' . $user_id ) ) {
            wp_die( 'Güvenlik hatası.' );
        }

        // IES_Wallet sınıfını kullanarak bakiye güncelle
        if ( class_exists( 'IES_Wallet' ) ) {
            $wallet = new IES_Wallet(); // tekrar constructor çalışır ama sadece hook ekler; sorun değil ama ideal değil.
            // Daha temiz çözüm: static instance / singleton; burada basitçe kullanıyoruz.

            if ( $type === 'credit' ) {
                $wallet->add_balance( $user_id, $amount, $desc, 'admin_credit' );
            } else {
                $wallet->deduct_balance( $user_id, $amount, $desc, 'admin_debit' );
            }
        }

        wp_redirect( admin_url( 'admin.php?page=ies-wallets&user_id=' . $user_id . '&updated=1' ) );
        exit;
    }
}
