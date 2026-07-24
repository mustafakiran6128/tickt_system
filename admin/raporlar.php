<?php
ob_start();
// C:\laragon\www\destek_as\admin\raporlar.php
require_once __DIR__ . '/../includes/header.php';
checkAccess(['Sistem Sahibi', 'Firma Yöneticisi', 'Departman Yöneticisi', 'Destek Personeli']);

$user_role = $_SESSION['role_name'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;
$company_id = $_SESSION['company_id'] ?? 1;

$is_manager = in_array($user_role, ['Sistem Sahibi', 'Firma Yöneticisi', 'Departman Yöneticisi']);
$error = '';
$success = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && !$is_manager) {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if (!empty($title) && !empty($content)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO staff_reports (user_id, title, content) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $title, $content]);
                $success = 'Raporunuz başarıyla yöneticiye gönderildi!';
                logActivity($pdo, "Yöneticiye yeni personel raporu gönderildi: " . $title, "staff_reports", $pdo->lastInsertId());
            } catch (\Exception $e) {
                $error = 'Rapor gönderilirken hata oluştu: ' . $e->getMessage();
            }
        } else {
            $error = 'Başlık ve rapor içeriği alanları zorunludur.';
        }
    } elseif ($action === 'delete' && $is_manager) {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM staff_reports WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Rapor kaydı başarıyla silindi!';
                logActivity($pdo, "Personel raporu silindi. ID: " . $id, "staff_reports", $id);
            } catch (\Exception $e) {
                $error = 'Rapor silinirken hata oluştu.';
            }
        }
    }
}

// Fetch reports
$reports = [];
try {
    if ($is_manager) {
        // Manager sees all reports submitted by staff
        $stmt = $pdo->query("
            SELECT r.*, u.first_name, u.last_name, 
                   (SELECT skill_name FROM agent_skills WHERE user_id = u.id LIMIT 1) AS branch
            FROM staff_reports r
            JOIN users u ON r.user_id = u.id
            ORDER BY r.id DESC
        ");
        $reports = $stmt->fetchAll();
    } else {
        // Specialist sees only their submitted reports
        $stmt = $pdo->prepare("
            SELECT r.*, u.first_name, u.last_name, 
                   (SELECT skill_name FROM agent_skills WHERE user_id = u.id LIMIT 1) AS branch
            FROM staff_reports r
            JOIN users u ON r.user_id = u.id
            WHERE r.user_id = ?
            ORDER BY r.id DESC
        ");
        $stmt->execute([$user_id]);
        $reports = $stmt->fetchAll();
    }
} catch (\Exception $e) {
    // Fail silently
}
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

    <!-- Layout Grid -->
    <div style="display: grid; grid-template-columns: <?php echo !$is_manager ? '2.2fr 1fr' : '1fr'; ?>; gap: 30px; align-items: flex-start;">
        
        <!-- Left/Main Side: Submitted Reports Table -->
        <div class="glass-card">
            <h3 style="font-size: 18px; margin-bottom: 25px;">
                <i class="fa-solid fa-file-invoice" style="margin-right: 8px; color: var(--primary);"></i> 
                <?php echo $is_manager ? 'Personelden Gelen Raporlar' : 'Gönderdiğim Raporlarım'; ?>
            </h3>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <?php if ($is_manager): ?>
                                <th>Gönderen Çalışan</th>
                                <th>Branşı / Alanı</th>
                            <?php endif; ?>
                            <th>Rapor Başlığı</th>
                            <th style="text-align: right;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr>
                                <td colspan="<?php echo $is_manager ? '5' : '3'; ?>" style="text-align: center; color: var(--text-muted);">Herhangi bir rapor kaydı bulunamadı.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reports as $rep): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($rep['created_at'])); ?></td>
                                    <?php if ($is_manager): ?>
                                        <td><strong><?php echo htmlspecialchars($rep['first_name'] . ' ' . $rep['last_name']); ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($rep['branch'] ?: 'Genel'); ?></span></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($rep['title']); ?></td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <button onclick="viewReport(<?php echo htmlspecialchars(json_encode($rep)); ?>)" class="btn-custom btn-custom-secondary" style="padding: 6px 12px; font-size: 12px;">
                                                <i class="fa-solid fa-eye"></i> Oku
                                            </button>
                                            <?php if ($is_manager): ?>
                                                <form method="POST" action="" onsubmit="return confirm('Bu raporu silmek istediğinize emin misiniz?');" style="margin: 0; display: inline-block;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $rep['id']; ?>">
                                                    <button type="submit" class="btn-custom btn-custom-danger" style="padding: 6px 12px; font-size: 12px;">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!$is_manager): ?>
            <!-- Right Side: Submit Report Form (Visible to staff only) -->
            <div class="glass-card">
                <h3 style="font-size: 18px; margin-bottom: 25px;"><i class="fa-solid fa-paper-plane" style="margin-right: 8px; color: var(--accent);"></i> Yöneticiye Rapor Yaz</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">

                    <div class="form-group">
                        <label class="form-label">Rapor Konusu / Başlık <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Rapor konusu girin..." required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Rapor İçeriği ve Detayları <span style="color:var(--danger)">*</span></label>
                        <textarea name="content" class="form-control" rows="8" placeholder="Detaylı performans veya günlük çalışma raporunuzu buraya yazın..." required></textarea>
                    </div>

                    <button type="submit" class="btn-custom btn-custom-primary" style="width: 100%; margin-top: 15px;">
                        Raporu Gönder <i class="fa-solid fa-paper-plane" style="margin-left: 6px;"></i>
                    </button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- View Report Modal -->
<div id="viewReportModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fa-solid fa-file-signature" style="color: var(--primary); margin-right: 8px;"></i> Rapor İçeriği</h3>
            <button onclick="hideModal('viewReportModal')" class="modal-close">&times;</button>
        </div>
        <div class="modal-body" style="display: flex; flex-direction: column; gap: 15px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <span id="rep_author" class="badge badge-info">Çalışan</span>
                <small id="rep_date" style="color: var(--text-muted);">Tarih</small>
            </div>
            <div>
                <h4 style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;">Rapor Başlığı</h4>
                <div id="rep_title" style="font-size: 14px; font-weight: 700; color: var(--text-primary);">Rapor Başlığı</div>
            </div>
            <div>
                <h4 style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;">Rapor Detayı</h4>
                <div id="rep_content" style="font-size: 13px; line-height: 1.6; color: var(--text-secondary); background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px; max-height: 250px; overflow-y: auto; white-space: pre-wrap;">İçerik...</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="hideModal('viewReportModal')" class="btn-custom btn-custom-secondary">Kapat</button>
        </div>
    </div>
</div>

<script>
function viewReport(rep) {
    document.getElementById('rep_author').innerText = rep.first_name + ' ' + rep.last_name + ' (' + (rep.branch ? rep.branch : 'Genel') + ')';
    document.getElementById('rep_date').innerText = new Date(rep.created_at).toLocaleDateString('tr-TR', {
        day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
    document.getElementById('rep_title').innerText = rep.title;
    document.getElementById('rep_content').innerText = rep.content;
    showModal('viewReportModal');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
