<?php
// C:\laragon\www\destek_as\admin\ayarlar.php
require_once __DIR__ . '/../includes/header.php';
?>

<div class="glass-card" style="text-align: center; padding: 60px 40px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; min-height: 400px;">
    <div style="width: 100px; height: 100px; border-radius: 50%; background: rgba(139, 92, 246, 0.1); border: 2px dashed var(--primary); display: flex; align-items: center; justify-content: center;">
        <i class="fa-solid fa-sliders" style="font-size: 40px; color: var(--primary);"></i>
    </div>
    <h2 style="font-size: 24px; margin-top: 10px;">Entegrasyon ve Sistem Ayarları</h2>
    <p style="color: var(--text-secondary); max-width: 500px; line-height: 1.6;">Şirket logo/çalışma saatleri, Webhook kayıtları, REST API anahtarları, E-posta okuma hesapları (IMAP/SMTP) ve bildirim kanallarını yapılandırabileceğiniz ayarlar ekranı **Faz 4** kapsamında geliştirilecektir.</p>
    <a href="/destek_as/index.php" class="btn-custom btn-custom-primary">Kontrol Paneline Dön</a>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
