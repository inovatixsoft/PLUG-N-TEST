<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ticket (Destek) Sistemi
 *
 * - Müşteri: Hesabım > Destek Taleplerim
 * - Admin: WooCommerce altında Ticket Yönetimi
 * - İki yönlü mesajlaşma + mail bildirimleri
 */
class IES_Ticket {

    protected $table_tickets;
    protected $table_messages;

    public function __construct() {
        global $wpdb;
        $this->table_tickets  = $wpdb->prefix . 'ies_tickets';
        $this->table_messages = $wpdb->prefix . 'ies_ticket_messages';

        // Müşteri paneli
        add_action( 'init', array( $this, 'add_endpoint' ) );
        add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu' ) );
        add_action( 'woocommerce_account_destek-talepleri_endpoint', array( $this, 'render_account_page' ) );

        // Form işlemleri
        add_action( 'template_redirect', array( $this, 'handle_customer_actions' ) );

        // Admin panel
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'admin_post_ies_admin_ticket_reply', array( $this, 'handle_admin_reply' ) );
        add_action( 'admin_post_ies_admin_ticket_status', array( $this, 'handle_admin_status' ) );
    }

    /* ============================================================
     *  MÜŞTERİ PANELİ
     * ============================================================ */

    public function add_endpoint() {
        add_rewrite_endpoint( 'destek-talepleri', EP_ROOT | EP_PAGES );
    }

    public function add_menu( $items ) {
        $logout = $items['customer-logout'];
        unset( $items['customer-logout'] );

        $items['destek-talepleri'] = 'Destek Taleplerim';

        $items['customer-logout'] = $logout;

        return $items;
    }

    public function render_account_page() {
        if ( ! is_user_logged_in() ) {
            echo '<p>Giriş yapmanız gerekiyor.</p>';
            return;
        }

        $user_id = get_current_user_id();

        wc_get_template(
            'ticket/account-tickets.php',
            array(
                'tickets' => $this->get_user_tickets( $user_id )
            ),
            '',
            IES_PATH . 'templates/'
        );
    }

    /**
     * Kullanıcının ticketlarını getir
     */
    protected function get_user_tickets( $user_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_tickets}
                 WHERE user_id = %d
                 ORDER BY created_at DESC",
                $user_id
            )
        );
    }

    /**
     * Ticket detay
     */
    protected function get_ticket( $ticket_id, $user_id = 0 ) {
        global $wpdb;

        $sql = "SELECT * FROM {$this->table_tickets} WHERE id = %d";
        $params = array( $ticket_id );

        if ( $user_id ) {
            $sql .= " AND user_id = %d";
            $params[] = $user_id;
        }

        return $wpdb->get_row(
            $wpdb->prepare( $sql, ...$params )
        );
    }

    /**
     * Ticket mesajları
     */
    protected function get_ticket_messages( $ticket_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.*, u.user_login
                 FROM {$this->table_messages} m
                 LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
                 WHERE m.ticket_id = %d
                 ORDER BY m.created_at ASC",
                $ticket_id
            )
        );
    }

    /* ============================================================
     *  CUSTOMER ACTIONS (TICKET CREATE / MESSAGE)
     * ============================================================ */

    public function handle_customer_actions() {

        if ( ! is_user_logged_in() ) {
            return;
        }

        $user_id = get_current_user_id();

        /* -------------------------------
         *  Ticket Oluştur
         * ------------------------------- */
        if ( isset( $_POST['ies_create_ticket'] ) ) {

            if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'ies_ticket_nonce' ) ) {
                wc_add_notice( 'Güvenlik hatası!', 'error' );
                return;
            }

            $subject = sanitize_text_field( $_POST['ies_ticket_subject'] );
            $message = wp_kses_post( $_POST['ies_ticket_message'] );

            if ( $subject === '' || $message === '' ) {
                wc_add_notice( 'Konu ve mesaj boş olamaz.', 'error' );
                return;
            }

            global $wpdb;

            $wpdb->insert(
                $this->table_tickets,
                array(
                    'user_id'    => $user_id,
                    'subject'    => $subject,
                    'status'     => 'open',
                    'created_at' => current_time( 'mysql' ),
                )
            );

            $ticket_id = $wpdb->insert_id;

            $wpdb->insert(
                $this->table_messages,
                array(
                    'ticket_id' => $ticket_id,
                    'user_id'   => $user_id,
                    'is_admin'  => 0,
                    'message'   => $message,
                    'created_at'=> current_time( 'mysql' ),
                )
            );

            /* Admin'e mail gönder */
            $this->send_admin_notification( $ticket_id, $subject, $message );

            wc_add_notice( 'Destek talebiniz oluşturuldu.', 'success' );

            wp_safe_redirect( wc_get_account_endpoint_url( 'destek-talepleri' ) );
            exit;
        }

        /* -------------------------------
         *  Ticket Mesajı Gönder
         * ------------------------------- */
        if ( isset( $_POST['ies_reply_ticket'] ) ) {

            if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'ies_ticket_reply_' . $_POST['ticket_id'] ) ) {
                wc_add_notice( 'Güvenlik hatası!', 'error' );
                return;
            }

            $ticket_id = intval( $_POST['ticket_id'] );
            $message   = wp_kses_post( $_POST['ies_message'] );

            if ( ! $ticket_id || $message === '' ) {
                wc_add_notice( 'Mesaj boş olamaz.', 'error' );
                return;
            }

            $ticket = $this->get_ticket( $ticket_id, $user_id );
            if ( ! $ticket ) {
                wc_add_notice( 'Ticket bulunamadı.', 'error' );
                return;
            }

            global $wpdb;

            $wpdb->insert(
                $this->table_messages,
                array(
                    'ticket_id' => $ticket_id,
                    'user_id'   => $user_id,
                    'is_admin'  => 0,
                    'message'   => $message,
                    'created_at'=> current_time( 'mysql' )
                )
            );

            /* Admin'e mail bildirimi */
            $this->send_admin_reply_notification( $ticket_id, $message, $user_id );

            wc_add_notice( 'Yanıtınız gönderildi.', 'success' );

            wp_safe_redirect( wc_get_account_endpoint_url( 'destek-talepleri' ) . '?ticket_id=' . $ticket_id );
            exit;
        }
    }

    /* ============================================================
     *  ADMIN PANELİ
     * ============================================================ */

    public function register_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'EPIN Destek Talepleri',
            'EPIN Destek Talepleri',
            'manage_woocommerce',
            'ies-tickets',
            array( $this, 'render_admin_page' )
        );
    }

    public function render_admin_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Yetkiniz yok.' );
        }

        echo '<div class="wrap ies-admin-page">';
        echo '<h1>EPIN Destek Talepleri</h1>';

        $ticket_id = isset( $_GET['ticket_id'] ) ? intval( $_GET['ticket_id'] ) : 0;

        if ( $ticket_id ) {
            $this->render_admin_ticket_detail( $ticket_id );
        } else {
            $this->render_admin_ticket_list();
        }

        echo '</div>';
    }

    /**
     * Ticket listesi
     */
    protected function render_admin_ticket_list() {
        global $wpdb;

        $tickets = $wpdb->get_results(
            "SELECT t.*, u.user_login, u.user_email
             FROM {$this->table_tickets} t
             LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
             ORDER BY t.created_at DESC
             LIMIT 200"
        );

        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>
                <th>ID</th>
                <th>Kullanıcı</th>
                <th>Konu</th>
                <th>Durum</th>
                <th>Tarih</th>
                <th>İşlem</th>
              </tr></thead>';
        echo '<tbody>';

        if ( empty( $tickets ) ) {
            echo '<tr><td colspan="6">Henüz ticket yok.</td></tr>';
        } else {
            foreach ( $tickets as $t ) {
                $detail = admin_url( 'admin.php?page=ies-tickets&ticket_id=' . $t->id );

                echo '<tr>';
                echo '<td>' . intval( $t->id ) . '</td>';
                echo '<td>' . esc_html( $t->user_login ) . '<br><small>' . esc_html( $t->user_email ) . '</small></td>';
                echo '<td>' . esc_html( $t->subject ) . '</td>';
                echo '<td>' . esc_html( ucfirst( $t->status ) ) . '</td>';
                echo '<td>' . esc_html( $t->created_at ) . '</td>';
                echo '<td><a href="' . esc_url( $detail ) . '" class="button">Aç</a></td>';
                echo '</tr>';
            }
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Admin ticket detay sayfası
     */
    protected function render_admin_ticket_detail( $ticket_id ) {
        global $wpdb;

        $ticket = $this->get_ticket( $ticket_id );
        if ( ! $ticket ) {
            echo '<p>Ticket bulunamadı.</p>';
            return;
        }

        $user = get_user_by( 'id', $ticket->user_id );
        $messages = $this->get_ticket_messages( $ticket_id );

        ?>
        <div class="ies-card">
            <h2>Ticket #<?php echo $ticket_id; ?></h2>
            <p><strong>Konu:</strong> <?php echo esc_html( $ticket->subject ); ?></p>
            <p><strong>Kullanıcı:</strong> <?php echo esc_html( $user->user_login ); ?> (<?php echo esc_html( $user->user_email ); ?>)</p>
            <p><strong>Durum:</strong> <?php echo ucfirst( $ticket->status ); ?></p>
        </div>

        <div class="ies-card">
            <h2>Mesajlar</h2>

            <div class="ies-ticket-thread">
                <?php
                if ( ! empty( $messages ) ) :
                    foreach ( $messages as $m ) :
                        $cls = $m->is_admin ? 'is-admin' : 'is-user';
                ?>
                        <div class="ies-ticket-msg <?php echo esc_attr( $cls ); ?>">
                            <div class="ies-ticket-meta">
                                <strong><?php echo $m->is_admin ? 'Admin' : esc_html( $m->user_login ); ?></strong>
                                <span><?php echo esc_html( $m->created_at ); ?></span>
                            </div>
                            <div class="ies-ticket-body">
                                <?php echo wp_kses_post( wpautop( $m->message ) ); ?>
                            </div>
                        </div>
                <?php
                    endforeach;
                else :
                    echo '<p>Henüz mesaj yok.</p>';
                endif;
                ?>
            </div>
        </div>

        <div class="ies-card">
            <h2>Yanıt Gönder</h2>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'ies_admin_ticket_reply_' . $ticket_id ); ?>
                <input type="hidden" name="action" value="ies_admin_ticket_reply">
                <input type="hidden" name="ticket_id" value="<?php echo intval( $ticket_id ); ?>">

                <textarea name="ies_admin_message" required rows="5" style="width:100%;"></textarea>

                <p>
                    <button type="submit" class="button button-primary">Yanıtla</button>
                </p>
            </form>
        </div>

        <div class="ies-card">
            <h2>Durum Güncelle</h2>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'ies_admin_ticket_status_' . $ticket_id ); ?>
                <input type="hidden" name="action" value="ies_admin_ticket_status">
                <input type="hidden" name="ticket_id" value="<?php echo intval( $ticket_id ); ?>">

                <select name="status">
                    <option value="open" <?php selected( $ticket->status, 'open' ); ?>>Açık</option>
                    <option value="answered" <?php selected( $ticket->status, 'answered' ); ?>>Yanıtlandı</option>
                    <option value="closed" <?php selected( $ticket->status, 'closed' ); ?>>Kapalı</option>
                </select>

                <p>
                    <button type="submit" class="button">Kaydet</button>
                </p>
            </form>
        </div>

        <?php
    }

    /* ============================================================
     *  ADMIN ACTIONS
     * ============================================================ */

    public function handle_admin_reply() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Yetkiniz yok!' );
        }

        $ticket_id = intval( $_POST['ticket_id'] );
        $msg       = wp_kses_post( $_POST['ies_admin_message'] );

        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'ies_admin_ticket_reply_' . $ticket_id ) ) {
            wp_die( 'Güvenlik hatası!' );
        }

        if ( ! $ticket_id || $msg === '' ) {
            wp_die( 'Boş mesaj gönderilemez.' );
        }

        global $wpdb;

        $wpdb->insert(
            $this->table_messages,
            array(
                'ticket_id' => $ticket_id,
                'user_id'   => 0,
                'is_admin'  => 1,
                'message'   => $msg,
                'created_at'=> current_time( 'mysql' )
            )
        );

        // Ticket durumunu güncelle
        $wpdb->update(
            $this->table_tickets,
            array( 'status' => 'answered' ),
            array( 'id' => $ticket_id )
        );

        // Müşteriye mail gönder
        $this->send_customer_notification( $ticket_id, $msg );

        wp_redirect( admin_url( 'admin.php?page=ies-tickets&ticket_id=' . $ticket_id ) );
        exit;
    }

    public function handle_admin_status() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Yetkiniz yok!' );

        $ticket_id = intval( $_POST['ticket_id'] );
        $status    = sanitize_text_field( $_POST['status'] );

        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'ies_admin_ticket_status_' . $ticket_id ) ) {
            wp_die( 'Güvenlik hatası!' );
        }

        global $wpdb;

        $wpdb->update(
            $this->table_tickets,
            array( 'status' => $status ),
            array( 'id' => $ticket_id )
        );

        wp_redirect( admin_url( 'admin.php?page=ies-tickets&ticket_id=' . $ticket_id ) );
        exit;
    }

    /* ============================================================
     *  MAIL BİLDİRİMLERİ
     * ============================================================ */

    protected function send_admin_notification( $ticket_id, $subject, $message ) {

        $admin_email = get_option( 'admin_email' );

        $body = "
Yeni bir destek talebi oluşturuldu.

Ticket ID: #{$ticket_id}
Konu: {$subject}

Mesaj:
{$message}

Admin panelden yanıtlayabilirsiniz:
" . admin_url( "admin.php?page=ies-tickets&ticket_id={$ticket_id}" );

        wp_mail( $admin_email, "Yeni Destek Talebi (#{$ticket_id})", $body );
    }

    protected function send_customer_notification( $ticket_id, $msg ) {
        global $wpdb;

        $ticket = $this->get_ticket( $ticket_id );
        if ( ! $ticket ) return;

        $user = get_user_by( 'id', $ticket->user_id );
        if ( ! $user ) return;

        $body = "
Destek talebiniz yanıtlandı.

Ticket Konusu: {$ticket->subject}

Yanıt:
{$msg}

Talebi görüntülemek için giriş yapın:
" . wc_get_account_endpoint_url( 'destek-talepleri' ) . "?ticket_id={$ticket_id}";

        wp_mail( $user->user_email, "Destek Talebiniz Yanıtlandı (#{$ticket_id})", $body );
    }

    protected function send_admin_reply_notification( $ticket_id, $msg, $user_id ) {
        $admin_email = get_option( 'admin_email' );
        $user        = get_user_by( 'id', $user_id );

        $body = "
Bir kullanıcı mevcut ticket'a yanıt yazdı.

Ticket ID: {$ticket_id}
Kullanıcı: {$user->user_login} ({$user->user_email})

Mesaj:
{$msg}

Ticket detay:
" . admin_url( "admin.php?page=ies-tickets&ticket_id={$ticket_id}" );

        wp_mail( $admin_email, "Ticket Yanıtı (#{$ticket_id})", $body );
    }
}
