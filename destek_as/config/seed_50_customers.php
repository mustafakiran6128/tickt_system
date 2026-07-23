<?php
// C:\laragon\www\destek_as\config\seed_50_customers.php
require_once __DIR__ . '/db.php';

echo "Seeding 50 customers...\n";

try {
    // Clear previous customer-related tables to avoid duplicates and starting fresh
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE customer_categories;");
    $pdo->exec("TRUNCATE TABLE customer_users;");
    $pdo->exec("TRUNCATE TABLE tickets;");
    $pdo->exec("TRUNCATE TABLE customers;");
    $pdo->exec("DELETE FROM users WHERE id > 2;");
    $pdo->exec("DELETE FROM user_roles WHERE user_id > 2;");
    $pdo->exec("ALTER TABLE users AUTO_INCREMENT = 3;");
    $pdo->exec("ALTER TABLE customers AUTO_INCREMENT = 1;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $pdo->beginTransaction();

    $company_names = [
        'Kuzey Teknoloji', 'Bulut Lojistik', 'Ege Bilişim', 'Marmara Gıda', 'Anadolu Tekstil', 
        'Akdeniz Turizm', 'Karadeniz Nakliyat', 'Efes Otomotiv', 'Zirve İnşaat', 'Hedef Kozmetik', 
        'Mega Enerji', 'Özkan Hukuk', 'Yıldız Mimarlık', 'Bursa Plastik', 'Güneş Medya', 
        'Dost Tarım', 'Mavi Denizcilik', 'Hilal Kimya', 'Serhat Metal', 'Doruk Telekom', 
        'Net Yazılım', 'Uzman Danışmanlık', 'Pınar Su', 'Kanyon Yapı', 'Aras Kargo', 
        'Vatan Bilgisayar', 'Tekno Market', 'Uzay Havacılık', 'Birlik Kooperatif', 'Karar Matbaa', 
        'Sistem Güvenlik', 'Vizyon Eğitim', 'Özgür Yayıncılık', 'Bilgi Akademi', 'Seçkin Mobilya', 
        'Öncü Factoring', 'Lider Faktoring', 'Merkez Döviz', 'Çağdaş Sağlık', 'Güven Tıp', 
        'Deha Otomasyon', 'Focus Reklam', 'Kreatif Tasarım', 'Pusula Harita', 'Rota Mühendislik', 
        'Kare İnşaat', 'Piramit Dekorasyon', 'Tempo Kurye', 'Doğa Peyzaj', 'Elit Temizlik'
    ];

    $contact_names = [
        'Ahmet Kuzey', 'Mehmet Bulut', 'Canan Ege', 'Elif Marmara', 'Mustafa Anadolu',
        'Zeynep Akdeniz', 'Hasan Karadeniz', 'Ayşe Efes', 'Ali Zirve', 'Fatma Hedef',
        'İbrahim Mega', 'Hatice Özkan', 'Kemal Yıldız', 'Derya Bursa', 'Murat Güneş',
        'Selin Dost', 'Deniz Mavi', 'Osman Hilal', 'Seda Serhat', 'Bülent Doruk',
        'Nihal Net', 'Hakan Uzman', 'Burcu Pınar', 'Cem Kanyon', 'Levent Aras',
        'Gökhan Vatan', 'Ebru Tekno', 'Serkan Uzay', 'Ömer Birlik', 'Tugay Karar',
        'Fatih Sistem', 'Gözde Vizyon', 'Yasin Özgür', 'Tuğba Bilgi', 'Adem Seçkin',
        'Nisa Öncü', 'Kadir Lider', 'Savaş Merkez', 'Ender Çağdaş', 'Berna Güven',
        'Mete Deha', 'Jale Focus', 'Uğur Kreatif', 'Tamer Pusula', 'Ceren Rota',
        'Oğuz Kare', 'Sibel Piramit', 'Koray Tempo', 'Aslı Doğa', 'Seçil Elit'
    ];

    // Fetch support packages
    $packages = $pdo->query("SELECT id FROM support_packages WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($packages)) {
        // Fallback package IDs
        $packages = [1, 2, 3];
    }

    $password_hash = password_hash('password', PASSWORD_BCRYPT);
    $role_id_stmt = $pdo->query("SELECT id FROM roles WHERE name = 'Müşteri Kullanıcısı' LIMIT 1");
    $cust_role_id = $role_id_stmt->fetchColumn() ?: 3;

    for ($i = 0; $i < 50; $i++) {
        $name = $company_names[$i];
        $contact = $contact_names[$i];
        $phone = '0555 ' . rand(100, 999) . ' ' . rand(10, 99) . ' ' . rand(10, 99);
        $email = 'info@' . strtolower(str_replace([' ', 'ö', 'ç', 'ş', 'ı', 'ğ', 'ü'], ['-', 'o', 'c', 's', 'i', 'g', 'u'], $name)) . '.com';
        $address = "Liman Cad. No: " . ($i + 1) . " / Merkez";
        $package_id = $packages[array_rand($packages)];
        $ticket_limit = [10, 20, 50, 100][rand(0, 3)];
        $priority_support = ($package_id == 2) ? 1 : 0;
        $status = 'active';

        // Insert Customer Company
        $stmt = $pdo->prepare("
            INSERT INTO customers 
            (company_id, name, contact_person, phone, email, address, contract_start_date, contract_end_date, support_package_id, monthly_ticket_limit, priority_support, status) 
            VALUES (1, ?, ?, ?, ?, ?, '2026-01-01', '2027-01-01', ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $contact, $phone, $email, $address, $package_id, $ticket_limit, $priority_support, $status]);
        $customer_id = $pdo->lastInsertId();

        // Assign Services (Categories)
        // 1 = Yazılım Geliştirme, 2 = Donanım Arızası
        // Some get Software, some Hardware, some BOTH!
        $service_type = ($i === 0) ? 3 : rand(1, 3);
        if ($service_type === 1) {
            // Software only
            $stmtCC = $pdo->prepare("INSERT INTO customer_categories (customer_id, category_id) VALUES (?, 1)");
            $stmtCC->execute([$customer_id]);
        } elseif ($service_type === 2) {
            // Hardware only
            $stmtCC = $pdo->prepare("INSERT INTO customer_categories (customer_id, category_id) VALUES (?, 2)");
            $stmtCC->execute([$customer_id]);
        } else {
            // Both Software & Hardware
            $stmtCC = $pdo->prepare("INSERT INTO customer_categories (customer_id, category_id) VALUES (?, 1)");
            $stmtCC->execute([$customer_id]);
            $stmtCC = $pdo->prepare("INSERT INTO customer_categories (customer_id, category_id) VALUES (?, 2)");
            $stmtCC->execute([$customer_id]);
        }

        // Create Customer User
        if ($i === 0) {
            $user_email = 'musteri@destek.com';
        } else {
            $user_email = strtolower(str_replace(' ', '', $contact)) . '@destek.com';
            // Remove Turkish characters for email
            $user_email = str_replace(['ö', 'ç', 'ş', 'ı', 'ğ', 'ü'], ['o', 'c', 's', 'i', 'g', 'u'], $user_email);
        }

        $stmtUser = $pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password, status) 
            VALUES (?, ?, ?, ?, 'active')
        ");
        // Split contact name
        $name_parts = explode(' ', $contact);
        $first_name = $name_parts[0];
        $last_name = $name_parts[1] ?? '';

        $stmtUser->execute([$first_name, $last_name, $user_email, $password_hash]);
        $user_id = $pdo->lastInsertId();

        // Assign Role
        $stmtRole = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmtRole->execute([$user_id, $cust_role_id]);

        // Link User to Customer Company
        $stmtCustUser = $pdo->prepare("INSERT INTO customer_users (customer_id, user_id) VALUES (?, ?)");
        $stmtCustUser->execute([$customer_id, $user_id]);
    }

    $pdo->commit();
    echo "Successfully seeded 50 customer companies and users!\n";
} catch (\Exception $e) {
    echo "Error seeding customers: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
