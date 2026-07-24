<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Handle AJAX direct assignment update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_assign'])) {
    header('Content-Type: application/json');
    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $agent_id = intval($_POST['agent_id'] ?? 0);
    $user_role = $_SESSION['role_name'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;

    if ($ticket_id > 0 && in_array($user_role, ['Sistem Sahibi', 'Firma Yöneticisi', 'Departman Yöneticisi'])) {
        try {
            $pdo->beginTransaction();

            // Deactivate old active assignments
            $stmtDeact = $pdo->prepare("UPDATE ticket_assignments SET status = 'reassigned', unassigned_at = CURRENT_TIMESTAMP WHERE ticket_id = ? AND status = 'active'");
            $stmtDeact->execute([$ticket_id]);

            if ($agent_id > 0) {
                // Create new assignment
                $stmtAssign = $pdo->prepare("INSERT INTO ticket_assignments (ticket_id, user_id, assigned_by, method, status) VALUES (?, ?, ?, 'manual', 'active')");
                $stmtAssign->execute([$ticket_id, $agent_id, $user_id]);

                // Auto update status to "Atandı" if current status is "Yeni" (1)
                $stmtT = $pdo->prepare("SELECT status_id FROM tickets WHERE id = ?");
                $stmtT->execute([$ticket_id]);
                $curr_status = $stmtT->fetchColumn();

                $stmtStatus = $pdo->prepare("SELECT id FROM ticket_statuses WHERE name = 'Atandı' LIMIT 1");
                $stmtStatus->execute();
                $atandi_id = $stmtStatus->fetchColumn();

                if ($atandi_id && $curr_status == 1) { 
                    $stmtUp = $pdo->prepare("UPDATE tickets SET status_id = ? WHERE id = ?");
                    $stmtUp->execute([$atandi_id, $ticket_id]);
                }
                
                // Fetch agent name for notification log
                $stmtAgentName = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id = ?");
                $stmtAgentName->execute([$agent_id]);
                $agent_name = $stmtAgentName->fetchColumn();

                logActivity($pdo, "Bilet doğrudan atandı. Bilet ID: {$ticket_id}, Uzman: {$agent_name}", "tickets", $ticket_id);
                createNotification($pdo, 'Yeni Talep Atandı', "Size yeni bir bilet atandı.", $agent_id, null, 'assignment');
            } else {
                // Set back to "Yeni" status if unassigned and it was "Atandı"
                $stmtT = $pdo->prepare("SELECT status_id FROM tickets WHERE id = ?");
                $stmtT->execute([$ticket_id]);
                $curr_status = $stmtT->fetchColumn();

                $stmtStatus = $pdo->prepare("SELECT id FROM ticket_statuses WHERE name = 'Atandı' LIMIT 1");
                $stmtStatus->execute();
                $atandi_id = $stmtStatus->fetchColumn();

                if ($atandi_id && $curr_status == $atandi_id) {
                    $stmtUp = $pdo->prepare("UPDATE tickets SET status_id = 1 WHERE id = ?"); // Set to Yeni (1)
                    $stmtUp->execute([$ticket_id]);
                }
                logActivity($pdo, "Bilet ataması kaldırıldı. Bilet ID: {$ticket_id}", "tickets", $ticket_id);
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Atama başarıyla güncellendi!']);
            exit;
        } catch (\Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Hata: ' . $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz işlem!']);
    exit;
}

// C:\laragon\www\destek_as\admin\ticketlar.php
require_once __DIR__ . '/../includes/header.php';
// Prevent customer users from visiting this admin portal page
if (($_SESSION['role_name'] ?? '') === 'Müşteri Kullanıcısı') {
    header('Location: /destek_as/customer/ticketlarim.php');
    exit;
}

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
            LIMIT 1) AS assignee_name,
           (SELECT ta.user_id 
            FROM ticket_assignments ta 
            WHERE ta.ticket_id = t.id AND ta.status = 'active' 
            LIMIT 1) AS assignee_id
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

// Only show active tickets (exclude Çözüldü=10, Kapatıldı=12, İptal edildi=14)
$query .= " AND t.status_id NOT IN (10, 12, 14)";

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

// Fetch filter options
$statuses = $pdo->query("SELECT id, name FROM ticket_statuses WHERE id NOT IN (10, 12, 14) ORDER BY id ASC")->fetchAll();
$priorities = $pdo->query("SELECT id, name FROM priorities ORDER BY level ASC")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

// Fetch support agents for dropdown list (Managers & Specialists)
$agents = [];
if (in_array(($_SESSION['role_name'] ?? ''), ['Sistem Sahibi', 'Firma Yöneticisi', 'Departman Yöneticisi'])) {
    $agents = $pdo->query("
        SELECT u.id, u.first_name, u.last_name 
        FROM users u
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE r.name IN ('Destek Personeli', 'Firma Yöneticisi', 'Departman Yöneticisi') AND u.status = 'active'
        ORDER BY u.first_name ASC
    ")->fetchAll();
}
?>

<div style="display: flex; flex-direction: column; gap: 30px;">
    <!-- Filter Card -->
    <div class="glass-card">
        <h3 style="font-size: 15px; margin-bottom: 15px;"><i class="fa-solid fa-filter" style="margin-right: 6px; color: var(--primary);"></i> Gelişmiş Arama ve Filtreleme</h3>
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
                <a href="/destek_as/admin/ticketlar.php" class="btn-custom btn-custom-secondary" style="height: 44px; padding: 0 15px; display: inline-flex; align-items: center; justify-content: center;" title="Filtreleri Temizle">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 18px;"><i class="fa-solid fa-list-check" style="margin-right: 8px; color: var(--primary);"></i> Destek Talepleri Listesi</h3>
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
                        <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">Kriterlere uygun destek talebi bulunamadı.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <?php 
                             if ($ticket['priority_name'] === 'Öncelikli') {
                                 $pri_color = '#f59e0b'; // Orange
                                 $row_style = 'style="background: rgba(245, 158, 11, 0.02); border-left: 4px solid #f59e0b;"';
                             } elseif ($ticket['priority_name'] === 'Yüksek') {
                                 $pri_color = '#ef4444'; // Red
                                 $row_style = 'style="background: rgba(239, 68, 68, 0.04); border-left: 4px solid #ef4444;"';
                             } else {
                                 $pri_color = '#3b82f6'; // Blue (Normal)
                                 $row_style = 'style="background: rgba(59, 130, 246, 0.02); border-left: 4px solid #3b82f6;"';
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
                                    <?php if (in_array($_SESSION['role_name'] ?? '', ['Sistem Sahibi', 'Firma Yöneticisi', 'Departman Yöneticisi'])): ?>
                                        <select class="form-control assign-select" onchange="assignAgent(this, <?php echo $ticket['id']; ?>)" style="padding: 4px 8px; font-size: 12px; height: 32px; width: 140px; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-primary); cursor: pointer;">
                                            <option value="">Atanmamış</option>
                                            <?php foreach ($agents as $agent): ?>
                                                <option value="<?php echo $agent['id']; ?>" <?php echo ($ticket['assignee_id'] == $agent['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <?php if (!empty($ticket['assignee_name'])): ?>
                                            <span style="font-weight: 500;"><i class="fa-solid fa-user-shield" style="margin-right: 4px; color: var(--accent);"></i> <?php echo htmlspecialchars($ticket['assignee_name']); ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 11px;">Atanmamış</span>
                                        <?php endif; ?>
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
                                        <i class="fa-solid fa-magnifying-glass-chart"></i> İncele & Çöz
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

<script>
function assignAgent(selectElem, ticketId) {
    const agentId = selectElem.value;
    const origBackground = selectElem.style.background;
    selectElem.style.background = 'rgba(139, 92, 246, 0.1)';

    const formData = new FormData();
    formData.append('ajax_assign', '1');
    formData.append('ticket_id', ticketId);
    formData.append('agent_id', agentId);

    fetch('/destek_as/admin/ticketlar.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            selectElem.style.background = 'rgba(16, 185, 129, 0.1)';
            setTimeout(() => {
                selectElem.style.background = origBackground;
            }, 1000);
            showNotificationToast(data.message, 'success');
        } else {
            selectElem.style.background = origBackground;
            alert(data.message);
        }
    })
    .catch(err => {
        selectElem.style.background = origBackground;
        console.error(err);
        alert('Bağlantı hatası oluştu!');
    });
}

function showNotificationToast(msg, type) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.bottom = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.style.background = type === 'success' ? 'var(--success)' : 'var(--danger)';
    toast.style.color = '#fff';
    toast.style.padding = '12px 24px';
    toast.style.borderRadius = '8px';
    toast.style.fontSize = '13px';
    toast.style.fontWeight = '600';
    toast.style.boxShadow = 'var(--shadow-md)';
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.3s ease';
    toast.innerText = msg;
    
    container.appendChild(toast);
    setTimeout(() => toast.style.opacity = '1', 50);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
