<?php
ob_start();
// C:\laragon\www\destek_as\admin\audit_logs.php
require_once __DIR__ . '/../includes/header.php';
checkAccess(['Firma Yöneticisi', 'Sistem Sahibi']);

$company_id = $_SESSION['company_id'] ?? 1;

// Get selected filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query depending on filter
$where_clause = "WHERE al.company_id = ?";
$params = [$company_id];

if ($filter === 'today') {
    $where_clause .= " AND DATE(al.created_at) = CURDATE()";
} elseif ($filter === 'week') {
    $where_clause .= " AND YEARWEEK(al.created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'month') {
    $where_clause .= " AND YEAR(al.created_at) = YEAR(CURDATE()) AND MONTH(al.created_at) = MONTH(CURDATE())";
}

try {
    // Fetch audit logs for this company
    $stmt = $pdo->prepare("
        SELECT al.*, u.first_name, u.last_name 
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        $where_clause
        ORDER BY al.id DESC LIMIT 500
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (\Exception $e) {
    $error = 'Log kayıtları yüklenirken hata oluştu: ' . $e->getMessage();
}

// Translate filter names for page headers
$filter_title = 'Tüm Zamanlar';
if ($filter === 'today') $filter_title = 'Bugün';
if ($filter === 'week') $filter_title = 'Bu Hafta';
if ($filter === 'month') $filter_title = 'Bu Ay';
?>

<style>
@media print {
    aside.sidebar, header.topbar, .btn-custom, .filter-tabs, form {
        display: none !important;
    }
    .main-wrapper {
        margin-left: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    .glass-card {
        background: none !important;
        border: none !important;
        box-shadow: none !important;
        color: #000 !important;
        padding: 0 !important;
    }
    body {
        background: #fff !important;
        color: #000 !important;
    }
    .custom-table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    .custom-table th, .custom-table td {
        color: #000 !important;
        border-bottom: 1px solid #ccc !important;
        padding: 8px !important;
    }
}
</style>

<div style="display: flex; flex-direction: column; gap: 30px;">
    <!-- Filters and Print Actions -->
    <div class="glass-card filter-tabs" style="padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div style="display: flex; gap: 10px;">
            <a href="?filter=all" class="btn-custom <?php echo $filter === 'all' ? 'btn-custom-primary' : 'btn-custom-secondary'; ?>" style="text-decoration: none; padding: 8px 16px;">Tümü</a>
            <a href="?filter=today" class="btn-custom <?php echo $filter === 'today' ? 'btn-custom-primary' : 'btn-custom-secondary'; ?>" style="text-decoration: none; padding: 8px 16px;">Günlük</a>
            <a href="?filter=week" class="btn-custom <?php echo $filter === 'week' ? 'btn-custom-primary' : 'btn-custom-secondary'; ?>" style="text-decoration: none; padding: 8px 16px;">Haftalık</a>
            <a href="?filter=month" class="btn-custom <?php echo $filter === 'month' ? 'btn-custom-primary' : 'btn-custom-secondary'; ?>" style="text-decoration: none; padding: 8px 16px;">Aylık</a>
        </div>
        <div>
            <button onclick="window.print()" class="btn-custom btn-custom-accent" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px;">
                <i class="fa-solid fa-print"></i> Raporu Yazdır / PDF Kaydet
            </button>
        </div>
    </div>

    <!-- Log Info Card -->
    <div class="glass-card">
        <h3 style="font-size: 18px; margin-bottom: 25px;"><i class="fa-solid fa-clock-rotate-left" style="margin-right: 8px; color: var(--primary);"></i> Sistem İşlem Geçmişi - <?php echo $filter_title; ?></h3>
        
        <div class="table-responsive">
            <table class="custom-table" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th>Tarih / Saat</th>
                        <th>Kullanıcı</th>
                        <th>İşlem Detayı</th>
                        <th>Etkilenen Tablo</th>
                        <th>Kayıt ID</th>
                        <th>IP Adresi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">Filtreye uygun işlem logu bulunmuyor.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><strong><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></strong></td>
                                <td>
                                    <?php if ($log['user_id']): ?>
                                        <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">Sistem</span>
                                    <?php endif; ?>
                                </td>
                                <td><span style="color: var(--text-primary); font-weight: 600;"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                <td><code><?php echo htmlspecialchars($log['record_type'] ?? '-'); ?></code></td>
                                <td>#<?php echo htmlspecialchars($log['record_id'] ?? '-'); ?></td>
                                <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
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
