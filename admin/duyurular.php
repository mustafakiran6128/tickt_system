<?php
ob_start();
// C:\laragon\www\destek_as\admin\duyurular.php
require_once __DIR__ . '/../includes/header.php';

$user_role = $_SESSION['role_name'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;
$company_id = $_SESSION['company_id'] ?? 1;

$can_manage = in_array($user_role, ['Sistem Sahibi', 'Firma Yöneticisi', 'Departman Yöneticisi']);
$error = '';
$success = '';

// Handle CRUD operations (Add / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $target_audience = $_POST['target_audience'] ?? 'All';

        if (!empty($title) && !empty($content)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO announcements (company_id, title, content, target_audience) VALUES (?, ?, ?, ?)");
                $stmt->execute([$company_id, $title, $content, $target_audience]);
                $success = 'Duyuru başarıyla yayınlandı!';
                logActivity($pdo, "Yeni duyuru yayınlandı: " . $title, "announcements", $pdo->lastInsertId());
            } catch (\Exception $e) {
                $error = 'Duyuru kaydedilirken hata oluştu: ' . $e->getMessage();
            }
        } else {
            $error = 'Başlık ve içerik alanları zorunludur.';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Duyuru başarıyla silindi!';
                logActivity($pdo, "Duyuru silindi. ID: " . $id, "announcements", $id);
            } catch (\Exception $e) {
                $error = 'Duyuru silinirken hata oluştu.';
            }
        }
    }
}

// Fetch announcements based on role
try {
    if ($can_manage) {
        // Managers see everything
        $stmt = $pdo->prepare("SELECT * FROM announcements WHERE company_id = ? ORDER BY id DESC");
        $stmt->execute([$company_id]);
    } elseif ($user_role === 'Müşteri Kullanıcısı') {
        // Customers see All or Customers
        $stmt = $pdo->prepare("SELECT * FROM announcements WHERE company_id = ? AND target_audience IN ('All', 'Customers') ORDER BY id DESC");
        $stmt->execute([$company_id]);
    } else {
        // Staff see All or Staff
        $stmt = $pdo->prepare("SELECT * FROM announcements WHERE company_id = ? AND target_audience IN ('All', 'Staff') ORDER BY id DESC");
        $stmt->execute([$company_id]);
    }
    $announcements = $stmt->fetchAll();

    // Mark visible announcements as read
    if (!empty($announcements) && $user_id > 0) {
        foreach ($announcements as $ann) {
            $stmtRead = $pdo->prepare("INSERT IGNORE INTO user_announcement_reads (user_id, announcement_id) VALUES (?, ?)");
            $stmtRead->execute([$user_id, $ann['id']]);
        }
    }
} catch (\Exception $e) {
    $announcements = [];
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
    <div style="display: grid; grid-template-columns: <?php echo $can_manage ? '2.2fr 1fr' : '1fr'; ?>; gap: 30px; align-items: flex-start;">
        <!-- Left Side: Announcements List -->
        <div class="glass-card">
            <h3 style="font-size: 18px; margin-bottom: 25px;"><i class="fa-solid fa-bullhorn" style="margin-right: 8px; color: var(--primary);"></i> Yayınlanan Duyurular</h3>

            <?php if (empty($announcements)): ?>
                <div style="text-align: center; color: var(--text-muted); padding: 40px 0;">Yayınlanmış herhangi bir duyuru bulunmuyor.</div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach ($announcements as $ann): ?>
                        <div style="padding: 20px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px; position: relative;">
                            <!-- Header Info -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                <div>
                                    <h4 style="font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;"><?php echo htmlspecialchars($ann['title']); ?></h4>
                                    <small style="color: var(--text-muted);"><?php echo date('d.m.Y H:i', strtotime($ann['created_at'])); ?></small>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <?php 
                                    $aud_label = 'Herkes';
                                    $aud_color = 'var(--primary)';
                                    if ($ann['target_audience'] === 'Customers') { $aud_label = 'Sadece Müşteriler'; $aud_color = 'var(--accent)'; }
                                    if ($ann['target_audience'] === 'Staff') { $aud_label = 'Sadece Uzmanlar'; $aud_color = 'var(--warning)'; }
                                    ?>
                                    <span class="badge" style="background: <?php echo $aud_color; ?>22; color: <?php echo $aud_color; ?>; border: 1px solid <?php echo $aud_color; ?>55; font-size: 10px; padding: 3px 8px;">
                                        <?php echo $aud_label; ?>
                                    </span>
                                    
                                    <?php if ($can_manage): ?>
                                        <form method="POST" action="" onsubmit="return confirm('Bu duyuruyu silmek istediğinize emin misiniz?');" style="margin: 0;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $ann['id']; ?>">
                                            <button type="submit" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 14px;" title="Duyuruyu Sil">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Content -->
                            <div style="font-size: 13px; line-height: 1.6; color: var(--text-secondary); white-space: pre-wrap;"><?php echo htmlspecialchars($ann['content']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($can_manage): ?>
            <!-- Right Side: Create Announcement Form -->
            <div class="glass-card">
                <h3 style="font-size: 18px; margin-bottom: 25px;"><i class="fa-solid fa-pen-to-square" style="margin-right: 8px; color: var(--accent);"></i> Yeni Duyuru Yayınla</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">

                    <div class="form-group">
                        <label class="form-label">Duyuru Başlığı <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Başlık girin..." required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Hedef Kitle <span style="color:var(--danger)">*</span></label>
                        <select name="target_audience" class="form-control" required>
                            <option value="All">Herkes (Müşteriler & Çalışanlar)</option>
                            <option value="Staff">Sadece Uzmanlar / Çalışanlar</option>
                            <option value="Customers">Sadece Müşteriler</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Duyuru İçeriği <span style="color:var(--danger)">*</span></label>
                        <textarea name="content" class="form-control" rows="6" placeholder="Duyuru detaylarını buraya yazın..." required></textarea>
                    </div>

                    <button type="submit" class="btn-custom btn-custom-primary" style="width: 100%; margin-top: 15px;">
                        Duyuruyu Yayınla <i class="fa-solid fa-paper-plane" style="margin-left: 6px;"></i>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
