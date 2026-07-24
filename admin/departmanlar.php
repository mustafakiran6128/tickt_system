<?php
// C:\laragon\www\destek_as\admin\departmanlar.php
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
        $description = trim($_POST['description'] ?? '');
        $manager_id = !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : null;
        $email = trim($_POST['email'] ?? '');
        $working_hours = trim($_POST['working_hours'] ?? '09:00-18:00');
        $default_priority = $_POST['default_priority'] ?? 'Normal';
        $status = $_POST['status'] ?? 'active';
        $daily_capacity = intval($_POST['daily_capacity'] ?? 10);
        $assignment_method = $_POST['assignment_method'] ?? 'round_robin';

        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO departments 
                    (company_id, name, description, manager_id, email, working_hours, default_priority, status, daily_capacity, assignment_method) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$company_id, $name, $description, $manager_id, $email, $working_hours, $default_priority, $status, $daily_capacity, $assignment_method]);
                $success = 'Departman başarıyla eklendi!';
                logActivity($pdo, "Yeni departman eklendi: " . $name, "departments", $pdo->lastInsertId());
            } catch (\Exception $e) {
                $error = 'Hata oluştu: ' . $e->getMessage();
            }
        } else {
            $error = 'Departman adı zorunludur.';
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $manager_id = !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : null;
        $email = trim($_POST['email'] ?? '');
        $working_hours = trim($_POST['working_hours'] ?? '09:00-18:00');
        $default_priority = $_POST['default_priority'] ?? 'Normal';
        $status = $_POST['status'] ?? 'active';
        $daily_capacity = intval($_POST['daily_capacity'] ?? 10);
        $assignment_method = $_POST['assignment_method'] ?? 'round_robin';

        if ($id > 0 && !empty($name)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE departments 
                    SET name = ?, description = ?, manager_id = ?, email = ?, working_hours = ?, default_priority = ?, status = ?, daily_capacity = ?, assignment_method = ? 
                    WHERE id = ? AND company_id = ?
                ");
                $stmt->execute([$name, $description, $manager_id, $email, $working_hours, $default_priority, $status, $daily_capacity, $assignment_method, $id, $company_id]);
                $success = 'Departman başarıyla güncellendi!';
                logActivity($pdo, "Departman güncellendi: " . $name, "departments", $id);
            } catch (\Exception $e) {
                $error = 'Hata oluştu: ' . $e->getMessage();
            }
        } else {
            $error = 'Departman adı zorunludur.';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ? AND company_id = ?");
                $stmt->execute([$id, $company_id]);
                $success = 'Departman başarıyla silindi!';
                logActivity($pdo, "Departman silindi. ID: " . $id, "departments", $id);
            } catch (\Exception $e) {
                $error = 'Departman silinirken hata oluştu (İlişkili bilet veya kategorileri bulunuyor).';
            }
        }
    }
}

// Fetch departments
$stmt = $pdo->prepare("
    SELECT d.*, u.first_name, u.last_name 
    FROM departments d
    LEFT JOIN users u ON d.manager_id = u.id
    WHERE d.company_id = ?
    ORDER BY d.id ASC
");
$stmt->execute([$company_id]);
$departments = $stmt->fetchAll();

// Fetch department managers candidate (Staff/Admins)
$stmtMgr = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name 
    FROM users u
    JOIN user_roles ur ON u.id = ur.user_id
    JOIN roles r ON ur.role_id = r.id
    WHERE ur.company_id = ? AND r.name IN ('Destek Personeli', 'Firma Yöneticisi', 'Departman Yöneticisi')
");
$stmtMgr->execute([$company_id]);
$managers = $stmtMgr->fetchAll();
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
            <h3 style="font-size: 18px;"><i class="fa-solid fa-sitemap" style="margin-right: 8px; color: var(--primary);"></i> Departman Yönetimi</h3>
            <button onclick="openAddModal()" class="btn-custom btn-custom-primary">
                <i class="fa-solid fa-plus"></i> Yeni Departman Ekle
            </button>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Departman Adı</th>
                        <th>Yönetici</th>
                        <th>İletişim E-Posta</th>
                        <th>Çalışma Saatleri</th>
                        <th>Atama Metodu</th>
                        <th>Kapasite</th>
                        <th>Durum</th>
                        <th style="text-align: right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($departments)): ?>
                        <tr><td colspan="8" style="text-align: center; color: var(--text-muted);">Kayıtlı departman bulunmuyor.</td></tr>
                    <?php else: ?>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td>
                                    <strong style="font-size:15px; color:var(--text-primary);"><?php echo htmlspecialchars($dept['name']); ?></strong>
                                    <div style="font-size:12px; color:var(--text-secondary);"><?php echo htmlspecialchars($dept['description'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <?php echo $dept['manager_id'] ? htmlspecialchars($dept['first_name'] . ' ' . $dept['last_name']) : '<span style="color:var(--text-secondary);">Atanmamış</span>'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($dept['email'] ?? '-'); ?></td>
                                <td><code><?php echo htmlspecialchars($dept['working_hours']); ?></code></td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($dept['assignment_method']); ?></span>
                                </td>
                                <td><strong><?php echo $dept['daily_capacity']; ?></strong> Talep/Gün</td>
                                <td>
                                    <?php if ($dept['status'] === 'active'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <button onclick='openEditModal(<?php echo json_encode($dept); ?>)' class="btn-custom btn-custom-secondary" style="padding: 6px 12px; font-size: 12px;">
                                            <i class="fa-solid fa-pen-to-square"></i> Düzenle
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Bu departmanı silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $dept['id']; ?>">
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

<!-- Add/Edit Department Modal -->
<div id="deptModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Yeni Departman Ekle</h3>
            <button onclick="hideModal('deptModal')" class="modal-close">&times;</button>
        </div>
        <form id="deptForm" method="POST" action="">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="deptId" value="">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Departman Adı <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="name" id="deptName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" id="deptDescription" class="form-control" rows="2"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Departman Yöneticisi</label>
                        <select name="manager_id" id="deptManager" class="form-control">
                            <option value="">Seçilmedi</option>
                            <?php foreach ($managers as $mgr): ?>
                                <option value="<?php echo $mgr['id']; ?>"><?php echo htmlspecialchars($mgr['first_name'] . ' ' . $mgr['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ortak Destek E-Postası</label>
                        <input type="email" name="email" id="deptEmail" class="form-control" placeholder="Örn: it@destek.com">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Çalışma Saatleri</label>
                        <input type="text" name="working_hours" id="deptWorkingHours" class="form-control" value="09:00-18:00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Varsayılan SLA Önceliği</label>
                        <select name="default_priority" id="deptPriority" class="form-control">
                            <option value="Düşük">Düşük</option>
                            <option value="Normal" selected>Normal</option>
                            <option value="Yüksek">Yüksek</option>
                            <option value="Kritik">Kritik</option>
                            <option value="Acil">Acil</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Günlük Maks. Talep Kapasitesi</label>
                        <input type="number" name="daily_capacity" id="deptCapacity" class="form-control" value="10" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Otomatik Atama Yöntemi</label>
                        <select name="assignment_method" id="deptAssignmentMethod" class="form-control">
                            <option value="round_robin">Sırayla Atama (Round Robin)</option>
                            <option value="least_workload">En Az İş Yüküne Göre (Workload)</option>
                            <option value="skills">Uzmanlık Becerisine Göre (Skills)</option>
                            <option value="priority">Önceliğe Göre (Priority Escalation)</option>
                            <option value="manual">Manuel Atama (Yönetici Tarafından)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Durum</label>
                    <select name="status" id="deptStatus" class="form-control">
                        <option value="active">Aktif</option>
                        <option value="passive">Pasif</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="hideModal('deptModal')" class="btn-custom btn-custom-secondary">Vazgeç</button>
                <button type="submit" class="btn-custom btn-custom-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Yeni Departman Ekle';
    document.getElementById('formAction').value = 'add';
    document.getElementById('deptId').value = '';
    
    // Clear form
    document.getElementById('deptName').value = '';
    document.getElementById('deptDescription').value = '';
    document.getElementById('deptManager').value = '';
    document.getElementById('deptEmail').value = '';
    document.getElementById('deptWorkingHours').value = '09:00-18:00';
    document.getElementById('deptPriority').value = 'Normal';
    document.getElementById('deptCapacity').value = '10';
    document.getElementById('deptAssignmentMethod').value = 'round_robin';
    document.getElementById('deptStatus').value = 'active';
    
    showModal('deptModal');
}

function openEditModal(dept) {
    document.getElementById('modalTitle').innerText = 'Departmanı Düzenle';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('deptId').value = dept.id;
    
    // Populate form
    document.getElementById('deptName').value = dept.name;
    document.getElementById('deptDescription').value = dept.description || '';
    document.getElementById('deptManager').value = dept.manager_id || '';
    document.getElementById('deptEmail').value = dept.email || '';
    document.getElementById('deptWorkingHours').value = dept.working_hours;
    document.getElementById('deptPriority').value = dept.default_priority;
    document.getElementById('deptCapacity').value = dept.daily_capacity;
    document.getElementById('deptAssignmentMethod').value = dept.assignment_method;
    document.getElementById('deptStatus').value = dept.status;
    
    showModal('deptModal');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
