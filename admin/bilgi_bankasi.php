<?php
// C:\laragon\www\destek_as\admin\bilgi_bankasi.php
require_once __DIR__ . '/../includes/header.php';
?>

<div class="glass-card" style="text-align: center; padding: 60px 40px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; min-height: 400px;">
    <div style="width: 100px; height: 100px; border-radius: 50%; background: rgba(6, 182, 212, 0.1); border: 2px dashed var(--info); display: flex; align-items: center; justify-content: center;">
        <i class="fa-solid fa-book-open" style="font-size: 40px; color: var(--info);"></i>
    </div>
    <h2 style="font-size: 24px; margin-top: 10px;">Kurumsal Bilgi Bankası</h2>
    <p style="color: var(--text-secondary); max-width: 500px; line-height: 1.6;">Çözülmüş destek taleplerinden otomatik makaleler üreten, rehberler ve sık sorulan soruları içeren Bilgi Bankası modülü **Faz 3** kapsamında geliştirilecektir.</p>
    <a href="/destek_as/index.php" class="btn-custom btn-custom-primary">Kontrol Paneline Dön</a>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
