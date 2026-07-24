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

// Handle AJAX Actions: Solve, Delete, Merge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];
    $ticket_id = intval($_POST['ticket_id'] ?? 0);

    if ($action !== 'merge_direct' && $ticket_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz bilet ID!']);
        exit;
    }

    try {
        if (!$is_manager) {
            echo json_encode(['status' => 'error', 'message' => 'Bu işlemi gerçekleştirmek için yetkiniz yok!']);
            exit;
        }

        if ($action === 'solve') {
            $stmtUp = $pdo->prepare("UPDATE tickets SET status_id = 10 WHERE id = ?");
            $stmtUp->execute([$ticket_id]);
            logActivity($pdo, "Bilet Kanban üzerinden kapatıldı/çözüldü (Bilet ID: {$ticket_id})", "tickets", $ticket_id);
            echo json_encode(['status' => 'success', 'message' => 'Talep başarıyla çözüldü olarak işaretlendi!']);
            exit;
        } 
        
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM ticket_assignments WHERE ticket_id = ?")->execute([$ticket_id]);
            $pdo->prepare("DELETE FROM ticket_messages WHERE ticket_id = ?")->execute([$ticket_id]);
            $pdo->prepare("DELETE FROM ticket_internal_notes WHERE ticket_id = ?")->execute([$ticket_id]);
            $pdo->prepare("DELETE FROM ticket_attachments WHERE ticket_id = ?")->execute([$ticket_id]);
            $pdo->prepare("DELETE FROM ticket_tags WHERE ticket_id = ?")->execute([$ticket_id]);
            $pdo->prepare("DELETE FROM ticket_relations WHERE ticket_id = ? OR related_ticket_id = ?")->execute([$ticket_id, $ticket_id]);
            $pdo->prepare("DELETE FROM ticket_tasks WHERE ticket_id = ?")->execute([$ticket_id]);
            $pdo->prepare("DELETE FROM ticket_time_entries WHERE ticket_id = ?")->execute([$ticket_id]);
            $pdo->prepare("DELETE FROM ticket_status_history WHERE ticket_id = ?")->execute([$ticket_id]);
            $pdo->prepare("DELETE FROM ticket_priority_history WHERE ticket_id = ?")->execute([$ticket_id]);
            $pdo->prepare("DELETE FROM sla_events WHERE ticket_id = ?")->execute([$ticket_id]);
            $pdo->prepare("DELETE FROM escalation_events WHERE ticket_id = ?")->execute([$ticket_id]);
            $pdo->prepare("DELETE FROM customer_ratings WHERE ticket_id = ?")->execute([$ticket_id]);
            $pdo->prepare("DELETE FROM email_messages WHERE ticket_id = ?")->execute([$ticket_id]);
            $pdo->prepare("DELETE FROM tickets WHERE id = ?")->execute([$ticket_id]);
            
            logActivity($pdo, "Bilet Kanban üzerinden tamamen silindi (Bilet ID: {$ticket_id})", "tickets", $ticket_id);
            echo json_encode(['status' => 'success', 'message' => 'Talep başarıyla silindi!']);
            exit;
        }

        if ($action === 'merge') {
            $target_number = trim($_POST['target_number'] ?? '');
            if (empty($target_number)) {
                echo json_encode(['status' => 'error', 'message' => 'Lütfen hedef bilet numarasını girin!']);
                exit;
            }

            $stmtSource = $pdo->prepare("SELECT ticket_number, title, description FROM tickets WHERE id = ?");
            $stmtSource->execute([$ticket_id]);
            $source = $stmtSource->fetch();

            if (!$source) {
                echo json_encode(['status' => 'error', 'message' => 'Kaynak bilet bulunamadı!']);
                exit;
            }

            $stmtTarget = $pdo->prepare("SELECT id FROM tickets WHERE ticket_number = ? AND company_id = ?");
            $stmtTarget->execute([$target_number, $company_id]);
            $target_id = $stmtTarget->fetchColumn();

            if (!$target_id) {
                echo json_encode(['status' => 'error', 'message' => 'Hedef bilet bulunamadı! (Lütfen doğru bilet numarasını girin, örn: YEB-2026-000010)']);
                exit;
            }

            if (intval($target_id) === intval($ticket_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Bir bileti kendisiyle birleştiremezsiniz!']);
                exit;
            }

            $merge_content = "[BİRLEŞTİRME] #" . $source['ticket_number'] . " numaralı talep bu taleple birleştirildi.\n\nKonu: " . $source['title'] . "\nAçıklama: " . $source['description'];
            $stmtMsg = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message_type, content) VALUES (?, ?, 'private', ?)");
            $stmtMsg->execute([$target_id, $user_id, $merge_content]);

            $stmtUpdateSource = $pdo->prepare("UPDATE tickets SET status_id = 15 WHERE id = ?");
            $stmtUpdateSource->execute([$ticket_id]);

            logActivity($pdo, "Bilet #{$source['ticket_number']}, #{$target_number} ile birleştirildi.", "tickets", $ticket_id);
            echo json_encode(['status' => 'success', 'message' => "Talepler başarıyla birleştirildi! Kaynak bilet durumu 'Birleştirildi' olarak güncellendi."]);
            exit;
        }

        if ($action === 'merge_direct') {
            $source_id = intval($_POST['source_id'] ?? 0);
            $target_id = intval($_POST['target_id'] ?? 0);

            if ($source_id <= 0 || $target_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Geçersiz bilet ID!']);
                exit;
            }

            if ($source_id === $target_id) {
                echo json_encode(['status' => 'error', 'message' => 'Bir bileti kendisiyle birleştiremezsiniz!']);
                exit;
            }

            $stmtSource = $pdo->prepare("SELECT ticket_number, title, description FROM tickets WHERE id = ?");
            $stmtSource->execute([$source_id]);
            $source = $stmtSource->fetch();

            $stmtTarget = $pdo->prepare("SELECT ticket_number FROM tickets WHERE id = ?");
            $stmtTarget->execute([$target_id]);
            $target = $stmtTarget->fetch();

            if (!$source || !$target) {
                echo json_encode(['status' => 'error', 'message' => 'Bilet bulunamadı!']);
                exit;
            }

            // Insert relationship into ticket_relations
            $stmtRel = $pdo->prepare("INSERT INTO ticket_relations (ticket_id, related_ticket_id, relation_type) VALUES (?, ?, 'merged')");
            $stmtRel->execute([$target_id, $source_id]);

            // Add private note/comment to target ticket
            $merge_content = "[BİRLEŞTİRME] #" . $source['ticket_number'] . " numaralı talep bu taleple birleştirildi.\n\nKonu: " . $source['title'] . "\nAçıklama: " . $source['description'];
            $stmtMsg = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message_type, content) VALUES (?, ?, 'private', ?)");
            $stmtMsg->execute([$target_id, $user_id, $merge_content]);

            // Set source ticket status to 15 (Birleştirildi)
            $stmtUpdateSource = $pdo->prepare("UPDATE tickets SET status_id = 15 WHERE id = ?");
            $stmtUpdateSource->execute([$source_id]);

            logActivity($pdo, "Bilet #{$source['ticket_number']}, #{$target['ticket_number']} ile birleştirildi.", "tickets", $source_id);
            echo json_encode(['status' => 'success', 'message' => "#{$source['ticket_number']} numaralı talep, başarıyla #{$target['ticket_number']} ile birleştirildi!"]);
            exit;
        }
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'İşlem hatası: ' . $e->getMessage()]);
        exit;
    }
}

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
    WHERE t.company_id = ? AND (t.status_id IN (2, 3, 5, 8) OR (t.status_id = 10 AND t.updated_at >= NOW() - INTERVAL 3 DAY))
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
                                 ondragover="allowDrop(event)"
                                 ondrop="dropOnCard(event, <?php echo $ticket['id']; ?>)"
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

                                <?php if ($is_manager): ?>
                                    <div class="card-actions" style="display: flex; align-items: center; gap: 10px; margin-top: 10px; border-top: 1px dashed var(--border-color); padding-top: 8px; font-size: 11px;">
                                        <!-- Solve -->
                                        <?php if ((int)$ticket['status_id'] !== 10): ?>
                                            <a href="#" onclick="kanbanAction(event, <?php echo $ticket['id']; ?>, 'solve')" style="color: var(--success); text-decoration: none; font-weight: 600;" title="Çözüldü Yap">
                                                <i class="fa-solid fa-check-double"></i> Çöz
                                            </a>
                                        <?php endif; ?>
                                        <!-- Merge -->
                                        <a href="#" onclick="startMergeMode(<?php echo $ticket['id']; ?>)" style="color: var(--primary); text-decoration: none; font-weight: 600;" title="Bilet Birleştir">
                                            <i class="fa-solid fa-link"></i> Birleştir
                                        </a>
                                        <!-- Delete -->
                                        <a href="#" onclick="kanbanAction(event, <?php echo $ticket['id']; ?>, 'delete')" style="color: var(--danger); text-decoration: none; font-weight: 600;" title="Bileti Sil">
                                            <i class="fa-solid fa-trash-can"></i> Sil
                                        </a>
                                        
                                        <!-- Detail Link -->
                                        <a href="/destek_as/admin/ticket_detay.php?id=<?php echo $ticket['id']; ?>" style="color: var(--text-muted); text-decoration: none; margin-left: auto;" title="Detayları İncele">
                                            <i class="fa-solid fa-circle-arrow-right" style="font-size: 13px;"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <!-- Overlay Link to detail for non-managers -->
                                    <a href="/destek_as/admin/ticket_detay.php?id=<?php echo $ticket['id']; ?>" style="position: absolute; bottom: 12px; right: 12px; color: var(--primary); font-size: 11px;" title="Detayları İncele">
                                        <i class="fa-solid fa-circle-arrow-right"></i>
                                    </a>
                                <?php endif; ?>
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

function kanbanAction(event, ticketId, action) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    if (action === 'delete') {
        if (!confirm('Bu talebi tamamen silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')) {
            return;
        }
    }

    let targetNumber = '';
    if (action === 'merge') {
        targetNumber = prompt('Bu talebi birleştirmek istediğiniz hedef bilet numarasını girin:\n(Örn: YEB-2026-000010)');
        if (targetNumber === null) return; // User cancelled
        targetNumber = targetNumber.trim();
        if (!targetNumber) {
            alert('Lütfen geçerli bir bilet numarası girin!');
            return;
        }
    }

    const formData = new FormData();
    formData.append('ajax_action', action);
    formData.append('ticket_id', ticketId);
    if (action === 'merge') {
        formData.append('target_number', targetNumber);
    }

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
            throw new Error("Sunucudan geçersiz yanıt.");
        }
    })
    .then(data => {
        if (data.status === 'success') {
            showNotificationToast(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotificationToast(data.message, 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        showNotificationToast('İşlem sırasında bağlantı hatası oluştu!', 'danger');
    });
}

let activeMergeTargetId = null;

function startMergeMode(ticketId) {
    if (activeMergeTargetId) {
        const prev = document.getElementById('ticket-' + activeMergeTargetId);
        if (prev) prev.classList.remove('merge-target-active');
    }

    if (activeMergeTargetId === ticketId) {
        // Toggle off if clicked again
        activeMergeTargetId = null;
        showNotificationToast('Birleştirme modu kapatıldı.', 'info');
        return;
    }

    activeMergeTargetId = ticketId;
    const card = document.getElementById('ticket-' + ticketId);
    if (card) {
        card.classList.add('merge-target-active');
        showNotificationToast('Birleştirme modu aktif. Sürüklediğiniz bileti bu biletin üzerine bırakın.', 'success');
    }
}

function dropOnCard(event, targetTicketId) {
    if (!activeMergeTargetId || activeMergeTargetId !== targetTicketId) {
        // Let it bubble up to column dropZone for normal status change
        return;
    }
    event.preventDefault();
    event.stopPropagation();

    if (!draggedCard) return;
    const sourceTicketId = draggedCard.getAttribute('data-ticket-id');

    if (parseInt(sourceTicketId) === parseInt(targetTicketId)) {
        showNotificationToast('Bir bileti kendisiyle birleştiremezsiniz!', 'danger');
        return;
    }

    const sourceNum = draggedCard.querySelector('small').innerText;
    const targetNum = document.getElementById('ticket-' + targetTicketId).querySelector('small').innerText;

    if (!confirm(`${sourceNum} numaralı talebi, ${targetNum} numaralı talep ile birleştirmek istediğinize emin misiniz?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('ajax_action', 'merge_direct');
    formData.append('source_id', sourceTicketId);
    formData.append('target_id', targetTicketId);

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
            throw new Error("Sunucudan geçersiz yanıt.");
        }
    })
    .then(data => {
        if (data.status === 'success') {
            showNotificationToast(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotificationToast(data.message, 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        showNotificationToast('Birleştirme hatası oluştu!', 'danger');
    });
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
.kanban-card.merge-target-active {
    border: 2px dashed var(--primary) !important;
    background: rgba(178, 94, 44, 0.08) !important;
    box-shadow: 0 0 15px rgba(178, 94, 44, 0.25) !important;
    animation: pulseBorder 1.5s infinite;
}
@keyframes pulseBorder {
    0% { border-color: var(--primary); }
    50% { border-color: transparent; }
    100% { border-color: var(--primary); }
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
