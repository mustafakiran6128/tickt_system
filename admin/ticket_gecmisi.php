<?php
ob_start();
// C:\laragon\www\destek_as\admin\ticket_gecmisi.php
require_once __DIR__ . '/../includes/header.php';
// Prevent customer users from visiting this admin portal page
if (($_SESSION['role_name'] ?? '') === 'Müşteri Kullanıcısı') {
    header('Location: /destek_as/customer/ticketlarim.php');
    exit;
}

$company_id = $_SESSION['company_id'] ?? 1;

// Gather filters
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status_id'] ?? '';
$priority_filter = $_GET['priority_id'] ?? '';
$category_filter = $_GET['category_id'] ?? '';

$query = "
    SELECT t.*, ts.name AS status_name, ts.color AS status_color, p.name AS priority_name, c.name AS category_name, cust.name AS cust_name,
           (SELECT CONCAT(u.first_name, ' ', u.last_name) 
            FROM ticket_assignments ta 
            JOIN users u ON ta.user_id = u.id 
            WHERE ta.ticket_id = t.id AND ta.status = 'active' 
            LIMIT 1) AS assignee_name
    FROM tickets t
    LEFT JOIN ticket_statuses ts ON t.status_id = ts.id
    LEFT JOIN priorities p ON t.priority_id = p.id
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN customers cust ON t.customer_id = cust.id
    WHERE t.company_id = :company_id
";

$params = [':company_id' => $company_id];

// Filter by assigned user if logged-in user is a technician (Destek Personeli)
if (($_SESSION['role_name'] ?? '') === 'Destek Personeli') {
    $query .= " AND t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = :assigned_user_id AND status = 'active')";
    $params[':assigned_user_id'] = $_SESSION['user_id'];
}

// Only show closed/resolved/cancelled tickets (Çözüldü=10, Kapatıldı=12, İptal edildi=14)
$query .= " AND t.status_id IN (10, 12, 14)";

if ($search !== '') {
    $query .= " AND (t.ticket_number LIKE :search OR t.title LIKE :search OR t.description LIKE :search OR cust.name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($status_filter !== '') {
    $query .= " AND t.status_id = :status_id";
    $params[':status_id'] = intval($status_filter);
}
if ($priority_filter !== '') {
    $query .= " AND t.priority_id = :priority_id";
    $params[':priority_id'] = intval($priority_filter);
}
if ($category_filter !== '') {
    $query .= " AND t.category_id = :category_id";
    $params[':category_id'] = intval($category_filter);
}

$query .= " ORDER BY t.id DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
} catch (\Exception $e) {
    $error = 'Sorgu hatası: ' . $e->getMessage();
}

// Fetch closed filter options
$statuses = $pdo->query("SELECT id, name FROM ticket_statuses WHERE id IN (10, 12, 14) ORDER BY id ASC")->fetchAll();
$priorities = $pdo->query("SELECT id, name FROM priorities ORDER BY level ASC")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();
?>

<div style="display: flex; flex-direction: column; gap: 30px;">
    <!-- Filter Card -->
    <div class="glass-card">
        <h3 style="font-size: 15px; margin-bottom: 15px;"><i class="fa-solid fa-filter" style="margin-right: 6px; color: var(--primary);"></i> Geçmiş Taleplerde Filtreleme</h3>
        <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 15px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Arama Terimi (No, Başlık, Açıklama)</label>
                <input type="text" name="search" class="form-control" placeholder="Arama yapın..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Durum</label>
                <select name="status_id" class="form-control">
                    <option value="">Tümü</option>
                    <?php foreach ($statuses as $stat): ?>
                        <option value="<?php echo $stat['id']; ?>" <?php echo $status_filter == $stat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($stat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Öncelik</label>
                <select name="priority_id" class="form-control">
                    <option value="">Tümü</option>
                    <?php foreach ($priorities as $pri): ?>
                        <option value="<?php echo $pri['id']; ?>" <?php echo $priority_filter == $pri['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($pri['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Kategori</label>
                <select name="category_id" class="form-control">
                    <option value="">Tümü</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn-custom btn-custom-primary" style="height: 44px; padding: 0 20px;">
                    <i class="fa-solid fa-magnifying-glass"></i> Filtrele
                </button>
                <a href="/destek_as/admin/ticket_gecmisi.php" class="btn-custom btn-custom-secondary" style="height: 44px; padding: 0 15px; display: inline-flex; align-items: center; justify-content: center;" title="Filtreleri Temizle">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 18px;"><i class="fa-solid fa-clock-rotate-left" style="margin-right: 8px; color: var(--accent);"></i> Çözülen ve Kapatılan Talepler (Geçmiş)</h3>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Ticket No</th>
                        <th>Başlık</th>
                        <th>Müşteri Firma</th>
                        <th>Kategori</th>
                        <th>Atanan Uzman</th>
                        <th>Öncelik</th>
                        <th>Oluşturma Tarihi</th>
                        <th>Durum</th>
                        <th style="text-align: right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">Arşivde kayıtlı destek talebi bulunamadı.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <?php 
                            $row_style = '';
                            $pri_color = 'var(--text-primary)';
                            if ($ticket['priority_name'] === 'Yüksek') {
                                $pri_color = '#f97316'; // Orange
                                $row_style = 'style="background: rgba(249, 115, 22, 0.02); border-left: 4px solid #f97316;"';
                            } elseif ($ticket['priority_name'] === 'Kritik') {
                                $pri_color = '#ef4444'; // Soft Red
                                $row_style = 'style="background: rgba(239, 68, 68, 0.04); border-left: 4px solid #ef4444;"';
                            } elseif ($ticket['priority_name'] === 'Acil') {
                                $pri_color = '#dc2626'; // Deep Red
                                $row_style = 'style="background: rgba(220, 38, 38, 0.06); border-left: 4px solid #dc2626;"';
                            }
                            ?>
                            <tr <?php echo $row_style; ?>>
                                <td><strong>#<?php echo $ticket['ticket_number']; ?></strong></td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($ticket['title']); ?></div>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($ticket['product_service'] ?? ''); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($ticket['cust_name'] ?? 'Müşteri Tanımsız'); ?></td>
                                <td><?php echo htmlspecialchars($ticket['category_name'] ?? 'Kategorisiz'); ?></td>
                                <td>
                                    <?php if (!empty($ticket['assignee_name'])): ?>
                                        <span style="font-weight: 500;"><i class="fa-solid fa-user-shield" style="margin-right: 4px; color: var(--accent);"></i> <?php echo htmlspecialchars($ticket['assignee_name']); ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 11px;">Atanmamış</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color: <?php echo $pri_color; ?>; font-weight: 600;">
                                        <?php echo htmlspecialchars($ticket['priority_name'] ?? 'Normal'); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></td>
                                <td>
                                    <span class="badge" style="background-color: <?php echo $ticket['status_color']; ?>22; color: <?php echo $ticket['status_color']; ?>; border: 1px solid <?php echo $ticket['status_color']; ?>55;">
                                        <?php echo htmlspecialchars($ticket['status_name']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="/destek_as/admin/ticket_detay.php?id=<?php echo $ticket['id']; ?>" class="btn-custom btn-custom-secondary" style="padding: 6px 12px; font-size: 12px;">
                                        <i class="fa-solid fa-magnifying-glass-chart"></i> İncele
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
