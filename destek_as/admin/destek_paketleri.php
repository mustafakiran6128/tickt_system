<?php
// C:\laragon\www\destek_as\admin\destek_paketleri.php
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
        $ticket_limit = intval($_POST['ticket_limit'] ?? -1);
        $support_hours = $_POST['support_hours'] ?? '9/5';
        $response_sla = !empty($_POST['response_sla']) ? intval($_POST['response_sla']) : null;
        $resolution_sla = !empty($_POST['resolution_sla']) ? intval($_POST['resolution_sla']) : null;
        $dedicated_agent = isset($_POST['dedicated_agent']) ? 1 : 0;
        $critical_intervention = isset($_POST['critical_intervention']) ? 1 : 0;
        $price = floatval($_POST['price'] ?? 0.00);
        $status = $_POST['status'] ?? 'active';

        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO support_packages 
                    (company_id, name, description, ticket_limit, support_hours, response_sla, resolution_sla, dedicated_agent, critical_intervention, price, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$company_id, $name, $description, $ticket_limit, $support_hours, $response_sla, $resolution_sla, $dedicated_agent, $critical_intervention, $price, $status]);
                $success = 'Destek paketi başarıyla eklendi!';
                logActivity($pdo, "Yeni destek paketi eklendi: " . $name, "support_packages", $pdo->lastInsertId());
            } catch (\Exception $e) {
                $error = 'Hata oluştu: ' . $e->getMessage();
            }
        } else {
            $error = 'Paket adı zorunludur.';
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $ticket_limit = intval($_POST['ticket_limit'] ?? -1);
        $support_hours = $_POST['support_hours'] ?? '9/5';
        $response_sla = !empty($_POST['response_sla']) ? intval($_POST['response_sla']) : null;
        $resolution_sla = !empty($_POST['resolution_sla']) ? intval($_POST['resolution_sla']) : null;
        $dedicated_agent = isset($_POST['dedicated_agent']) ? 1 : 0;
        $critical_intervention = isset($_POST['critical_intervention']) ? 1 : 0;
        $price = floatval($_POST['price'] ?? 0.00);
        $status = $_POST['status'] ?? 'active';

        if ($id > 0 && !empty($name)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE support_packages 
                    SET name = ?, description = ?, ticket_limit = ?, support_hours = ?, response_sla = ?, resolution_sla = ?, dedicated_agent = ?, critical_intervention = ?, price = ?, status = ? 
                    WHERE id = ? AND company_id = ?
                ");
                $stmt->execute([$name, $description, $ticket_limit, $support_hours, $response_sla, $resolution_sla, $dedicated_agent, $critical_intervention, $price, $status, $id, $company_id]);
                $success = 'Destek paketi başarıyla güncellendi!';
                logActivity($pdo, "Destek paketi güncellendi: " . $name, "support_packages", $id);
            } catch (\Exception $e) {
                $error = 'Hata oluştu: ' . $e->getMessage();
            }
        } else {
            $error = 'Paket adı zorunludur.';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM support_packages WHERE id = ? AND company_id = ?");
                $stmt->execute([$id, $company_id]);
                $success = 'Destek paketi silindi!';
                logActivity($pdo, "Destek paketi silindi. ID: " . $id, "support_packages", $id);
            } catch (\Exception $e) {
                $error = 'Paket silinirken hata oluştu (Müşterilere tanımlı olabilir).';
            }
        }
    }
}

// Fetch support packages
$stmt = $pdo->prepare("SELECT * FROM support_packages WHERE company_id = ? ORDER BY id ASC");
$stmt->execute([$company_id]);
$packages = $stmt->fetchAll();
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
            <h3 style="font-size: 18px;"><i class="fa-solid fa-cubes" style="margin-right: 8px; color: var(--primary);"></i> Destek Paketleri</h3>
            <button onclick="openAddModal()" class="btn-custom btn-custom-primary">
                <i class="fa-solid fa-plus"></i> Yeni Paket Ekle
            </button>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Paket Adı</th>
                        <th>Aylık Limit</th>
                        <th>Destek Süresi</th>
                        <th>SLA İlk Yanıt</th>
                        <th>SLA Çözüm</th>
                        <th>Özellikler</th>
                        <th>Fiyat</th>
                        <th>Durum</th>
                        <th style="text-align: right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($packages)): ?>
                        <tr><td colspan="9" style="text-align: center; color: var(--text-muted);">Kayıtlı destek paketi bulunmuyor.</td></tr>
                    <?php else: ?>
                        <?php foreach ($packages as $pkg): ?>
                            <tr>
                                <td>
                                    <strong style="font-size:15px; color:var(--text-primary);"><?php echo htmlspecialchars($pkg['name']); ?></strong>
                                    <div style="font-size:12px; color:var(--text-secondary);"><?php echo htmlspecialchars($pkg['description'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <?php echo $pkg['ticket_limit'] == -1 ? '<span style="color:var(--accent); font-weight:bold;">Sınırsız</span>' : $pkg['ticket_limit'] . ' Talep'; ?>
                                </td>
                                <td><strong><?php echo $pkg['support_hours']; ?></strong></td>
                                <td><?php echo $pkg['response_sla'] ? $pkg['response_sla'] . ' dk' : '-'; ?></td>
                                <td><?php echo $pkg['resolution_sla'] ? $pkg['resolution_sla'] . ' dk' : '-'; ?></td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:4px; font-size:11px;">
                                        <?php if ($pkg['dedicated_agent']): ?>
                                            <span style="color:var(--accent);"><i class="fa-solid fa-check"></i> Özel Temsilci</span>
                                        <?php endif; ?>
                                        <?php if ($pkg['critical_intervention']): ?>
                                            <span style="color:var(--success);"><i class="fa-solid fa-check"></i> Kritik Müdahale</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><strong><?php echo number_format($pkg['price'], 2, ',', '.'); ?> TL</strong></td>
                                <td>
                                    <?php if ($pkg['status'] === 'active'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <button onclick='openEditModal(<?php echo json_encode($pkg); ?>)' class="btn-custom btn-custom-secondary" style="padding: 6px 12px; font-size: 12px;">
                                            <i class="fa-solid fa-pen-to-square"></i> Düzenle
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Bu paketi silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">
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

<!-- Add/Edit Package Modal -->
<div id="packageModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Yeni Paket Ekle</h3>
            <button onclick="hideModal('packageModal')" class="modal-close">&times;</button>
        </div>
        <form id="packageForm" method="POST" action="">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="packageId" value="">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Paket Adı <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="name" id="pkgName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" id="pkgDescription" class="form-control" rows="2"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Aylık Talep Limiti (-1: Sınırsız)</label>
                        <input type="number" name="ticket_limit" id="pkgTicketLimit" class="form-control" value="-1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Destek Çalışma Süresi</label>
                        <select name="support_hours" id="pkgSupportHours" class="form-control">
                            <option value="9/5">9/5 (Mesai saatleri)</option>
                            <option value="24/7">24/7 (Sürekli destek)</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">SLA İlk Yanıt Süresi (Dakika)</label>
                        <input type="number" name="response_sla" id="pkgResponseSla" class="form-control" placeholder="Örn: 30">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SLA Çözüm Süresi (Dakika)</label>
                        <input type="number" name="resolution_sla" id="pkgResolutionSla" class="form-control" placeholder="Örn: 240">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Aylık Ücret (TL)</label>
                        <input type="number" step="0.01" name="price" id="pkgPrice" class="form-control" value="0.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Durum</label>
                        <select name="status" id="pkgStatus" class="form-control">
                            <option value="active">Aktif</option>
                            <option value="passive">Pasif</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 20px; align-items: center; margin: 15px 0;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="dedicated_agent" id="pkgDedicated" value="1" style="width:16px; height:16px; accent-color: var(--primary);">
                        <label for="pkgDedicated" class="form-label" style="margin-bottom:0; cursor:pointer;">Özel Müşteri Temsilcisi</label>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="critical_intervention" id="pkgCritical" value="1" style="width:16px; height:16px; accent-color: var(--primary);">
                        <label for="pkgCritical" class="form-label" style="margin-bottom:0; cursor:pointer;">Kritik Durum Müdahalesi</label>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="hideModal('packageModal')" class="btn-custom btn-custom-secondary">Vazgeç</button>
                <button type="submit" class="btn-custom btn-custom-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Yeni Paket Ekle';
    document.getElementById('formAction').value = 'add';
    document.getElementById('packageId').value = '';
    
    // Clear form
    document.getElementById('pkgName').value = '';
    document.getElementById('pkgDescription').value = '';
    document.getElementById('pkgTicketLimit').value = '-1';
    document.getElementById('pkgSupportHours').value = '9/5';
    document.getElementById('pkgResponseSla').value = '';
    document.getElementById('pkgResolutionSla').value = '';
    document.getElementById('pkgPrice').value = '0.00';
    document.getElementById('pkgDedicated').checked = false;
    document.getElementById('pkgCritical').checked = false;
    document.getElementById('pkgStatus').value = 'active';
    
    showModal('packageModal');
}

function openEditModal(pkg) {
    document.getElementById('modalTitle').innerText = 'Destek Paketini Düzenle';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('packageId').value = pkg.id;
    
    // Populate form
    document.getElementById('pkgName').value = pkg.name;
    document.getElementById('pkgDescription').value = pkg.description || '';
    document.getElementById('pkgTicketLimit').value = pkg.ticket_limit;
    document.getElementById('pkgSupportHours').value = pkg.support_hours;
    document.getElementById('pkgResponseSla').value = pkg.response_sla || '';
    document.getElementById('pkgResolutionSla').value = pkg.resolution_sla || '';
    document.getElementById('pkgPrice').value = pkg.price;
    document.getElementById('pkgDedicated').checked = pkg.dedicated_agent == 1;
    document.getElementById('pkgCritical').checked = pkg.critical_intervention == 1;
    document.getElementById('pkgStatus').value = pkg.status;
    
    showModal('packageModal');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
