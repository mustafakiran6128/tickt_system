<?php
// C:\laragon\www\destek_as\admin\musteriler.php
require_once __DIR__ . '/../includes/header.php';
checkAccess(['Firma Yöneticisi', 'Sistem Sahibi']);

$company_id = $_SESSION['company_id'] ?? 1;
$error = '';
$success = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contract_start = !empty($_POST['contract_start_date']) ? $_POST['contract_start_date'] : null;
        $contract_end = !empty($_POST['contract_end_date']) ? $_POST['contract_end_date'] : null;
        $package_id = 1; // Force Standart Paket (Request)
        $ticket_limit = 0; // Force unlimited
        $priority_support = 0; // Force no VIP
        $status = $_POST['status'] ?? 'active';

        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO customers 
                    (company_id, name, contact_person, phone, email, address, contract_start_date, contract_end_date, support_package_id, monthly_ticket_limit, priority_support, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$company_id, $name, $contact_person, $phone, $email, $address, $contract_start, $contract_end, $package_id, $ticket_limit, $priority_support, $status]);
                $customer_id = $pdo->lastInsertId();

                // Save selected categories (services)
                $selected_cats = $_POST['categories'] ?? [];
                foreach ($selected_cats as $cat_id) {
                    $stmtCC = $pdo->prepare("INSERT INTO customer_categories (customer_id, category_id) VALUES (?, ?)");
                    $stmtCC->execute([$customer_id, intval($cat_id)]);
                }

                $success = 'Müşteri firma başarıyla eklendi!';
                logActivity($pdo, "Yeni müşteri eklendi: " . $name, "customers", $customer_id);
            } catch (\Exception $e) {
                $error = 'Hata oluştu: ' . $e->getMessage();
            }
        } else {
            $error = 'Müşteri adı alanı zorunludur.';
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contract_start = !empty($_POST['contract_start_date']) ? $_POST['contract_start_date'] : null;
        $contract_end = !empty($_POST['contract_end_date']) ? $_POST['contract_end_date'] : null;
        $package_id = 1; // Force Standart Paket (Request)
        $ticket_limit = 0; // Force unlimited
        $priority_support = 0; // Force no VIP
        $status = $_POST['status'] ?? 'active';

        if ($id > 0 && !empty($name)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE customers 
                    SET name = ?, contact_person = ?, phone = ?, email = ?, address = ?, contract_start_date = ?, contract_end_date = ?, support_package_id = ?, monthly_ticket_limit = ?, priority_support = ?, status = ? 
                    WHERE id = ? AND company_id = ?
                ");
                $stmt->execute([$name, $contact_person, $phone, $email, $address, $contract_start, $contract_end, $package_id, $ticket_limit, $priority_support, $status, $id, $company_id]);

                // Clear previous and save new selected categories (services)
                $stmtClear = $pdo->prepare("DELETE FROM customer_categories WHERE customer_id = ?");
                $stmtClear->execute([$id]);

                $selected_cats = $_POST['categories'] ?? [];
                foreach ($selected_cats as $cat_id) {
                    $stmtCC = $pdo->prepare("INSERT INTO customer_categories (customer_id, category_id) VALUES (?, ?)");
                    $stmtCC->execute([$id, intval($cat_id)]);
                }

                $success = 'Müşteri firma başarıyla güncellendi!';
                logActivity($pdo, "Müşteri firma güncellendi: " . $name, "customers", $id);
            } catch (\Exception $e) {
                $error = 'Hata oluştu: ' . $e->getMessage();
            }
        } else {
            $error = 'Müşteri adı alanı zorunludur.';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ? AND company_id = ?");
                $stmt->execute([$id, $company_id]);
                $success = 'Müşteri firma başarıyla silindi!';
                logActivity($pdo, "Müşteri firma silindi. ID: " . $id, "customers", $id);
            } catch (\Exception $e) {
                $error = 'Müşteri silinirken hata oluştu (İlişkili destek kayıtları veya kullanıcıları bulunuyor).';
            }
        }
    }
}

// Fetch customers linked to this tenant company
$stmt = $pdo->prepare("
    SELECT c.*, sp.name AS package_name,
           (SELECT GROUP_CONCAT(cat.name SEPARATOR ', ') 
            FROM customer_categories cc 
            JOIN categories cat ON cc.category_id = cat.id 
            WHERE cc.customer_id = c.id) AS services,
           (SELECT GROUP_CONCAT(cc.category_id) 
            FROM customer_categories cc 
            WHERE cc.customer_id = c.id) AS category_ids
    FROM customers c
    LEFT JOIN support_packages sp ON c.support_package_id = sp.id
    WHERE c.company_id = ? 
    ORDER BY c.id DESC
");
$stmt->execute([$company_id]);
$customers = $stmt->fetchAll();

// Fetch support packages for dropdown
$stmtPkg = $pdo->prepare("SELECT id, name FROM support_packages WHERE company_id = ? AND status = 'active'");
$stmtPkg->execute([$company_id]);
$packages = $stmtPkg->fetchAll();

// Fetch active categories (services) for checkboxes
$stmtCats = $pdo->prepare("SELECT id, name FROM categories WHERE status = 'active' AND name != 'Genel Destek'");
$stmtCats->execute();
$all_categories = $stmtCats->fetchAll();
?>

<div style="display: flex; flex-direction: column; gap: 30px;">
    <!-- Messages -->
    <?php if (!empty($success)): ?>
        <div style="background-color: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: var(--success); padding: 16px; border-radius: 12px; font-weight: 500;">
            <i class="fa-solid fa-circle-check" style="margin-right: 8px;"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div style="background-color: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: var(--danger); padding: 16px; border-radius: 12px; font-weight: 500;">
            <i class="fa-solid fa-circle-exclamation" style="margin-right: 8px;"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Table Card -->
    <div class="glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="font-size: 18px;"><i class="fa-solid fa-handshake" style="margin-right: 8px; color: var(--primary);"></i> Müşteri Firmaları</h3>
            <button onclick="openAddModal()" class="btn-custom btn-custom-primary">
                <i class="fa-solid fa-plus"></i> Yeni Müşteri Ekle
            </button>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Müşteri Adı</th>
                        <th>Tanımlı Hizmetler</th>
                        <th>İrtibat Kişisi</th>
                        <th>Telefon / E-Posta</th>
                        <th>Destek Paketi</th>
                        <th>Aylık Limit</th>
                        <th>Sözleşme Bitiş</th>
                        <th>Durum</th>
                        <th style="text-align: right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="9" style="text-align: center; color: var(--text-muted);">Henüz kayıtlı müşteri firması bulunmuyor.</td></tr>
                    <?php else: ?>
                        <?php foreach ($customers as $cust): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($cust['name']); ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($cust['services'])): ?>
                                        <?php 
                                        $srvs = explode(', ', $cust['services']);
                                        foreach ($srvs as $srv):
                                            $badge_class = ($srv === 'Yazılım Geliştirme') ? 'badge-info' : 'badge-warning';
                                            echo '<span class="badge ' . $badge_class . '" style="font-size: 10px; margin-right: 4px; display: inline-block; margin-bottom: 2px; padding: 2px 6px;">' . htmlspecialchars($srv) . '</span>';
                                        endforeach;
                                        ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 11px;">Hizmet Tanımsız</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($cust['contact_person'] ?? '-'); ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($cust['phone'] ?? '-'); ?></div>
                                    <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($cust['email'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-info">Standart Paket</span>
                                </td>
                                <td>Sınırsız</td>
                                <td>
                                    <?php 
                                    if ($cust['contract_end_date']) {
                                        $end_time = strtotime($cust['contract_end_date']);
                                        echo date('d.m.Y', $end_time);
                                        if ($end_time < time()) {
                                            echo ' <span style="color:var(--danger);font-weight:bold;">(Süresi Doldu)</span>';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($cust['status'] === 'active'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <button onclick='openEditModal(<?php echo json_encode($cust); ?>)' class="btn-custom btn-custom-secondary" style="padding: 6px 12px; font-size: 12px;">
                                            <i class="fa-solid fa-pen-to-square"></i> Düzenle
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Bu müşteriyi silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $cust['id']; ?>">
                                            <button type="submit" class="btn-custom btn-custom-danger" style="padding: 6px 12px; font-size: 12px;">
                                                <i class="fa-solid fa-trash"></i> Sil
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Customer Modal -->
<div id="customerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Yeni Müşteri Ekle</h3>
            <button onclick="hideModal('customerModal')" class="modal-close">&times;</button>
        </div>
        <form id="customerForm" method="POST" action="">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="customerId" value="">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Müşteri Firma Adı <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="name" id="custName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Yetkili / İrtibat Kişisi</label>
                    <input type="text" name="contact_person" id="custContact" class="form-control">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" id="custPhone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-Posta</label>
                        <input type="email" name="email" id="custEmail" class="form-control">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Sözleşme Başlangıç Tarihi</label>
                        <input type="date" name="contract_start_date" id="custContractStart" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sözleşme Bitiş Tarihi</label>
                        <input type="date" name="contract_end_date" id="custContractEnd" class="form-control">
                    </div>
                </div>
                <div style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Destek Paketi</label>
                        <select name="support_package_id" id="custPackage" class="form-control">
                            <option value="1" selected>Standart Paket</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Aylık Ticket Limiti</label>
                        <input type="number" name="monthly_ticket_limit" id="custLimit" class="form-control" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Adres</label>
                    <textarea name="address" id="custAddress" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" style="display:block; margin-bottom: 8px;">Satın Alınan Hizmetler</label>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                        <?php foreach ($all_categories as $cat): ?>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>" class="category-checkbox" style="width:16px; height:16px; accent-color: var(--primary);">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="display: none; gap: 20px; align-items: center; margin: 15px 0;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="priority_support" id="custPriority" value="1" style="width:16px; height:16px; accent-color: var(--primary);">
                        <label for="custPriority" class="form-label" style="margin-bottom:0; cursor:pointer;">VIP Öncelikli Destek</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Durum</label>
                    <select name="status" id="custStatus" class="form-control">
                        <option value="active">Aktif</option>
                        <option value="passive">Pasif</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="hideModal('customerModal')" class="btn-custom btn-custom-secondary">Vazgeç</button>
                <button type="submit" class="btn-custom btn-custom-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Yeni Müşteri Ekle';
    document.getElementById('formAction').value = 'add';
    document.getElementById('customerId').value = '';
    
    // Clear form
    document.getElementById('custName').value = '';
    document.getElementById('custContact').value = '';
    document.getElementById('custPhone').value = '';
    document.getElementById('custEmail').value = '';
    document.getElementById('custAddress').value = '';
    document.getElementById('custContractStart').value = '';
    document.getElementById('custContractEnd').value = '';
    document.getElementById('custPackage').value = '';
    document.getElementById('custLimit').value = '20';
    document.getElementById('custPriority').checked = false;
    document.getElementById('custStatus').value = 'active';

    // Clear service checkboxes
    Array.from(document.querySelectorAll('.category-checkbox')).forEach(cb => {
        cb.checked = false;
    });
    
    showModal('customerModal');
}

function openEditModal(cust) {
    document.getElementById('modalTitle').innerText = 'Müşteri Firmayı Düzenle';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('customerId').value = cust.id;
    
    // Populate form
    document.getElementById('custName').value = cust.name;
    document.getElementById('custContact').value = cust.contact_person || '';
    document.getElementById('custPhone').value = cust.phone || '';
    document.getElementById('custEmail').value = cust.email || '';
    document.getElementById('custAddress').value = cust.address || '';
    document.getElementById('custContractStart').value = cust.contract_start_date || '';
    document.getElementById('custContractEnd').value = cust.contract_end_date || '';
    document.getElementById('custPackage').value = cust.support_package_id || '';
    document.getElementById('custLimit').value = cust.monthly_ticket_limit;
    document.getElementById('custPriority').checked = cust.priority_support == 1;
    document.getElementById('custStatus').value = cust.status;

    // Reset and select active service checkboxes
    const checkboxes = Array.from(document.querySelectorAll('.category-checkbox'));
    checkboxes.forEach(cb => cb.checked = false);

    if (cust.category_ids) {
        const activeIds = cust.category_ids.split(',').map(id => id.trim());
        checkboxes.forEach(cb => {
            if (activeIds.includes(cb.value)) {
                cb.checked = true;
            }
        });
    }
    
    showModal('customerModal');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
