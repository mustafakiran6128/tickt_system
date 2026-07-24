<?php
// C:\laragon\www\destek_as\includes\header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base URL for absolute paths
$base_url = '/destek_as';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Detect current page file name for active menu states
$current_page = basename($_SERVER['PHP_SELF']);

// Check authentication unless on login/public page
$public_pages = ['login.php', 'register.php'];
if (!in_array($current_page, $public_pages) && !isLoggedIn()) {
    header('Location: ' . $base_url . '/auth/login.php');
    exit;
}

$user_role = $_SESSION['role_name'] ?? '';
$user_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : 'Misafir';
$avatar_initials = isset($_SESSION['first_name']) ? mb_substr($_SESSION['first_name'], 0, 1) . mb_substr($_SESSION['last_name'], 0, 1) : 'M';
$user_id = $_SESSION['user_id'] ?? 0;

// Handle notification mark all read request
if (isLoggedIn() && isset($_GET['mark_all_read'])) {
    try {
        if ($user_role === 'Müşteri Kullanıcısı') {
            $stmtMark = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE customer_user_id = ?");
            $stmtMark->execute([$user_id]);
        } else {
            $stmtMark = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmtMark->execute([$user_id]);
        }
        $clean_url = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: " . $clean_url);
        exit;
    } catch (\Exception $e) {
        // Fail silently
    }
}

// Fetch user's notifications
$unread_notifications_count = 0;
$user_notifications = [];
if (isLoggedIn()) {
    try {
        if ($user_role === 'Müşteri Kullanıcısı') {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE customer_user_id = ? AND is_read = 0");
            $stmtCount->execute([$user_id]);
            $unread_notifications_count = intval($stmtCount->fetchColumn());

            $stmtList = $pdo->prepare("SELECT * FROM notifications WHERE customer_user_id = ? ORDER BY id DESC LIMIT 5");
            $stmtList->execute([$user_id]);
            $user_notifications = $stmtList->fetchAll();
        } else {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmtCount->execute([$user_id]);
            $unread_notifications_count = intval($stmtCount->fetchColumn());

            $stmtList = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 5");
            $stmtList->execute([$user_id]);
            $user_notifications = $stmtList->fetchAll();
        }
    } catch (\Exception $e) {
        // Fail silently
    }
}

// Fetch sidebar counts
$sidebar_tickets_count = 0;
$sidebar_announcements_count = 0;
if (isLoggedIn()) {
    try {
        $company_id = $_SESSION['company_id'] ?? 1;
        // 1. Count active tickets
        if ($user_role === 'Müşteri Kullanıcısı') {
            $stmtC = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE customer_id = ? AND status_id NOT IN (10, 12, 14)");
            $stmtC->execute([$_SESSION['customer_id'] ?? 0]);
            $sidebar_tickets_count = intval($stmtC->fetchColumn());
        } elseif ($user_role === 'Destek Personeli') {
            $stmtC = $pdo->prepare("
                SELECT COUNT(*) FROM tickets 
                WHERE company_id = ? 
                  AND status_id NOT IN (10, 12, 14) 
                  AND id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND status = 'active')
            ");
            $stmtC->execute([$company_id, $user_id]);
            $sidebar_tickets_count = intval($stmtC->fetchColumn());
        } else {
            // Managers see all active
            $stmtC = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE company_id = ? AND status_id NOT IN (10, 12, 14)");
            $stmtC->execute([$company_id]);
            $sidebar_tickets_count = intval($stmtC->fetchColumn());
        }

        // 2. Count unread announcements in the last 7 days
        if ($user_role === 'Müşteri Kullanıcısı') {
            $stmtA = $pdo->prepare("
                SELECT COUNT(*) FROM announcements 
                WHERE company_id = ? 
                  AND target_audience IN ('All', 'Customers') 
                  AND created_at >= NOW() - INTERVAL 7 DAY
                  AND id NOT IN (SELECT announcement_id FROM user_announcement_reads WHERE user_id = ?)
            ");
            $stmtA->execute([$company_id, $user_id]);
            $sidebar_announcements_count = intval($stmtA->fetchColumn());
        } elseif ($user_role === 'Destek Personeli') {
            $stmtA = $pdo->prepare("
                SELECT COUNT(*) FROM announcements 
                WHERE company_id = ? 
                  AND target_audience IN ('All', 'Staff') 
                  AND created_at >= NOW() - INTERVAL 7 DAY
                  AND id NOT IN (SELECT announcement_id FROM user_announcement_reads WHERE user_id = ?)
            ");
            $stmtA->execute([$company_id, $user_id]);
            $sidebar_announcements_count = intval($stmtA->fetchColumn());
        } else {
            $stmtA = $pdo->prepare("
                SELECT COUNT(*) FROM announcements 
                WHERE company_id = ? 
                  AND created_at >= NOW() - INTERVAL 7 DAY
                  AND id NOT IN (SELECT announcement_id FROM user_announcement_reads WHERE user_id = ?)
            ");
            $stmtA->execute([$company_id, $user_id]);
            $sidebar_announcements_count = intval($stmtA->fetchColumn());
        }
    } catch (\Exception $e) {
        // Fail silently
    }
}

// Fetch details for Sidebar Profile Display
$displayed_role = $user_role;
if ($user_role === 'Müşteri Kullanıcısı') {
    try {
        $stmtUserCats = $pdo->prepare("
            SELECT GROUP_CONCAT(cat.name SEPARATOR ', ') 
            FROM customer_categories cc 
            JOIN categories cat ON cc.category_id = cat.id 
            WHERE cc.customer_id = ?
        ");
        $stmtUserCats->execute([$_SESSION['customer_id'] ?? 0]);
        $services = $stmtUserCats->fetchColumn();
        $profile_detail = $services ? 'Hizmetler: ' . $services : 'Hizmet Tanımsız';
    } catch (\Exception $e) {
        $profile_detail = '';
    }
} elseif ($user_role === 'Destek Personeli') {
    try {
        $stmtUserSkills = $pdo->prepare("
            SELECT GROUP_CONCAT(skill_name SEPARATOR ', ') 
            FROM agent_skills 
            WHERE user_id = ?
        ");
        $stmtUserSkills->execute([$_SESSION['user_id'] ?? 0]);
        $skills = $stmtUserSkills->fetchColumn();
        $profile_detail = $skills ? 'Uzmanlık: ' . $skills : 'Uzmanlık Tanımsız';
        if ($skills) {
            $displayed_role = $skills . ' Personeli';
        }
    } catch (\Exception $e) {
        $profile_detail = '';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destek A.Ş. | Kurumsal Destek ve Ticket Yönetimi</title>
    <!-- FontAwesome Icon Set -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Premium Stylesheet -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php if (isLoggedIn()): ?>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-headset" style="font-size: 24px; color: var(--primary);"></i>
            <h2>Destek A.Ş.</h2>
        </div>
        <ul class="sidebar-menu">
            <li class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base_url; ?>/index.php">
                    <i class="fa-solid fa-chart-pie"></i> Kontrol Paneli
                </a>
            </li>

            <?php if ($user_role === 'Sistem Sahibi'): ?>
                <!-- System Owner Specific Links -->
                <li class="<?php echo $current_page == 'firmalar.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/admin/firmalar.php">
                        <i class="fa-solid fa-building"></i> Tüm Firmalar
                    </a>
                </li>
                <li class="<?php echo $current_page == 'kullanicilar.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/admin/kullanicilar.php">
                        <i class="fa-solid fa-users-gear"></i> Sistem Kullanıcıları
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($user_role === 'Firma Yöneticisi' || $user_role === 'Sistem Sahibi'): ?>
                <!-- Company Manager Links -->
                <li class="<?php echo $current_page == 'departmanlar.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/admin/departmanlar.php">
                        <i class="fa-solid fa-sitemap"></i> Departman Yönetimi
                    </a>
                </li>
                <li class="<?php echo $current_page == 'musteriler.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/admin/musteriler.php">
                        <i class="fa-solid fa-handshake"></i> Müşteri Firmaları
                    </a>
                </li>
                <li class="<?php echo $current_page == 'kategoriler.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/admin/kategoriler.php">
                        <i class="fa-solid fa-folder-open"></i> Projelerimiz
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($user_role === 'Müşteri Kullanıcısı'): ?>
                <!-- Customer Specific Links -->
                <li class="<?php echo $current_page == 'yeni_ticket.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/customer/yeni_ticket.php">
                        <i class="fa-solid fa-circle-plus"></i> Yeni Destek Talebi
                    </a>
                </li>
                <li class="<?php echo $current_page == 'ticketlarim.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/customer/ticketlarim.php" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <span><i class="fa-solid fa-receipt"></i> Aktif Taleplerim</span>
                        <?php if ($sidebar_tickets_count > 0): ?>
                            <span style="background: var(--danger); color: #fff; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 8px;"><?php echo $sidebar_tickets_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="<?php echo $current_page == 'ticket_gecmisi.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/customer/ticket_gecmisi.php">
                        <i class="fa-solid fa-clock-rotate-left"></i> Çözülenler / Arşiv
                    </a>
                </li>

                <li class="<?php echo $current_page == 'takvim.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/admin/takvim.php">
                        <i class="fa-solid fa-calendar-days"></i> Takvim Görünümü
                    </a>
                </li>
            <?php else: ?>
                <!-- Staff and Management Ticket Links -->
                <li class="<?php echo $current_page == 'ticketlar.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/admin/ticketlar.php" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <span><i class="fa-solid fa-receipt"></i> Aktif Talepler</span>
                        <?php if ($sidebar_tickets_count > 0): ?>
                            <span style="background: var(--danger); color: #fff; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 8px;"><?php echo $sidebar_tickets_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="<?php echo $current_page == 'ticket_gecmisi.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/admin/ticket_gecmisi.php">
                        <i class="fa-solid fa-clock-rotate-left"></i> Çözülenler / Arşiv
                    </a>
                </li>
                <li class="<?php echo $current_page == 'takvim.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/admin/takvim.php">
                        <i class="fa-solid fa-calendar-days"></i> Takvim Görünümü
                    </a>
                </li>
                <li class="<?php echo $current_page == 'kanban.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/admin/kanban.php">
                        <i class="fa-solid fa-table-columns"></i> Kanban Panosu
                    </a>
                </li>
            <?php endif; ?>


            
            <li class="<?php echo $current_page == 'duyurular.php' ? 'active' : ''; ?>">
                <a href="<?php echo $base_url; ?>/admin/duyurular.php" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <span><i class="fa-solid fa-bullhorn"></i> Duyurular</span>
                    <?php if ($sidebar_announcements_count > 0): ?>
                        <span style="background: var(--accent); color: #fff; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 8px;"><?php echo $sidebar_announcements_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <?php if ($user_role !== 'Müşteri Kullanıcısı' && $user_role !== 'Gözlemci Kullanıcı'): ?>
                <!-- Reports for Staff/Managers -->
                <li class="<?php echo $current_page == 'raporlar.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/admin/raporlar.php">
                        <i class="fa-solid fa-chart-line"></i> Raporlama
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($user_role === 'Firma Yöneticisi' || $user_role === 'Sistem Sahibi'): ?>
                <li class="<?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>/admin/audit_logs.php">
                        <i class="fa-solid fa-clock-rotate-left"></i> İşlem Logları
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        
        <!-- Sidebar User Card -->
        <div class="sidebar-user" style="padding: 15px; background: rgba(255,255,255,0.02); border-radius: 12px; margin-top: auto; display: flex; align-items: center; gap: 10px; border: 1px solid var(--border-color); overflow: hidden;">
            <div class="sidebar-user-avatar" style="width: 40px; height: 40px; border-radius: 50%; background: var(--grad-primary); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; font-size: 14px; flex-shrink: 0;"><?php echo strtoupper($avatar_initials); ?></div>
            <div class="sidebar-user-info" style="flex-grow: 1; overflow: hidden; display: flex; flex-direction: column; gap: 2px;">
                <div class="sidebar-user-name" style="font-weight: 700; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-primary);"><?php echo htmlspecialchars($user_name); ?></div>
                <?php if (!empty($profile_detail)): ?>
                    <div style="font-size: 9px; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 4px;" title="<?php echo htmlspecialchars($profile_detail); ?>">
                        <i class="fa-solid fa-tags" style="font-size: 8px;"></i> <span><?php echo htmlspecialchars($profile_detail); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <a href="<?php echo $base_url; ?>/auth/logout.php" class="sidebar-user-logout" title="Çıkış Yap" style="color: var(--text-muted); font-size: 16px; margin-left: 5px; transition: color 0.2s;">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </aside>
    
    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <!-- Top Navigation Bar -->
        <header class="topbar">
            <div class="page-title">
                <h1>
                    <?php
                    // Display meaningful page titles
                    switch ($current_page) {
                        case 'index.php': echo 'Yönetim Kontrol Paneli'; break;
                        case 'firmalar.php': echo 'Firma Listesi'; break;
                        case 'kullanicilar.php': echo 'Kullanıcı Yönetimi'; break;
                        case 'departmanlar.php': echo 'Departman Yönetimi'; break;
                        case 'musteriler.php': echo 'Müşteri Yönetim Paneli'; break;
                        case 'kategoriler.php': echo 'Projelerimiz'; break;
                        case 'yeni_ticket.php': echo 'Yeni Destek Talebi Oluştur'; break;
                        case 'ticketlarim.php': echo 'Aktif Destek Taleplerim'; break;
                        case 'ticketlar.php': echo 'Aktif Destek Talepleri'; break;
                        case 'ticket_gecmisi.php': echo 'Çözülen Talepler / Arşiv'; break;
                        case 'kanban.php': echo 'Kanban İş Akış Panosu'; break;
                        case 'takvim.php': echo 'Takvim İş Planlama Görünümü'; break;

                        case 'duyurular.php': echo 'Duyurular ve Kesinti Bildirimleri'; break;
                        case 'raporlar.php': echo 'Sistem SLA ve Performans Raporları'; break;
                        case 'audit_logs.php': echo 'Audit Log (Sistem İşlem Geçmişi)'; break;
                        default: echo 'Destek Sistemi';
                    }
                    ?>
                </h1>
            </div>
            <div class="topbar-actions">
                <div class="notification-bell-container" style="position: relative;">
                    <div class="notification-bell" onclick="toggleNotificationsDropdown(event)" style="position: relative; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 50%;">
                        <i class="fa-regular fa-bell" style="font-size: 16px; color: var(--text-primary);"></i>
                        <?php if ($unread_notifications_count > 0): ?>
                            <span class="notification-count" style="position: absolute; top: -2px; right: -2px; background: var(--accent); color: #fff; border-radius: 50%; font-size: 8px; width: 14px; height: 14px; display: flex; align-items: center; justify-content: center; font-weight: 700; border: 2px solid var(--bg-sidebar);"><?php echo $unread_notifications_count; ?></span>
                        <?php endif; ?>
                    </div>
                    <!-- Dropdown List -->
                    <div id="notificationsDropdown" style="display: none; position: absolute; right: 0; top: 42px; width: 280px; background: var(--bg-sidebar); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: var(--shadow-md); z-index: 1000; padding: 10px 0;">
                        <div style="padding: 8px 15px; border-bottom: 1px solid var(--border-color); font-weight: 700; font-size: 12px; color: var(--text-primary); display: flex; justify-content: space-between; align-items: center;">
                            <span>Bildirimler</span>
                            <?php if ($unread_notifications_count > 0): ?>
                                <a href="#" onclick="markAllNotificationsRead(event)" style="font-size: 10px; color: var(--accent); text-decoration: none; font-weight: 500;">Tümünü Okundu Say</a>
                            <?php endif; ?>
                        </div>
                        <div style="max-height: 250px; overflow-y: auto;">
                            <?php if (empty($user_notifications)): ?>
                                <div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 11px;">Yeni bildiriminiz bulunmuyor.</div>
                            <?php else: ?>
                                <?php foreach ($user_notifications as $notif): 
                                    $notif_link = '#';
                                    if (preg_match('/YEB-\d{4}-\d{6}/', $notif['title'] . ' ' . $notif['content'], $matches)) {
                                        $stmtTicketId = $pdo->prepare("SELECT id FROM tickets WHERE ticket_number = ? LIMIT 1");
                                        $stmtTicketId->execute([$matches[0]]);
                                        $t_id = $stmtTicketId->fetchColumn();
                                        if ($t_id) {
                                            $notif_link = "/destek_as/admin/ticket_detay.php?id=" . $t_id;
                                        }
                                    }
                                ?>
                                    <div onclick="if('<?php echo $notif_link; ?>' !== '#') { window.location.href='<?php echo $notif_link; ?>'; }" style="padding: 10px 15px; border-bottom: 1px solid var(--border-color); font-size: 11px; transition: background 0.2s; cursor: <?php echo $notif_link !== '#' ? 'pointer' : 'default'; ?>; background: <?php echo $notif['is_read'] ? 'transparent' : 'rgba(178, 94, 44, 0.04)'; ?>;" onmouseover="this.style.background='rgba(178, 94, 44, 0.08)'" onmouseout="this.style.background='<?php echo $notif['is_read'] ? 'transparent' : 'rgba(178, 94, 44, 0.04)'; ?>'">
                                        <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 2px; display: flex; justify-content: space-between; align-items: center;">
                                            <span><?php echo htmlspecialchars($notif['title']); ?></span>
                                            <?php if (!$notif['is_read']): ?>
                                                <span style="width: 5px; height: 5px; background: var(--accent); border-radius: 50%;"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="color: var(--text-secondary); font-size: 10px; line-height: 1.4; margin-bottom: 3px;"><?php echo htmlspecialchars($notif['content']); ?></div>
                                        <small style="color: var(--text-muted); font-size: 8px;"><?php echo date('d.m.Y H:i', strtotime($notif['created_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div style="font-size: 13px; color: var(--text-secondary);">
                    <i class="fa-regular fa-calendar-check" style="margin-right: 6px;"></i>
                    <?php echo date('d.m.Y'); ?>
                </div>
            </div>
        </header>
        
        <!-- Main Scrollable Content Area -->
        <main class="content-container">
    <?php endif; ?>

<script>
function toggleNotificationsDropdown(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('notificationsDropdown');
    if (dropdown.style.display === 'none' || !dropdown.style.display) {
        dropdown.style.display = 'block';
    } else {
        dropdown.style.display = 'none';
    }
}
function markAllNotificationsRead(event) {
    event.preventDefault();
    window.location.href = '?mark_all_read=1';
}
// Close dropdown when clicking outside
document.addEventListener('click', function() {
    const dropdown = document.getElementById('notificationsDropdown');
    if (dropdown) dropdown.style.display = 'none';
});
</script>
