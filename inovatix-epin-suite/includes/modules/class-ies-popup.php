<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ürün sepete eklenirken ID / ZONE / Sunucu / Not gibi ekstra bilgileri toplar.
 * Hangi kategoride hangi alanların zorunlu olduğunu admin panelden ayarlarsın.
 */
class IES_Popup {

    protected $option_key = 'ies_popup_category_fields';

    public function __construct() {
        // Admin ayar sayfası
        add_action( 'admin_menu', array( $this, 'register_menu' ), 40 );
        add_action( 'admin_init', array( $this, 'save_settings' ) );

        // Frontend popup çıktısı (HTML)
        add_action( 'wp_footer', array( $this, 'render_popup_html' ) );

        // Sepete eklemeden önce validasyon
        add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 3 );

        // Sepete ürün eklenirken extra veriyi cart item içine koy
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );

        // Sepet / ödeme sayfasında item altında göster
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );

        // Sipariş oluşturulurken item meta olarak kaydet
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 10, 4 );
    }

    /* ============================================================
     *  ADMIN – KATEGORİ BAZLI ALAN AYARI
     * ============================================================ */

    public function register_menu() {
        add_submenu_page(
            'woocommerce',
            'EPIN Bilgi Alanları',
            'EPIN Bilgi Alanları',
            'manage_woocommerce',
            'ies-popup-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Bu sayfaya erişim yetkiniz yok.' );
        }

        $saved = get_option( $this->option_key, array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }

        $categories = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ) );

        ?>
        <div class="wrap ies-admin-page">
            <h1>EPIN Bilgi Alanları (ID / ZONE / Sunucu / Not)</h1>
            <p class="description">
                Buradan, her ürün kategorisi için müşteriden hangi alanların isteneceğini belirleyebilirsiniz.
                Örneğin Mobile Legends kategorisinde "Oyuncu ID" ve "Zone ID" zorunlu olabilir.
            </p>

            <form method="post">
                <?php wp_nonce_field( 'ies_popup_settings' ); ?>

                <div class="ies-card">
                    <h2 style="margin-top:0;">Kategori Bazlı Alan Seçimi</h2>

                    <?php if ( empty( $categories ) ) : ?>
                        <p>Herhangi bir ürün kategorisi bulunamadı.</p>
                    <?php else : ?>
                        <table class="widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th style="width:120px;">Oyuncu ID</th>
                                    <th style="width:120px;">Zone ID</th>
                                    <th style="width:120px;">Sunucu</th>
                                    <th style="width:160px;">Ek Not</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $categories as $cat ) : 
                                    $fields = isset( $saved[ $cat->term_id ] ) && is_array( $saved[ $cat->term_id ] )
                                        ? $saved[ $cat->term_id ]
                                        : array();
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html( $cat->name ); ?></strong><br>
                                            <span style="color:#777;font-size:11px;"><?php echo esc_html( $cat->slug ); ?> (ID: <?php echo intval( $cat->term_id ); ?>)</span>
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="checkbox" name="ies_popup_fields[<?php echo intval( $cat->term_id ); ?>][player_id]"
                                                value="1" <?php checked( isset( $fields['player_id'] ) && $fields['player_id'] ); ?>>
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="checkbox" name="ies_popup_fields[<?php echo intval( $cat->term_id ); ?>][zone_id]"
                                                value="1" <?php checked( isset( $fields['zone_id'] ) && $fields['zone_id'] ); ?>>
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="checkbox" name="ies_popup_fields[<?php echo intval( $cat->term_id ); ?>][server]"
                                                value="1" <?php checked( isset( $fields['server'] ) && $fields['server'] ); ?>>
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="checkbox" name="ies_popup_fields[<?php echo intval( $cat->term_id ); ?>][note]"
                                                value="1" <?php checked( isset( $fields['note'] ) && $fields['note'] ); ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <p style="margin-top:15px;">
                        <button type="submit" class="button button-primary">Ayarları Kaydet</button>
                    </p>
                </div>
            </form>
        </div>
        <?php
    }

    public function save_settings() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ies_popup_settings' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $raw = isset( $_POST['ies_popup_fields'] ) ? (array) $_POST['ies_popup_fields'] : array();
        $clean = array();

        foreach ( $raw as $term_id => $fields ) {
            $term_id = intval( $term_id );
            if ( $term_id <= 0 ) continue;

            $flags = array();
            $fields = (array) $fields;

            if ( ! empty( $fields['player_id'] ) ) $flags['player_id'] = 1;
            if ( ! empty( $fields['zone_id'] ) )   $flags['zone_id']   = 1;
            if ( ! empty( $fields['server'] ) )    $flags['server']    = 1;
            if ( ! empty( $fields['note'] ) )      $flags['note']      = 1;

            if ( ! empty( $flags ) ) {
                $clean[ $term_id ] = $flags;
            }
        }

        update_option( $this->option_key, $clean );
    }

    /* ============================================================
     *  ORTAK – ÜRÜN İÇİN GEREKEN ALANLARI BUL
     * ============================================================ */

    protected function get_required_fields_for_product( $product_id ) {
        $mapping = get_option( $this->option_key, array() );
        if ( empty( $mapping ) || ! is_array( $mapping ) ) {
            return array();
        }

        $terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return array();
        }

        $needed = array();

        foreach ( $terms as $tid ) {
            $tid = intval( $tid );
            if ( isset( $mapping[ $tid ] ) && is_array( $mapping[ $tid ] ) ) {
                foreach ( $mapping[ $tid ] as $field_key => $flag ) {
                    if ( $flag ) {
                        $needed[ $field_key ] = 1;
                    }
                }
            }
        }

        return array_keys( $needed ); // ['player_id', 'zone_id', ...]
    }

    /* ============================================================
     *  FRONTEND – POPUP HTML
     *  (JS ile sepete ekle butonuna basılınca gösterilecek)
     * ============================================================ */

    public function render_popup_html() {
        if ( ! is_product() && ! is_tax( 'product_cat' ) ) {
            return;
        }

        ?>
        <div class="ies-cart-popup-overlay" style="display:none;">
            <div class="ies-cart-popup">
                <div class="ies-cart-popup-inner">
                    <button type="button" class="ies-cart-popup-close">&times;</button>

                    <h3>Hesap Bilgilerini Girin</h3>
                    <p class="ies-cart-popup-sub">
                        Satın alacağınız EPIN’in doğru hesaba tanımlanabilmesi için aşağıdaki alanları doldurmanız gerekmektedir.
                    </p>

                    <form method="post" class="ies-cart-popup-form">
                        <?php // Bu form JS ile ürün ID'si ve add-to-cart parametresi set edilerek gönderilecek. ?>

                        <input type="hidden" name="ies_popup_product_id" id="ies_popup_product_id" value="">
                        <input type="hidden" name="add-to-cart" id="ies_popup_add_to_cart" value="">

                        <div class="ies-cart-field">
                            <label for="ies_player_id">Oyuncu ID</label>
                            <input type="text" name="ies_player_id" id="ies_player_id" placeholder="Örn: 123456789">
                        </div>

                        <div class="ies-cart-field">
                            <label for="ies_zone_id">Zone ID</label>
                            <input type="text" name="ies_zone_id" id="ies_zone_id" placeholder="Örn: 1234">
                        </div>

                        <div class="ies-cart-field">
                            <label for="ies_server">Sunucu</label>
                            <input type="text" name="ies_server" id="ies_server" placeholder="Sunucu adı / no">
                        </div>

                        <div class="ies-cart-field">
                            <label for="ies_note">Ek Not</label>
                            <textarea name="ies_note" id="ies_note" rows="3" placeholder="İsteğe bağlı açıklama..."></textarea>
                        </div>

                        <p class="ies-cart-popup-warning">
                            Lütfen bilgileri doğru girdiğinizden emin olun. Yanlış girilen ID / Zone bilgileri müşteri sorumluluğundadır.
                        </p>

                        <div class="ies-cart-popup-actions">
                            <button type="button" class="ies-cart-popup-cancel">Vazgeç</button>
                            <button type="submit" class="ies-cart-popup-submit">Sepete Ekle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /* ============================================================
     *  ADD TO CART VALIDATION
     * ============================================================ */

    public function validate_add_to_cart( $passed, $product_id, $quantity ) {
        // Bu ürün için zorunlu alan listesi
        $required_fields = $this->get_required_fields_for_product( $product_id );
        if ( empty( $required_fields ) ) {
            return $passed;
        }

        // POST alanlarını oku
        $player_id = isset( $_POST['ies_player_id'] ) ? trim( wp_unslash( $_POST['ies_player_id'] ) ) : '';
        $zone_id   = isset( $_POST['ies_zone_id'] )   ? trim( wp_unslash( $_POST['ies_zone_id'] ) )   : '';
        $server    = isset( $_POST['ies_server'] )    ? trim( wp_unslash( $_POST['ies_server'] ) )    : '';
        $note      = isset( $_POST['ies_note'] )      ? trim( wp_unslash( $_POST['ies_note'] ) )      : '';

        // Her bir field için kontrol
        foreach ( $required_fields as $field ) {
            if ( $field === 'player_id' && $player_id === '' ) {
                wc_add_notice( 'Lütfen Oyuncu ID alanını doldurun.', 'error' );
                $passed = false;
            }
            if ( $field === 'zone_id' && $zone_id === '' ) {
                wc_add_notice( 'Lütfen Zone ID alanını doldurun.', 'error' );
                $passed = false;
            }
            if ( $field === 'server' && $server === '' ) {
                wc_add_notice( 'Lütfen Sunucu alanını doldurun.', 'error' );
                $passed = false;
            }
            if ( $field === 'note' && $note === '' ) {
                wc_add_notice( 'Lütfen Ek Not alanını doldurun.', 'error' );
                $passed = false;
            }
        }

        return $passed;
    }

    /* ============================================================
     *  CART ITEM DATA
     * ============================================================ */

    public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
        $required_fields = $this->get_required_fields_for_product( $product_id );
        if ( empty( $required_fields ) ) {
            return $cart_item_data;
        }

        $player_id = isset( $_POST['ies_player_id'] ) ? trim( wp_unslash( $_POST['ies_player_id'] ) ) : '';
        $zone_id   = isset( $_POST['ies_zone_id'] )   ? trim( wp_unslash( $_POST['ies_zone_id'] ) )   : '';
        $server    = isset( $_POST['ies_server'] )    ? trim( wp_unslash( $_POST['ies_server'] ) )    : '';
        $note      = isset( $_POST['ies_note'] )      ? trim( wp_unslash( $_POST['ies_note'] ) )      : '';

        $data = array();

        if ( in_array( 'player_id', $required_fields, true ) && $player_id !== '' ) {
            $data['player_id'] = $player_id;
        }
        if ( in_array( 'zone_id', $required_fields, true ) && $zone_id !== '' ) {
            $data['zone_id'] = $zone_id;
        }
        if ( in_array( 'server', $required_fields, true ) && $server !== '' ) {
            $data['server'] = $server;
        }
        if ( in_array( 'note', $required_fields, true ) && $note !== '' ) {
            $data['note'] = $note;
        }

        if ( ! empty( $data ) ) {
            $cart_item_data['ies_epin_info'] = $data;
        }

        return $cart_item_data;
    }

    public function display_cart_item_data( $item_data, $cart_item ) {
        if ( empty( $cart_item['ies_epin_info'] ) || ! is_array( $cart_item['ies_epin_info'] ) ) {
            return $item_data;
        }

        $map = array(
            'player_id' => 'Oyuncu ID',
            'zone_id'   => 'Zone ID',
            'server'    => 'Sunucu',
            'note'      => 'Ek Not',
        );

        foreach ( $cart_item['ies_epin_info'] as $key => $value ) {
            if ( isset( $map[ $key ] ) && $value !== '' ) {
                $item_data[] = array(
                    'name'  => $map[ $key ],
                    'value' => wc_clean( $value ),
                );
            }
        }

        return $item_data;
    }

    /* ============================================================
     *  ORDER ITEM META
     * ============================================================ */

    public function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
        if ( empty( $values['ies_epin_info'] ) || ! is_array( $values['ies_epin_info'] ) ) {
            return;
        }

        $map = array(
            'player_id' => 'Oyuncu ID',
            'zone_id'   => 'Zone ID',
            'server'    => 'Sunucu',
            'note'      => 'Ek Not',
        );

        foreach ( $values['ies_epin_info'] as $key => $value ) {
            if ( isset( $map[ $key ] ) && $value !== '' ) {
                $item->add_meta_data( $map[ $key ], $value, true );
            }
        }
    }
}
