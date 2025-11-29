<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * EPIN Anasayfa Modülü
 *
 * - Admin: WooCommerce altında "EPIN Anasayfa" ayar sayfası.
 * - Frontend: [ies_home_layout] shortcode'u ile BursaGB tarzı ana EPIN sayfası.
 */
class IES_Homepage {

    protected $option_key = 'ies_home_layout';

    public function __construct() {
        // Admin menü
        add_action( 'admin_menu', array( $this, 'register_menu' ), 50 );

        // Ayar kaydetme
        add_action( 'admin_init', array( $this, 'handle_admin_save' ) );

        // Shortcode
        add_shortcode( 'ies_home_layout', array( $this, 'render_shortcode' ) );
    }

    /* ============================================================
     *  ADMIN AYAR SAYFASI
     * ============================================================ */

    public function register_menu() {
        add_submenu_page(
            'woocommerce',
            'EPIN Anasayfa',
            'EPIN Anasayfa',
            'manage_woocommerce',
            'ies-home-layout',
            array( $this, 'render_admin_page' )
        );
    }

    public function render_admin_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Bu sayfaya erişim yetkiniz yok.' );
        }

        $options = get_option( $this->option_key, array() );
        if ( ! is_array( $options ) ) {
            $options = array();
        }

        $highlight_cats = isset( $options['highlight_cats'] ) ? (array) $options['highlight_cats'] : array();
        $strip_cats     = isset( $options['strip_cats'] ) ? (array) $options['strip_cats'] : array();
        $lol_cat        = isset( $options['lol_cat'] ) ? intval( $options['lol_cat'] ) : 0;
        $steam_cat      = isset( $options['steam_cat'] ) ? intval( $options['steam_cat'] ) : 0;

        $all_cats = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ) );

        ?>
        <div class="wrap ies-admin-page">
            <h1>EPIN Anasayfa Tasarımı</h1>
            <p class="description">
                BursaGB tarzında EPIN anasayfanızı buradan yapılandırabilirsiniz. Sonrasında
                <code>[ies_home_layout]</code> shortcode'unu bir sayfaya ekleyerek bu tasarımı gösterebilirsiniz.
            </p>

            <form method="post">
                <?php wp_nonce_field( 'ies_home_layout_save' ); ?>

                <div class="ies-card">
                    <h2 style="margin-top:0;">1. Öne Çıkan Kategori Kartları (Üst Satır)</h2>
                    <p class="description">
                        Anasayfanın üst kısmında, büyük kartlar halinde gösterilecek kategorileri seçin.
                        Örn: Mobile Legends, PUBG, Valorant...
                    </p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Kart 1</th>
                            <td><?php $this->render_category_select( 'highlight_cats[0]', isset( $highlight_cats[0] ) ? intval( $highlight_cats[0] ) : 0, $all_cats ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Kart 2</th>
                            <td><?php $this->render_category_select( 'highlight_cats[1]', isset( $highlight_cats[1] ) ? intval( $highlight_cats[1] ) : 0, $all_cats ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Kart 3</th>
                            <td><?php $this->render_category_select( 'highlight_cats[2]', isset( $highlight_cats[2] ) ? intval( $highlight_cats[2] ) : 0, $all_cats ); ?></td>
                        </tr>
                    </table>
                </div>

                <div class="ies-card">
                    <h2 style="margin-top:0;">2. Yatay Oyun Şeritleri</h2>
                    <p class="description">
                        Her bir kategori için, altındaki ürünler yatay kaydırmalı şerit şeklinde gösterilir.
                        Örn: "Popüler Oyunlar", "En Çok Satanlar", "Yeni EPIN'ler" gibi.
                    </p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Şerit 1</th>
                            <td><?php $this->render_category_select( 'strip_cats[0]', isset( $strip_cats[0] ) ? intval( $strip_cats[0] ) : 0, $all_cats ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Şerit 2</th>
                            <td><?php $this->render_category_select( 'strip_cats[1]', isset( $strip_cats[1] ) ? intval( $strip_cats[1] ) : 0, $all_cats ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Şerit 3</th>
                            <td><?php $this->render_category_select( 'strip_cats[2]', isset( $strip_cats[2] ) ? intval( $strip_cats[2] ) : 0, $all_cats ); ?></td>
                        </tr>
                    </table>
                </div>

                <div class="ies-card">
                    <h2 style="margin-top:0;">3. League of Legends Alanı</h2>
                    <p class="description">
                        LoL özel alanında sekmeli kartlar, farklı LoL ürünleri gösterilebilir. Burada LoL kategorisini seçin.
                    </p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">LoL Kategorisi</th>
                            <td><?php $this->render_category_select( 'lol_cat', $lol_cat, $all_cats ); ?></td>
                        </tr>
                    </table>
                </div>

                <div class="ies-card">
                    <h2 style="margin-top:0;">4. Steam Oyun Alanı</h2>
                    <p class="description">
                        Steam cüzdan kodları veya oyunlarını bir blok halinde göstermek için kullanılacak kategori.
                    </p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Steam Kategorisi</th>
                            <td><?php $this->render_category_select( 'steam_cat', $steam_cat, $all_cats ); ?></td>
                        </tr>
                    </table>
                </div>

                <p>
                    <button type="submit" class="button button-primary">Ayarları Kaydet</button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Kategori seçimi için helper.
     */
    protected function render_category_select( $name, $selected_id, $all_cats ) {
        echo '<select name="' . esc_attr( $name ) . '">';
        echo '<option value="0">— Seçili değil —</option>';

        if ( ! empty( $all_cats ) && ! is_wp_error( $all_cats ) ) {
            foreach ( $all_cats as $cat ) {
                printf(
                    '<option value="%d"%s>%s</option>',
                    intval( $cat->term_id ),
                    selected( $selected_id, $cat->term_id, false ),
                    esc_html( $cat->name )
                );
            }
        }

        echo '</select>';
    }

    /**
     * Ayar formu kaydetme
     */
    public function handle_admin_save() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ies_home_layout_save' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $highlight_raw = isset( $_POST['highlight_cats'] ) ? (array) $_POST['highlight_cats'] : array();
        $strip_raw     = isset( $_POST['strip_cats'] ) ? (array) $_POST['strip_cats'] : array();
        $lol_cat       = isset( $_POST['lol_cat'] ) ? intval( $_POST['lol_cat'] ) : 0;
        $steam_cat     = isset( $_POST['steam_cat'] ) ? intval( $_POST['steam_cat'] ) : 0;

        $highlight_cats = array();
        foreach ( $highlight_raw as $k => $v ) {
            $highlight_cats[ $k ] = intval( $v );
        }

        $strip_cats = array();
        foreach ( $strip_raw as $k => $v ) {
            $strip_cats[ $k ] = intval( $v );
        }

        $options = array(
            'highlight_cats' => $highlight_cats,
            'strip_cats'     => $strip_cats,
            'lol_cat'        => $lol_cat,
            'steam_cat'      => $steam_cat,
        );

        update_option( $this->option_key, $options );
    }

    /* ============================================================
     *  SHORTCODE – [ies_home_layout]
     * ============================================================ */

    public function render_shortcode( $atts ) {
        ob_start();

        $options = get_option( $this->option_key, array() );
        if ( ! is_array( $options ) ) {
            $options = array();
        }

        $highlight_cats = isset( $options['highlight_cats'] ) ? (array) $options['highlight_cats'] : array();
        $strip_cats     = isset( $options['strip_cats'] ) ? (array) $options['strip_cats'] : array();
        $lol_cat        = isset( $options['lol_cat'] ) ? intval( $options['lol_cat'] ) : 0;
        $steam_cat      = isset( $options['steam_cat'] ) ? intval( $options['steam_cat'] ) : 0;

        ?>
        <div class="ies-home-layout">

            <?php // ÜST ÖNE ÇIKAN KARTLAR ?>
            <section class="ies-home-hero-row">
                <div class="ies-home-hero-grid">
                    <?php
                    foreach ( $highlight_cats as $cat_id ) {
                        $cat_id = intval( $cat_id );
                        if ( ! $cat_id ) continue;

                        $term = get_term( $cat_id, 'product_cat' );
                        if ( ! $term || is_wp_error( $term ) ) continue;

                        $link = get_term_link( $term );
                        $thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
                        $img = $thumb_id ? wp_get_attachment_image( $thumb_id, 'woocommerce_thumbnail' ) : '';

                        ?>
                        <a href="<?php echo esc_url( $link ); ?>" class="ies-hero-card ies-neon-hover">
                            <div class="ies-hero-card-bg"></div>
                            <div class="ies-hero-card-inner">
                                <div class="ies-hero-card-thumb">
                                    <?php echo $img; ?>
                                </div>
                                <div class="ies-hero-card-text">
                                    <h2><?php echo esc_html( $term->name ); ?></h2>
                                    <?php if ( $term->description ) : ?>
                                        <p><?php echo esc_html( wp_trim_words( strip_tags( $term->description ), 12 ) ); ?></p>
                                    <?php endif; ?>
                                    <span class="ies-hero-card-cta">Tümünü Gör &raquo;</span>
                                </div>
                            </div>
                        </a>
                        <?php
                    }
                    ?>
                </div>
            </section>

            <?php // YATAY ŞERİTLER (kategori bazlı ürün şeritleri) ?>
            <?php if ( ! empty( $strip_cats ) ) : ?>
                <?php foreach ( $strip_cats as $cat_id ) :
                    $cat_id = intval( $cat_id );
                    if ( ! $cat_id ) continue;

                    $term = get_term( $cat_id, 'product_cat' );
                    if ( ! $term || is_wp_error( $term ) ) continue;

                    $link = get_term_link( $term );

                    $products = wc_get_products( array(
                        'status' => 'publish',
                        'limit'  => 12,
                        'orderby'=> 'date',
                        'order'  => 'DESC',
                        'category' => array( $term->slug ),
                    ) );
                ?>
                    <section class="ies-game-strip">
                        <div class="ies-strip-header">
                            <div>
                                <h2><?php echo esc_html( $term->name ); ?></h2>
                                <?php if ( $term->description ) : ?>
                                    <p><?php echo esc_html( wp_trim_words( strip_tags( $term->description ), 18 ) ); ?></p>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo esc_url( $link ); ?>" class="ies-btn ies-btn-ghost ies-neon-hover">
                                Tümü
                            </a>
                        </div>

                        <?php if ( ! empty( $products ) ) : ?>
                            <div class="ies-game-strip-products">
                                <?php foreach ( $products as $product ) :
                                    $p_id   = $product->get_id();
                                    $plink  = get_permalink( $p_id );
                                    $pimg   = $product->get_image( 'woocommerce_thumbnail' );
                                    $pname  = $product->get_name();
                                    $pprice = $product->get_price_html();
                                ?>
                                    <a href="<?php echo esc_url( $plink ); ?>" class="ies-game-card ies-neon-hover">
                                        <div class="ies-game-card-thumb">
                                            <?php echo $pimg; ?>
                                        </div>
                                        <div class="ies-game-card-body">
                                            <div class="ies-game-card-title"><?php echo esc_html( $pname ); ?></div>
                                            <?php if ( $pprice ) : ?>
                                                <div class="ies-game-card-price"><?php echo wp_kses_post( $pprice ); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p>Bu kategori için ürün bulunamadı.</p>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php // LoL özel alanı (varsayılan basit blok) ?>
            <?php if ( $lol_cat ) :
                $term = get_term( $lol_cat, 'product_cat' );
                if ( $term && ! is_wp_error( $term ) ) :
                    $products = wc_get_products( array(
                        'status' => 'publish',
                        'limit'  => 8,
                        'category' => array( $term->slug ),
                    ) );
                ?>
                <section class="ies-lol-section">
                    <div class="ies-lol-header">
                        <h2><?php echo esc_html( $term->name ); ?> Özel Alanı</h2>
                        <a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="ies-btn ies-btn-ghost ies-neon-hover">
                            Tümü
                        </a>
                    </div>
                    <?php if ( ! empty( $products ) ) : ?>
                        <div class="ies-lol-products">
                            <?php foreach ( $products as $product ) :
                                $p_id   = $product->get_id();
                                $plink  = get_permalink( $p_id );
                                $pimg   = $product->get_image( 'woocommerce_thumbnail' );
                                $pname  = $product->get_name();
                                $pprice = $product->get_price_html();
                            ?>
                                <a href="<?php echo esc_url( $plink ); ?>" class="ies-lol-card ies-neon-hover">
                                    <div class="ies-lol-thumb"><?php echo $pimg; ?></div>
                                    <div class="ies-lol-body">
                                        <div class="ies-lol-title"><?php echo esc_html( $pname ); ?></div>
                                        <?php if ( $pprice ) : ?>
                                            <div class="ies-lol-price"><?php echo wp_kses_post( $pprice ); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p>Bu LoL kategorisinde gösterilecek ürün bulunamadı.</p>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
            <?php endif; ?>

            <?php // Steam alanı ?>
            <?php if ( $steam_cat ) :
                $term = get_term( $steam_cat, 'product_cat' );
                if ( $term && ! is_wp_error( $term ) ) :
                    $products = wc_get_products( array(
                        'status' => 'publish',
                        'limit'  => 6,
                        'category' => array( $term->slug ),
                    ) );
                ?>
                <section class="ies-steam-section">
                    <div class="ies-steam-header">
                        <h2><?php echo esc_html( $term->name ); ?></h2>
                        <a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="ies-btn ies-btn-ghost ies-neon-hover">
                            Tümü
                        </a>
                    </div>
                    <?php if ( ! empty( $products ) ) : ?>
                        <div class="ies-steam-grid">
                            <?php foreach ( $products as $product ) :
                                $p_id   = $product->get_id();
                                $plink  = get_permalink( $p_id );
                                $pimg   = $product->get_image( 'woocommerce_thumbnail' );
                                $pname  = $product->get_name();
                                $pprice = $product->get_price_html();
                            ?>
                                <a href="<?php echo esc_url( $plink ); ?>" class="ies-steam-card ies-neon-hover">
                                    <div class="ies-steam-thumb"><?php echo $pimg; ?></div>
                                    <div class="ies-steam-body">
                                        <div class="ies-steam-title"><?php echo esc_html( $pname ); ?></div>
                                        <?php if ( $pprice ) : ?>
                                            <div class="ies-steam-price"><?php echo wp_kses_post( $pprice ); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p>Bu Steam kategorisinde gösterilecek ürün bulunamadı.</p>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
            <?php endif; ?>

        </div><!-- .ies-home-layout -->
        <?php

        return ob_get_clean();
    }
}
