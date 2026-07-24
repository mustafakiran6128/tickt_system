<?php
// C:\laragon\www\destek_as\customer\ticket_gecmisi.php
require_once __DIR__ . '/../includes/header.php';
checkAccess(['Müşteri Kullanıcısı']);

$customer_id = $_SESSION['customer_id'] ?? 0;

try {
    // Fetch customer's resolved/closed/cancelled tickets
    $stmt = $pdo->prepare("
        SELECT t.*, ts.name AS status_name, ts.color AS status_color, p.name AS priority_name, c.name AS category_name
        FROM tickets t
        LEFT JOIN ticket_statuses ts ON t.status_id = ts.id
        LEFT JOIN priorities p ON t.priority_id = p.id
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.customer_id = ? AND t.status_id IN (10, 12, 14)
        ORDER BY t.id DESC
    ");
    $stmt->execute([$customer_id]);
    $tickets = $stmt->fetchAll();
} catch (\Exception $e) {
    $error = 'Talepleriniz yüklenirken hata oluştu: ' . $e->getMessage();
}
?>

<div style="display: flex; flex-direction: column; gap: 30px;">
    <!-- Top Actions Card -->
    <div class="glass-card" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h3 style="font-size: 18px; margin-bottom: 5px;">Çözülen / Kapatılan Taleplerim (Arşiv)</h3>
            <p style="color: var(--text-secondary); font-size: 13px;">Aşağıdaki listeden çözüme kavuşturulmuş veya kapatılmış geçmiş destek taleplerinizin geçmişini inceleyebilirsiniz.</p>
        </div>
        <a href="/destek_as/customer/ticketlarim.php" class="btn-custom btn-custom-secondary">
            <i class="fa-solid fa-arrow-left"></i> Aktif Taleplerim
        </a>
    </div>

    <!-- Table Card -->
    <div class="glass-card">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Takip No</th>
                        <th>Başlık</th>
                        <th>Kategori</th>
                        <th>Öncelik</th>
                        <th>Oluşturma Tarihi</th>
                        <th>Durum</th>
                        <th style="text-align: right;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">Arşivde çözülmüş/kapatılmış destek talebiniz bulunmamaktadır.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $tick): ?>
                            <?php 
                            $row_style = '';
                            $pri_color = 'var(--text-primary)';
                            if ($tick['priority_name'] === 'Yüksek') {
                                $pri_color = '#f97316'; // Orange
                                $row_style = 'style="background: rgba(249, 115, 22, 0.02); border-left: 4px solid #f97316;"';
                            } elseif ($tick['priority_name'] === 'Kritik') {
                                $pri_color = '#ef4444'; // Soft Red
                                $row_style = 'style="background: rgba(239, 68, 68, 0.04); border-left: 4px solid #ef4444;"';
                            } elseif ($tick['priority_name'] === 'Acil') {
                                $pri_color = '#dc2626'; // Deep Red
                                $row_style = 'style="background: rgba(220, 38, 38, 0.06); border-left: 4px solid #dc2626;"';
                            }
                            ?>
                            <tr <?php echo $row_style; ?>>
                                <td><strong>#<?php echo $tick['ticket_number']; ?></strong></td>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($tick['title']); ?></div>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($tick['product_service'] ?? ''); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($tick['category_name'] ?? 'Kategorisiz'); ?></td>
                                <td>
                                    <span style="color: <?php echo $pri_color; ?>; font-weight: 600;">
                                        <?php echo htmlspecialchars($tick['priority_name'] ?? 'Normal'); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($tick['created_at'])); ?></td>
                                <td>
                                    <span class="badge" style="background-color: <?php echo $tick['status_color']; ?>22; color: <?php echo $tick['status_color']; ?>; border: 1px solid <?php echo $tick['status_color']; ?>55;">
                                        <?php echo htmlspecialchars($tick['status_name']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="/destek_as/admin/ticket_detay.php?id=<?php echo $tick['id']; ?>" class="btn-custom btn-custom-secondary" style="padding: 6px 12px; font-size: 12px;">
                                        <i class="fa-solid fa-magnifying-glass"></i> İncele
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
