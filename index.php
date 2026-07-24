<?php
// C:\laragon\www\destek_as\index.php
require_once __DIR__ . '/includes/header.php';

// Fetch stats based on user role and company
$company_id = $_SESSION['company_id'] ?? 1;
$user_id = $_SESSION['user_id'] ?? null;
$role_name = $_SESSION['role_name'] ?? '';

// Default values
$stat_open = 0;
$stat_today = 0;
$stat_critical = 0;
$stat_sla_breach = 0;

try {
    if ($role_name === 'Müşteri Kullanıcısı') {
        $customer_id = $_SESSION['customer_id'] ?? 0;
        
        // Fetch customer stats
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $stat_open = $stmt->fetchColumn();

        // Active tickets (not solved, closed, cancelled)
        $stmtActive = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE customer_id = ? AND status_id NOT IN (10, 12, 14)");
        $stmtActive->execute([$customer_id]);
        $stat_active = intval($stmtActive->fetchColumn());

        // Solved/Closed tickets
        $stmtSolved = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE customer_id = ? AND status_id IN (10, 12, 14)");
        $stmtSolved->execute([$customer_id]);
        $stat_solved = intval($stmtSolved->fetchColumn());

        // Fetch customer services
        $stmtUserCats = $pdo->prepare("
            SELECT GROUP_CONCAT(cat.name SEPARATOR ', ') 
            FROM customer_categories cc 
            JOIN categories cat ON cc.category_id = cat.id 
            WHERE cc.customer_id = ?
        ");
        $stmtUserCats->execute([$customer_id]);
        $user_services = $stmtUserCats->fetchColumn();
    } else {
        // Fetch staff stats
        if ($role_name === 'Destek Personeli') {
            // Fetch technician skills
            $stmtUserSkills = $pdo->prepare("
                SELECT GROUP_CONCAT(skill_name SEPARATOR ', ') 
                FROM agent_skills 
                WHERE user_id = ?
            ");
            $stmtUserSkills->execute([$user_id]);
            $user_skills = $stmtUserSkills->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM tickets t 
                WHERE t.company_id = ? 
                  AND t.status_id NOT IN (SELECT id FROM ticket_statuses WHERE is_closed = 1)
                  AND t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND status = 'active')
            ");
            $stmt->execute([$company_id, $user_id]);
            $stat_open = $stmt->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM tickets t 
                WHERE t.company_id = ? 
                  AND DATE(t.created_at) = CURDATE()
                  AND t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND status = 'active')
            ");
            $stmt->execute([$company_id, $user_id]);
            $stat_today = $stmt->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM tickets t 
                WHERE t.company_id = ? 
                  AND t.priority_id IN (SELECT id FROM priorities WHERE name IN ('Kritik', 'Acil')) 
                  AND t.status_id NOT IN (SELECT id FROM ticket_statuses WHERE is_closed = 1)
                  AND t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND status = 'active')
            ");
            $stmt->execute([$company_id, $user_id]);
            $stat_critical = $stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE company_id = ? AND status_id NOT IN (SELECT id FROM ticket_statuses WHERE is_closed = 1)");
            $stmt->execute([$company_id]);
            $stat_open = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE company_id = ? AND DATE(created_at) = CURDATE()");
            $stmt->execute([$company_id]);
            $stat_today = $stmt->fetchColumn();

            // Critical and urgent priority IDs
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE company_id = ? AND priority_id IN (SELECT id FROM priorities WHERE name IN ('Kritik', 'Acil')) AND status_id NOT IN (SELECT id FROM ticket_statuses WHERE is_closed = 1)");
            $stmt->execute([$company_id]);
            $stat_critical = $stmt->fetchColumn();
        }

        // SLA breached tickets
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sla_events WHERE is_breached = 1");
        $stmt->execute();
        $stat_sla_breach = $stmt->fetchColumn();

        // Fetch Kanban Statuses distribution counts (Atama bekliyor: 2, Atandı: 3, Müşteriden bilgi bekliyor: 5, Test ediliyor: 8)
        $stmtKanban = $pdo->prepare("
            SELECT ts.id, ts.name, COALESCE(COUNT(t.id), 0) as cnt
            FROM ticket_statuses ts
            LEFT JOIN tickets t ON t.status_id = ts.id AND t.company_id = ?
            WHERE ts.id IN (2, 3, 5, 8)
            GROUP BY ts.id, ts.name
            ORDER BY ts.id ASC
        ");
        $stmtKanban->execute([$company_id]);
        $kanban_chart_rows = $stmtKanban->fetchAll();

        $kanban_labels = [];
        $kanban_counts = [];
        foreach ($kanban_chart_rows as $row) {
            $kanban_labels[] = $row['name'];
            $kanban_counts[] = intval($row['cnt']);
        }

        // Fetch Weekly created and active tickets trend for the last 7 days
        $date_labels = [];
        $daily_created = [];
        $daily_active = [];

        for ($i = 6; $i >= 0; $i--) {
            $date_str = date('Y-m-d', strtotime("-$i days"));
            $date_display = date('d.m', strtotime("-$i days"));
            $date_labels[] = $date_display;

            // Count tickets created on that date
            $stmtC = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE company_id = ? AND DATE(created_at) = ?");
            $stmtC->execute([$company_id, $date_str]);
            $daily_created[] = intval($stmtC->fetchColumn());

            // Count active tickets on that date
            $stmtA = $pdo->prepare("
                SELECT COUNT(*) FROM tickets 
                WHERE company_id = ? 
                  AND DATE(created_at) <= ? 
                  AND (status_id NOT IN (10, 12, 14) OR DATE(updated_at) > ?)
            ");
            $stmtA->execute([$company_id, $date_str, $date_str]);
            $daily_active[] = intval($stmtA->fetchColumn());
        }
    }
} catch (\Exception $e) {
    // Handle error silently
}
?>

<div style="display: flex; flex-direction: column; gap: 30px;">
    <!-- Stats Grid -->
    <div class="stats-grid">
        <?php if ($role_name === 'Müşteri Kullanıcısı'): ?>
            <div class="glass-card stat-card">
                <div class="stat-icon primary">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-value"><?php echo $stat_open; ?></span>
                    <span class="stat-label">Toplam Taleplerim</span>
                </div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-icon warning">
                    <i class="fa-solid fa-spinner"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-value"><?php echo $stat_active; ?></span>
                    <span class="stat-label">Aktif Taleplerim</span>
                </div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-icon success">
                    <i class="fa-solid fa-circle-check" style="color: var(--success);"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-value"><?php echo $stat_solved; ?></span>
                    <span class="stat-label">Çözülen Taleplerim</span>
                </div>
            </div>
        <?php else: ?>
            <div class="glass-card stat-card">
                <div class="stat-icon primary">
                    <i class="fa-solid fa-ticket-incoming">📂</i>
                </div>
                <div class="stat-details">
                    <span class="stat-value"><?php echo $stat_open; ?></span>
                    <span class="stat-label">Açık Talepler</span>
                </div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-icon accent">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-value"><?php echo $stat_today; ?></span>
                    <span class="stat-label">Bugün Açılanlar</span>
                </div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-icon danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-value"><?php echo $stat_critical; ?></span>
                    <span class="stat-label">Kritik Talepler</span>
                </div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-icon warning">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-value"><?php echo $stat_sla_breach; ?></span>
                    <span class="stat-label">SLA İhlalleri</span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($role_name !== 'Müşteri Kullanıcısı'): ?>
        <!-- Charts Grid -->
        <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 30px; margin-top: 10px;">
            <!-- Chart 1: Daily Created & Active trend -->
            <div class="glass-card" style="padding: 24px; min-height: 320px; display: flex; flex-direction: column; gap: 15px;">
                <h3 style="font-size: 15px; font-weight: 700; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-chart-area" style="color: var(--primary);"></i> Haftalık Talep Dağılım & Trend
                </h3>
                <div style="flex-grow: 1; position: relative; height: 230px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Chart 2: Kanban Workflow Doughnut -->
            <div class="glass-card" style="padding: 24px; min-height: 320px; display: flex; flex-direction: column; gap: 15px;">
                <h3 style="font-size: 15px; font-weight: 700; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-chart-pie" style="color: var(--primary);"></i> Kanban İş Dağılımı
                </h3>
                <div style="flex-grow: 1; position: relative; height: 230px; display: flex; justify-content: center; align-items: center;">
                    <canvas id="kanbanDoughnutChart" style="max-height: 220px; max-width: 220px;"></canvas>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Active Tickets & Announcements -->
    <div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 30px;">
        <!-- Left Side: Recent Tickets -->
        <div class="glass-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 18px;"><i class="fa-solid fa-list-check" style="margin-right: 8px; color: var(--primary);"></i> Son Aktif Destek Talepleri</h3>
                <?php if ($role_name === 'Müşteri Kullanıcısı'): ?>
                    <a href="/destek_as/customer/yeni_ticket.php" class="btn-custom btn-custom-primary btn-sm" style="padding: 6px 12px; font-size: 12px;">Yeni Talep Aç</a>
                <?php else: ?>
                    <a href="/destek_as/admin/ticketlar.php" class="btn-custom btn-custom-secondary btn-sm" style="padding: 6px 12px; font-size: 12px;">Tümünü Gör</a>
                <?php endif; ?>
            </div>
            
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Ticket No</th>
                            <th>Başlık</th>
                            <th>Müşteri</th>
                            <th>Kategori</th>
                            <th>Öncelik</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            if ($role_name === 'Müşteri Kullanıcısı') {
                                $stmt = $pdo->prepare("
                                    SELECT t.*, ts.name AS status_name, ts.color AS status_color, p.name AS priority_name, c.name AS category_name
                                    FROM tickets t
                                    LEFT JOIN ticket_statuses ts ON t.status_id = ts.id
                                    LEFT JOIN priorities p ON t.priority_id = p.id
                                    LEFT JOIN categories c ON t.category_id = c.id
                                    WHERE t.customer_id = ? AND t.status_id NOT IN (10, 12, 14)
                                    ORDER BY t.id DESC LIMIT 5
                                ");
                                $stmt->execute([$_SESSION['customer_id'] ?? 0]);
                            } else {
                                if ($role_name === 'Destek Personeli') {
                                    $stmt = $pdo->prepare("
                                        SELECT t.*, ts.name AS status_name, ts.color AS status_color, p.name AS priority_name, c.name AS category_name, cust.name AS cust_name
                                        FROM tickets t
                                        LEFT JOIN ticket_statuses ts ON t.status_id = ts.id
                                        LEFT JOIN priorities p ON t.priority_id = p.id
                                        LEFT JOIN categories c ON t.category_id = c.id
                                        LEFT JOIN customers cust ON t.customer_id = cust.id
                                        WHERE t.company_id = ?
                                          AND t.status_id NOT IN (10, 12, 14)
                                          AND t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND status = 'active')
                                        ORDER BY t.id DESC LIMIT 5
                                    ");
                                    $stmt->execute([$company_id, $user_id]);
                                } else {
                                    $stmt = $pdo->prepare("
                                        SELECT t.*, ts.name AS status_name, ts.color AS status_color, p.name AS priority_name, c.name AS category_name, cust.name AS cust_name
                                        FROM tickets t
                                        LEFT JOIN ticket_statuses ts ON t.status_id = ts.id
                                        LEFT JOIN priorities p ON t.priority_id = p.id
                                        LEFT JOIN categories c ON t.category_id = c.id
                                        LEFT JOIN customers cust ON t.customer_id = cust.id
                                        WHERE t.company_id = ? AND t.status_id NOT IN (10, 12, 14)
                                        ORDER BY t.id DESC LIMIT 5
                                    ");
                                    $stmt->execute([$company_id]);
                                }
                            }
                            $recent_tickets = $stmt->fetchAll();

                            if (empty($recent_tickets)) {
                                echo "<tr><td colspan='6' style='text-align: center; color: var(--text-muted);'>Gösterilecek aktif destek talebi bulunmamaktadır.</td></tr>";
                            } else {
                                foreach ($recent_tickets as $ticket) {
                                    $cust_display = $ticket['cust_name'] ?? 'Müşteri Tanımsız';
                                    if ($role_name === 'Müşteri Kullanıcısı') {
                                        $cust_display = 'Ben';
                                    }
                                    echo "
                                    <tr onclick=\"window.location.href='/destek_as/admin/ticket_detay.php?id={$ticket['id']}'\" style='cursor: pointer;' onmouseover='this.style.background=\"rgba(255,255,255,0.02)\"' onmouseout='this.style.background=\"transparent\"'>
                                        <td><a href='/destek_as/admin/ticket_detay.php?id={$ticket['id']}' style='color: var(--accent); font-weight: 700; text-decoration: none;'>#{$ticket['ticket_number']}</a></td>
                                        <td style='font-weight: 600;'>" . htmlspecialchars($ticket['title']) . "</td>
                                        <td>" . htmlspecialchars($cust_display) . "</td>
                                        <td>" . htmlspecialchars($ticket['category_name'] ?? 'Kategorisiz') . "</td>
                                        <td>" . htmlspecialchars($ticket['priority_name'] ?? 'Normal') . "</td>
                                        <td>
                                            <span class='badge' style='background-color: " . htmlspecialchars($ticket['status_color']) . "22; color: " . htmlspecialchars($ticket['status_color']) . "; border: 1px solid " . htmlspecialchars($ticket['status_color']) . "55;'>
                                                " . htmlspecialchars($ticket['status_name']) . "
                                            </span>
                                        </td>
                                    </tr>";
                                }
                            }
                        } catch (\Exception $e) {
                            echo "<tr><td colspan='6'>Hata: {$e->getMessage()}</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Side: Announcements -->
        <div class="glass-card" style="display: flex; flex-direction: column; gap: 20px;">
            <h3 style="font-size: 18px;"><i class="fa-solid fa-bullhorn" style="color: var(--accent); margin-right: 8px;"></i> Son Duyurular</h3>
            <div style="display: flex; flex-direction: column; gap: 15px; overflow-y: auto; max-height: 350px;">
                <?php
                try {
                    if (in_array($role_name, ['Sistem Sahibi', 'Firma Yöneticisi', 'Departman Yöneticisi'])) {
                        $stmt = $pdo->prepare("SELECT * FROM announcements WHERE company_id = ? ORDER BY id DESC LIMIT 3");
                        $stmt->execute([$company_id]);
                    } elseif ($role_name === 'Müşteri Kullanıcısı') {
                        $stmt = $pdo->prepare("SELECT * FROM announcements WHERE company_id = ? AND target_audience IN ('All', 'Customers') ORDER BY id DESC LIMIT 3");
                        $stmt->execute([$company_id]);
                    } else {
                        $stmt = $pdo->prepare("SELECT * FROM announcements WHERE company_id = ? AND target_audience IN ('All', 'Staff') ORDER BY id DESC LIMIT 3");
                        $stmt->execute([$company_id]);
                    }
                    $announcements = $stmt->fetchAll();

                    if (empty($announcements)) {
                        // Create a default announcement if empty
                        echo "
                        <div style='background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px;'>
                            <div style='display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;'>
                                <span class='badge badge-success'>Sistem Aktif</span>
                                <small style='color: var(--text-muted);'>Bugün</small>
                            </div>
                            <h4 style='font-size: 14px; margin-bottom: 5px;'>Sistem Kullanıma Hazır</h4>
                            <p style='color: var(--text-secondary); font-size: 12px;'>Destek paneli kurulumu tamamlanmıştır. Yeni talepler açabilir, personelleri yönetebilirsiniz.</p>
                        </div>";
                    } else {
                        foreach ($announcements as $ann) {
                            $badge_class = 'badge-info';
                            if ($ann['type'] === 'Maintenance') $badge_class = 'badge-warning';
                            if ($ann['type'] === 'Outage' || $ann['type'] === 'Security') $badge_class = 'badge-danger';
                            if ($ann['type'] === 'Feature') $badge_class = 'badge-primary';

                            echo "
                            <div style='background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px;'>
                                <div style='display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;'>
                                    <span class='badge {$badge_class}'>{$ann['type']}</span>
                                    <small style='color: var(--text-muted);'>" . date('d.m.Y', strtotime($ann['created_at'])) . "</small>
                                </div>
                                <h4 style='font-size: 14px; margin-bottom: 5px;'>" . htmlspecialchars($ann['title']) . "</h4>
                                <p style='color: var(--text-secondary); font-size: 12px;'>" . htmlspecialchars($ann['content']) . "</p>
                            </div>";
                        }
                    }
                } catch (\Exception $e) {
                    echo "<p style='color: var(--danger);'>Duyurular yüklenirken hata oluştu.</p>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php if ($role_name !== 'Müşteri Kullanıcısı'): ?>
<!-- Load Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Kanban Doughnut Chart
    const ctxKanban = document.getElementById('kanbanDoughnutChart').getContext('2d');
    
    new Chart(ctxKanban, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($kanban_labels); ?>,
            datasets: [{
                label: 'Talep Dağılımı',
                data: <?php echo json_encode($kanban_counts); ?>,
                backgroundColor: [
                    '#f59e0b', // Atama bekliyor
                    '#3b82f6', // Atandı
                    '#10b981', // Müşteriden bilgi bekliyor
                    '#8b5cf6'  // Test ediliyor
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#3e2f1f',
                        font: { weight: '600', size: 10 },
                        padding: 10
                    }
                }
            },
            cutout: '65%' // Doughnut inner circle size
        }
    });

    // Trend Chart
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    
    const gradCreated = ctxTrend.createLinearGradient(0, 0, 0, 200);
    gradCreated.addColorStop(0, 'rgba(217, 119, 6, 0.15)');
    gradCreated.addColorStop(1, 'rgba(217, 119, 6, 0.0)');

    const gradActive = ctxTrend.createLinearGradient(0, 0, 0, 200);
    gradActive.addColorStop(0, 'rgba(59, 130, 246, 0.15)');
    gradActive.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($date_labels); ?>,
            datasets: [
                {
                    label: 'Açık Talepler',
                    data: <?php echo json_encode($daily_active); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: gradActive,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3b82f6'
                },
                {
                    label: 'Bugün Açılanlar',
                    data: <?php echo json_encode($daily_created); ?>,
                    borderColor: '#d97706',
                    backgroundColor: gradCreated,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#d97706'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#3e2f1f',
                        font: { weight: '600', size: 11 }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: '#a08e7d'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        color: '#a08e7d'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
