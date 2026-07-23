<?php
// C:\laragon\www\destek_as\customer\pro_paket.php
require_once __DIR__ . '/../includes/header.php';
checkAccess(['Müşteri Kullanıcısı']);

$customer_id = $_SESSION['customer_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

$error = '';
$success = '';

// Fetch current package
try {
    $stmt = $pdo->prepare("
        SELECT c.*, sp.name AS package_name, sp.price AS package_price 
        FROM customers c
        LEFT JOIN support_packages sp ON c.support_package_id = sp.id
        WHERE c.id = ?
    ");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();
    $current_package_id = intval($customer['support_package_id'] ?? 1);
} catch (\Exception $e) {
    $error = 'Müşteri bilgileri alınamadı.';
}

// Handle upgrade action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upgrade_pkg']) && $current_package_id === 1) {
    try {
        $pdo->beginTransaction();

        // Upgrade customer package to Pro (ID 2) and enable VIP support (priority_support = 1)
        $stmtUpdate = $pdo->prepare("UPDATE customers SET support_package_id = 2, priority_support = 1 WHERE id = ?");
        $stmtUpdate->execute([$customer_id]);

        // Insert log activity
        logActivity($pdo, "Müşteri Pro Paket'e ($15) yükseltti.", "customers", $customer_id);

        $pdo->commit();
        $success = "Tebrikler! Hesabınız başarıyla Pro Paket'e yükseltildi. Ayrıcalıklarınız anında aktif edildi!";
        
        // Refresh customer details
        $current_package_id = 2;
        $customer['package_name'] = 'Pro Paket';
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Yükseltme işlemi sırasında hata oluştu: ' . $e->getMessage();
    }
}
?>

<div style="max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 30px;">
    <!-- Messages -->
    <?php if (!empty($success)): ?>
        <div style="background-color: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: var(--success); padding: 16px; border-radius: 12px; font-weight: 600; text-align: center;">
            <i class="fa-solid fa-circle-check" style="margin-right: 8px;"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div style="background-color: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: var(--danger); padding: 16px; border-radius: 12px; font-weight: 600; text-align: center;">
            <i class="fa-solid fa-circle-exclamation" style="margin-right: 8px;"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Package Comparison Card -->
    <div class="glass-card" style="position: relative; overflow: hidden; padding: 40px 30px; text-align: center;">
        <!-- Neon Glow Effect -->
        <div style="position: absolute; top: -50px; left: 50%; transform: translateX(-50%); width: 200px; height: 200px; background: rgba(139, 92, 246, 0.25); filter: blur(60px); border-radius: 50%; pointer-events: none;"></div>

        <div style="font-size: 40px; margin-bottom: 15px; color: var(--accent);"><i class="fa-solid fa-gem"></i></div>
        <h2 style="font-size: 26px; font-weight: 800; margin-bottom: 10px;">Pro Paket Ayrıcalıkları</h2>
        <p style="color: var(--text-secondary); font-size: 14px; max-width: 500px; margin: 0 auto 30px auto;">Standart paketin sınırlamalarından kurtulun, teknik ekibimizden kesintisiz ve yüksek öncelikli destek almaya hemen başlayın.</p>

        <!-- Feature Comparison List -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; text-align: left; margin: 0 auto 40px auto; max-width: 550px; background: rgba(255,255,255,0.02); padding: 25px; border-radius: 16px; border: 1px solid var(--border-color);">
            <!-- Standart Package -->
            <div style="border-right: 1px solid var(--border-color); padding-right: 20px;">
                <h4 style="font-size: 13px; font-weight: 700; color: var(--text-muted); margin-bottom: 15px; text-transform: uppercase;">Standart Paket</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; font-size: 12px; color: var(--text-secondary);">
                    <li><i class="fa-solid fa-check" style="color: var(--success); margin-right: 6px;"></i> Günlük 5 Destek Talebi</li>
                    <li><i class="fa-solid fa-xmark" style="color: var(--danger); margin-right: 8px;"></i> Acil/Kritik Talep Yetkisi</li>
                    <li><i class="fa-solid fa-xmark" style="color: var(--danger); margin-right: 8px;"></i> Yüksek SLA Garantisi</li>
                    <li><i class="fa-solid fa-check" style="color: var(--success); margin-right: 6px;"></i> E-Posta ile Bildirimler</li>
                </ul>
            </div>
            <!-- Pro Package -->
            <div style="padding-left: 20px;">
                <h4 style="font-size: 13px; font-weight: 700; color: var(--accent); margin-bottom: 15px; text-transform: uppercase;">Pro Paket 🚀</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; font-size: 12px; color: var(--text-primary);">
                    <li><i class="fa-solid fa-check" style="color: var(--accent); margin-right: 6px;"></i> <strong>Günlük 10 Destek Talebi</strong></li>
                    <li><i class="fa-solid fa-check" style="color: var(--accent); margin-right: 6px;"></i> <strong>Günde 5 Adet Acil Talep</strong></li>
                    <li><i class="fa-solid fa-check" style="color: var(--accent); margin-right: 6px;"></i> <strong>SLA Öncelikli Müdahale</strong></li>
                    <li><i class="fa-solid fa-check" style="color: var(--accent); margin-right: 6px;"></i> WhatsApp / Portal Desteği</li>
                </ul>
            </div>
        </div>

        <?php if ($current_package_id === 2): ?>
            <!-- Already Pro Status -->
            <div style="background: rgba(139, 92, 246, 0.1); border: 1px solid var(--accent); padding: 15px 25px; border-radius: 12px; display: inline-flex; align-items: center; gap: 10px; color: var(--accent); font-weight: 700;">
                <i class="fa-solid fa-circle-check"></i> Pro Paket Ayrıcalıklarınız Aktif!
            </div>
        <?php else: ?>
            <!-- Upgrade Form -->
            <div style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
                <div style="font-size: 32px; font-weight: 900; color: var(--text-primary);">
                    $15 <span style="font-size: 14px; font-weight: 500; color: var(--text-secondary);">/ Aylık</span>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="upgrade_pkg" value="1">
                    <button type="submit" class="btn-custom" style="background: var(--grad-primary); color: #fff; border: none; padding: 14px 40px; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4); transition: transform 0.2s;">
                        <i class="fa-solid fa-rocket"></i> Hemen Pro Paket'e Geç
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
