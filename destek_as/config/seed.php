<?php
// C:\laragon\www\destek_as\config\seed.php
require_once __DIR__ . '/db.php';

try {
    // Check if companies exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM companies");
    $company_count = $stmt->fetchColumn();

    if ($company_count == 0) {
        // 1. Insert Destek A.Ş. Company
        $stmt = $pdo->prepare("INSERT INTO companies (id, name, trade_name, tax_number, tax_office, phone, email, website, address) VALUES (1, 'Destek A.Ş. Yazılım A.Ş.', 'Destek A.Ş. Bilgi Teknolojileri', '1234567890', 'Giresun V.D.', '05016621628', 'info@destek.net', 'destek.net', 'Pazarsuyu Köyü 2. OSB Giresun Teknopark Bulancak / Giresun')");
        $stmt->execute();

        // 2. Insert Support Packages
        $stmt = $pdo->prepare("INSERT INTO support_packages (id, company_id, name, description, ticket_limit, support_hours, response_sla, resolution_sla, dedicated_agent, critical_intervention, price) VALUES 
        (1, 1, 'Standart Paket', 'Mesai saatlerinde destek, aylık 20 talep limiti', 20, '9/5', 480, 2880, 0, 0, 1500.00),
        (2, 1, 'Profesyonel Paket', 'Öncelikli destek, aylık 100 talep limiti', 100, '9/5', 120, 720, 0, 1, 4500.00),
        (3, 1, 'Kurumsal Paket', '7/24 destek, sınırsız talep, özel müşteri temsilcisi', -1, '24/7', 15, 240, 1, 1, 12000.00)");
        $stmt->execute();

        // 3. Insert Customer Companies
        $stmt = $pdo->prepare("INSERT INTO customers (id, company_id, name, contact_person, phone, email, address, contract_start_date, contract_end_date, support_package_id, monthly_ticket_limit, priority_support) VALUES 
        (1, 1, 'Kuzey Teknoloji', 'Ahmet Kuzey', '05551112233', 'ahmet@kuzey.com', 'Giresun Merkez', '2026-01-01', '2027-01-01', 2, 100, 1),
        (2, 1, 'Bulut Lojistik', 'Mehmet Bulut', '05554445566', 'mehmet@bulut.com', 'Bulancak Giresun', '2026-02-15', '2027-02-15', 1, 20, 0)");
        $stmt->execute();

        // 4. Insert Default Departments
        $stmt = $pdo->prepare("INSERT INTO departments (id, company_id, name, description, email, working_hours, default_priority, assignment_method) VALUES 
        (1, 1, 'Yazılım Destek', 'Yazılım hataları ve güncelleme destek birimi', 'yazilim@destek.net', '09:00-18:00', 'Normal', 'round_robin'),
        (2, 1, 'Teknik Servis', 'Donanım ve fiziksel arıza destek birimi', 'teknik@destek.net', '09:00-18:00', 'Yüksek', 'least_workload'),
        (3, 1, 'Bilgi İşlem', 'İç IT ve sunucu altyapı yönetim birimi', 'it@destek.net', '09:00-18:00', 'Kritik', 'manual')");
        $stmt->execute();
    }

    // Check if users exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();

    if ($user_count == 0) {
        // Hash passwords
        $pw_admin = password_hash('admin', PASSWORD_BCRYPT);
        $pw_user = password_hash('password', PASSWORD_BCRYPT);

        // Insert Users
        $stmt = $pdo->prepare("INSERT INTO users (id, email, password, first_name, last_name, phone, status) VALUES 
        (1, 'admin@destek.com', ?, 'Mustafa', 'Admin', '05016621628', 'active'),
        (2, 'yonetici@destek.com', ?, 'Selim', 'Yönetici', '05553334455', 'active'),
        (3, 'personel@destek.com', ?, 'Kemal', 'Teknisyen', '05554443322', 'active'),
        (4, 'musteri@destek.com', ?, 'Ahmet', 'Kuzey', '05551112233', 'active')");
        
        $stmt->execute([$pw_admin, $pw_user, $pw_user, $pw_user]);

        // Map Roles
        // Roles: 1 = Sistem Sahibi, 2 = Firma Yöneticisi, 3 = Departman Yöneticisi, 4 = Destek Personeli, 5 = Müşteri Kullanıcısı, 6 = Gözlemci Kullanıcı
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id, company_id) VALUES 
        (1, 1, 1), -- Mustafa is System Owner
        (2, 2, 1), -- Selim is Company Manager
        (3, 4, 1), -- Kemal is Support Agent
        (4, 5, 1)  -- Ahmet is Customer User");
        $stmt->execute();

        // Link Customer Users
        $stmt = $pdo->prepare("INSERT INTO customer_users (customer_id, user_id, role) VALUES 
        (1, 4, 'manager')"); // Ahmet is manager of Kuzey Teknoloji customer
        $stmt->execute();

        // Assign Kemal to Yazılım Destek department
        $stmt = $pdo->prepare("INSERT INTO department_users (department_id, user_id, is_manager) VALUES 
        (1, 3, 0)");
        $stmt->execute();
    }
} catch (\Exception $e) {
    error_log("Seeding failed: " . $e->getMessage());
}
?>
