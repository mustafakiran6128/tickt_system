<?php
// C:\laragon\www\destek_as\admin\kullanicilar.php
require_once __DIR__ . '/../includes/header.php';
checkAccess(['Firma Yöneticisi', 'Sistem Sahibi']);

$company_id = $_SESSION['company_id'] ?? 1;
$error = '';
$success = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role_id = intval($_POST['role_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        if (!empty($first_name) && !empty($last_name) && !empty($email) && !empty($password) && $role_id > 0) {
            try {
                $pdo->beginTransaction();

                // Insert user
                $hashed_pw = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, phone, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$email, $hashed_pw, $first_name, $last_name, $phone, $status]);
                $new_user_id = $pdo->lastInsertId();

                // Map role
                $stmtRole = $pdo->prepare("INSERT INTO user_roles (user_id, role_id, company_id) VALUES (?, ?, ?)");
                $stmtRole->execute([$new_user_id, $role_id, $company_id]);

                // Save branch specialty if technician (Request 1)
                if ($role_id === 4 && !empty($_POST['branch_dal'])) {
                    $stmtSkill = $pdo->prepare("INSERT INTO agent_skills (user_id, skill_name) VALUES (?, ?)");
                    $stmtSkill->execute([$new_user_id, $_POST['branch_dal']]);
                }

                $pdo->commit();
                $success = 'Kullanıcı başarıyla eklendi!';
                logActivity($pdo, "Yeni kullanıcı eklendi: " . $email, "users", $new_user_id);
            } catch (\Exception $e) {
                $pdo->rollBack();
                $error = 'Hata oluştu: E-posta adresi zaten kullanımda olabilir.';
            }
        } else {
            $error = 'Lütfen Ad, Soyad, E-Posta, Şifre ve Rol alanlarını doldurun.';
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role_id = intval($_POST['role_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        if ($id > 0 && !empty($first_name) && !empty($last_name) && !empty($email) && $role_id > 0) {
            try {
                $pdo->beginTransaction();

                // Update user details
                if (!empty($password)) {
                    $hashed_pw = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, password = ?, phone = ?, status = ? WHERE id = ?");
                    $stmt->execute([$first_name, $last_name, $email, $hashed_pw, $phone, $status, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, status = ? WHERE id = ?");
                    $stmt->execute([$first_name, $last_name, $email, $phone, $status, $id]);
                }

                // Update role mapping
                $stmtDel = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
                $stmtDel->execute([$id]);
                $stmtAdd = $pdo->prepare("INSERT INTO user_roles (user_id, role_id, company_id) VALUES (?, ?, ?)");
                $stmtAdd->execute([$id, $role_id, $company_id]);

                // Update branch specialty if technician (Request 1)
                $stmtDelSkill = $pdo->prepare("DELETE FROM agent_skills WHERE user_id = ?");
                $stmtDelSkill->execute([$id]);
                if ($role_id === 4 && !empty($_POST['branch_dal'])) {
                    $stmtSkill = $pdo->prepare("INSERT INTO agent_skills (user_id, skill_name) VALUES (?, ?)");
                    $stmtSkill->execute([$id, $_POST['branch_dal']]);
                }

                $pdo->commit();
                $success = 'Kullanıcı başarıyla güncellendi!';
                logActivity($pdo, "Kullanıcı güncellendi: " . $email, "users", $id);
            } catch (\Exception $e) {
                $pdo->rollBack();
                $error = 'Hata oluştu: E-posta adresi kullanımda olabilir. ' . $e->getMessage();
            }
        } else {
            $error = 'Ad, Soyad, E-Posta ve Rol alanları zorunludur.';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 1) { // Do not delete the main administrator
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Kullanıcı başarıyla silindi!';
                logActivity($pdo, "Kullanıcı silindi. ID: " . $id, "users", $id);
            } catch (\Exception $e) {
                $error = 'Kullanıcı silinirken hata oluştu (İlişkili bilet veya işlem logları bulunuyor olabilir).';
            }
        } else {
            $error = 'Birincil yönetici kullanıcısı silinemez.';
        }
    }
}

// Fetch all users linked to this tenant company
$stmt = $pdo->prepare("
    SELECT u.*, r.name AS role_name, r.id AS role_id, ask.skill_name AS branch_dal
    FROM users u
    JOIN user_roles ur ON u.id = ur.user_id
    JOIN roles r ON ur.role_id = r.id
    LEFT JOIN agent_skills ask ON u.id = ask.user_id
    WHERE ur.company_id = ?
    ORDER BY u.id ASC
");
$stmt->execute([$company_id]);
$users = $stmt->fetchAll();

// Fetch roles for dropdown list
$stmtRoles = $pdo->prepare("SELECT id, name FROM roles WHERE company_id = ? OR is_system = 1 ORDER BY name ASC");
$stmtRoles->execute([$company_id]);
$roles = $stmtRoles->fetchAll();

// Fetch active automation projects for branch options
$projects = $pdo->query("SELECT name FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
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

    <!-- Users Table Card -->
    <div class="glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="font-size: 18px;"><i class="fa-solid fa-users-gear" style="margin-right: 8px; color: var(--primary);"></i> Sistem Kullanıcıları Yönetimi</h3>
            <button onclick="openAddModal()" class="btn-custom btn-custom-primary">
                <i class="fa-solid fa-plus"></i> Yeni Kullanıcı Ekle
            </button>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad Soyad</th>
                        <th>E-Posta</th>
                        <th>Telefon</th>
                        <th>Rol</th>
                        <th>Durum</th>
                        <th style="text-align: right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $usr): ?>
                        <tr>
                            <td><strong>#<?php echo $usr['id']; ?></strong></td>
                            <td><div style="font-weight: 600;"><?php echo htmlspecialchars($usr['first_name'] . ' ' . $usr['last_name']); ?></div></td>
                            <td><?php echo htmlspecialchars($usr['email']); ?></td>
                            <td><?php echo htmlspecialchars($usr['phone'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-primary"><?php echo htmlspecialchars($usr['role_name']); ?></span>
                            </td>
                            <td>
                                <?php if ($usr['status'] === 'active'): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?php echo htmlspecialchars($usr['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <button onclick='openEditModal(<?php echo json_encode($usr); ?>)' class="btn-custom btn-custom-secondary" style="padding: 6px 12px; font-size: 12px;">
                                        <i class="fa-solid fa-pen-to-square"></i> Düzenle
                                    </button>
                                    <?php if ($usr['id'] > 1): ?>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $usr['id']; ?>">
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

<!-- Add/Edit User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Yeni Kullanıcı Ekle</h3>
            <button onclick="hideModal('userModal')" class="modal-close">&times;</button>
        </div>
        <form id="userForm" method="POST" action="">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="userId" value="">
            
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Ad <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="first_name" id="userFirstName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Soyad <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="last_name" id="userLastName" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">E-Posta Adresi <span style="color:var(--danger)">*</span></label>
                    <input type="email" name="email" id="userEmail" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Şifre <span id="pwdReq" style="color:var(--danger)">*</span></label>
                    <input type="password" name="password" id="userPassword" class="form-control" placeholder="Şifreyi giriniz...">
                    <small id="pwdHelp" style="color:var(--text-muted); display:none; margin-top:5px;">Düzenleme modunda şifreyi boş bırakırsanız eski şifre korunacaktır.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Telefon</label>
                    <input type="text" name="phone" id="userPhone" class="form-control">
                </div>
                <!-- Branch Specialty (Request 1) -->
                <div class="form-group" id="branchDalGroup" style="display: none;">
                    <label class="form-label">Branş Dalı (Otomasyon Projesi)</label>
                    <select name="branch_dal" id="userBranchDal" class="form-control">
                        <option value="">Branş Seçilmedi</option>
                        <?php foreach ($projects as $proj_name): ?>
                            <option value="<?php echo htmlspecialchars($proj_name); ?>"><?php echo htmlspecialchars($proj_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Rolü <span style="color:var(--danger)">*</span></label>
                        <select name="role_id" id="userRole" class="form-control" onchange="toggleBranchField()" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hesap Durumu</label>
                        <select name="status" id="userStatus" class="form-control">
                            <option value="active">Aktif</option>
                            <option value="passive">Pasif</option>
                            <option value="suspended">Askıya Alınmış</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="hideModal('userModal')" class="btn-custom btn-custom-secondary">Vazgeç</button>
                <button type="submit" class="btn-custom btn-custom-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleBranchField() {
    const roleSelect = document.getElementById('userRole');
    const branchGroup = document.getElementById('branchDalGroup');
    if (roleSelect.value === '4') {
        branchGroup.style.display = 'block';
    } else {
        branchGroup.style.display = 'none';
        document.getElementById('userBranchDal').value = '';
    }
}

function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Yeni Kullanıcı Ekle';
    document.getElementById('formAction').value = 'add';
    document.getElementById('userId').value = '';
    
    // Clear & setup form
    document.getElementById('userFirstName').value = '';
    document.getElementById('userLastName').value = '';
    document.getElementById('userEmail').value = '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userPassword').required = true;
    document.getElementById('pwdReq').style.display = 'inline';
    document.getElementById('pwdHelp').style.display = 'none';
    document.getElementById('userPhone').value = '';
    document.getElementById('userRole').value = '';
    document.getElementById('userStatus').value = 'active';
    document.getElementById('userBranchDal').value = '';
    
    toggleBranchField();
    showModal('userModal');
}

function openEditModal(usr) {
    document.getElementById('modalTitle').innerText = 'Kullanıcıyı Düzenle';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('userId').value = usr.id;
    
    // Populate form
    document.getElementById('userFirstName').value = usr.first_name;
    document.getElementById('userLastName').value = usr.last_name;
    document.getElementById('userEmail').value = usr.email;
    document.getElementById('userPassword').value = '';
    document.getElementById('userPassword').required = false;
    document.getElementById('pwdReq').style.display = 'none';
    document.getElementById('pwdHelp').style.display = 'block';
    document.getElementById('userPhone').value = usr.phone || '';
    document.getElementById('userRole').value = usr.role_id;
    document.getElementById('userStatus').value = usr.status;
    document.getElementById('userBranchDal').value = usr.branch_dal || '';
    
    toggleBranchField();
    showModal('userModal');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
