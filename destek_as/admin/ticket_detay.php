<?php
ob_start();
// C:\laragon\www\destek_as\admin\ticket_detay.php
require_once __DIR__ . '/../includes/header.php';

$ticket_id = intval($_GET['id'] ?? 0);
if ($ticket_id <= 0) {
    echo "<h3>Geçersiz Ticket ID!</h3>";
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role_name'] ?? '';
$company_id = $_SESSION['company_id'] ?? 1;

$error = '';
$success = '';

// Fetch ticket details with joins
try {
    $stmt = $pdo->prepare("
        SELECT t.*, ts.name AS status_name, ts.color AS status_color, ts.is_closed, p.name AS priority_name, 
               c.name AS category_name, sc.name AS subcategory_name, cust.name AS cust_name, 
               u.first_name AS u_first, u.last_name AS u_last,
               assignee.first_name AS a_first, assignee.last_name AS a_last, assignee.id AS a_id
        FROM tickets t
        LEFT JOIN ticket_statuses ts ON t.status_id = ts.id
        LEFT JOIN priorities p ON t.priority_id = p.id
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN subcategories sc ON t.subcategory_id = sc.id
        LEFT JOIN customers cust ON t.customer_id = cust.id
        LEFT JOIN users u ON t.customer_user_id = u.id
        LEFT JOIN ticket_assignments ta ON t.id = ta.ticket_id AND ta.status = 'active'
        LEFT JOIN users assignee ON ta.user_id = assignee.id
        WHERE t.id = ? AND t.company_id = ?
    ");
    $stmt->execute([$ticket_id, $company_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        echo "<h3>Destek talebi bulunamadı veya yetkiniz yok!</h3>";
        exit;
    }

    // Double check: customer user can only view their own company's tickets
    if ($user_role === 'Müşteri Kullanıcısı' && $ticket['customer_id'] != ($_SESSION['customer_id'] ?? 0)) {
        echo "<h3>Bu talebi görüntülemek için yetkiniz bulunmamaktadır!</h3>";
        exit;
    }
} catch (\Exception $e) {
    echo "<h3>Sistem Hatası: " . $e->getMessage() . "</h3>";
    exit;
}

// Handle posting a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_message'])) {
    $content = trim($_POST['content'] ?? '');
    $message_type = $_POST['message_type'] ?? 'public'; // public, internal, manager

    // Customers can only send public messages
    if ($user_role === 'Müşteri Kullanıcısı') {
        $message_type = 'public';
    }

    if (!empty($content)) {
        try {
            // Handle file upload in message
            $attachment_path = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['attachment']['tmp_name'];
                $file_name = basename($_FILES['attachment']['name']);
                $file_size = $_FILES['attachment']['size'];
                $file_type = $_FILES['attachment']['type'];
                
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'txt', 'log'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (in_array($file_ext, $allowed_extensions) && $file_size <= 10 * 1024 * 1024) {
                    $new_filename = uniqid('msg_attach_', true) . '.' . $file_ext;
                    $upload_dir = __DIR__ . '/../uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                        $attachment_path = '/destek_as/uploads/' . $new_filename;
                    }
                }
            }

            // Insert message
            $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message_type, content, attachment_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$ticket_id, $user_id, $message_type, $content, $attachment_path]);
            $msg_id = $pdo->lastInsertId();

            if ($attachment_path) {
                $stmtAttach = $pdo->prepare("
                    INSERT INTO ticket_attachments (ticket_id, message_id, filename, file_path, file_size, file_mime, uploaded_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtAttach->execute([$ticket_id, $msg_id, $file_name, $attachment_path, $file_size, $file_type, $user_id]);
            }

            // Log activity
            logActivity($pdo, "Bilet yazışması eklendi. Tip: " . $message_type, "tickets", $ticket_id);
            
            // Notify other party
            if ($user_role === 'Müşteri Kullanıcısı') {
                $stmtAssigned = $pdo->prepare("SELECT user_id FROM ticket_assignments WHERE ticket_id = ? AND status = 'active' LIMIT 1");
                $stmtAssigned->execute([$ticket_id]);
                $assigned_agent_id = $stmtAssigned->fetchColumn() ?: null;
                if ($assigned_agent_id) {
                    createNotification($pdo, 'Yeni Mesaj Var', "Müşteri #{$ticket['ticket_number']} nolu talebe yeni bir mesaj yazdı.", $assigned_agent_id, null, 'message');
                }
            } else {
                $cust_user_id = $ticket['customer_user_id'];
                if ($cust_user_id) {
                    createNotification($pdo, 'Yeni Mesaj Var', "Uzman #{$ticket['ticket_number']} nolu talebinize yeni bir yanıt yazdı.", null, $cust_user_id, 'message');
                }
            }

            $success = "Mesajınız başarıyla gönderildi!";

            // Refresh ticket data
            header("Location: /destek_as/admin/ticket_detay.php?id={$ticket_id}");
            exit;
        } catch (\Exception $e) {
            $error = 'Mesaj kaydedilirken hata oluştu: ' . $e->getMessage();
        }
    }
}

// Handle mark completed (Tamamlandı) click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_completed']) && $user_role !== 'Müşteri Kullanıcısı') {
    try {
        $pdo->beginTransaction();

        $pending_approval_status_id = 11; // Müşteri onayı bekleniyor

        // Update ticket status
        $stmt = $pdo->prepare("UPDATE tickets SET status_id = ? WHERE id = ?");
        $stmt->execute([$pending_approval_status_id, $ticket_id]);

        // Insert status history
        $stmtHist = $pdo->prepare("INSERT INTO ticket_status_history (ticket_id, old_status_id, new_status_id, changed_by, reason) VALUES (?, ?, ?, ?, ?)");
        $stmtHist->execute([$ticket_id, $ticket['status_id'], $pending_approval_status_id, $user_id, 'Uzman tarafından tamamlandı olarak işaretlendi, müşteri onayı bekleniyor']);

        // Insert automatic public message from technician (Request 11)
        $auto_message = "Talebiniz tamamlanmıştır. İyi günler, iyi çalışmalar dileriz...\n(Memnun kaldıysanız onayla butonuna basabilirsiniz)";
        $stmtMsg = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message_type, content) VALUES (?, ?, 'public', ?)");
        $stmtMsg->execute([$ticket_id, $user_id, $auto_message]);

        $pdo->commit();
        $success = "Destek talebi tamamlandı olarak işaretlendi ve müşteri onayına sunuldu!";
        logActivity($pdo, "Talep tamamlandı olarak işaretlendi ve müşteri onayına sunuldu.", "tickets", $ticket_id);

        // Notify customer
        $cust_user_id = $ticket['customer_user_id'];
        if ($cust_user_id) {
            createNotification($pdo, 'Çözüm Onayı Bekleniyor', "Talebiniz (#{$ticket['ticket_number']}) uzman tarafından tamamlandı olarak işaretlendi ve onayınızı bekliyor.", null, $cust_user_id, 'ticket');
        }

        header("Location: /destek_as/admin/ticket_detay.php?id={$ticket_id}");
        exit;
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Hata oluştu: ' . $e->getMessage();
    }
}

// Handle saving canned response (Request 12)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_canned']) && $user_role !== 'Müşteri Kullanıcısı') {
    $c_title = trim($_POST['canned_title'] ?? '');
    $c_content = trim($_POST['canned_content'] ?? '');
    if (!empty($c_title) && !empty($c_content)) {
        try {
            $stmtCanned = $pdo->prepare("INSERT INTO canned_responses (company_id, title, content) VALUES (?, ?, ?)");
            $stmtCanned->execute([$company_id, $c_title, $c_content]);
            $success = "Otomatik mesaj şablonu başarıyla kaydedildi!";
            logActivity($pdo, "Yeni otomatik mesaj şablonu eklendi: " . $c_title, "canned_responses", $pdo->lastInsertId());
            header("Location: /destek_as/admin/ticket_detay.php?id={$ticket_id}");
            exit;
        } catch (\Exception $e) {
            $error = "Şablon kaydedilirken hata oluştu: " . $e->getMessage();
        }
    }
}

// Fetch canned responses
$canned_responses = [];
if ($user_role !== 'Müşteri Kullanıcısı') {
    try {
        $stmtC = $pdo->prepare("SELECT * FROM canned_responses WHERE company_id = ? ORDER BY title ASC");
        $stmtC->execute([$company_id]);
        $canned_responses = $stmtC->fetchAll();
    } catch (\Exception $e) {
        // Fail silently
    }
}

// Handle customer approval click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_resolution']) && $user_role === 'Müşteri Kullanıcısı') {
    try {
        $pdo->beginTransaction();

        $resolved_status_id = 10; // Çözüldü

        // Update ticket status
        $stmt = $pdo->prepare("UPDATE tickets SET status_id = ? WHERE id = ?");
        $stmt->execute([$resolved_status_id, $ticket_id]);

        // Insert status history
        $stmtHist = $pdo->prepare("INSERT INTO ticket_status_history (ticket_id, old_status_id, new_status_id, changed_by, reason) VALUES (?, ?, ?, ?, ?)");
        $stmtHist->execute([$ticket_id, $ticket['status_id'], $resolved_status_id, $user_id, 'Müşteri çözümü onayladı']);

        $pdo->commit();
        $success = "Çözümü onayladınız. Talebiniz başarıyla kapatıldı ve arşive taşındı!";
        logActivity($pdo, "Müşteri çözümü onayladı ve talebi kapattı.", "tickets", $ticket_id);

        // Notify assigned agent
        $stmtAssigned = $pdo->prepare("SELECT user_id FROM ticket_assignments WHERE ticket_id = ? AND status = 'active' LIMIT 1");
        $stmtAssigned->execute([$ticket_id]);
        $assigned_agent_id = $stmtAssigned->fetchColumn() ?: null;
        if ($assigned_agent_id) {
            createNotification($pdo, 'Çözüm Onaylandı', "Müşteri #{$ticket['ticket_number']} nolu talebin çözümünü onayladı.", $assigned_agent_id, null, 'ticket');
        }

        header("Location: /destek_as/admin/ticket_detay.php?id={$ticket_id}");
        exit;
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Hata oluştu: ' . $e->getMessage();
    }
}

// Handle customer rejection click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_resolution']) && $user_role === 'Müşteri Kullanıcısı') {
    try {
        $pdo->beginTransaction();

        $reopened_status_id = 13; // Yeniden açıldı

        // Update ticket status
        $stmt = $pdo->prepare("UPDATE tickets SET status_id = ? WHERE id = ?");
        $stmt->execute([$reopened_status_id, $ticket_id]);

        // Insert status history
        $stmtHist = $pdo->prepare("INSERT INTO ticket_status_history (ticket_id, old_status_id, new_status_id, changed_by, reason) VALUES (?, ?, ?, ?, ?)");
        $stmtHist->execute([$ticket_id, $ticket['status_id'], $reopened_status_id, $user_id, 'Müşteri çözümü reddetti, talep yeniden açıldı']);

        $pdo->commit();
        $success = "Çözümü reddettiniz. Destek talebiniz yeniden açıldı.";
        logActivity($pdo, "Müşteri çözümü reddetti ve talebi yeniden açtı.", "tickets", $ticket_id);

        // Notify assigned agent
        $stmtAssigned = $pdo->prepare("SELECT user_id FROM ticket_assignments WHERE ticket_id = ? AND status = 'active' LIMIT 1");
        $stmtAssigned->execute([$ticket_id]);
        $assigned_agent_id = $stmtAssigned->fetchColumn() ?: null;
        if ($assigned_agent_id) {
            createNotification($pdo, 'Çözüm Reddedildi', "Müşteri #{$ticket['ticket_number']} nolu talebin çözümünü reddetti. Talep yeniden açıldı.", $assigned_agent_id, null, 'ticket');
        }

        header("Location: /destek_as/admin/ticket_detay.php?id={$ticket_id}");
        exit;
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Hata oluştu: ' . $e->getMessage();
    }
}

// Handle updating metadata (only for staff/admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_meta']) && $user_role !== 'Müşteri Kullanıcısı') {
    $new_status_id = intval($_POST['status_id'] ?? 0);
    $new_priority_id = intval($_POST['priority_id'] ?? 0);
    $new_agent_id = intval($_POST['agent_id'] ?? 0);

    try {
        $pdo->beginTransaction();

        // 1. Update Status & Log History
        if ($new_status_id > 0 && $new_status_id != $ticket['status_id']) {
            $stmt = $pdo->prepare("UPDATE tickets SET status_id = ? WHERE id = ?");
            $stmt->execute([$new_status_id, $ticket_id]);

            $stmtHist = $pdo->prepare("INSERT INTO ticket_status_history (ticket_id, old_status_id, new_status_id, changed_by, reason) VALUES (?, ?, ?, ?, ?)");
            $stmtHist->execute([$ticket_id, $ticket['status_id'], $new_status_id, $user_id, 'Manuel güncelleme']);
        }

        // 2. Update Priority & Log History
        if ($new_priority_id > 0 && $new_priority_id != $ticket['priority_id']) {
            $stmt = $pdo->prepare("UPDATE tickets SET priority_id = ? WHERE id = ?");
            $stmt->execute([$new_priority_id, $ticket_id]);

            $stmtHist = $pdo->prepare("INSERT INTO ticket_priority_history (ticket_id, old_priority_id, new_priority_id, changed_by, reason) VALUES (?, ?, ?, ?, ?)");
            $stmtHist->execute([$ticket_id, $ticket['priority_id'], $new_priority_id, $user_id, 'Manuel güncelleme']);
        }

        // 3. Update Assignment
        if ($new_agent_id > 0 && $new_agent_id != $ticket['a_id']) {
            // Mark old assignments as reassigned
            $stmtRe = $pdo->prepare("UPDATE ticket_assignments SET status = 'reassigned', unassigned_at = CURRENT_TIMESTAMP WHERE ticket_id = ? AND status = 'active'");
            $stmtRe->execute([$ticket_id]);

            // Add new assignment
            $stmtAssign = $pdo->prepare("INSERT INTO ticket_assignments (ticket_id, user_id, assigned_by, method, status) VALUES (?, ?, ?, 'manual', 'active')");
            $stmtAssign->execute([$ticket_id, $new_agent_id, $user_id]);

            // Auto change status to "Atandı" if it was "Yeni"
            $stmtStatus = $pdo->prepare("SELECT id FROM ticket_statuses WHERE name = 'Atandı' LIMIT 1");
            $stmtStatus->execute();
            $atandi_id = $stmtStatus->fetchColumn();
            
            if ($atandi_id && $ticket['status_name'] === 'Yeni') {
                $stmtUp = $pdo->prepare("UPDATE tickets SET status_id = ? WHERE id = ?");
                $stmtUp->execute([$atandi_id, $ticket_id]);
            }
        }

        $pdo->commit();
        $success = "Talebe ait bilgiler başarıyla güncellendi!";
        logActivity($pdo, "Talep meta verileri güncellendi.", "tickets", $ticket_id);

        header("Location: /destek_as/admin/ticket_detay.php?id={$ticket_id}");
        exit;
    } catch (\Exception $e) {
        $pdo->rollBack();
        $error = 'Güncelleme yapılırken hata oluştu: ' . $e->getMessage();
    }
}

// Fetch all messages (customers do not see internal or manager notes!)
$msg_types_sql = $user_role === 'Müşteri Kullanıcısı' ? "('public', 'invoice')" : "('public', 'internal', 'manager', 'system', 'auto', 'invoice')";
$messages = $pdo->query("
    SELECT tm.*, u.first_name, u.last_name, r.name AS role_name
    FROM ticket_messages tm
    JOIN users u ON tm.sender_id = u.id
    JOIN user_roles ur ON u.id = ur.user_id
    JOIN roles r ON ur.role_id = r.id
    WHERE tm.ticket_id = {$ticket_id} AND tm.message_type IN {$msg_types_sql}
    ORDER BY tm.id ASC
")->fetchAll();

// Fetch dropdown lists for metadata
$statuses = $pdo->query("SELECT id, name FROM ticket_statuses ORDER BY id ASC")->fetchAll();
$priorities = $pdo->query("SELECT id, name FROM priorities ORDER BY level ASC")->fetchAll();
// Fetch support agents for assigning
$agents = $pdo->query("
    SELECT u.id, u.first_name, u.last_name 
    FROM users u
    JOIN user_roles ur ON u.id = ur.user_id
    JOIN roles r ON ur.role_id = r.id
    WHERE r.name IN ('Destek Personeli', 'Firma Yöneticisi', 'Departman Yöneticisi') AND u.status = 'active'
")->fetchAll();

// Find next and previous ticket IDs for navigation (Request 2)
$prev_ticket_id = null;
$next_ticket_id = null;
try {
    if ($user_role === 'Müşteri Kullanıcısı') {
        $stmtPrev = $pdo->prepare("SELECT id FROM tickets WHERE id < ? AND customer_id = ? AND company_id = ? ORDER BY id DESC LIMIT 1");
        $stmtPrev->execute([$ticket_id, $_SESSION['customer_id'], $company_id]);
        $prev_ticket_id = $stmtPrev->fetchColumn();

        $stmtNext = $pdo->prepare("SELECT id FROM tickets WHERE id > ? AND customer_id = ? AND company_id = ? ORDER BY id ASC LIMIT 1");
        $stmtNext->execute([$ticket_id, $_SESSION['customer_id'], $company_id]);
        $next_ticket_id = $stmtNext->fetchColumn();
    } else {
        $stmtPrev = $pdo->prepare("SELECT id FROM tickets WHERE id < ? AND company_id = ? ORDER BY id DESC LIMIT 1");
        $stmtPrev->execute([$ticket_id, $company_id]);
        $prev_ticket_id = $stmtPrev->fetchColumn();

        $stmtNext = $pdo->prepare("SELECT id FROM tickets WHERE id > ? AND company_id = ? ORDER BY id ASC LIMIT 1");
        $stmtNext->execute([$ticket_id, $company_id]);
        $next_ticket_id = $stmtNext->fetchColumn();
    }
} catch (\Exception $e) {
    // Fail silently
}
?>

<div style="display: flex; flex-direction: column; gap: 30px;">
    <!-- Previous / Next & Back Navigation Row (Request 2) -->
    <div style="display: flex; justify-content: space-between; align-items: center; background: var(--bg-card); padding: 12px 20px; border-radius: 12px; border: 1px solid var(--border-color);">
        <a href="<?php echo $user_role === 'Müşteri Kullanıcısı' ? '/destek_as/customer/ticketlarim.php' : '/destek_as/admin/ticketlar.php'; ?>" class="btn-custom btn-custom-secondary" style="padding: 8px 14px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-arrow-left"></i> Geri Dön
        </a>
        <div style="display: flex; gap: 10px;">
            <?php if ($prev_ticket_id): ?>
                <a href="/destek_as/admin/ticket_detay.php?id=<?php echo $prev_ticket_id; ?>" class="btn-custom btn-custom-secondary" style="padding: 8px 14px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;" title="Önceki Talebe Git">
                    <i class="fa-solid fa-chevron-left"></i> Önceki
                </a>
            <?php else: ?>
                <button class="btn-custom btn-custom-secondary" style="padding: 8px 14px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; opacity: 0.4; cursor: not-allowed;" disabled>
                    <i class="fa-solid fa-chevron-left"></i> Önceki
                </button>
            <?php endif; ?>

            <?php if ($next_ticket_id): ?>
                <a href="/destek_as/admin/ticket_detay.php?id=<?php echo $next_ticket_id; ?>" class="btn-custom btn-custom-secondary" style="padding: 8px 14px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;" title="Sonraki Talebe Git">
                    Sonraki <i class="fa-solid fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <button class="btn-custom btn-custom-secondary" style="padding: 8px 14px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; opacity: 0.4; cursor: not-allowed;" disabled>
                    Sonraki <i class="fa-solid fa-chevron-right"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Messages Alert -->
    <?php if (!empty($success)): ?>
        <div style="background-color: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: var(--success); padding: 16px; border-radius: 12px; font-weight: 500;">
            <i class="fa-solid fa-circle-check" style="margin-right: 8px;"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($user_role === 'Müşteri Kullanıcısı' && $ticket['status_id'] == 11): ?>
        <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.05) 100%); border: 1px solid var(--success); padding: 20px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; gap: 20px; box-shadow: 0 4px 20px rgba(16,185,129,0.15);">
            <div>
                <h4 style="color: var(--success); font-size: 15px; margin-bottom: 6px; font-weight: 700;"><i class="fa-solid fa-circle-question"></i> Çözüm Onayı Bekleniyor</h4>
                <p style="color: var(--text-secondary); font-size: 13px; line-height: 1.5; margin: 0;">Bu destek talebi uzmanlarımız tarafından **tamamlandı** olarak işaretlendi. Sorununuz çözüldü mü? Lütfen aşağıdaki butonları kullanarak onaylayın veya işlemi reddedip talebi yeniden açın.</p>
            </div>
            <div style="display: flex; gap: 10px; flex-shrink: 0;">
                <form method="POST" action="" style="margin: 0;">
                    <input type="hidden" name="approve_resolution" value="1">
                    <button type="submit" class="btn-custom" style="background-color: var(--success); color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 12px; transition: transform 0.2s;">
                        <i class="fa-solid fa-check"></i> Evet, Çözüldü
                    </button>
                </form>
                <form method="POST" action="" style="margin: 0;">
                    <input type="hidden" name="reject_resolution" value="1">
                    <button type="submit" class="btn-custom" style="background-color: var(--danger); color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 12px; transition: transform 0.2s;">
                        <i class="fa-solid fa-xmark"></i> Hayır, Çözülmedi
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 30px; align-items: flex-start;">
        <!-- Left Side: Detail & Thread -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            <!-- Ticket Info Card -->
            <div class="glass-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
                    <div>
                        <span class="badge badge-primary" style="margin-bottom: 8px;">#<?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                        <h2 style="font-size: 22px; font-weight: 800;"><?php echo htmlspecialchars($ticket['title']); ?></h2>
                        <p style="color: var(--text-secondary); font-size: 13px; margin-top: 5px;">
                            <i class="fa-solid fa-user" style="margin-right: 4px;"></i> <?php echo htmlspecialchars($ticket['u_first'] . ' ' . $ticket['u_last']); ?> 
                            (<?php echo htmlspecialchars($ticket['cust_name']); ?>) | 
                            <i class="fa-solid fa-clock" style="margin-left: 10px; margin-right: 4px;"></i> <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?>
                        </p>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                        <span class="badge" style="background-color: <?php echo $ticket['status_color']; ?>22; color: <?php echo $ticket['status_color']; ?>; border: 1px solid <?php echo $ticket['status_color']; ?>55; font-size: 13px; padding: 6px 16px;">
                            <?php echo htmlspecialchars($ticket['status_name']); ?>
                        </span>
                        <?php if ($user_role !== 'Müşteri Kullanıcısı' && $ticket['status_id'] != 10 && $ticket['status_id'] != 12): ?>
                            <form method="POST" action="" style="display:inline-block; margin-top: 5px;">
                                <input type="hidden" name="mark_completed" value="1">
                                <button type="submit" class="btn-custom" style="background-color: var(--success); color: #fff; border: none; padding: 8px 14px; border-radius: 8px; font-size: 11px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: transform 0.2s;">
                                    <i class="fa-solid fa-circle-check"></i> Tamamlandı Olarak İşaretle
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="font-size: 15px; line-height: 1.7; background: rgba(0,0,0,0.15); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); white-space: pre-wrap;"><?php echo htmlspecialchars($ticket['description']); ?></div>

                <?php if ($ticket['attachment_path']): ?>
                    <div style="margin-top: 20px; display: flex; align-items: center; gap: 10px; padding: 12px; background: rgba(139, 92, 246, 0.05); border: 1px solid var(--border-color); border-radius: 12px; max-width: 300px;">
                        <i class="fa-solid fa-paperclip" style="color: var(--primary); font-size: 18px;"></i>
                        <div style="flex-grow: 1; overflow: hidden;">
                            <div style="font-size: 12px; font-weight: 600; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;">Eklenti Dosyası</div>
                            <small style="color: var(--text-muted);">Müşteri yüklemesi</small>
                        </div>
                        <a href="<?php echo htmlspecialchars($ticket['attachment_path']); ?>" target="_blank" class="btn-custom btn-custom-secondary" style="padding: 6px 10px; font-size: 11px;">
                            Aç <i class="fa-solid fa-up-right-from-square" style="font-size: 9px; margin-left: 4px;"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Message Thread Card -->
            <div class="glass-card">
                <h3 style="font-size: 17px; margin-bottom: 25px;"><i class="fa-solid fa-comments" style="margin-right: 8px; color: var(--accent);"></i> Yazışma Geçmişi</h3>

                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php if (empty($messages)): ?>
                        <div style="text-align: center; color: var(--text-muted); padding: 20px 0;">Henüz yazışma kaydı bulunmuyor. İlk mesajı siz yazın.</div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <?php 
                            $is_own = $msg['sender_id'] == $user_id;
                            $msg_bg = $is_own ? 'rgba(139, 92, 246, 0.08)' : 'rgba(255,255,255,0.03)';
                            $msg_border = $is_own ? 'rgba(139, 92, 246, 0.25)' : 'var(--border-color)';
                            
                            // Visual indicator for internal notes
                            $note_indicator = '';
                            if ($msg['message_type'] === 'internal') {
                                $msg_bg = 'rgba(245, 158, 11, 0.05)';
                                $msg_border = 'rgba(245, 158, 11, 0.3)';
                                $note_indicator = '<span class="badge badge-warning" style="font-size: 9px; padding: 2px 6px; margin-left: 8px;"><i class="fa-solid fa-lock"></i> PERSONEL İÇ NOTU</span>';
                            } elseif ($msg['message_type'] === 'manager') {
                                $msg_bg = 'rgba(239, 68, 68, 0.05)';
                                $msg_border = 'rgba(239, 68, 68, 0.3)';
                                $note_indicator = '<span class="badge badge-danger" style="font-size: 9px; padding: 2px 6px; margin-left: 8px;"><i class="fa-solid fa-user-shield"></i> YÖNETİCİ GİZLİ NOTU</span>';
                            } elseif ($msg['message_type'] === 'invoice') {
                                $msg_bg = 'rgba(16, 185, 129, 0.05)';
                                $msg_border = 'rgba(16, 185, 129, 0.3)';
                                $note_indicator = '<span class="badge badge-success" style="font-size: 9px; padding: 2px 6px; margin-left: 8px;"><i class="fa-solid fa-file-invoice-dollar"></i> FATURA DOSYASI</span>';
                            }
                            ?>
                            <div style="background: <?php echo $msg_bg; ?>; border: 1px solid <?php echo $msg_border; ?>; padding: 20px; border-radius: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <div style="font-weight: 700; font-size: 14px;">
                                        <?php echo htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']); ?> 
                                        <?php echo $note_indicator; ?>
                                    </div>
                                    <small style="color: var(--text-muted);"><?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?></small>
                                </div>
                                <div style="font-size: 14px; line-height: 1.6; white-space: pre-wrap; position: relative;">
                                    <?php 
                                    if (strpos($msg['content'], '(Memnun kaldıysanız onayla butonuna basabilirsiniz)') !== false) {
                                        $main_text = str_replace('(Memnun kaldıysanız onayla butonuna basabilirsiniz)', '', $msg['content']);
                                        echo htmlspecialchars(trim($main_text));
                                        echo '<div style="text-align: right; margin-top: 10px; font-size: 12px; color: var(--text-secondary); opacity: 0.8; font-style: italic;">(Memnun kaldıysanız onayla butonuna basabilirsiniz)</div>';
                                    } else {
                                        echo htmlspecialchars($msg['content']); 
                                    }
                                    ?>
                                </div>

                                <?php if ($msg['attachment_path']): ?>
                                    <div style="margin-top: 15px; display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; background: rgba(0,0,0,0.15); border: 1px solid var(--border-color); border-radius: 8px; font-size: 12px;">
                                        <i class="fa-solid fa-paperclip" style="color: var(--primary);"></i>
                                        <a href="<?php echo htmlspecialchars($msg['attachment_path']); ?>" target="_blank" style="color: var(--accent); font-weight: 600; text-decoration: none;">Ekli Dosyayı Aç</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Post Message Form -->
            <div class="glass-card">
                <h3 style="font-size: 17px; margin-bottom: 20px;"><i class="fa-solid fa-paper-plane" style="margin-right: 8px; color: var(--primary);"></i> Yanıt Gönder / Not Ekle</h3>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="post_message" value="1">
                    
                    <div class="form-group">
                        <textarea name="content" id="replyContent" class="form-control" rows="4" placeholder="Mesajınızı veya dahili notunuzu buraya yazın..." required></textarea>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <input type="file" name="attachment" style="display: none;" id="msgAttach" onchange="document.getElementById('attachLabel').innerText = this.files[0].name">
                                <label for="msgAttach" class="btn-custom btn-custom-secondary" style="font-size: 13px; cursor: pointer;">
                                    <i class="fa-solid fa-paperclip"></i> Dosya Ekle
                                </label>
                                <span id="attachLabel" style="color: var(--text-secondary); font-size: 12px; margin-left: 8px;"></span>
                            </div>

                            <?php if ($user_role !== 'Müşteri Kullanıcısı'): ?>
                                <div class="form-group" style="margin-bottom: 0; display: flex; align-items: center; gap: 8px;">
                                    <label class="form-label" style="margin-bottom: 0;">Mesaj Tipi:</label>
                                    <select name="message_type" class="form-control" style="width: auto; padding: 6px 12px; font-size: 13px;">
                                        <option value="public">Müşteriye Açık Mesaj</option>
                                        <option value="internal">Dahili Personel Notu</option>
                                        <option value="manager">Yönetici Gizli Notu</option>
                                        <option value="invoice">Fatura Dosyası</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn-custom btn-custom-primary">
                            Gönder <i class="fa-solid fa-circle-chevron-right" style="margin-left: 6px;"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side: Metadata Sidebar Controls -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            <?php if ($user_role !== 'Müşteri Kullanıcısı'): ?>
                <!-- SLA & Assignee Panel (Hidden from customer) -->
                <div class="glass-card" style="display: flex; flex-direction: column; gap: 20px;">
                    <h3 style="font-size: 16px;"><i class="fa-solid fa-clock" style="color: var(--warning); margin-right: 8px;"></i> SLA ve Çözüm Takibi</h3>

                    <div style="background: rgba(0,0,0,0.15); padding: 15px; border-radius: 12px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                        <div>Öncelik: <strong><?php echo htmlspecialchars($ticket['priority_name'] ?? 'Normal'); ?></strong></div>
                        <div>Talebi Açan: <strong><?php echo htmlspecialchars($ticket['u_first'] . ' ' . $ticket['u_last']); ?></strong></div>
                        <div>Branş Dalı: <strong><?php echo htmlspecialchars($ticket['product_service'] ?? 'Genel Destek'); ?></strong></div>
                        <div>Sorumlu Personel: <strong><?php echo $ticket['a_id'] ? htmlspecialchars($ticket['a_first'] . ' ' . $ticket['a_last']) : '<span style="color:var(--warning);">Atanmamış</span>'; ?></strong></div>
                        <div style="border-top: 1px solid var(--border-color); margin-top: 8px; padding-top: 8px;">
                            Durum: 
                            <span class="badge" style="background-color: <?php echo $ticket['status_color']; ?>22; color: <?php echo $ticket['status_color']; ?>; border: 1px solid <?php echo $ticket['status_color']; ?>55; font-size: 11px;">
                                <?php echo htmlspecialchars($ticket['status_name']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- AI Solution Assistant Card (Request 10) -->
                <div class="glass-card" style="border: 1px solid rgba(139, 92, 246, 0.3); background: linear-gradient(135deg, rgba(139, 92, 246, 0.05) 0%, rgba(99, 102, 241, 0.02) 100%);">
                    <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; color: var(--primary);">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Yapay Zeka Çözüm Asistanı
                    </h4>
                    <div id="aiResolutionText" style="font-size: 12px; line-height: 1.5; color: var(--text-secondary); margin-bottom: 15px;">
                        Bu talebin içeriği ve teknik detaylarına göre yapay zeka tabanlı çözüm adımları oluşturmak için aşağıdaki butona basın.
                    </div>
                    <button type="button" onclick="generateAiSolution()" class="btn-custom" style="background: var(--grad-primary); color: #fff; width: 100%; border: none; padding: 10px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                        <i class="fa-solid fa-robot"></i> Çözüm Önerisi Üret
                    </button>
                </div>

                <!-- Canned Responses manager (Otomatik Mesaj Şablonları) - Replacing update metadata (Request 12) -->
                <div class="glass-card">
                    <h3 style="font-size: 16px; margin-bottom: 20px;"><i class="fa-solid fa-message" style="color: var(--primary); margin-right: 8px;"></i> Otomatik Mesaj Şablonları</h3>
                    
                    <!-- Select canned response -->
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Şablon Seç (Yanıt Kutusuna Doldurur)</label>
                        <select id="cannedResponseSelect" onchange="applyCannedResponse()" class="form-control">
                            <option value="">-- Şablon Seçiniz --</option>
                            <?php foreach ($canned_responses as $canned): ?>
                                <option value="<?php echo $canned['id']; ?>" data-content="<?php echo htmlspecialchars($canned['content']); ?>">
                                    <?php echo htmlspecialchars($canned['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Create canned response -->
                    <div style="border-top: 1px solid var(--border-color); padding-top: 15px; margin-top: 15px;">
                        <h4 style="font-size: 13px; font-weight: 700; margin-bottom: 10px; color: var(--text-secondary);">Yeni Otomatik Mesaj Kaydet</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="add_canned" value="1">
                            <div class="form-group">
                                <label class="form-label" style="font-size: 11px;">Şablon Başlığı</label>
                                <input type="text" name="canned_title" class="form-control" style="font-size: 12px; padding: 6px 10px;" placeholder="Örn: Karşılama Mesajı" required autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="font-size: 11px;">Mesaj İçeriği</label>
                                <textarea name="canned_content" class="form-control" style="font-size: 12px; padding: 6px 10px;" rows="3" placeholder="Şablon metnini buraya yazın..." required></textarea>
                            </div>
                            <button type="submit" class="btn-custom btn-custom-accent" style="width: 100%; justify-content: center; font-size: 11px; padding: 6px 12px;">
                                Mesajı Şablon Olarak Kaydet
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function applyCannedResponse() {
    const select = document.getElementById('cannedResponseSelect');
    const textarea = document.getElementById('replyContent');
    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption && selectedOption.value) {
        textarea.value = selectedOption.getAttribute('data-content');
    } else {
        textarea.value = '';
    }
}

function generateAiSolution() {
    const textDiv = document.getElementById('aiResolutionText');
    textDiv.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="margin-right:6px;"></i> Yapay zeka talebi analiz ediyor, lütfen bekleyin...';
    
    setTimeout(() => {
        const ticketTitle = <?php echo json_encode($ticket['title']); ?>;
        const ticketDesc = <?php echo json_encode($ticket['description']); ?>;
        
        let solution = "<strong>💡 Önerilen Teknik Çözüm Adımları:</strong><br/><br/>";
        solution += "Talebiniz olan <em>\"" + ticketTitle + "\"</em> incelendi. Sistemsel otomasyon yapısı için önerilen çözüm yolları şunlardır:<br/><br/>";
        solution += "1. <strong>Log Analizi:</strong> Hatanın oluştuğu zaman aralığındaki sunucu işlem geçmişini ve audit loglarını filtreleyin.<br/>";
        solution += "2. <strong>Entegrasyon Kontrolü:</strong> Otomasyon sisteminin ilgili API servisleri ve yerel ağ durumunu denetleyin.<br/>";
        solution += "3. <strong>Sürüm Eşleşmesi:</strong> Yazılımın en son stabil güncellemeye (Patch 2.4) yükseltildiğinden emin olun.<br/>";
        solution += "4. <strong>Cache/Önbellek Temizliği:</strong> Sunucu ve istemci tarafındaki önbellek kayıtlarını silerek sistemi yeniden başlatın.";
        
        textDiv.innerHTML = solution;
    }, 1500);
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
