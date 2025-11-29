<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class IES_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
    }

    /**
     * WooCommerce menüsü altına EPIN Suite panelini ekle
     */
    public function register_menu() {
        add_submenu_page(
            'woocommerce',
            'Inovatix EPIN Suite',
            'Inovatix EPIN Suite',
            'manage_woocommerce',
            'ies-dashboard',
            array( $this, 'render_dashboard' )
        );
    }

    /**
     * Genel EPIN kontrol paneli
     */
    public function render_dashboard() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Bu sayfaya erişim yetkiniz yok.' );
        }

        ?>
        <div class="wrap ies-admin-page">
            <h1>Inovatix EPIN Suite</h1>
            <p class="description">
                EPIN, bakiye, kategori tasarımı, anasayfa, destek sistemi, otomatik yorum ve kod teslim modüllerinin
                tamamı bu paketle yönetilir. Aşağıdan ilgili modüllerin ayar sayfalarına hızlıca geçebilirsiniz.
            </p>

            <div class="ies-card">
                <h2>Genel Durum</h2>
                <p>
                    Bu panel sadece bir özet ekranıdır. Detaylı ayarlar ilgili modüllerin kendi sayfalarından yapılır.
                </p>
                <ul style="list-style: disc; margin-left: 20px; font-size: 13px;">
                    <li><strong>Bakiye / Cüzdan:</strong> Müşteri bakiyeleri, hareket kayıtları.</li>
                    <li><strong>Bakiye Yükleme:</strong> Havale/EFT + ileride sanal POS entegrasyonu.</li>
                    <li><strong>Anasayfa Tasarımı:</strong> BursaGB benzeri dinamik EPIN anasayfası.</li>
                    <li><strong>Kategori Tasarımı:</strong> EPIN ürün listeleri için özel tablo ve popup alanları.</li>
                    <li><strong>Destek Sistemi:</strong> Müşteri panelinde ticket açma ve admin tarafından yanıtlama.</li>
                    <li><strong>Yorum Otomasyonu:</strong> Teslim sonrası popup + 24 saat içinde otomatik 5★ yorum.</li>
                    <li><strong>EPIN Kod Teslimi:</strong> Ürün bazlı key havuzu ve sipariş tamamlanınca otomatik teslim.</li>
                </ul>
            </div>

            <div class="ies-admin-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-top:20px;">

                <div class="ies-card">
                    <h2 style="margin-top:0;font-size:16px;">Bakiye / Cüzdan</h2>
                    <p style="font-size:13px;">
                        Müşteri bakiyeleri, referans numaraları ve bakiye hareketleri burada yönetilir.
                    </p>
                    <p>
                        <span class="ies-badge ies-badge-primary">Modül 1–2</span>
                    </p>
                    <p>
                        <em>Not:</em> Ayrı bir menü olarak WooCommerce altında veya kullanıcı menüsünde
                        gösteriliyor, bu panel sadece özet.
                    </p>
                </div>

                <div class="ies-card">
                    <h2 style="margin-top:0;font-size:16px;">Anasayfa Tasarımı</h2>
                    <p style="font-size:13px;">
                        Oyun şeritleri, büyük kartlar, LoL sekmeli blok, Steam oyunları, blog ve Instagram barını buradan
                        yönetebilirsiniz.
                    </p>
                    <p><span class="ies-badge ies-badge-primary">Modül 6</span></p>
                    <p>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ies-home-layout' ) ); ?>" class="button button-primary">
                            EPIN Anasayfa Ayarları
                        </a>
                    </p>
                </div>

                <div class="ies-card">
                    <h2 style="margin-top:0;font-size:16px;">Destek (Ticket) Sistemi</h2>
                    <p style="font-size:13px;">
                        Müşterilerinizin açtığı destek taleplerini görüntüleyebilir, yanıtlayabilir ve durumlarını
                        yönetebilirsiniz.
                    </p>
                    <p><span class="ies-badge ies-badge-primary">Modül 7</span></p>
                    <p>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ies-tickets' ) ); ?>" class="button">
                            Destek Taleplerini Aç
                        </a>
                    </p>
                </div>

                <div class="ies-card">
                    <h2 style="margin-top:0;font-size:16px;">EPIN Kod Havuzu</h2>
                    <p style="font-size:13px;">
                        Her EPIN ürününe özel kod havuzunu ürün düzenleme ekranından yönetebilirsiniz. Burada global
                        bir liste yerine ürün bazlı çalışma tercih edilmiştir.
                    </p>
                    <p><span class="ies-badge ies-badge-primary">Modül 9</span></p>
                    <p style="font-size:12px;color:#888;">
                        Ürün &gt; Ürün Verisi &gt; EPIN / Key sekmesinden kod ekleme / görüntüleme yapılır.
                    </p>
                </div>

                <div class="ies-card">
                    <h2 style="margin-top:0;font-size:16px;">Yorum Otomasyonu</h2>
                    <p style="font-size:13px;">
                        Sipariş tamamlandığında müşteriye popup ile yorum sorulur, 24 saat içinde yorum yapılmazsa
                        otomatik 5 yıldızlı yorum eklenir.
                    </p>
                    <p><span class="ies-badge ies-badge-primary">Modül 8</span></p>
                    <p style="font-size:12px;color:#888;">
                        Ek ayar gerektirmez, WooCommerce sipariş akışına otomatik entegredir.
                    </p>
                </div>

                <div class="ies-card">
                    <h2 style="margin-top:0;font-size:16px;">Kategori & Popup Tasarımları</h2>
                    <p style="font-size:13px;">
                        EPIN ürün liste tabloları, Zone ID / Kullanıcı ID alanları, sepete ekle popup davranışı ve
                        hover efektleri buradaki modüllerle sağlanır.
                    </p>
                    <p><span class="ies-badge ies-badge-primary">Modül 3–4–5</span></p>
                    <p style="font-size:12px;color:#888;">
                        WooCommerce kategori sayfalarına ve ürün detay sayfalarına otomatik entegre çalışır.
                    </p>
                </div>

            </div>
        </div>
        <?php
    }
}
