<?php
/**
 * EPIN Kategori Şablonu
 *
 * Bu dosya, WooCommerce ürün kategorilerini özel EPIN tasarımıyla gösterir.
 * WoodMart / tema uyumlu kalması için WooCommerce hook yapısı mümkün olduğunca korunmuştur.
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$term = get_queried_object();
?>

<?php
    /**
     * Hook: woocommerce_before_main_content.
     *
     * @hooked woocommerce_output_content_wrapper - 10 (tema wrapper'ını getirir)
     */
    do_action( 'woocommerce_before_main_content' );
?>

<div class="ies-category-page-wrapper">

    <?php
    // Üst başlık + breadcrumb
    ?>
    <header class="ies-category-header">
        <h1 class="ies-category-title">
            <?php woocommerce_page_title(); ?>
        </h1>

        <?php if ( function_exists( 'woocommerce_breadcrumb' ) ) : ?>
            <div class="ies-breadcrumb-wrapper">
                <?php woocommerce_breadcrumb(); ?>
            </div>
        <?php endif; ?>
    </header>

    <?php
    // ALT KATEGORİ ŞERİDİ
    // Mobilde de kaydırmalı buton şeridi şeklinde duracak.
    ?>
    <section class="ies-subcategory-strip">
        <?php
        if ( $term && ! is_wp_error( $term ) && isset( $term->term_id ) ) {

            $current_term_id = $term->term_id;
            $parent_id       = $term->parent;

            // Eğer bu kategori bir alt kategori ise, kardeşleri (aynı parent) listeleriz.
            // Eğer bu kategori üst kategori ise, kendi alt kategorileri listeleriz.
            if ( $parent_id ) {
                $base_parent = $parent_id;
            } else {
                $base_parent = $current_term_id;
            }

            $child_terms = get_terms( array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'parent'     => $base_parent,
            ) );

            if ( ! empty( $child_terms ) && ! is_wp_error( $child_terms ) ) :
        ?>
            <div class="ies-subcategory-strip-inner">
                <?php
                // "Tümü" butonu (üst kategori linki)
                $all_link = get_term_link( $base_parent, 'product_cat' );
                ?>
                <a href="<?php echo esc_url( $all_link ); ?>"
                   class="ies-subcat-pill <?php echo $current_term_id === $base_parent ? 'is-active' : ''; ?>">
                    Tümü
                </a>

                <?php foreach ( $child_terms as $child ) :
                    $link   = get_term_link( $child, 'product_cat' );
                    $active = $current_term_id === $child->term_id ? 'is-active' : '';
                ?>
                    <a href="<?php echo esc_url( $link ); ?>"
                       class="ies-subcat-pill <?php echo esc_attr( $active ); ?>">
                        <?php echo esc_html( $child->name ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php
            endif;
        }
        ?>
    </section>

    <?php
    // ÜRÜN LİSTESİ TABLOSU
    ?>
    <section class="ies-product-table-section">
        <?php if ( woocommerce_product_loop() ) : ?>

            <?php
            /**
             * Hook: woocommerce_before_shop_loop.
             *
             * @hooked woocommerce_output_all_notices - 10
             * @hooked woocommerce_result_count - 20
             * @hooked woocommerce_catalog_ordering - 30
             */
            do_action( 'woocommerce_before_shop_loop' );
            ?>

            <div class="ies-product-table">
                <div class="ies-product-table-header">
                    <div class="ies-pt-col ies-pt-col-title">Ürün</div>
                    <div class="ies-pt-col ies-pt-col-price">Fiyat</div>
                    <div class="ies-pt-col ies-pt-col-actions">İşlem</div>
                </div>

                <div class="ies-product-table-body">
                    <?php
                    while ( have_posts() ) :
                        the_post();
                        global $product;

                        if ( ! $product ) {
                            continue;
                        }

                        $product_id = $product->get_id();
                        $permalink  = get_permalink( $product_id );
                        $title      = $product->get_name();
                        $price_html = $product->get_price_html();
                        $image_html = $product->get_image( 'woocommerce_thumbnail' );
                    ?>
                        <div class="ies-product-table-row">
                            <div class="ies-pt-col ies-pt-col-title">
                                <a href="<?php echo esc_url( $permalink ); ?>" class="ies-product-link">
                                    <span class="ies-pt-thumb"><?php echo $image_html; ?></span>
                                    <span class="ies-pt-title-text"><?php echo esc_html( $title ); ?></span>
                                </a>
                            </div>

                            <div class="ies-pt-col ies-pt-col-price">
                                <?php if ( $price_html ) : ?>
                                    <span class="ies-pt-price"><?php echo wp_kses_post( $price_html ); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="ies-pt-col ies-pt-col-actions">
                                <?php
                                // Standart WooCommerce "add to cart" butonunu kullanalım (popup JS tarafından kesilecek)
                                woocommerce_template_loop_add_to_cart();
                                ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <?php
            /**
             * Hook: woocommerce_after_shop_loop.
             *
             * @hooked woocommerce_pagination - 10
             */
            do_action( 'woocommerce_after_shop_loop' );
            ?>

        <?php else : ?>

            <?php
            /**
             * Hook: woocommerce_no_products_found.
             *
             * @hooked wc_no_products_found - 10
             */
            do_action( 'woocommerce_no_products_found' );
            ?>

        <?php endif; ?>
    </section>

    <?php
    // ALT SEO / TAB BÖLÜMÜ
    // - Kategori Hakkında: Term description
    // - Nasıl Kullanılır: İleride term meta veya sabit metinle doldurulabilir
    // - S.S.S. / Ek Bilgi: İleride özelleştirilebilir
    ?>

    <section class="ies-category-seo-tabs">
        <div class="ies-seo-tabs-nav">
            <button type="button" class="ies-seo-tab-btn is-active" data-target="ies-tab-about">Kategori Hakkında</button>
            <button type="button" class="ies-seo-tab-btn" data-target="ies-tab-howto">Nasıl Kullanılır?</button>
            <button type="button" class="ies-seo-tab-btn" data-target="ies-tab-faq">S.S.S / Ek Bilgi</button>
        </div>

        <div class="ies-seo-tabs-content">
            <div id="ies-tab-about" class="ies-seo-tab-pane is-active">
                <?php
                if ( $term && ! is_wp_error( $term ) ) {
                    $desc = term_description( $term, 'product_cat' );
                    if ( $desc ) {
                        echo wp_kses_post( wpautop( $desc ) );
                    } else {
                        echo '<p>Bu kategori için açıklama henüz eklenmemiş.</p>';
                    }
                }
                ?>
            </div>

            <div id="ies-tab-howto" class="ies-seo-tab-pane">
                <p>
                    Bu kategoride satın aldığınız EPIN / kodlar, siparişiniz onaylandıktan sonra <strong>hesabım &gt; siparişlerim</strong>
                    sayfanızdan ve e-posta yoluyla sizlere iletilir. Oyun veya platform içindeki "Kod Kullan / Redeem" alanına girerek
                    aktif edebilirsiniz.
                </p>
                <p>
                    Her oyun için özel kullanım talimatlarını ürün açıklamalarında ayrıca görebilirsiniz.
                </p>
            </div>

            <div id="ies-tab-faq" class="ies-seo-tab-pane">
                <p><strong>Soru:</strong> Kod ne kadar sürede teslim edilir?</p>
                <p><strong>Cevap:</strong> Ödemeden sonra genellikle birkaç saniye ile birkaç dakika içinde otomatik olarak teslim edilir.
                    Gecikme yaşanırsa canlı destek veya destek talebi üzerinden bize ulaşabilirsiniz.</p>

                <p><strong>Soru:</strong> Yanlış ID / Zone girişi yaparsam ne olur?</p>
                <p><strong>Cevap:</strong> Yanlış girilen bilgilerden kaynaklı sorunlarda, sağlayıcı iade kabul etmiyorsa sorumluluk müşteriye aittir.
                    Lütfen satın almadan önce hesap bilgilerinizi mutlaka kontrol edin.</p>
            </div>
        </div>
    </section>

</div><!-- .ies-category-page-wrapper -->

<?php
    /**
     * Hook: woocommerce_after_main_content.
     *
     * @hooked woocommerce_output_content_wrapper_end - 10
     */
    do_action( 'woocommerce_after_main_content' );
?>

<?php
    /**
     * Hook: woocommerce_sidebar.
     *
     * @hooked woocommerce_get_sidebar - 10
     */
    do_action( 'woocommerce_sidebar' );
?>

<?php
get_footer( 'shop' );
