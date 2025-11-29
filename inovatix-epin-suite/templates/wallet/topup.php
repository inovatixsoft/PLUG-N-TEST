<?php
defined( 'ABSPATH' ) || exit;

/**
 * $balance        → mevcut bakiye (float)
 * $reference_code → müşteri referans no (string)
 * $banks          → banka listesi (name, iban, holder)
 * $pos_enabled    → sanal pos aktif mi? (bool)
 * $pos_commission → pos komisyon yüzdesi (float)
 */

$user = wp_get_current_user();
?>

<div class="ies-wallet-topup-page">

    <h2 class="ies-section-title">Bakiye Yükle</h2>

    <div class="ies-wallet-topup-grid">

        <!-- SOL KUTU: Mevcut Bakiye + Referans No -->
        <div class="ies-wallet-box">
            <h3>Mevcut Bakiyeniz</h3>
            <p class="ies-wallet-balance">
                <?php echo number_format_i18n( $balance, 2 ); ?> <span>TL</span>
            </p>

            <div class="ies-wallet-ref">
                <h4>Referans Numaranız</h4>
                <?php if ( $reference_code ) : ?>
                    <p class="ies-ref-code">
                        <code><?php echo esc_html( $reference_code ); ?></code>
                    </p>
                    <p class="ies-ref-note">
                        <strong>ÖNEMLİ:</strong> Havale / EFT yaparken banka açıklama kısmına mutlaka
                        <strong>bu referans numarasını</strong> yazmalısınız.<br>
                        Aynı referans numarası başka müşteride <strong>kullanılmaz</strong>.
                    </p>
                <?php else : ?>
                    <p>Referans numarası oluşturulamadı. Lütfen tekrar giriş yapın.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- SAĞ KUTU: Bakiye Yükleme Formu -->
        <div class="ies-wallet-box">
            <h3>Bakiye Yükleme Talebi</h3>

            <form method="post" class="ies-topup-form">
                <?php wp_nonce_field( 'ies_topup_nonce' ); ?>

                <div class="ies-form-group">
                    <label for="ies_topup_amount">Yüklemek istediğiniz tutar</label>
                    <div class="ies-amount-input">
                        <input type="number"
                               id="ies_topup_amount"
                               name="ies_topup_amount"
                               min="1"
                               step="1"
                               required
                               placeholder="Örn: 100">
                        <span class="ies-amount-suffix">TL</span>
                    </div>
                </div>

                <div class="ies-form-group">
                    <label>Ödeme Yöntemi</label>

                    <label class="ies-radio">
                        <input type="radio" name="ies_topup_method" value="havale" checked>
                        <span>Havale / EFT ile Bakiye Yükle</span>
                    </label>

                    <?php if ( ! empty( $pos_enabled ) ) : ?>
                        <label class="ies-radio">
                            <input type="radio" name="ies_topup_method" value="pos">
                            <span>
                                Kart ile Ödeme (Sanal POS)
                                <?php if ( $pos_commission > 0 ) : ?>
                                    <small>
                                        (POS komisyonu: <?php echo esc_html( $pos_commission ); ?>%)
                                    </small>
                                <?php endif; ?>
                            </span>
                        </label>

                        <?php if ( $pos_commission > 0 ) : ?>
                            <p class="ies-pos-info">
                                Örnek: 100 TL yüklemek isterseniz, POS komisyonu ile birlikte
                                toplam tutar <strong>(100 + %<?php echo esc_html( $pos_commission ); ?>)</strong>
                                olarak kredi kartınızdan çekilecektir.
                            </p>
                        <?php endif; ?>
                    <?php else : ?>
                        <p class="ies-pos-disabled-note">
                            Kart ile ödeme (sanal POS) şu anda aktif değildir. İlerleyen dönemlerde eklenecektir.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="ies-form-group">
                    <p class="ies-bank-warning">
                        Havale / EFT ile bakiye yüklerken, banka açıklama kısmına
                        <strong>mutlaka referans numaranızı</strong> yazın. Aksi durumda
                        ödemenin hesabınıza tanımlanması gecikebilir.
                    </p>
                </div>

                <div class="ies-form-group ies-topup-submit-wrap">
                    <button type="submit"
                            name="ies_topup_submit"
                            class="ies-btn ies-btn-primary ies-neon-hover">
                        Bakiye Yükleme Talebini Gönder
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- ALT BÖLÜM: Banka Hesapları -->
    <div class="ies-wallet-banks">
        <h3>Havale / EFT Hesaplarımız</h3>

        <?php if ( ! empty( $banks ) ) : ?>
            <div class="ies-bank-grid">
                <?php foreach ( $banks as $bank ) :
                    $name   = isset( $bank['name'] )   ? $bank['name']   : '';
                    $iban   = isset( $bank['iban'] )   ? $bank['iban']   : '';
                    $holder = isset( $bank['holder'] ) ? $bank['holder'] : '';
                ?>
                    <div class="ies-bank-card">
                        <div class="ies-bank-name"><?php echo esc_html( $name ); ?></div>
                        <div class="ies-bank-holder"><?php echo esc_html( $holder ); ?></div>
                        <div class="ies-bank-iban">
                            <span>IBAN:</span>
                            <strong><?php echo esc_html( $iban ); ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p>Şu anda kayıtlı banka hesabı bulunmamaktadır. Lütfen site yöneticisi ile iletişime geçin.</p>
        <?php endif; ?>
    </div>

</div>
