<?php
// C:\laragon\www\destek_as\auth\login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Automatically seed database if empty
require_once __DIR__ . '/../config/seed.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header('Location: /destek_as/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        try {
            // Find user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Fetch role and company
                $stmtRole = $pdo->prepare("
                    SELECT ur.role_id, r.name AS role_name, ur.company_id 
                    FROM user_roles ur
                    JOIN roles r ON ur.role_id = r.id
                    WHERE ur.user_id = ?
                ");
                $stmtRole->execute([$user['id']]);
                $roleMapping = $stmtRole->fetch();

                if ($roleMapping) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['role_id'] = $roleMapping['role_id'];
                    $_SESSION['role_name'] = $roleMapping['role_name'];
                    $_SESSION['company_id'] = $roleMapping['company_id'] ?? 1; // Default to company 1

                    // If customer user, fetch customer_id
                    if ($roleMapping['role_name'] === 'Müşteri Kullanıcısı') {
                        $stmtCust = $pdo->prepare("SELECT customer_id FROM customer_users WHERE user_id = ?");
                        $stmtCust->execute([$user['id']]);
                        $cust = $stmtCust->fetch();
                        $_SESSION['customer_id'] = $cust['customer_id'] ?? null;
                    }

                    // Log activity
                    logActivity($pdo, "Sisteme giriş yapıldı", "users", $user['id']);

                    header('Location: /destek_as/index.php');
                    exit;
                } else {
                    $error = 'Kullanıcıya atanmış geçerli bir rol bulunamadı.';
                }
            } else {
                $error = 'Hatalı e-posta adresi veya şifre!';
            }
        } catch (\Exception $e) {
            $error = 'Sistem hatası oluştu: ' . $e->getMessage();
        }
    } else {
        $error = 'Lütfen tüm alanları doldurun.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap | Destek A.Ş. Portalı</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/destek_as/assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: var(--bg-main);
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo i {
            font-size: 40px;
            background: var(--grad-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        .login-logo h1 {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.5px;
            background: var(--grad-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .login-logo p {
            color: var(--text-secondary);
            font-size: 13px;
            margin-top: 5px;
        }
        .error-message {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--danger);
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .test-accounts {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            font-size: 12px;
        }
        .test-accounts h4 {
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-weight: 600;
        }
        .test-accounts ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .test-accounts li {
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
        }
        .test-accounts span {
            color: var(--text-secondary);
            font-family: monospace;
        }
    </style>
</head>
<body>

<div class="glass-card login-card">
    <div class="login-logo">
        <i class="fa-solid fa-headset"></i>
        <h1>Destek A.Ş.</h1>
        <p>Kurumsal Destek ve Ticket Portalı</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-message">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label" for="email">E-Posta Adresi</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="örnek@destek.com" required autocomplete="email">
        </div>
        <div class="form-group">
            <label class="form-label" for="password">Şifre</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-custom btn-custom-primary" style="width: 100%; justify-content: center; margin-top: 10px;">
            Giriş Yap <i class="fa-solid fa-right-to-bracket" style="margin-left: 6px;"></i>
        </button>
    </form>

    <div class="test-accounts" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color);">
        <h4 style="margin-bottom: 15px; text-align: center; color: var(--accent);"><i class="fa-solid fa-bolt"></i> Hızlı Demo Girişleri</h4>
        
        <div style="display: grid; grid-template-columns: 1.1fr 1fr; gap: 20px;">
            <!-- Left Column: Specialists -->
            <div>
                <h5 style="color: var(--text-secondary); margin-bottom: 10px; font-size: 11px; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 4px;">🛠️ Destek Uzmanları</h5>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <!-- Yazılım -->
                    <button type="button" onclick="quickLogin('keremyazici@destek.com', 'password')" class="btn-custom btn-custom-secondary" style="width: 100%; font-size: 10px; padding: 5px 8px; text-align: left; display: flex; justify-content: space-between; align-items: center; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.15);">
                        <span>Kerem Yazıcı</span>
                        <span class="badge badge-info" style="font-size: 8px; padding: 1px 4px; color: var(--primary);">Veteriner</span>
                    </button>
                    <button type="button" onclick="quickLogin('selinyilmaz@destek.com', 'password')" class="btn-custom btn-custom-secondary" style="width: 100%; font-size: 10px; padding: 5px 8px; text-align: left; display: flex; justify-content: space-between; align-items: center; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.15);">
                        <span>Selin Yılmaz</span>
                        <span class="badge badge-info" style="font-size: 8px; padding: 1px 4px; color: var(--primary);">Restoran</span>
                    </button>
                    <button type="button" onclick="quickLogin('boracelik@destek.com', 'password')" class="btn-custom btn-custom-secondary" style="width: 100%; font-size: 10px; padding: 5px 8px; text-align: left; display: flex; justify-content: space-between; align-items: center; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.15);">
                        <span>Bora Çelik</span>
                        <span class="badge badge-info" style="font-size: 8px; padding: 1px 4px; color: var(--primary);">Otel</span>
                    </button>
                    <!-- Donanım -->
                    <button type="button" onclick="quickLogin('hakandemir@destek.com', 'password')" class="btn-custom btn-custom-secondary" style="width: 100%; font-size: 10px; padding: 5px 8px; text-align: left; display: flex; justify-content: space-between; align-items: center; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.15);">
                        <span>Hakan Demir</span>
                        <span class="badge badge-warning" style="font-size: 8px; padding: 1px 4px; color: var(--warning);">E-Ticaret</span>
                    </button>
                    <button type="button" onclick="quickLogin('muratkaya@destek.com', 'password')" class="btn-custom btn-custom-secondary" style="width: 100%; font-size: 10px; padding: 5px 8px; text-align: left; display: flex; justify-content: space-between; align-items: center; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.15);">
                        <span>Murat Kaya</span>
                        <span class="badge badge-warning" style="font-size: 8px; padding: 1px 4px; color: var(--warning);">Veteriner</span>
                    </button>
                    <button type="button" onclick="quickLogin('elifsahin@destek.com', 'password')" class="btn-custom btn-custom-secondary" style="width: 100%; font-size: 10px; padding: 5px 8px; text-align: left; display: flex; justify-content: space-between; align-items: center; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.15);">
                        <span>Elif Şahin</span>
                        <span class="badge badge-warning" style="font-size: 8px; padding: 1px 4px; color: var(--warning);">Restoran</span>
                    </button>
                </div>
            </div>
            
            <!-- Right Column: Customers -->
            <div>
                <h5 style="color: var(--text-secondary); margin-bottom: 10px; font-size: 11px; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 4px;">👥 Müşteriler</h5>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <button type="button" onclick="quickLogin('musteri@destek.com', 'password')" class="btn-custom btn-custom-secondary" style="width: 100%; font-size: 10px; padding: 5px 8px; text-align: left; display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.02);">
                        <span>Ahmet Kuzey</span>
                        <span class="badge" style="font-size: 8px; padding: 1px 4px; border: 1px solid var(--border-color);">Tüm Projeler</span>
                    </button>
                    <button type="button" onclick="quickLogin('mehmetbulut@destek.com', 'password')" class="btn-custom btn-custom-secondary" style="width: 100%; font-size: 10px; padding: 5px 8px; text-align: left; display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.02);">
                        <span>Mehmet Bulut</span>
                        <span class="badge" style="font-size: 8px; padding: 1px 4px; border: 1px solid var(--border-color);">Restoran</span>
                    </button>
                    <button type="button" onclick="quickLogin('hasankaradeniz@destek.com', 'password')" class="btn-custom btn-custom-secondary" style="width: 100%; font-size: 10px; padding: 5px 8px; text-align: left; display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.02);">
                        <span>Hasan Karadeniz</span>
                        <span class="badge" style="font-size: 8px; padding: 1px 4px; border: 1px solid var(--border-color);">E-Ticaret</span>
                    </button>
                    <button type="button" onclick="quickLogin('cananege@destek.com', 'password')" class="btn-custom btn-custom-secondary" style="width: 100%; font-size: 10px; padding: 5px 8px; text-align: left; display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.02);">
                        <span>Canan Ege</span>
                        <span class="badge" style="font-size: 8px; padding: 1px 4px; border: 1px solid var(--border-color);">Otel</span>
                    </button>
                </div>
                
                <h5 style="color: var(--text-secondary); margin-top: 15px; margin-bottom: 8px; font-size: 11px; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 4px;">👑 Yönetim</h5>
                <button type="button" onclick="quickLogin('admin@destek.com', 'admin')" class="btn-custom btn-custom-secondary" style="width: 100%; font-size: 10px; padding: 6px 8px; text-align: center; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.15);">
                    Sistem Yöneticisi (Admin)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function quickLogin(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
    document.querySelector('form').submit();
}
</script>

</body>
</html>
