<?php
// C:\laragon\www\destek_as\admin\firmalar.php
require_once __DIR__ . '/../includes/header.php';
checkAccess(['Sistem Sahibi']);

$error = '';
$success = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $trade_name = trim($_POST['trade_name'] ?? '');
        $tax_number = trim($_POST['tax_number'] ?? '');
        $tax_office = trim($_POST['tax_office'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO companies (name, trade_name, tax_number, tax_office, phone, email, website, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $trade_name, $tax_number, $tax_office, $phone, $email, $website, $address, $status]);
                $success = 'Firma başarıyla eklendi!';
                logActivity($pdo, "Yeni firma eklendi: " . $name, "companies", $pdo->lastInsertId());
            } catch (\Exception $e) {
                $error = 'Hata oluştu: ' . $e->getMessage();
            }
        } else {
            $error = 'Firma adı alanı zorunludur.';
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $trade_name = trim($_POST['trade_name'] ?? '');
        $tax_number = trim($_POST['tax_number'] ?? '');
        $tax_office = trim($_POST['tax_office'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if ($id > 0 && !empty($name)) {
            try {
                $stmt = $pdo->prepare("UPDATE companies SET name = ?, trade_name = ?, tax_number = ?, tax_office = ?, phone = ?, email = ?, website = ?, address = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $trade_name, $tax_number, $tax_office, $phone, $email, $website, $address, $status, $id]);
                $success = 'Firma başarıyla güncellendi!';
                logActivity($pdo, "Firma bilgileri güncellendi: " . $name, "companies", $id);
            } catch (\Exception $e) {
                $error = 'Hata oluştu: ' . $e->getMessage();
            }
        } else {
            $error = 'Firma adı alanı zorunludur.';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 1) { // Do not delete primary Destek A.Ş. company
            try {
                $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Firma başarıyla silindi!';
                logActivity($pdo, "Firma sistemden silindi. ID: " . $id, "companies", $id);
            } catch (\Exception $e) {
                $error = 'Firma silinirken hata oluştu (İlişkili kayıtlar bulunuyor olabilir).';
            }
        } else {
            $error = 'Birincil sistem sahibi firması silinemez.';
        }
    }
}

// Fetch all companies
$companies = $pdo->query("SELECT * FROM companies ORDER BY id ASC")->fetchAll();
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
            <h3 style="font-size: 18px;"><i class="fa-solid fa-building" style="margin-right: 8px; color: var(--primary);"></i> Sistemdeki Tüm Kayıtlı Firmalar</h3>
            <button onclick="openAddModal()" class="btn-custom btn-custom-primary">
                <i class="fa-solid fa-plus"></i> Yeni Firma Ekle
            </button>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Firma Adı</th>
                        <th>Vergi No / Dairesi</th>
                        <th>Telefon / E-Posta</th>
                        <th>Sektör</th>
                        <th>Durum</th>
                        <th style="text-align: right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $comp): ?>
                        <tr>
                            <td><strong>#<?php echo $comp['id']; ?></strong></td>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($comp['name']); ?></div>
                                <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($comp['trade_name'] ?? ''); ?></small>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($comp['tax_number'] ?? '-'); ?></div>
                                <small style="color: var(--text-muted);"><?php echo htmlspecialchars($comp['tax_office'] ?? ''); ?></small>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($comp['phone'] ?? '-'); ?></div>
                                <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($comp['email'] ?? ''); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($comp['sector'] ?? '-'); ?></td>
                            <td>
                                <?php if ($comp['status'] === 'active'): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Pasif</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <button onclick='openEditModal(<?php echo json_encode($comp); ?>)' class="btn-custom btn-custom-secondary" style="padding: 6px 12px; font-size: 12px;">
                                        <i class="fa-solid fa-pen-to-square"></i> Düzenle
                                    </button>
                                    <?php if ($comp['id'] > 1): ?>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Bu firmayı silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $comp['id']; ?>">
                                            <button type="submit" class="btn-custom btn-custom-danger" style="padding: 6px 12px; font-size: 12px;">
                                                <i class="fa-solid fa-trash"></i> Sil
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Company Modal -->
<div id="companyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Yeni Firma Ekle</h3>
            <button onclick="hideModal('companyModal')" class="modal-close">&times;</button>
        </div>
        <form id="companyForm" method="POST" action="">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="companyId" value="">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Firma Adı <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="name" id="compName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ticari Unvan</label>
                    <input type="text" name="trade_name" id="compTradeName" class="form-control">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Vergi Numarası</label>
                        <input type="text" name="tax_number" id="compTaxNumber" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vergi Dairesi</label>
                        <input type="text" name="tax_office" id="compTaxOffice" class="form-control">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" id="compPhone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-Posta</label>
                        <input type="email" name="email" id="compEmail" class="form-control">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Web Sitesi</label>
                        <input type="text" name="website" id="compWebsite" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sektör</label>
                        <input type="text" name="sector" id="compSector" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Adres</label>
                    <textarea name="address" id="compAddress" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Durum</label>
                    <select name="status" id="compStatus" class="form-control">
                        <option value="active">Aktif</option>
                        <option value="passive">Pasif</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="hideModal('companyModal')" class="btn-custom btn-custom-secondary">Vazgeç</button>
                <button type="submit" class="btn-custom btn-custom-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Yeni Firma Ekle';
    document.getElementById('formAction').value = 'add';
    document.getElementById('companyId').value = '';
    
    // Clear form
    document.getElementById('compName').value = '';
    document.getElementById('compTradeName').value = '';
    document.getElementById('compTaxNumber').value = '';
    document.getElementById('compTaxOffice').value = '';
    document.getElementById('compPhone').value = '';
    document.getElementById('compEmail').value = '';
    document.getElementById('compWebsite').value = '';
    document.getElementById('compSector').value = '';
    document.getElementById('compAddress').value = '';
    document.getElementById('compStatus').value = 'active';
    
    showModal('companyModal');
}

function openEditModal(company) {
    document.getElementById('modalTitle').innerText = 'Firmayı Düzenle';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('companyId').value = company.id;
    
    // Populate form
    document.getElementById('compName').value = company.name;
    document.getElementById('compTradeName').value = company.trade_name || '';
    document.getElementById('compTaxNumber').value = company.tax_number || '';
    document.getElementById('compTaxOffice').value = company.tax_office || '';
    document.getElementById('compPhone').value = company.phone || '';
    document.getElementById('compEmail').value = company.email || '';
    document.getElementById('compWebsite').value = company.website || '';
    document.getElementById('compSector').value = company.sector || '';
    document.getElementById('compAddress').value = company.address || '';
    document.getElementById('compStatus').value = company.status;
    
    showModal('companyModal');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
