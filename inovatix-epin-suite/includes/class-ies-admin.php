<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Genel admin paneli / dashboard
 * - Sol menüde "Inovatix EPIN Suite" ana menüsü
 * - Altında: Cüzdan, Anasayfa, Destek, vb. zaten diğer modüllerin submenu'leri var
 */
class IES_Admin {

    public function __construct() {
        // Sadece hook’a asılıyoruz. Burada current_user_can, wp_get_current_user vb. KULLANMIYORUZ.
        add_action( 'admin_menu', array( $this, 'register_menu' ), 40 );
    }

    /**
     * Admin menü kaydı
     */
    public function register_menu() {
        // Bu fonksiyon bu noktada güvenli: WordPress admin tam yüklenmiş oluyor.
        add_menu_page(
            'Inovatix EPIN Suite',
            'Inovatix EPIN',
            'manage_woocommerce',
            'ies-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-shield-alt',
            55
        );
    }

    /**
     * Dashboard sayfası (basit özet)
     */
    public function render_dashboard() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Bu sayfayı görüntüleme yetkiniz yok.' );
        }

        ?>
        <div class="wrap ies-admin-page">
            <h1>Inovatix EPIN Suite</h1>

            <div class="ies-card">
                <h2>Sistem Özeti</h2>
                <p>
                    Bu panelden EPIN sisteminin tüm modüllerine ulaşabilirsiniz:
                </p>
                <ul>
                    <li><strong>Cüzdan &amp; Bakiye:</strong> WooCommerce &gt; EPIN Cüzdan</li>
                    <li><strong>Anasayfa Tasarımı:</strong> WooCommerce &gt; EPIN Anasayfa</li>
                    <li><strong>Destek Talepleri:</strong> WooCommerce &gt; EPIN Destek Talepleri</li>
                    <li><strong>Otomatik Yorumlar:</strong> Sipariş tamamlandığında arka planda çalışır</li>
                    <li><strong>EPIN Kod Yönetimi:</strong> Ürün düzenleme sayfasında "EPIN Kodları" sekmesi</li>
                </ul>
                <p>
                    Tema: <code>#333</code> koyu arka plan, vurgu rengi: <code>#6347d6</code> (neon hover).
                </p>
            </div>
        </div>
        <?php
    }

}
