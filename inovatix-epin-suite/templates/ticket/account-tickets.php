<?php
defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
    echo '<p>Bu sayfayı görmek için giriş yapmalısınız.</p>';
    return;
}

$user_id = get_current_user_id();
global $wpdb;

$table_tickets  = $wpdb->prefix . 'ies_tickets';
$table_messages = $wpdb->prefix . 'ies_ticket_messages';

$current_ticket_id = isset( $_GET['ticket_id'] ) ? intval( $_GET['ticket_id'] ) : 0;
$current_ticket    = null;
$current_messages  = array();

if ( $current_ticket_id ) {
    $current_ticket = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table_tickets} WHERE id = %d AND user_id = %d",
            $current_ticket_id,
            $user_id
        )
    );

    if ( $current_ticket ) {
        $current_messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.*, u.user_login
                 FROM {$table_messages} m
                 LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
                 WHERE m.ticket_id = %d
                 ORDER BY m.created_at ASC",
                $current_ticket_id
            )
        );
    }
}
?>

<div class="ies-account-tickets-page">

    <h2 class="ies-section-title">Destek Taleplerim</h2>

    <div class="ies-ticket-layout">

        <!-- SOL: TICKET LİSTESİ -->
        <div class="ies-ticket-list">
            <h3>Talep Listesi</h3>

            <?php if ( empty( $tickets ) ) : ?>
                <p>Henüz açılmış bir destek talebiniz bulunmamaktadır.</p>
            <?php else : ?>
                <ul class="ies-ticket-list-ul">
                    <?php foreach ( $tickets as $t ) :
                        $link   = wc_get_account_endpoint_url( 'destek-talepleri' ) . '?ticket_id=' . intval( $t->id );
                        $status = ucfirst( $t->status );
                        ?>
                        <li class="ies-ticket-list-item <?php echo $current_ticket_id === intval( $t->id ) ? 'is-active' : ''; ?>">
                            <a href="<?php echo esc_url( $link ); ?>">
                                <span class="ies-ticket-id">#<?php echo intval( $t->id ); ?></span>
                                <span class="ies-ticket-subject"><?php echo esc_html( $t->subject ); ?></span>
                                <span class="ies-ticket-status ies-status-<?php echo esc_attr( $t->status ); ?>">
                                    <?php echo esc_html( $status ); ?>
                                </span>
                                <span class="ies-ticket-date"><?php echo esc_html( $t->created_at ); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        </div>

        <!-- SAĞ: DETAY VEYA YENİ TICKET -->
        <div class="ies-ticket-detail">

            <?php if ( $current_ticket && $current_ticket_id ) : ?>

                <div class="ies-card">
                    <h3>Ticket #<?php echo intval( $current_ticket->id ); ?></h3>
                    <p><strong>Konu:</strong> <?php echo esc_html( $current_ticket->subject ); ?></p>
                    <p><strong>Durum:</strong> <?php echo esc_html( ucfirst( $current_ticket->status ) ); ?></p>
                    <p><strong>Oluşturulma:</strong> <?php echo esc_html( $current_ticket->created_at ); ?></p>
                </div>

                <div class="ies-card">
                    <h3>Mesajlar</h3>

                    <div class="ies-ticket-thread">
                        <?php if ( ! empty( $current_messages ) ) : ?>
                            <?php foreach ( $current_messages as $m ) :
                                $cls = $m->is_admin ? 'is-admin' : 'is-user';
                                ?>
                                <div class="ies-ticket-msg <?php echo esc_attr( $cls ); ?>">
                                    <div class="ies-ticket-meta">
                                        <strong>
                                            <?php echo $m->is_admin ? 'Destek Ekibi' : esc_html( $m->user_login ); ?>
                                        </strong>
                                        <span><?php echo esc_html( $m->created_at ); ?></span>
                                    </div>
                                    <div class="ies-ticket-body">
                                        <?php echo wp_kses_post( wpautop( $m->message ) ); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>Bu ticket için henüz mesaj yok.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ies-card">
                    <h3>Yanıt Gönder</h3>

                    <form method="post" class="ies-ticket-reply-form">
                        <?php wp_nonce_field( 'ies_ticket_reply_' . $current_ticket_id ); ?>
                        <input type="hidden" name="ticket_id" value="<?php echo intval( $current_ticket_id ); ?>">
                        <input type="hidden" name="ies_reply_ticket" value="1">

                        <p>
                            <textarea name="ies_message" rows="4" required
                                      placeholder="Destek ekibine yanıtınızı buraya yazın."></textarea>
                        </p>

                        <p>
                            <button type="submit" class="button button-primary">Yanıt Gönder</button>
                        </p>
                    </form>
                </div>

            <?php else : ?>

                <div class="ies-card">
                    <h3>Yeni Destek Talebi Oluştur</h3>
                    <p>Yaşadığınız problem, ödeme sorunu, kod kullanımı vb. tüm konular için buradan bize ulaşabilirsiniz.</p>

                    <form method="post" class="ies-ticket-new-form">
                        <?php wp_nonce_field( 'ies_ticket_nonce' ); ?>
                        <input type="hidden" name="ies_create_ticket" value="1">

                        <p>
                            <label for="ies_ticket_subject">Konu</label>
                            <input type="text"
                                   id="ies_ticket_subject"
                                   name="ies_ticket_subject"
                                   required
                                   class="input-text"
                                   placeholder="Örn: Kod çalışmıyor, ödeme sorunu, vb.">
                        </p>

                        <p>
                            <label for="ies_ticket_message">Mesajınız</label>
                            <textarea id="ies_ticket_message"
                                      name="ies_ticket_message"
                                      rows="6"
                                      required
                                      placeholder="Detaylı olarak sorununuzu yazınız. Sipariş numaranız, oyun adınız, hesap ID gibi bilgileri ekleyin."></textarea>
                        </p>

                        <p>
                            <button type="submit" class="button button-primary">Destek Talebi Gönder</button>
                        </p>
                    </form>
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>
