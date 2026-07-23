<?php
ob_start();
// C:\laragon\www\destek_as\admin\takvim.php
require_once __DIR__ . '/../includes/header.php';

$user_role = $_SESSION['role_name'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;
$company_id = $_SESSION['company_id'] ?? 1;
$customer_id = $_SESSION['customer_id'] ?? 0;

// Get selected month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

if ($month < 1 || $month > 12) $month = intval(date('m'));
if ($year < 2000 || $year > 2100) $year = intval(date('Y'));

// Calculate previous/next month/year links
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Fetch tickets for this month
$tickets = [];
try {
    if (in_array($user_role, ['Sistem Sahibi', 'Firma Yöneticisi', 'Departman Yöneticisi'])) {
        // Managers/Admin see all tickets
        $stmt = $pdo->prepare("
            SELECT t.id, t.ticket_number, t.title, t.description, t.created_at, 
                   c.name AS cust_name, p.name AS priority_name
            FROM tickets t
            LEFT JOIN customers c ON t.customer_id = c.id
            LEFT JOIN priorities p ON t.priority_id = p.id
            WHERE t.company_id = ?
              AND YEAR(t.created_at) = ?
              AND MONTH(t.created_at) = ?
        ");
        $stmt->execute([$company_id, $year, $month]);
        $tickets = $stmt->fetchAll();
    } elseif ($user_role === 'Müşteri Kullanıcısı') {
        // Customer sees their own tickets
        $stmt = $pdo->prepare("
            SELECT t.id, t.ticket_number, t.title, t.description, t.created_at, 
                   'Ben' AS cust_name, p.name AS priority_name
            FROM tickets t
            LEFT JOIN priorities p ON t.priority_id = p.id
            WHERE t.customer_id = ?
              AND YEAR(t.created_at) = ?
              AND MONTH(t.created_at) = ?
        ");
        $stmt->execute([$customer_id, $year, $month]);
        $tickets = $stmt->fetchAll();
    } else {
        // Specialist sees assigned tickets
        $stmt = $pdo->prepare("
            SELECT t.id, t.ticket_number, t.title, t.description, t.created_at, 
                   c.name AS cust_name, p.name AS priority_name
            FROM tickets t
            LEFT JOIN customers c ON t.customer_id = c.id
            LEFT JOIN priorities p ON t.priority_id = p.id
            WHERE t.company_id = ?
              AND t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND status = 'active')
              AND YEAR(t.created_at) = ?
              AND MONTH(t.created_at) = ?
        ");
        $stmt->execute([$company_id, $user_id, $year, $month]);
        $tickets = $stmt->fetchAll();
    }

    // Map priority colors dynamically (priorities table does not have color column)
    foreach ($tickets as &$t) {
        if ($t['priority_name'] === 'Öncelikli') {
            $t['priority_color'] = '#f59e0b';
        } elseif ($t['priority_name'] === 'Yüksek') {
            $t['priority_color'] = '#ef4444';
        } else {
            $t['priority_color'] = '#3b82f6';
        }
    }
    unset($t);
} catch (\Exception $e) {
    // Fail silently
}

// Group tickets by day
$tickets_by_day = [];
foreach ($tickets as $ticket) {
    $day = intval(date('j', strtotime($ticket['created_at'])));
    $tickets_by_day[$day][] = $ticket;
}

// Month name in Turkish
$month_names = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
    7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
];
$month_name_tr = $month_names[$month];

// Calendar grid calculations
$first_day_of_month = mktime(0, 0, 0, $month, 1, $year);
$number_of_days = date('t', $first_day_of_month);
$date_components = getdate($first_day_of_month);
// Convert Sunday=0...Saturday=6 to Monday=0...Sunday=6
$day_of_week = $date_components['wday']; 
$day_of_week = ($day_of_week + 6) % 7; // shift Sunday to index 6, Monday to index 0

$days_of_week = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
?>

<div style="display: flex; flex-direction: column; gap: 30px;">
    <!-- Calendar Navigation Card -->
    <div class="glass-card" style="padding: 20px; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn-custom btn-custom-secondary" style="padding: 8px 15px; text-decoration: none;">
                <i class="fa-solid fa-chevron-left"></i> Önceki Ay
            </a>
            <h2 style="font-size: 20px; font-weight: 800; margin: 0; min-width: 150px; text-align: center;">
                <?php echo "{$month_name_tr} {$year}"; ?>
            </h2>
            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn-custom btn-custom-secondary" style="padding: 8px 15px; text-decoration: none;">
                Sonraki Ay <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <form method="GET" action="" style="display: flex; gap: 8px; margin: 0;">
                <select name="month" class="form-control" style="padding: 6px 12px; width: 120px;">
                    <?php foreach ($month_names as $m_num => $m_name): ?>
                        <option value="<?php echo $m_num; ?>" <?php echo $m_num === $month ? 'selected' : ''; ?>><?php echo $m_name; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="year" class="form-control" style="padding: 6px 12px; width: 100px;">
                    <?php for ($y = date('Y') - 3; $y <= date('Y') + 3; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y === $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-custom btn-custom-primary" style="padding: 8px 15px;">Git</button>
            </form>
        </div>
    </div>

    <!-- Calendar Grid Card -->
    <div class="glass-card" style="padding: 20px; overflow-x: auto;">
        <div style="min-width: 900px;">
            <!-- Days Header -->
            <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; text-align: center; font-weight: 700; color: var(--text-muted); font-size: 13px; text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <?php foreach ($days_of_week as $day_name): ?>
                    <div><?php echo $day_name; ?></div>
                <?php endforeach; ?>
            </div>

            <!-- Calendar Days -->
            <div style="display: grid; grid-template-columns: repeat(7, 1fr); grid-auto-rows: minmax(130px, auto); gap: 10px;">
                <!-- Empty Cells for Prev Month Days Offset -->
                <?php for ($i = 0; $i < $day_of_week; $i++): ?>
                    <div style="background: rgba(255, 255, 255, 0.01); border: 1px dashed rgba(255,255,255,0.03); border-radius: 12px; opacity: 0.3;"></div>
                <?php endfor; ?>

                <!-- Current Month Days -->
                <?php for ($current_day = 1; $current_day <= $number_of_days; $current_day++): ?>
                    <?php 
                    $is_today = ($current_day === intval(date('j')) && $month === intval(date('m')) && $year === intval(date('Y')));
                    $day_tickets = $tickets_by_day[$current_day] ?? [];
                    $visible_tickets = array_slice($day_tickets, 0, 3);
                    $hidden_count = count($day_tickets) - count($visible_tickets);
                    ?>
                    <div onclick="showDayTicketsModal(event, <?php echo $current_day; ?>, <?php echo htmlspecialchars(json_encode($day_tickets)); ?>)" style="background: <?php echo $is_today ? 'rgba(139, 92, 246, 0.05)' : 'rgba(255,255,255,0.02)'; ?>; border: 1px solid <?php echo $is_today ? 'var(--primary)' : 'var(--border-color)'; ?>; border-radius: 12px; padding: 10px; display: flex; flex-direction: column; gap: 8px; position: relative; cursor: <?php echo !empty($day_tickets) ? 'pointer' : 'default'; ?>;" class="cal-day-cell">
                        <!-- Day Number -->
                        <span style="font-weight: 800; font-size: 14px; color: <?php echo $is_today ? 'var(--primary)' : 'var(--text-muted)'; ?>;">
                            <?php echo $current_day; ?>
                            <?php if ($is_today): ?>
                                <small style="font-size: 9px; background: var(--primary); color: #fff; padding: 1px 5px; border-radius: 4px; margin-left: 5px;">Bugün</small>
                            <?php endif; ?>
                        </span>

                        <!-- Tickets List inside day cell -->
                        <div style="display: flex; flex-direction: column; gap: 6px; flex-grow: 1;">
                            <?php foreach ($visible_tickets as $ticket): ?>
                                <div onclick="event.stopPropagation(); showTicketDetail(<?php echo htmlspecialchars(json_encode($ticket)); ?>)" style="background: rgba(255, 255, 255, 0.04); border-left: 3px solid <?php echo htmlspecialchars($ticket['priority_color'] ?: 'var(--accent)'); ?>; padding: 6px 8px; border-radius: 6px; cursor: pointer; transition: background 0.2s;" class="ticket-cal-item">
                                    <div style="font-size: 9px; font-weight: 700; color: var(--text-muted); display: flex; justify-content: space-between;">
                                        <span>#<?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                                    </div>
                                    <div style="font-size: 11px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px;">
                                        <?php echo htmlspecialchars($ticket['title']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if ($hidden_count > 0): ?>
                                <div style="font-size: 10px; font-weight: 700; color: var(--accent); padding: 4px 6px; border-radius: 4px; background: rgba(20, 184, 166, 0.1); text-align: center; margin-top: auto;">
                                    +<?php echo $hidden_count; ?> Daha Fazla
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<!-- Ticket Detail Modal -->
<div id="ticketCalModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fa-solid fa-ticket" style="color: var(--primary); margin-right: 8px;"></i> Talep Detayı</h3>
            <button onclick="hideModal('ticketCalModal')" class="modal-close">&times;</button>
        </div>
        <div class="modal-body" style="display: flex; flex-direction: column; gap: 15px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <div>
                    <span id="m_ticket_number" class="badge badge-primary">#YEB-0000</span>
                    <span id="m_priority" class="badge" style="margin-left: 8px;">Normal</span>
                </div>
                <small id="m_date" style="color: var(--text-muted);">Tarih</small>
            </div>
            <div>
                <h4 style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;">Müşteri / Gönderen</h4>
                <div id="m_customer" style="font-size: 14px; font-weight: 700; color: var(--text-primary);">Şirket Adı</div>
            </div>
            <div>
                <h4 style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;">Talep Başlığı</h4>
                <div id="m_title" style="font-size: 14px; font-weight: 700; color: var(--text-primary);">Talep Konusu</div>
            </div>
            <div>
                <h4 style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;">Detaylı Açıklama</h4>
                <div id="m_desc" style="font-size: 13px; line-height: 1.6; color: var(--text-secondary); background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px; max-height: 200px; overflow-y: auto;">İçerik...</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="hideModal('ticketCalModal')" class="btn-custom btn-custom-secondary">Kapat</button>
            <a id="m_detail_link" href="#" class="btn-custom btn-custom-primary" style="text-decoration: none;">Talebe Git <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i></a>
        </div>
    </div>
</div>

<!-- Day Tickets Modal (Request 2) -->
<div id="dayTicketsModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fa-solid fa-calendar-day" style="color: var(--primary); margin-right: 8px;"></i> <span id="dayModalTitleDate"></span> Talepleri</h3>
            <button onclick="hideModal('dayTicketsModal')" class="modal-close">&times;</button>
        </div>
        <div class="modal-body" style="max-height: 400px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px;">
            <div id="dayTicketsList" style="display: flex; flex-direction: column; gap: 10px;"></div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="hideModal('dayTicketsModal')" class="btn-custom btn-custom-secondary">Kapat</button>
        </div>
    </div>
</div>

<style>
.ticket-cal-item:hover {
    background: rgba(255, 255, 255, 0.08) !important;
}
.cal-day-cell:hover {
    background: rgba(255, 255, 255, 0.04) !important;
    border-color: rgba(139, 92, 246, 0.3) !important;
}
</style>

<script>
function showTicketDetail(ticket) {
    document.getElementById('m_ticket_number').innerText = '#' + ticket.ticket_number;
    
    // Priority badge
    const pBadge = document.getElementById('m_priority');
    pBadge.innerText = ticket.priority_name;
    pBadge.style.backgroundColor = (ticket.priority_color || 'var(--accent)') + '22';
    pBadge.style.color = ticket.priority_color || 'var(--accent)';
    pBadge.style.border = '1px solid ' + (ticket.priority_color || 'var(--accent)') + '55';

    document.getElementById('m_date').innerText = new Date(ticket.created_at).toLocaleDateString('tr-TR', {
        day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
    document.getElementById('m_customer').innerText = ticket.cust_name;
    document.getElementById('m_title').innerText = ticket.title;
    document.getElementById('m_desc').innerText = ticket.description || 'Açıklama belirtilmemiş.';
    document.getElementById('m_detail_link').href = '/destek_as/admin/ticket_detay.php?id=' + ticket.id;

    showModal('ticketCalModal');
}

function showDayTicketsModal(event, day, dayTickets) {
    if (event) event.stopPropagation();
    if (!dayTickets || dayTickets.length === 0) return;

    document.getElementById('dayModalTitleDate').innerText = day + ' ' + <?php echo json_encode($month_name_tr); ?> + ' ' + <?php echo json_encode($year); ?>;
    
    const listDiv = document.getElementById('dayTicketsList');
    listDiv.innerHTML = '';
    
    dayTickets.forEach(ticket => {
        const item = document.createElement('div');
        item.style.background = 'rgba(255, 255, 255, 0.03)';
        item.style.border = '1px solid var(--border-color)';
        item.style.borderLeft = '4px solid ' + (ticket.priority_color || 'var(--accent)');
        item.style.padding = '12px 16px';
        item.style.borderRadius = '8px';
        item.style.cursor = 'pointer';
        item.style.display = 'flex';
        item.style.justifyContent = 'space-between';
        item.style.alignItems = 'center';
        item.style.transition = 'background 0.2s';
        
        item.onmouseover = () => item.style.background = 'rgba(255, 255, 255, 0.06)';
        item.onmouseout = () => item.style.background = 'rgba(255, 255, 255, 0.03)';
        
        item.onclick = () => {
            hideModal('dayTicketsModal');
            showTicketDetail(ticket);
        };
        
        item.innerHTML = `
            <div>
                <span class="badge" style="background: ${ticket.priority_color || 'var(--accent)'}22; color: ${ticket.priority_color || 'var(--accent)'}; border: 1px solid ${ticket.priority_color || 'var(--accent)'}55; font-size:10px;">#${ticket.ticket_number}</span>
                <strong style="margin-left: 8px; font-size:13px; color:var(--text-primary);">${ticket.title}</strong>
                <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">Müşteri: ${ticket.cust_name}</div>
            </div>
            <i class="fa-solid fa-chevron-right" style="color:var(--text-muted); font-size:12px;"></i>
        `;
        listDiv.appendChild(item);
    });
    
    showModal('dayTicketsModal');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
