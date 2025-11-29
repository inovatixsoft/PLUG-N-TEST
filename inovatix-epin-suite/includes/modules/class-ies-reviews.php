<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Otomatik Yorum Sistemi
 *
 * - Sipariş "completed" olduğunda 24 saat sonrasına cron event planlar.
 * - Müşteri 24 saat içinde kendi yorumunu yazmazsa, ürünlere otomatik 5★ yorum ekler.
 * - Hesabım > Sipariş Detayı sayfasında hızlı yorum formu gösterir.
 */
class IES_Reviews {

    public function __construct() {
        // Sipariş tamamlandığında planlama
        add_action( 'woocommerce_order_status_completed', array( $this, 'schedule_auto_review' ), 10, 1 );

        // Cron event (order_id parametreli)
        add_action( 'ies_auto_review_event', array( $this, 'run_auto_review' ), 10, 1 );

        // Müşteri panelinde sipariş detayında hızlı yorum formu
        add_action( 'woocommerce_view_order', array( $this, 'render_quick_review_forms' ), 20, 1 );

        // Müşteri yorum formunu işler
        add_action( 'template_redirect', array( $this, 'handle_quick_review_submit' ) );
    }

    /* ============================================================
     *  SİPARİŞ TAMAMLANINCA PLANLAMA
     * ============================================================ */

    public function schedule_auto_review( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        // Daha önce planlanmış mı?
        if ( $order->get_meta( '_ies_auto_review_scheduled' ) ) {
            return;
        }

        // 24 saat sonrası (WP zamanına göre)
        $timestamp = time() + DAY_IN_SECONDS;

        wp_schedule_single_event( $timestamp, 'ies_auto_review_event', array( $order_id ) );

        $order->update_meta_data( '_ies_auto_review_scheduled', 1 );
        $order->save();
    }

    /* ============================================================
     *  CRON: OTOMATİK YORUM OLUŞTUR
     * ============================================================ */

    public function run_auto_review( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        // Zaten otomatik yorum yapıldıysa tekrar yapma
        if ( $order->get_meta( '_ies_auto_review_done' ) ) {
            return;
        }

        $user_id = $order->get_user_id();
        if ( ! $user_id ) {
            // misafir sipariş ise otomatik yorum atmayalım
            return;
        }

        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) return;

        $items = $order->get_items();
        if ( empty( $items ) ) return;

        foreach ( $items as $item ) {
            $product_id = $item->get_product_id();
            if ( ! $product_id ) continue;

            // Ürün yorumlara açıksa ve kullanıcı zaten yorum yazmamışsa
            if ( ! $this->product_allows_reviews( $product_id ) ) {
                continue;
            }

            if ( $this->user_already_reviewed( $user_id, $product_id ) ) {
                continue;
            }

            $this->create_auto_review( $user, $product_id );
        }

        // İşaretle
        $order->update_meta_data( '_ies_auto_review_done', 1 );
        $order->save();
    }

    protected function product_allows_reviews( $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) return false;

        return $product->get_reviews_allowed();
    }

    protected function user_already_reviewed( $user_id, $product_id ) {
        $args = array(
            'post_id' => $product_id,
            'user_id' => $user_id,
            'status'  => 'approve',
            'count'   => true,
            'type'    => 'review',
        );

        $count = get_comments( $args );

        return $count > 0;
    }

    protected function create_auto_review( $user, $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) return;

        $names = array(
            'Süper hızlı teslimat, teşekkürler!',
            'Anında teslim, güvenilir satıcı.',
            'Sorunsuz işlem, herkese tavsiye ederim.',
            'Uygun fiyat, hızlı teslim. Harika!',
            'Siparişim saniyeler içinde teslim edildi.',
            'Destek çok ilgili, teşekkür ederim.',
            'Her zamanki gibi güvenilir ve hızlı.',
            'Hiç sorun yaşamadım, gönül rahatlığıyla alın.',
        );

        $content = $names[ array_rand( $names ) ];

        $commentdata = array(
            'comment_post_ID'      => $product_id,
            'comment_author'       => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_author_url'   => '',
            'comment_content'      => $content,
            'comment_type'         => 'review',
            'comment_parent'       => 0,
            'user_id'              => $user->ID,
            'comment_approved'     => 1,
        );

        $comment_id = wp_insert_comment( $commentdata );

        if ( $comment_id ) {
            // 5 yıldız
            update_comment_meta( $comment_id, 'rating', 5 );
        }
    }

    /* ============================================================
     *  MÜŞTERİ PANELİ: HIZLI YORUM FORMU
     * ============================================================ */

    public function render_quick_review_forms( $order_id ) {
        if ( ! is_user_logged_in() ) return;

        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $user_id = get_current_user_id();
        if ( $order->get_user_id() != $user_id ) return;

        $items = $order->get_items();
        if ( empty( $items ) ) return;

        echo '<div class="ies-card ies-order-reviews-block">';
        echo '<h2>Siparişiniz için hızlı yorum bırakın</h2>';
        echo '<p>Almış olduğunuz ürünler için 5 yıldızlı hızlı yorum bırakabilir veya mesajınızı düzenleyebilirsiniz.</p>';

        foreach ( $items as $item_id => $item ) {
            $product_id = $item->get_product_id();
            if ( ! $product_id ) continue;

            if ( ! $this->product_allows_reviews( $product_id ) ) {
                continue;
            }

            // Zaten yorum varsa form gösterme
            if ( $this->user_already_reviewed( $user_id, $product_id ) ) {
                continue;
            }

            $product = wc_get_product( $product_id );
            if ( ! $product ) continue;

            $product_link = get_permalink( $product_id );
            $product_title = $product->get_name();

            ?>
            <div class="ies-order-review-item">
                <div class="ies-order-review-header">
                    <a href="<?php echo esc_url( $product_link ); ?>" target="_blank">
                        <?php echo esc_html( $product_title ); ?>
                    </a>
                </div>

                <form method="post" class="ies-order-review-form">
                    <?php wp_nonce_field( 'ies_quick_review_' . $order_id . '_' . $product_id ); ?>
                    <input type="hidden" name="ies_quick_review" value="1">
                    <input type="hidden" name="order_id" value="<?php echo intval( $order_id ); ?>">
                    <input type="hidden" name="product_id" value="<?php echo intval( $product_id ); ?>">

                    <p class="ies-order-review-rating">
                        <span>Değerlendirme:</span>
                        <span class="ies-stars">
                            ★★★★★
                        </span>
                        <input type="hidden" name="rating" value="5">
                    </p>

                    <p>
                        <textarea name="comment" rows="3" placeholder="Yorumunuz (boş bırakırsanız otomatik güzel bir yorum eklenir)."></textarea>
                    </p>

                    <p>
                        <button type="submit" class="button button-primary">Yorumu Gönder</button>
                    </p>
                </form>
            </div>
            <?php
        }

        echo '</div>';
    }

    /**
     * Müşteri hızlı yorum formunu işler.
     */
    public function handle_quick_review_submit() {
        if ( ! isset( $_POST['ies_quick_review'] ) ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            return;
        }

        $order_id   = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

        if ( ! $order_id || ! $product_id ) {
            return;
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ies_quick_review_' . $order_id . '_' . $product_id ) ) {
            wc_add_notice( 'Güvenlik hatası, lütfen tekrar deneyin.', 'error' );
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wc_add_notice( 'Sipariş bulunamadı.', 'error' );
            return;
        }

        $user_id = get_current_user_id();
        if ( $order->get_user_id() != $user_id ) {
            wc_add_notice( 'Bu sipariş size ait değil.', 'error' );
            return;
        }

        if ( $this->user_already_reviewed( $user_id, $product_id ) ) {
            wc_add_notice( 'Bu ürüne zaten yorum yapmışsınız.', 'error' );
            return;
        }

        $rating = isset( $_POST['rating'] ) ? intval( $_POST['rating'] ) : 5;
        if ( $rating < 1 || $rating > 5 ) {
            $rating = 5;
        }

        $comment_text = isset( $_POST['comment'] ) ? wp_kses_post( $_POST['comment'] ) : '';

        // Yorum boşsa random güzel text seç
        if ( $comment_text === '' ) {
            $names = array(
                'Her şey çok hızlı ve sorunsuz ilerledi, teşekkür ederim.',
                'Fiyatlar uygun, teslimat anında. Harika bir deneyim.',
                'Sorunsuz EPIN teslimatı, tekrar tercih edeceğim.',
                'Çok memnun kaldım, güvenilir bir site.',
                'İlk alışverişimdi, beklediğimden iyi çıktı.',
            );
            $comment_text = $names[ array_rand( $names ) ];
        }

        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            wc_add_notice( 'Kullanıcı bulunamadı.', 'error' );
            return;
        }

        $commentdata = array(
            'comment_post_ID'      => $product_id,
            'comment_author'       => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_author_url'   => '',
            'comment_content'      => $comment_text,
            'comment_type'         => 'review',
            'comment_parent'       => 0,
            'user_id'              => $user->ID,
            'comment_approved'     => 1,
        );

        $comment_id = wp_insert_comment( $commentdata );
        if ( $comment_id ) {
            update_comment_meta( $comment_id, 'rating', $rating );
            wc_add_notice( 'Yorumunuz için teşekkür ederiz.', 'success' );
        } else {
            wc_add_notice( 'Yorum eklenirken bir hata oluştu.', 'error' );
        }

        // Sipariş detay sayfasına geri dön
        wp_safe_redirect( wc_get_account_endpoint_url( 'view-order' ) . $order_id . '/' );
        exit;
    }
}
