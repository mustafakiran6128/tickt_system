<?php
ob_start();
// C:\laragon\www\destek_as\admin\kategoriler.php
require_once __DIR__ . '/../includes/header.php';
checkAccess(['Firma Yöneticisi', 'Sistem Sahibi']);

$company_id = $_SESSION['company_id'] ?? 1;
$error = '';
$success = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $default_dept = !empty($_POST['default_department_id']) ? intval($_POST['default_department_id']) : null;
        $default_priority = $_POST['default_priority'] ?? 'Normal';
        $status = $_POST['status'] ?? 'active';

        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO categories 
                    (company_id, name, description, default_department_id, default_priority, status) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$company_id, $name, $description, $default_dept, $default_priority, $status]);
                $success = 'Proje başarıyla eklendi!';
                logActivity($pdo, "Yeni proje eklendi: " . $name, "categories", $pdo->lastInsertId());
            } catch (\Exception $e) {
                $error = 'Hata oluştu: ' . $e->getMessage();
            }
        } else {
            $error = 'Proje adı zorunludur.';
        }
    } elseif ($action === 'edit_category') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $default_dept = !empty($_POST['default_department_id']) ? intval($_POST['default_department_id']) : null;
        $default_priority = $_POST['default_priority'] ?? 'Normal';
        $status = $_POST['status'] ?? 'active';

        if ($id > 0 && !empty($name)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE categories 
                    SET name = ?, description = ?, default_department_id = ?, default_priority = ?, status = ? 
                    WHERE id = ? AND company_id = ?
                ");
                $stmt->execute([$name, $description, $default_dept, $default_priority, $status, $id, $company_id]);
                $success = 'Proje başarıyla güncellendi!';
                logActivity($pdo, "Proje güncellendi: " . $name, "categories", $id);
            } catch (\Exception $e) {
                $error = 'Hata oluştu: ' . $e->getMessage();
            }
        } else {
            $error = 'Proje adı zorunludur.';
        }
    } elseif ($action === 'delete_category') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND company_id = ?");
                $stmt->execute([$id, $company_id]);
                $success = 'Proje başarıyla silindi!';
                logActivity($pdo, "Proje silindi. ID: " . $id, "categories", $id);
            } catch (\Exception $e) {
                $error = 'Proje silinirken hata oluştu (İlişkili biletleri bulunuyor).';
            }
        }
    }
}

// Fetch categories
$stmt = $pdo->prepare("
    SELECT c.*, d.name AS dept_name
    FROM categories c
    LEFT JOIN departments d ON c.default_department_id = d.id
    WHERE c.company_id = ?
    ORDER BY c.id ASC
");
$stmt->execute([$company_id]);
$categories = $stmt->fetchAll();

// Fetch departments for default assignment dropdown
$stmtDept = $pdo->prepare("SELECT id, name FROM departments WHERE company_id = ? AND status = 'active'");
$stmtDept->execute([$company_id]);
$departments = $stmtDept->fetchAll();
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

    <div style="display: grid; grid-template-columns: 1fr; gap: 30px; align-items: flex-start;">
        <!-- Left Side: Categories List -->
        <div class="glass-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="font-size: 18px;"><i class="fa-solid fa-folder-open" style="margin-right: 8px; color: var(--primary);"></i> Projelerimiz</h3>
                <button onclick="openAddCatModal()" class="btn-custom btn-custom-primary">
                    <i class="fa-solid fa-plus"></i> Proje Ekle
                </button>
            </div>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Proje Otomasyon Adı</th>
                            <th>Varsayılan Departman</th>
                            <th>Varsayılan Öncelik</th>
                            <th>Durum</th>
                            <th style="text-align: right;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <strong style="font-size: 14px;"><?php echo htmlspecialchars($cat['name']); ?></strong>
                                    <div style="font-size: 11px; color: var(--text-secondary);"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($cat['dept_name'] ?? 'Atanmamış'); ?></td>
                                <td><?php echo htmlspecialchars($cat['default_priority'] ?? 'Normal'); ?></td>
                                <td>
                                    <?php if ($cat['status'] === 'active'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <button onclick='openEditCatModal(<?php echo json_encode($cat); ?>)' class="btn-custom btn-custom-secondary" style="padding: 6px 12px; font-size: 12px;" title="Düzenle">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Bu projeyi silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" class="btn-custom btn-custom-danger" style="padding: 6px 12px; font-size: 12px;" title="Sil">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div id="catModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="catModalTitle">Yeni Proje Ekle</h3>
            <button onclick="hideModal('catModal')" class="modal-close">&times;</button>
        </div>
        <form id="catForm" method="POST" action="">
            <input type="hidden" name="action" id="catFormAction" value="add_category">
            <input type="hidden" name="id" id="catId" value="">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Proje Otomasyon Adı <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="name" id="catName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" id="catDescription" class="form-control" rows="2"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Yönlendirilecek Departman</label>
                        <select name="default_department_id" id="catDefaultDept" class="form-control">
                            <option value="">Departman Seçilmedi</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Varsayılan SLA Önceliği</label>
                        <select name="default_priority" id="catDefaultPriority" class="form-control">
                            <option value="Normal" selected>Normal</option>
                            <option value="Öncelikli">Öncelikli</option>
                            <option value="Yüksek">Yüksek</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Durum</label>
                    <select name="status" id="catStatus" class="form-control">
                        <option value="active">Aktif</option>
                        <option value="passive">Pasif</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="hideModal('catModal')" class="btn-custom btn-custom-secondary">Vazgeç</button>
                <button type="submit" class="btn-custom btn-custom-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddCatModal() {
    document.getElementById('catModalTitle').innerText = 'Yeni Proje Ekle';
    document.getElementById('catFormAction').value = 'add_category';
    document.getElementById('catId').value = '';
    
    // Clear form
    document.getElementById('catName').value = '';
    document.getElementById('catDescription').value = '';
    document.getElementById('catDefaultDept').value = '';
    document.getElementById('catDefaultPriority').value = 'Normal';
    document.getElementById('catStatus').value = 'active';
    
    showModal('catModal');
}

function openEditCatModal(cat) {
    document.getElementById('catModalTitle').innerText = 'Projeyi Düzenle';
    document.getElementById('catFormAction').value = 'edit_category';
    document.getElementById('catId').value = cat.id;
    
    // Populate form
    document.getElementById('catName').value = cat.name;
    document.getElementById('catDescription').value = cat.description || '';
    document.getElementById('catDefaultDept').value = cat.default_department_id || '';
    document.getElementById('catDefaultPriority').value = cat.default_priority;
    document.getElementById('catStatus').value = cat.status;
    
    showModal('catModal');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
