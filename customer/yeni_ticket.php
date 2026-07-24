<?php
// C:\laragon\www\destek_as\customer\yeni_ticket.php
require_once __DIR__ . '/../includes/header.php';
checkAccess(['Müşteri Kullanıcısı']);

$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$company_id = $_SESSION['company_id'] ?? 1;

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $subcategory_id = null;
    $ticket_type_id = !empty($_POST['ticket_type_id']) ? intval($_POST['ticket_type_id']) : null;
    $priority_id = !empty($_POST['priority_id']) ? intval($_POST['priority_id']) : null;
    $product_service = trim($_POST['product_service'] ?? '');
    
    // Resolve category_id from selected product_service (project name)
    $category_id = null;
    if (!empty($product_service)) {
        $stmtCatId = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
        $stmtCatId->execute([$product_service]);
        $category_id = $stmtCatId->fetchColumn() ?: null;
    }
    $project_name = null;
    $comm_pref = 'email';
    $available_time = null;

    if (!empty($title) && !empty($description)) {
        try {
            // Generate ticket number: YEB-YYYY-XXXXXX
            $year = date('Y');
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE YEAR(created_at) = ?");
            $stmt->execute([$year]);
            $count = $stmt->fetchColumn();
            $sequence = str_pad($count + 1, 6, '0', STR_PAD_LEFT);
            $ticket_number = "YEB-{$year}-{$sequence}";

            // Handle file upload
            $attachment_path = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['attachment']['tmp_name'];
                $file_name = basename($_FILES['attachment']['name']);
                $file_size = $_FILES['attachment']['size'];
                $file_type = $_FILES['attachment']['type'];
                
                // Security checks
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'txt', 'log'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (in_array($file_ext, $allowed_extensions)) {
                    if ($file_size <= 10 * 1024 * 1024) { // Max 10MB
                        // Sanitize filename
                        $safe_filename = preg_replace("/[^a-zA-Z0-9\._-]/", "", $file_name);
                        $new_filename = uniqid('attach_', true) . '.' . $file_ext;
                        
                        $upload_dir = __DIR__ . '/../uploads/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        $dest_path = $upload_dir . $new_filename;
                        if (move_uploaded_file($file_tmp, $dest_path)) {
                            $attachment_path = '/destek_as/uploads/' . $new_filename;
                        } else {
                            $error = 'Dosya sunucuya kaydedilirken hata oluştu.';
                        }
                    } else {
                        $error = 'Yüklenen dosya boyutu 10MB limitini aşamaz.';
                    }
                } else {
                    $error = 'Geçersiz dosya uzantısı. İzin verilenler: ' . implode(', ', $allowed_extensions);
                }
            }

            if (empty($error)) {
                // Get default ticket status "Yeni"
                $stmtStatus = $pdo->prepare("SELECT id FROM ticket_statuses WHERE name = 'Yeni' AND (company_id = ? OR is_system = 1) LIMIT 1");
                $stmtStatus->execute([$company_id]);
                $status_id = $stmtStatus->fetchColumn() ?: 1;

                // Create ticket
                $stmt = $pdo->prepare("
                    INSERT INTO tickets 
                    (company_id, ticket_number, title, description, category_id, subcategory_id, product_service, priority_id, ticket_type_id, project_name, attachment_path, communication_preference, available_time, status_id, customer_id, customer_user_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $company_id, $ticket_number, $title, $description, $category_id, $subcategory_id, 
                    $product_service, $priority_id, $ticket_type_id, $project_name, $attachment_path, 
                    $comm_pref, $available_time, $status_id, $customer_id, $user_id
                ]);

                $ticket_id = $pdo->lastInsertId();

                // Write into attachments table if uploaded
                if ($attachment_path) {
                    $stmtAttach = $pdo->prepare("
                        INSERT INTO ticket_attachments (ticket_id, filename, file_path, file_size, file_mime, uploaded_by) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmtAttach->execute([$ticket_id, $safe_filename, $attachment_path, $file_size, $file_type, $user_id]);
                }

                // Log audit trail
                logActivity($pdo, "Yeni destek talebi oluşturuldu: #{$ticket_number}", "tickets", $ticket_id);

                // Auto-assignment triggers: Round-robin algorithm based on branch/specialty (Request 2)
                $stmtEligible = $pdo->prepare("
                    SELECT u.id 
                    FROM users u 
                    JOIN agent_skills ask ON u.id = ask.user_id 
                    JOIN user_roles ur ON u.id = ur.user_id
                    JOIN roles r ON ur.role_id = r.id
                    WHERE ask.skill_name = ? AND u.status = 'active' AND r.id = 4
                ");
                $stmtEligible->execute([$product_service]);
                $target_group = $stmtEligible->fetchAll(PDO::FETCH_COLUMN);

                $dept_id = 1; // Default department

                if (!empty($target_group)) {
                    // Query the last assigned user for this project
                    $stmtLast = $pdo->prepare("
                        SELECT ta.user_id 
                        FROM ticket_assignments ta 
                        JOIN tickets t ON ta.ticket_id = t.id 
                        WHERE t.product_service = ? AND ta.status = 'active'
                        ORDER BY ta.id DESC 
                        LIMIT 1
                    ");
                    $stmtLast->execute([$product_service]);
                    $last_assigned_user_id = $stmtLast->fetchColumn();

                    if ($last_assigned_user_id && in_array(intval($last_assigned_user_id), $target_group)) {
                        // General alternating logic (Round-robin) for N agents
                        $last_index = array_search(intval($last_assigned_user_id), $target_group);
                        if ($last_index !== false) {
                            $next_index = ($last_index + 1) % count($target_group);
                            $assigned_user_id = $target_group[$next_index];
                        } else {
                            $assigned_user_id = $target_group[array_rand($target_group)];
                        }
                    } else {
                        // First ticket or no match: pick randomly
                        $assigned_user_id = $target_group[array_rand($target_group)];
                    }

                        // Update ticket status and department
                        $stmtStatusAtandi = $pdo->prepare("SELECT id FROM ticket_statuses WHERE name = 'Atandı' LIMIT 1");
                        $stmtStatusAtandi->execute();
                        $status_atandi_id = $stmtStatusAtandi->fetchColumn() ?: 2;

                        $update_stmt = $pdo->prepare("UPDATE tickets SET department_id = ?, status_id = ? WHERE id = ?");
                        $update_stmt->execute([$dept_id, $status_atandi_id, $ticket_id]);

                        // Save assignment record
                        $stmtAssign = $pdo->prepare("INSERT INTO ticket_assignments (ticket_id, user_id, assigned_by, method, status) VALUES (?, ?, ?, 'round_robin', 'active')");
                        $stmtAssign->execute([$ticket_id, $assigned_user_id, $user_id]);

                        // Log activity
                        logActivity($pdo, "Ticket otomatik olarak teknisyene atandı. Teknisyen ID: {$assigned_user_id}", "tickets", $ticket_id);
                        
                        // Notify assigned agent
                        createNotification($pdo, 'Yeni Talep Atandı', "Size yeni bir destek talebi atandı: #{$ticket_number}.", $assigned_user_id, null, 'assignment');
                    }
                }

                // Notify customer
                createNotification($pdo, 'Talep Oluşturuldu', "Talebiniz (#{$ticket_number}) başarıyla oluşturuldu.", null, $user_id, 'ticket');

                $success = "Talep başarıyla oluşturuldu! Takip Numaranız: <strong>#{$ticket_number}</strong>";
            } catch (\Exception $e) {
                $error = 'Talep oluşturulurken bir hata meydana geldi: ' . $e->getMessage();
            }
    } else {
        $error = 'Lütfen Başlık ve Açıklama alanlarını eksiksiz doldurun.';
    }
}

// Fetch categories, priorities, ticket types
if (($_SESSION['role_name'] ?? '') === 'Müşteri Kullanıcısı') {
    $stmtC = $pdo->prepare("
        SELECT c.* 
        FROM categories c
        JOIN customer_categories cc ON c.id = cc.category_id
        WHERE cc.customer_id = ? AND c.status = 'active' AND c.name != 'Genel Destek'
        ORDER BY c.name ASC
    ");
    $stmtC->execute([$customer_id ?? 0]);
    $categories = $stmtC->fetchAll();
} else {
    $categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' AND name != 'Genel Destek' ORDER BY name ASC")->fetchAll();
}
$priorities = $pdo->query("SELECT * FROM priorities ORDER BY level ASC")->fetchAll();
$ticket_types = $pdo->query("SELECT * FROM ticket_types WHERE status = 'active' AND id IN (1, 2, 3) ORDER BY name ASC")->fetchAll();
?>

<div style="max-width: 800px; margin: 0 auto;">
    <!-- Messages -->
    <?php if (!empty($success)): ?>
        <div style="background-color: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: var(--success); padding: 16px; border-radius: 12px; font-weight: 500; margin-bottom: 25px;">
            <i class="fa-solid fa-circle-check" style="margin-right: 8px;"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div style="background-color: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: var(--danger); padding: 16px; border-radius: 12px; font-weight: 500; margin-bottom: 25px;">
            <i class="fa-solid fa-circle-exclamation" style="margin-right: 8px;"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card">
        <h3 style="font-size: 18px; margin-bottom: 25px;"><i class="fa-solid fa-circle-plus" style="margin-right: 8px; color: var(--primary);"></i> Yeni Destek Talebi Oluştur</h3>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <span>Talep Başlığı <span style="color:var(--danger)">*</span></span>
                    <span id="aiStatusBadge" class="badge" style="background: rgba(139, 92, 246, 0.15); color: var(--primary); border: 1px solid var(--primary); font-size: 11px; padding: 4px 10px; display: none; align-items: center; gap: 6px; transition: all 0.3s ease;">
                        <i class="fa-solid fa-robot"></i> Yapay Zeka Hazır
                    </span>
                </label>
                <input type="text" name="title" id="ticketTitle" class="form-control" placeholder="Açıklama alanına göre otomatik oluşturulur" readonly required autocomplete="off" style="background: rgba(255,255,255,0.02); cursor: not-allowed; color: var(--text-muted);">
            </div>

            <div class="form-group">
                <label class="form-label" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <span>Detaylı Açıklama <span style="color:var(--danger)">*</span></span>
                    <span id="aiDescSuggestionBtn" style="color: var(--accent); font-size: 11px; cursor: pointer; display: none; font-weight: 600; text-decoration: underline;" onclick="applyAiDescription()">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Önerilen Şablonu Doldur
                    </span>
                </label>
                <textarea name="description" id="ticketDescription" class="form-control" rows="6" placeholder="Karşılaştığınız sorunu veya talebinizi detaylıca açıklayın..." required oninput="debouncedAIClassify()"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Dosya / Ekran Görüntüsü Yükle</label>
                <input type="file" name="attachment" class="form-control" style="padding: 8px 12px; background: rgba(0,0,0,0.15);">
                <small style="color:var(--text-muted); display:block; margin-top:5px;">İzin verilen uzantılar: JPG, PNG, PDF, DOC, XLS, ZIP, TXT, LOG (Maks. 10MB)</small>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div class="form-group">
                    <label class="form-label">İlgili Hizmetimiz <span style="color:var(--danger)">*</span></label>
                    <select name="product_service" id="productServiceInput" class="form-control" onchange="updateAutoTitle();" required>
                        <option value="">Seçiniz</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Talep Türü</label>
                    <select name="ticket_type_id" id="ticketTypeSelect" class="form-control">
                        <option value="">Seçiniz</option>
                        <?php foreach ($ticket_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Öncelik Seviyesi</label>
                    <select name="priority_id" id="prioritySelect" class="form-control">
                        <option value="">Seçiniz</option>
                        <?php foreach ($priorities as $pri): ?>
                            <option value="<?php echo $pri['id']; ?>" <?php echo $pri['name'] === 'Normal' ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pri['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px;">
                <a href="/destek_as/customer/ticketlarim.php" class="btn-custom btn-custom-secondary">Taleplerime Dön</a>
                <button type="submit" class="btn-custom btn-custom-primary">Talebi Gönder <i class="fa-solid fa-paper-plane" style="margin-left: 6px;"></i></button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const productSelect = document.getElementById('productServiceInput');
    
    // Auto-select the first purchased category/service on page load
    const validOptions = Array.from(productSelect.options).filter(opt => opt.value !== "");
    if (validOptions.length > 0) {
        productSelect.value = validOptions[0].value;
        updateAutoTitle();
    }
});

let aiTimeout = null;
function debouncedAIClassify() {
    clearTimeout(aiTimeout);
    const descVal = document.getElementById('ticketDescription').value.trim();
    if (descVal.length < 4) {
        document.getElementById('aiStatusBadge').style.display = 'none';
        document.getElementById('aiDescSuggestionBtn').style.display = 'none';
        updateAutoTitle();
        return;
    }

    // Show classifying state
    const badge = document.getElementById('aiStatusBadge');
    badge.style.display = 'inline-flex';
    badge.style.background = 'rgba(139, 92, 246, 0.15)';
    badge.style.color = 'var(--primary)';
    badge.style.borderColor = 'var(--primary)';
    badge.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> 🤖 Yapay Zeka analiz ediyor...';

    aiTimeout = setTimeout(() => {
        fetch(`/destek_as/api/ai_classify.php?text=${encodeURIComponent(descVal)}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update form values
                    const d = data.data;

                    // Set product select (which acts as category/service selection)
                    const productSelect = document.getElementById('productServiceInput');
                    
                    let targetVal = "";
                    if (d.category_id == 1) targetVal = "Yazılım Geliştirme";
                    else if (d.category_id == 2) targetVal = "Donanım Arızası";

                    // Check if option is available for this customer
                    const hasOption = Array.from(productSelect.options).some(opt => opt.value === targetVal);
                    if (hasOption) {
                        productSelect.value = targetVal;
                    }
                    
                    updateAutoTitle();

                    // Set priority
                    document.getElementById('prioritySelect').value = d.priority_id;

                    // Set ticket type
                    document.getElementById('ticketTypeSelect').value = d.ticket_type_id;

                    // Description suggestions (Ghost Writing / Placeholder)
                    currentAiDescriptionSuggestion = d.description_suggestion || '';
                    const descTextarea = document.getElementById('ticketDescription');
                    const descBtn = document.getElementById('aiDescSuggestionBtn');

                    // Set suggestion as ghost writing placeholder
                    descTextarea.placeholder = currentAiDescriptionSuggestion;
                    descBtn.style.display = 'none';

                    // Show success state on badge
                    badge.style.background = 'rgba(16, 185, 129, 0.15)';
                    badge.style.color = 'var(--success)';
                    badge.style.borderColor = 'var(--success)';
                    badge.innerHTML = `<i class="fa-solid fa-robot"></i> 🤖 Yapay Zeka: Sınıflandırma yapıldı!`;
                    
                    // Fade out badge after 3 seconds
                    setTimeout(() => {
                        badge.style.display = 'none';
                    }, 3500);
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(() => {
                badge.style.display = 'none';
            });
    }, 600);
}

let currentAiDescriptionSuggestion = '';
function applyAiDescription() {
    const descTextarea = document.getElementById('ticketDescription');
    if (currentAiDescriptionSuggestion) {
        descTextarea.value = currentAiDescriptionSuggestion;
    }
    descTextarea.focus();
    
    // Highlight animation
    descTextarea.style.borderColor = 'var(--accent)';
    descTextarea.style.boxShadow = '0 0 15px rgba(20, 184, 166, 0.4)';
    setTimeout(() => {
        descTextarea.style.borderColor = '';
        descTextarea.style.boxShadow = '';
    }, 1200);

    document.getElementById('aiDescSuggestionBtn').style.display = 'none';
}

function updateAutoTitle() {
    const productSelect = document.getElementById('productServiceInput');
    const titleInput = document.getElementById('ticketTitle');

    const productText = productSelect.selectedIndex >= 0 && productSelect.options[productSelect.selectedIndex].value !== "" ? productSelect.options[productSelect.selectedIndex].value : "";

    if (productText) {
        titleInput.value = `${productText} Destek Talebi`;
    } else {
        titleInput.value = 'Yeni Destek Talebi';
    }
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
