<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * EPIN Kod Yönetimi + Otomatik Kod Teslim Sistemi
 *
 * - Ürün düzenleme ekranına “Kod Havuzu” sekmesi ekler
 * - Sipariş tamamlandığında kodları otomatik teslim eder
 * - Kullanılmış kodları işaretler
 * - Müşteri panelinde sipariş detaylarında kodları gösterir
 * - Müşteriye “Kodunuz hazır” e-postası gider
 */
class IES_EPIN_Manager {

    public function __construct() {

        // Admin ürün edit ekranı (tab ekle)
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'register_epin_tab' ) );
        add_action( 'woocommerce_product_data_panels', array( $this, 'render_epin_panel' ) );

        // Kaydet
        add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product_epin_data' ) );

        // Sipariş tamamlandığında kodları teslim et
        add_action( 'woocommerce_order_status_completed', array( $this, 'deliver_epin_codes' ), 10, 1 );

        // Sipariş detayında kodları göster
        add_action( 'woocommerce_order_item_meta_end', array( $this, 'display_codes_on_order' ), 10, 3 );
    }

    /* ============================================================
     *  ADMIN ÜRÜN DÜZENLEME
     * ============================================================ */

    public function register_epin_tab( $tabs ) {
        $tabs['ies_epin'] = array(
            'label'  => 'EPIN Kodları',
            'target' => 'ies_epin_data',
            'class'  => array(),
        );
        return $tabs;
    }

    public function render_epin_panel() {
        global $post;

        $codes_raw       = get_post_meta( $post->ID, '_ies_epin_codes', true );
        $used_raw        = get_post_meta( $post->ID, '_ies_epin_used', true );
        $auto_deliver    = get_post_meta( $post->ID, '_ies_epin_auto', true );
        $codes_per_order = get_post_meta( $post->ID, '_ies_epin_per_order', true );

        $codes = is_array( $codes_raw ) ? $codes_raw : array();
        $used  = is_array( $used_raw ) ? $used_raw : array();

        ?>
        <div id="ies_epin_data" class="panel woocommerce_options_panel">
            <h2>EPIN Kod Yönetimi</h2>

            <p class="description">
                Bu bölüm üzerinden ürünün satışında teslim edilecek EPIN / KEY kodlarını yönetebilirsiniz.
            </p>

            <div class="options_group">
                <?php
                woocommerce_wp_checkbox( array(
                    'id'          => '_ies_epin_auto',
                    'label'       => 'Otomatik kod teslimi aktif olsun',
                    'value'       => $auto_deliver ? 'yes' : 'no',
                    'cbvalue'     => 'yes',
                ) );

                woocommerce_wp_text_input( array(
                    'id'          => '_ies_epin_per_order',
                    'label'       => 'Sipariş başına kaç kod verilsin?',
                    'type'        => 'number',
                    'placeholder' => '1',
                    'desc_tip'    => true,
                    'description' => 'Tek bir ürün satın alındığında kaç adet kod teslim edileceğini ayarlar.',
                    'value'       => $codes_per_order ? intval( $codes_per_order ) : 1,
                ) );
                ?>
            </div>

            <h3>Kod Havuzu (Boş satırları siler)</h3>
            <textarea name="ies_epin_codes" style="width:100%;min-height:200px;font-family:monospace;">
<?php echo implode("\n", $codes ); ?>
            </textarea>

            <h3>Kullanılmış Kodlar (Salt okunur)</h3>
            <textarea readonly style="width:100%;min-height:150px;background:#f2f2f2;color:#555;">
<?php echo implode("\n", $used ); ?>
            </textarea>
        </div>
        <?php
    }

    public function save_product_epin_data( $product ) {

        $id = $product->get_id();

        // Otomatik teslim
        $auto = isset( $_POST['_ies_epin_auto'] ) ? 'yes' : 'no';
        update_post_meta( $id, '_ies_epin_auto', $auto );

        // Sipariş başına kod sayısı
        $per_order = isset( $_POST['_ies_epin_per_order'] ) ? intval( $_POST['_ies_epin_per_order'] ) : 1;
        update_post_meta( $id, '_ies_epin_per_order', $per_order );

        // Kodlar
        if ( isset( $_POST['ies_epin_codes'] ) ) {
            $raw = trim( $_POST['ies_epin_codes'] );
            $lines = array_filter( array_map( 'trim', explode("\n", $raw ) ) );
            update_post_meta( $id, '_ies_epin_codes', $lines );
        }
    }

    /* ============================================================
     *  SİPARİŞ TAMAMLANINDA OTOMATİK KOD TESLİMİ
     * ============================================================ */

    public function deliver_epin_codes( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        foreach ( $order->get_items() as $item_id => $item ) {

            $product_id = $item->get_product_id();
            if ( ! $product_id ) continue;

            $auto = get_post_meta( $product_id, '_ies_epin_auto', true );
            if ( $auto !== 'yes' ) continue;

            // Kaç kod verilecek?
            $per_order = intval( get_post_meta( $product_id, '_ies_epin_per_order', true ) );
            if ( $per_order < 1 ) $per_order = 1;

            // Kod havuzu
            $codes = get_post_meta( $product_id, '_ies_epin_codes', true );
            $codes = is_array( $codes ) ? $codes : array();

            // Yeterli kod var mı?
            if ( count( $codes ) < $per_order ) {
                $order->add_order_note( "EPIN kodu bulunamadı (stok yetersiz)." );
                continue;
            }

            // Kodları çek
            $deliver = array_splice( $codes, 0, $per_order );

            // Kullanılmış kodlara ekle
            $used = get_post_meta( $product_id, '_ies_epin_used', true );
            $used = is_array( $used ) ? $used : array();
            $used = array_merge( $used, $deliver );

            // Güncelle
            update_post_meta( $product_id, '_ies_epin_codes', $codes );
            update_post_meta( $product_id, '_ies_epin_used',  $used );

            // Sipariş meta olarak ekle
            wc_add_order_item_meta( $item_id, '_ies_epin_codes', $deliver );

            // Müşteriye mail gönder
            $this->send_epin_mail( $order, $deliver, $product_id );
        }
    }

    /* ============================================================
     *  MÜŞTERİ PANELİ – SİPARİŞ DETAYINDA GÖSTER
     * ============================================================ */

    public function display_codes_on_order( $item_id, $item, $order ) {

        $codes = wc_get_order_item_meta( $item_id, '_ies_epin_codes', true );
        if ( empty( $codes ) || ! is_array( $codes ) ) {
            return;
        }

        echo '<div class="ies-order-epin-box">';
        echo '<strong>EPIN Kodlarınız:</strong><br>';

        foreach ( $codes as $code ) {
            echo '<div class="ies-epin-code">' . esc_html( $code ) . '</div>';
        }

        echo '</div>';
    }

    /* ============================================================
     *  MAIL TEMSİLİ
     * ============================================================ */

    protected function send_epin_mail( $order, $codes, $product_id ) {

        $user = $order->get_user();
        if ( ! $user ) return;

        $product = wc_get_product( $product_id );
        $product_name = $product ? $product->get_name() : 'EPIN Ürünü';

        $message  = "Merhaba {$user->display_name},\n\n";
        $message .= "{$product_name} ürünü için EPIN kodlarınız hazır!\n\n";

        foreach ( $codes as $code ) {
            $message .= "- {$code}\n";
        }

        $message .= "\nSipariş Detayları: " . $order->get_view_order_url() . "\n\n";
        $message .= "Teşekkür ederiz.\n";

        wp_mail(
            $user->user_email,
            'EPIN Kodlarınız Hazır – ' . $product_name,
            $message
        );
    }
}
