<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Inovatix Epin Suite - Anasayfa Bölümleri
 * Shortcode: [ies_home_sections]
 */
class IES_Home_Sections {

    /**
     * Bölüm konfigürasyonları
     *
     * Şimdilik buradan yönetiyoruz. Sonra istersen admin paneline taşıyabiliriz.
     *
     * 1–5: Ürün bölümleri
     * 6: Blog
     * 7: Instagram
     * 8: Facebook
     * 9: TikTok
     */
    protected $sections = array();

    public function __construct() {
        // BURAYI KENDİNE GÖRE DOLDUR
        $this->sections = array(
            // 1. BÖLÜM
            1 => array(
                'type'          => 'products_showroom',   // ürün listesi
                'title'         => '1. Bölüm Başlık',     // ANASAYFADA GÖRÜNECEK
                'banner_gif'    => 'https://.../banner1.gif', // SAĞDAKİ GIF
                'all_url'       => home_url( '/urun-kategori-1/' ), // TÜMÜ LİNKİ
                'category_slug' => 'pubg-epin',           // WooCommerce kategori slug
                'limit'         => 8,                     // Gösterilecek ürün sayısı
                'style'         => 'tabs',                // "tabs" veya "slider" (şimdilik görsel amaçlı)
            ),

            // 2. BÖLÜM
            2 => array(
                'type'          => 'products_showroom',
                'title'         => '2. Bölüm Başlık',
                'banner_gif'    => 'https://.../banner2.gif',
                'all_url'       => home_url( '/urun-kategori-2/' ),
                'category_slug' => 'pubg-tr',
                'limit'         => 8,
                'style'         => 'tabs',
            ),

            // 3. BÖLÜM
            3 => array(
                'type'          => 'products_showroom',
                'title'         => '3. Bölüm Başlık',
                'banner_gif'    => 'https://.../banner3.gif',
                'all_url'       => home_url( '/urun-kategori-3/' ),
                'category_slug' => 'pubg-global',
                'limit'         => 8,
                'style'         => 'slider',
            ),

            // 4. BÖLÜM
            4 => array(
                'type'          => 'products_showroom',
                'title'         => '4. Bölüm Başlık',
                'banner_gif'    => 'https://.../banner4.gif',
                'all_url'       => home_url( '/urun-kategori-4/' ),
                'category_slug' => 'mobile-legends',
                'limit'         => 8,
                'style'         => 'slider',
            ),

            // 5. BÖLÜM
            5 => array(
                'type'          => 'products_showroom',
                'title'         => '5. Bölüm Başlık',
                'banner_gif'    => 'https://.../banner5.gif',
                'all_url'       => home_url( '/urun-kategori-5/' ),
                'category_slug' => 'valorant',
                'limit'         => 8,
                'style'         => 'slider',
            ),

            // 6. BÖLÜM: BLOG
            6 => array(
                'type'        => 'blog',
                'title'       => 'Blog Yazıları',
                'all_url'     => get_permalink( get_option( 'page_for_posts' ) ),
                'posts_per_page' => 3,
            ),

            // 7. BÖLÜM: INSTAGRAM
            7 => array(
                'type'          => 'social',
                'title'         => "Instagram'da Bizi Takip Edin!",
                'subtitle'      => 'Kampanyalar, çekilişler ve duyurular için bizi takip et.',
                'button_label'  => 'Takip Et',
                'url'           => 'https://instagram.com/seninhesabin', // BURAYA GERÇEK INSTAGRAM LİNKİ
                'icon_class'    => 'fab fa-instagram',
            ),

            // 8. BÖLÜM: FACEBOOK
            8 => array(
                'type'          => 'social',
                'title'         => "Facebook'ta Bizi Takip Edin!",
                'subtitle'      => 'Son duyurular ve kampanyalar için Facebook sayfamıza katıl.',
                'button_label'  => 'Takip Et',
                'url'           => 'https://facebook.com/seninhesabin',
                'icon_class'    => 'fab fa-facebook',
            ),

            // 9. BÖLÜM: TIKTOK
            9 => array(
                'type'          => 'social',
                'title'         => "TikTok'ta Bizi Takip Edin!",
                'subtitle'      => 'Eğlenceli içerikler ve klipler için TikTok kanalımızı takip et.',
                'button_label'  => 'Takip Et',
                'url'           => 'https://www.tiktok.com/@seninhesabin',
                'icon_class'    => 'fab fa-tiktok',
            ),
        );
    }

    /**
     * Init
     */
    public function init() {
        add_shortcode( 'ies_home_sections', array( $this, 'render_home_sections' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * CSS / JS
     */
    public function enqueue_assets() {
        // Basit CSS – istersen theme içine taşıyabilirsin
        wp_add_inline_style(
            'woocommerce-general',
            $this->get_inline_css()
        );
    }

    protected function get_inline_css() {
        return "
        .ies-home-section {
            margin-bottom: 30px;
        }
        .ies-home-section .home-section-indicator,
        .ies-home-section .home-section-tabs {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #111827;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 15px;
        }
        .ies-home-section h2 {
            font-size: 20px;
            margin: 0;
            color: #ffffff;
        }
        .ies-home-section .showroom-banner {
            max-height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }
        .ies-home-section .show-all-btn {
            background: #6347d6;
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 14px;
            text-decoration: none;
            transition: all .2s ease;
            border: 1px solid #6347d6;
        }
        .ies-home-section .show-all-btn:hover {
            background: transparent;
            color: #6347d6;
        }
        .ies-home-section .product-top {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .ies-home-section .product-base {
            flex: 1 1 calc(25% - 10px);
            min-width: 200px;
        }
        .ies-home-section .product-item {
            background: #1f2933;
            border-radius: 10px;
            padding: 10px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .ies-home-section .pimg-base img {
            width: 100%;
            border-radius: 8px;
            object-fit: cover;
        }
        .ies-home-section .product-detail {
            margin-top: 8px;
            color: #ffffff;
        }
        .ies-home-section .product-name {
            font-size: 14px;
            font-weight: 600;
        }
        .ies-home-section .product-price .sales-price {
            color: #6347d6;
        }
        .ies-home-section-social {
            background: #111827;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .ies-home-section-social .social-title {
            font-size: 20px;
            margin-bottom: 5px;
            color: #ffffff;
        }
        .ies-home-section-social .social-subtitle {
            color: #9ca3af;
            font-size: 14px;
        }
        .ies-home-section-social .social-btn {
            background: #6347d6;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 999px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        .ies-home-section-social .social-btn:hover {
            opacity: 0.9;
        }
        .ies-home-section-blog .ies-blog-item {
            background: #111827;
            border-radius: 10px;
            padding: 16px;
            height: 100%;
        }
        .ies-home-section-blog .ies-blog-title {
            font-size: 16px;
            margin-bottom: 8px;
        }
        .ies-home-section-blog .ies-blog-meta {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 8px;
        }
        .ies-home-section-blog .ies-blog-excerpt {
            font-size: 14px;
            color: #e5e7eb;
        }
        @media (max-width: 992px) {
            .ies-home-section .product-base {
                flex: 1 1 calc(50% - 10px);
            }
        }
        @media (max-width: 576px) {
            .ies-home-section .product-base {
                flex: 1 1 100%;
            }
            .ies-home-section .home-section-indicator,
            .ies-home-section .home-section-tabs {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
        ";
    }

    /**
     * Shortcode render
     */
    public function render_home_sections( $atts = array(), $content = null ) {
        ob_start();

        echo '<div class="ies-home-sections-wrapper container-fluid">';

        foreach ( $this->sections as $index => $section ) {

            echo '<section class="ies-home-section ies-home-section-' . esc_attr( $index ) . '">';

            switch ( $section['type'] ) {
                case 'products_showroom':
                    $this->render_products_section( $section, $index );
                    break;

                case 'blog':
                    $this->render_blog_section( $section, $index );
                    break;

                case 'social':
                    $this->render_social_section( $section, $index );
                    break;
            }

            echo '</section>';
        }

        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Ürün bölümü (1–5)
     */
    protected function render_products_section( $section, $index ) {
        $title         = isset( $section['title'] ) ? $section['title'] : '';
        $banner_gif    = isset( $section['banner_gif'] ) ? $section['banner_gif'] : '';
        $all_url       = isset( $section['all_url'] ) ? $section['all_url'] : '#';
        $category_slug = isset( $section['category_slug'] ) ? $section['category_slug'] : '';
        $limit         = ! empty( $section['limit'] ) ? intval( $section['limit'] ) : 8;

        // WooCommerce ürün çekme
        if ( ! function_exists( 'wc_get_products' ) ) {
            echo '<p>WooCommerce aktif değil.</p>';
            return;
        }

        $args = array(
            'status'  => 'publish',
            'limit'   => $limit,
        );

        if ( $category_slug ) {
            $args['category'] = array( $category_slug );
        }

        $products = wc_get_products( $args );

        $section_id = 'showroom-selection-' . $index;
        ?>
        <div class="top-product <?php echo esc_attr( $section_id ); ?>" data-is-mobile="">
            <div class="home-section-indicator">
                <div class="indicator-section-main">
                    <h2><?php echo esc_html( $title ); ?></h2>
                </div>

                <?php if ( $banner_gif ) : ?>
                    <img class="showroom-banner" src="<?php echo esc_url( $banner_gif ); ?>" alt="<?php echo esc_attr( $title ); ?>">
                <?php endif; ?>

                <a href="<?php echo esc_url( $all_url ); ?>" class="show-all-btn <?php echo esc_attr( $section_id ); ?>-show-all-url">
                    Tümü
                </a>
            </div>

            <div class="row product-top <?php echo esc_attr( $section_id ); ?>-container shroom-style-0">
                <?php
                if ( ! empty( $products ) ) :
                    foreach ( $products as $product ) :
                        $product_id   = $product->get_id();
                        $product_link = get_permalink( $product_id );
                        $product_name = $product->get_name();
                        $image        = $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'product-image' ) );
                        $price_html   = $product->get_price_html();
                        ?>
                        <div class="col-lg-3 col-md-4 col-xs-12 col-8 product-base px-1">
                            <div class="product-item">
                                <div class="pimg-base">
                                    <a href="<?php echo esc_url( $product_link ); ?>" aria-label="<?php echo esc_attr( $product_name ); ?>">
                                        <?php echo $image; ?>
                                    </a>
                                </div>
                                <div class="product-detail">
                                    <a href="<?php echo esc_url( $product_link ); ?>">
                                        <div class="brand-product-name-container">
                                            <span class="product-name threedots"><?php echo esc_html( $product_name ); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-space-between">
                                            <div class="product-price">
                                                <div class="sales-price fw-600 fs-18">
                                                    <?php echo wp_kses_post( $price_html ); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php
                    endforeach;
                else :
                    ?>
                    <p style="color:#fff; padding:10px 0;">Bu bölüm için ürün bulunamadı.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Blog bölümü (6. bölüm)
     */
    protected function render_blog_section( $section, $index ) {
        $title          = isset( $section['title'] ) ? $section['title'] : 'Blog';
        $all_url        = isset( $section['all_url'] ) ? $section['all_url'] : '#';
        $posts_per_page = ! empty( $section['posts_per_page'] ) ? intval( $section['posts_per_page'] ) : 3;

        $args = array(
            'post_type'           => 'post',
            'posts_per_page'      => $posts_per_page,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
        );

        $q = new WP_Query( $args );
        ?>
        <div class="ies-home-section ies-home-section-blog">
            <div class="home-section-indicator">
                <div class="indicator-section-main">
                    <h2><?php echo esc_html( $title ); ?></h2>
                </div>

                <a href="<?php echo esc_url( $all_url ); ?>" class="show-all-btn">
                    Tümü
                </a>
            </div>

            <div class="row">
                <?php if ( $q->have_posts() ) : ?>
                    <?php while ( $q->have_posts() ) : $q->the_post(); ?>
                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="ies-blog-item">
                                <h3 class="ies-blog-title">
                                    <a href="<?php the_permalink(); ?>" style="color:#fff; text-decoration:none;">
                                        <?php the_title(); ?>
                                    </a>
                                </h3>
                                <div class="ies-blog-meta">
                                    <?php echo get_the_date(); ?> • <?php echo get_the_author(); ?>
                                </div>
                                <div class="ies-blog-excerpt">
                                    <?php echo wp_trim_words( get_the_excerpt(), 18, '...' ); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else : ?>
                    <p style="color:#fff; padding:10px 0;">Henüz blog yazısı eklenmemiş.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Sosyal medya bölümleri (7, 8, 9)
     */
    protected function render_social_section( $section, $index ) {
        $title        = isset( $section['title'] ) ? $section['title'] : '';
        $subtitle     = isset( $section['subtitle'] ) ? $section['subtitle'] : '';
        $button_label = isset( $section['button_label'] ) ? $section['button_label'] : 'Takip Et';
        $url          = isset( $section['url'] ) ? $section['url'] : '#';
        $icon_class   = isset( $section['icon_class'] ) ? $section['icon_class'] : 'fas fa-share-alt';
        ?>
        <div class="ies-home-section-social">
            <div class="social-text">
                <div class="social-title"><?php echo esc_html( $title ); ?></div>
                <?php if ( $subtitle ) : ?>
                    <div class="social-subtitle"><?php echo esc_html( $subtitle ); ?></div>
                <?php endif; ?>
            </div>
            <div class="social-action">
                <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" class="social-btn">
                    <i class="<?php echo esc_attr( $icon_class ); ?>"></i>
                    <span><?php echo esc_html( $button_label ); ?></span>
                </a>
            </div>
        </div>
        <?php
    }
}
