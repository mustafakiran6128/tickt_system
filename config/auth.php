<?php
// C:\laragon\www\destek_as\config\auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkAccess($allowed_roles = []) {
    if (!isLoggedIn()) {
        header('Location: /destek_as/auth/login.php');
        exit;
    }

    if (empty($allowed_roles)) {
        return true;
    }

    $user_role = $_SESSION['role_name'] ?? '';
    
    if (in_array($user_role, $allowed_roles) || $user_role === 'Sistem Sahibi') {
        return true;
    }

    // Access denied page/message
    http_response_code(403);
    echo "
    <div style='font-family: sans-serif; text-align: center; margin-top: 100px;'>
        <h1 style='color: #ef4444;'>Yetkisiz Erişim!</h1>
        <p>Bu sayfayı görüntülemek için yetkiniz bulunmamaktadır.</p>
        <a href='/destek_as/auth/login.php' style='color: #8b5cf6; text-decoration: none; font-weight: bold;'>Giriş Ekranına Dön</a>
    </div>";
    exit;
}

function logActivity($pdo, $action, $record_type = null, $record_id = null, $old_value = null, $new_value = null) {
    $company_id = $_SESSION['company_id'] ?? null;
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (company_id, user_id, action, record_type, record_id, ip_address, old_value, new_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$company_id, $user_id, $action, $record_type, $record_id, $ip_address, $old_value, $new_value]);
    } catch (\Exception $e) {
        // Silently fail or log to server error log
        error_log("Audit log error: " . $e->getMessage());
    }
}

function createNotification($pdo, $title, $content, $target_user_id = null, $target_customer_user_id = null, $type = 'system') {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, customer_user_id, title, content, notification_type, is_read) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$target_user_id, $target_customer_user_id, $title, $content, $type]);
    } catch (\Exception $e) {
        error_log("Notification error: " . $e->getMessage());
    }
}
