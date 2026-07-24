<?php
// config/seed_6_technicians.php
require_once __DIR__ . '/db.php';

echo "Seeding 6 technicians...\n";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // Delete any old support staff (role_id = 4)
    $pdo->exec("DELETE FROM users WHERE id IN (SELECT user_id FROM user_roles WHERE role_id = 4);");
    $pdo->exec("DELETE FROM user_roles WHERE role_id = 4;");
    $pdo->exec("DELETE FROM department_users WHERE user_id NOT IN (SELECT id FROM users);");
    $pdo->exec("DELETE FROM agent_skills WHERE user_id NOT IN (SELECT id FROM users);");
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $pdo->beginTransaction();

    $technicians = [
        [
            'first_name' => 'Kerem',
            'last_name' => 'Yazıcı',
            'email' => 'keremyazici@destek.com',
            'depts' => [1], // Yazılım Destek
            'skills' => ['Yazılım']
        ],
        [
            'first_name' => 'Selin',
            'last_name' => 'Yılmaz',
            'email' => 'selinyilmaz@destek.com',
            'depts' => [1], // Yazılım Destek
            'skills' => ['Yazılım']
        ],
        [
            'first_name' => 'Hakan',
            'last_name' => 'Demir',
            'email' => 'hakandemir@destek.com',
            'depts' => [2], // Teknik Servis (Donanım)
            'skills' => ['Donanım']
        ],
        [
            'first_name' => 'Murat',
            'last_name' => 'Kaya',
            'email' => 'muratkaya@destek.com',
            'depts' => [2], // Teknik Servis (Donanım)
            'skills' => ['Donanım']
        ],
        [
            'first_name' => 'Bora',
            'last_name' => 'Çelik',
            'email' => 'boracelik@destek.com',
            'depts' => [1], // Yazılım Destek
            'skills' => ['Yazılım']
        ],
        [
            'first_name' => 'Elif',
            'last_name' => 'Şahin',
            'email' => 'elifsahin@destek.com',
            'depts' => [2], // Teknik Servis (Donanım)
            'skills' => ['Donanım']
        ]
    ];

    $password_hash = password_hash('password', PASSWORD_BCRYPT);

    foreach ($technicians as $t) {
        // Insert User
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$t['first_name'], $t['last_name'], $t['email'], $password_hash]);
        $user_id = $pdo->lastInsertId();

        // Assign Role 4 (Destek Personeli)
        $stmtRole = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, 4)");
        $stmtRole->execute([$user_id]);

        // Assign Departments
        foreach ($t['depts'] as $dept_id) {
            $stmtDept = $pdo->prepare("INSERT INTO department_users (department_id, user_id, is_manager) VALUES (?, ?, 0)");
            $stmtDept->execute([$dept_id, $user_id]);
        }

        // Assign Skills
        foreach ($t['skills'] as $skill) {
            $stmtSkill = $pdo->prepare("INSERT INTO agent_skills (user_id, skill_name, proficiency_level) VALUES (?, ?, 5)");
            $stmtSkill->execute([$user_id, $skill]);
        }
    }

    $pdo->commit();
    echo "Successfully seeded 6 technicians!\n";
} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error seeding technicians: " . $e->getMessage() . "\n";
}
