<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Check access
$user_role = $_SESSION['role_name'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;
$company_id = $_SESSION['company_id'] ?? 1;

if (!in_array($user_role, ['Sistem Sahibi', 'Firma Yöneticisi', 'Departman Yöneticisi', 'Destek Personeli'])) {
    header('Location: /destek_as/index.php');
    exit;
}

$is_manager = in_array($user_role, ['Sistem Sahibi', 'Firma Yöneticisi', 'Departman Yöneticisi']);

// Handle AJAX Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update_status'])) {
    header('Content-Type: application/json');
    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $new_status_id = intval($_POST['status_id'] ?? 0);

    if ($ticket_id > 0 && $new_status_id > 0) {
        try {
            // Check if ticket is assigned to this user if they are not manager
            if (!$is_manager) {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM ticket_assignments WHERE ticket_id = ? AND user_id = ? AND status = 'active'");
                $stmtCheck->execute([$ticket_id, $user_id]);
                if ($stmtCheck->fetchColumn() == 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Bu talebi taşımak için yetkiniz yok! (Yalnızca size atanan talepleri taşıyabilirsiniz)']);
                    exit;
                }
            }

            // Update ticket status
            $stmtUp = $pdo->prepare("UPDATE tickets SET status_id = ? WHERE id = ?");
            $stmtUp->execute([$new_status_id, $ticket_id]);

            // Log activity
            $stmtStatusName = $pdo->prepare("SELECT name FROM ticket_statuses WHERE id = ?");
            $stmtStatusName->execute([$new_status_id]);
            $status_name = $stmtStatusName->fetchColumn();

            logActivity($pdo, "Bilet durumu Kanban üzerinden güncellendi: {$status_name} (Bilet ID: {$ticket_id})", "tickets", $ticket_id);

            echo json_encode(['status' => 'success', 'message' => 'Durum başarıyla güncellendi!']);
            exit;
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Hata: ' . $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz parametreler!']);
    exit;
}

// Fetch all columns (statuses)
$statuses = $pdo->query("SELECT id, name, color FROM ticket_statuses WHERE id IN (2, 3, 5, 8, 10) ORDER BY id ASC")->fetchAll();

// Fetch tickets (excluding cancelled/closed in archive)
$query = "
    SELECT t.*, ts.name AS status_name, ts.color AS status_color, p.name AS priority_name, c.name AS category_name, cust.name AS cust_name,
           (SELECT CONCAT(u.first_name, ' ', u.last_name) FROM ticket_assignments ta JOIN users u ON ta.user_id = u.id WHERE ta.ticket_id = t.id AND ta.status = 'active' LIMIT 1) AS assignee_name,
           (SELECT ta.user_id FROM ticket_assignments ta WHERE ta.ticket_id = t.id AND ta.status = 'active' LIMIT 1) AS assignee_id
    FROM tickets t
    LEFT JOIN ticket_statuses ts ON t.status_id = ts.id
    LEFT JOIN priorities p ON t.priority_id = p.id
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN customers cust ON t.customer_id = cust.id
    WHERE t.company_id = ? AND t.status_id IN (2, 3, 5, 8, 10)
    ORDER BY t.id DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$company_id]);
$all_tickets = $stmt->fetchAll();

// Group tickets by status_id
$grouped_tickets = [];
foreach ($statuses as $status) {
    $grouped_tickets[$status['id']] = [];
}
foreach ($all_tickets as $ticket) {
    if (isset($grouped_tickets[$ticket['status_id']])) {
        $grouped_tickets[$ticket['status_id']][] = $ticket;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; flex-direction: column; gap: 20px; height: calc(100vh - 120px);">
    <!-- Kanban Header -->
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-size: 22px; font-weight: 700; color: var(--text-primary);"><i class="fa-solid fa-table-columns" style="margin-right: 8px; color: var(--primary);"></i> Kanban İş Akış Panosu</h2>
            <p style="color: var(--text-secondary); font-size: 13px;">Talepleri durumlarına göre sürükle-bırak yöntemiyle güncelleyebilirsiniz.</p>
        </div>
        <?php if (!$is_manager): ?>
            <span class="badge" style="background: rgba(178, 94, 44, 0.08); color: var(--primary); padding: 8px 12px; font-size: 12px; border: 1px solid var(--border-color);">
                <i class="fa-solid fa-lock" style="margin-right: 6px;"></i> Yalnızca kendi atamalarınızı taşıyabilirsiniz.
            </span>
        <?php endif; ?>
    </div>

    <!-- Kanban Board Container -->
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; overflow-x: auto; flex-grow: 1; padding-bottom: 15px; align-items: stretch; min-height: 500px;">
        <?php foreach ($statuses as $status): ?>
            <!-- Kanban Column -->
            <div class="kanban-col" data-status-id="<?php echo $status['id']; ?>" ondragover="allowDrop(event)" ondrop="dropCard(event)" style="background: rgba(255,255,255,0.4); border: 1px solid var(--border-color); border-radius: 16px; display: flex; flex-direction: column; max-height: 100%; min-width: 250px;">
                <!-- Column Header -->
                <div style="padding: 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(178, 94, 44, 0.03); border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="width: 10px; height: 10px; border-radius: 50%; background-color: <?php echo $status['color']; ?>;"></span>
                        <h4 style="font-size: 13px; font-weight: 700; color: var(--text-primary);"><?php echo htmlspecialchars($status['id'] == 10 ? 'Kapanan Ticket' : $status['name']); ?></h4>
                    </div>
                    <span style="background: var(--bg-sidebar); border: 1px solid var(--border-color); color: var(--text-secondary); font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 10px;">
                        <?php echo count($grouped_tickets[$status['id']]); ?>
                    </span>
                </div>

                <!-- Cards Container -->
                <div class="kanban-cards" style="padding: 12px; display: flex; flex-direction: column; gap: 12px; overflow-y: auto; flex-grow: 1; max-height: calc(100vh - 280px);">
                    <?php if (empty($grouped_tickets[$status['id']])): ?>
                        <div class="kanban-empty" style="text-align: center; color: var(--text-muted); font-size: 11px; padding: 30px 0; border: 2px dashed rgba(0,0,0,0.03); border-radius: 12px;">Talep yok</div>
                    <?php else: ?>
                        <?php foreach ($grouped_tickets[$status['id']][$status['id'] === 10 ? 'slice' : 'all'] ?? $grouped_tickets[$status['id']] as $ticket): 
                            // Determine drag eligibility
                            $can_drag = $is_manager || (intval($ticket['assignee_id']) === intval($user_id));
                            
                            // Determine priority color
                            if ($ticket['priority_name'] === 'Öncelikli') {
                                $pri_border = 'border-left: 4px solid #f59e0b;';
                                $pri_badge = '#f59e0b';
                            } elseif ($ticket['priority_name'] === 'Yüksek') {
                                $pri_border = 'border-left: 4px solid #ef4444;';
                                $pri_badge = '#ef4444';
                            } else {
                                $pri_border = 'border-left: 4px solid #b25e2c;';
                                $pri_badge = '#b25e2c';
                            }
                        ?>
                            <!-- Kanban Card -->
                            <div class="kanban-card" 
                                 id="ticket-<?php echo $ticket['id']; ?>"
                                 data-ticket-id="<?php echo $ticket['id']; ?>"
                                 draggable="<?php echo $can_drag ? 'true' : 'false'; ?>"
                                 ondragstart="dragCard(event)"
                                 style="background: var(--bg-sidebar); border: 1px solid var(--border-color); <?php echo $pri_border; ?> border-radius: 12px; padding: 14px; box-shadow: var(--shadow-sm); cursor: <?php echo $can_drag ? 'grab' : 'not-allowed'; ?>; position: relative; transition: var(--transition);">
                                
                                <!-- Card Header -->
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <small style="color: var(--primary); font-weight: 700; font-size: 10px;">#<?php echo htmlspecialchars($ticket['ticket_number']); ?></small>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <span style="font-size: 9px; padding: 2px 6px; border-radius: 4px; color: #fff; background: <?php echo $pri_badge; ?>; font-weight: 700;">
                                            <?php echo htmlspecialchars($ticket['priority_name']); ?>
                                        </span>
                                        <?php if (!$can_drag): ?>
                                            <i class="fa-solid fa-lock" style="color: var(--text-muted); font-size: 10px;" title="Atanmadığınız için taşıyamazsınız"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Card Body -->
                                <h5 style="font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 8px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="<?php echo htmlspecialchars($ticket['title']); ?>">
                                    <?php echo htmlspecialchars($ticket['title']); ?>
                                </h5>

                                <div style="display: flex; flex-direction: column; gap: 4px; font-size: 10px; color: var(--text-secondary); border-top: 1px solid var(--border-color); padding-top: 8px; margin-top: 8px;">
                                    <div>Müşteri: <strong><?php echo htmlspecialchars($ticket['cust_name'] ?? 'Genel'); ?></strong></div>
                                    <div>Branş Dalı: <strong><?php echo htmlspecialchars($ticket['product_service'] ?? 'Genel Destek'); ?></strong></div>
                                    <div style="margin-top: 4px; display: flex; align-items: center; gap: 4px; color: var(--text-muted);">
                                        <i class="fa-solid fa-user-gear"></i>
                                        <span><?php echo $ticket['assignee_name'] ? htmlspecialchars($ticket['assignee_name']) : 'Uzman Atanmamış'; ?></span>
                                    </div>
                                </div>

                                <!-- Overlay Link to detail -->
                                <a href="/destek_as/admin/ticket_detay.php?id=<?php echo $ticket['id']; ?>" style="position: absolute; bottom: 12px; right: 12px; color: var(--primary); font-size: 11px;" title="Detayları İncele">
                                    <i class="fa-solid fa-circle-arrow-right"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Drag Drop Scripts & Toasts -->
<script>
let draggedCard = null;

function dragCard(event) {
    draggedCard = event.currentTarget;
    event.dataTransfer.setData('text/plain', draggedCard.id);
    draggedCard.style.opacity = '0.5';
}

function allowDrop(event) {
    event.preventDefault();
}

function dropCard(event) {
    event.preventDefault();
    const col = event.currentTarget.closest('.kanban-col');
    if (!col || !draggedCard) return;

    const newStatusId = col.getAttribute('data-status-id');
    const ticketId = draggedCard.getAttribute('data-ticket-id');
    const cardToMove = draggedCard; // Capture local reference

    // Reset card opacity
    cardToMove.style.opacity = '1';

    // Call API
    const formData = new FormData();
    formData.append('ajax_update_status', '1');
    formData.append('ticket_id', ticketId);
    formData.append('status_id', newStatusId);

    fetch('kanban.php', {
        method: 'POST',
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error("Geçersiz JSON yanıtı:", text);
            throw new Error("Sunucudan geçersiz yanıt: " + text.substring(0, 100));
        }
    })
    .then(data => {
        if (data.status === 'success') {
            // Relocate card in DOM
            const cardsContainer = col.querySelector('.kanban-cards');
            const emptyDiv = cardsContainer.querySelector('.kanban-empty');
            if (emptyDiv) emptyDiv.remove();

            cardsContainer.appendChild(cardToMove);
            showNotificationToast(data.message, 'success');
            
            // Reload page to re-calculate numbers and columns cleanly
            setTimeout(() => {
                window.location.reload();
            }, 600);
        } else {
            showNotificationToast(data.message, 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        showNotificationToast('Bağlantı hatası oluştu! Hata: ' + err.message, 'danger');
    });

    draggedCard = null;
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

// Setup dragend to ensure reset opacity
document.addEventListener('dragend', function(e) {
    if (e.target.classList.contains('kanban-card')) {
        e.target.style.opacity = '1';
    }
});
</script>

<style>
.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md) !important;
    border-color: var(--primary) !important;
}
/* Style scrollbars of columns */
.kanban-cards::-webkit-scrollbar {
    width: 5px;
}
.kanban-cards::-webkit-scrollbar-track {
    background: transparent;
}
.kanban-cards::-webkit-scrollbar-thumb {
    background: rgba(178, 94, 44, 0.15);
    border-radius: 10px;
}
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
