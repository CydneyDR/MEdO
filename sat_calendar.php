<?php
require_once 'db.php';
require_once 'sat_sidebar.php';

// PROCESS ADD EVENT NG FOCAL
$success_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_focal_event'])) {
    $event_title = trim($_POST['event_title']);
    $event_date = $_POST['event_date'];

    if (!empty($event_title) && !empty($event_date)) {
        $stmt = $pdo->prepare("INSERT INTO satellite_schedules (satellite_id, event_title, event_date, status) VALUES (?, ?, ?, 'Upcoming')");
        $stmt->execute([$sat_id, $event_title, $event_date]);
        $success_msg = "Event successfully added to your calendar!";
    }
}

// Kunin ang napiling buwan at taon mula sa URL
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

if ($month < 1) {
    $month = 12;
    $year--;
}
if ($month > 12) {
    $month = 1;
    $year++;
}

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day_of_month = mktime(0, 0, 0, $month, 1, $year);
$start_day_of_week = date('w', $first_day_of_month);
$month_name = date('F Y', $first_day_of_month);

$start_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
$end_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . $days_in_month;

// Kunin ang lahat ng schedules para sa satellite ID na ito (galing man kay Admin o kay Focal)
$stmt = $pdo->prepare("SELECT * FROM satellite_schedules WHERE satellite_id = ? AND event_date BETWEEN ? AND ? ORDER BY event_date ASC");
$stmt->execute([$sat_id, $start_date, $end_date]);
$raw_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events_by_date = [];
foreach ($raw_schedules as $ev) {
    $day_num = intval(date('j', strtotime($ev['event_date'])));
    $events_by_date[$day_num][] = $ev['event_title'];
}

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
?>

<style>
    .calendar-header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        background: #f8fafc;
        padding: 16px 24px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .calendar-header-bar h3 {
        margin: 0;
        font-size: 1.4rem;
        color: #0f172a;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .nav-btn {
        background: #ffffff;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 9px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 700;
        transition: all 0.2s;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
    }

    .nav-btn:hover {
        background: #3b82f6;
        color: #ffffff;
        border-color: #3b82f6;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 10px;
    }

    .calendar-day-header {
        text-align: center;
        font-weight: 700;
        font-size: 0.8rem;
        color: #64748b;
        text-transform: uppercase;
        padding-bottom: 10px;
        letter-spacing: 1px;
    }

    .calendar-cell {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        min-height: 120px;
        padding: 12px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        position: relative;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .calendar-cell:hover {
        border-color: #3b82f6;
        box-shadow: 0 6px 15px rgba(59, 130, 246, 0.08);
        transform: translateY(-2px);
    }

    .calendar-cell.empty {
        background: #f8fafc;
        border: 1px dashed #e2e8f0;
        opacity: 0.4;
        cursor: default;
    }

    .calendar-cell.empty:hover {
        transform: none;
        box-shadow: none;
        border-color: #e2e8f0;
    }

    .calendar-cell.today {
        border-color: #3b82f6;
        background: #f0f9ff;
    }

    .day-number {
        font-weight: 700;
        font-size: 0.95rem;
        color: #334155;
        margin-bottom: 8px;
    }

    .calendar-cell.today .day-number {
        background: #3b82f6;
        color: white;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 5px rgba(59, 130, 246, 0.4);
    }

    .event-badge {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        color: #1e40af;
        border-left: 3px solid #3b82f6;
        border-radius: 4px;
        padding: 5px 8px;
        font-size: 0.75rem;
        font-weight: 700;
        margin-bottom: 4px;
        word-break: break-word;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .alert-success {
        background: #dcfce7;
        color: #16a34a;
        padding: 14px 18px;
        border-radius: 10px;
        border: 1px solid #bbf7d0;
        margin-bottom: 24px;
        font-weight: 700;
        font-size: 0.9rem;
    }

    dialog {
        border: none;
        border-radius: 16px;
        padding: 0;
        width: 100%;
        max-width: 450px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    dialog::backdrop {
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
    }
</style>

<div class="page-header">Event Calendar & Schedules</div>

<?php if (!empty($success_msg)): ?>
    <div class="alert-success"><?php echo $success_msg; ?></div>
<?php endif; ?>

<!-- FULL WIDTH MODERN CALENDAR CARD -->
<div class="modern-card">
    <div class="calendar-header-bar">
        <a href="sat_calendar.php?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="nav-btn">←
            Prev</a>
        <h3><?php echo $month_name; ?></h3>
        <a href="sat_calendar.php?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="nav-btn">Next
            →</a>
    </div>

    <div class="calendar-grid">
        <div class="calendar-day-header">Sun</div>
        <div class="calendar-day-header">Mon</div>
        <div class="calendar-day-header">Tue</div>
        <div class="calendar-day-header">Wed</div>
        <div class="calendar-day-header">Thu</div>
        <div class="calendar-day-header">Fri</div>
        <div class="calendar-day-header">Sat</div>

        <?php
        for ($i = 0; $i < $start_day_of_week; $i++) {
            echo '<div class="calendar-cell empty"></div>';
        }

        for ($day = 1; $day <= $days_in_month; $day++) {
            $is_today = ($day == date('j') && $month == date('n') && $year == date('Y'));
            $today_class = $is_today ? 'today' : '';
            $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);

            echo '<div class="calendar-cell ' . $today_class . '" onclick="openAddEventModal(\'' . $date_str . '\')">';
            echo '<div class="day-number">' . $day . '</div>';

            if (isset($events_by_date[$day])) {
                foreach ($events_by_date[$day] as $title) {
                    echo '<div class="event-badge">' . htmlspecialchars($title) . '</div>';
                }
            }
            echo '</div>';
        }
        ?>
    </div>
</div>

<!-- ADD EVENT MODAL -->
<dialog id="eventModal">
    <div class="modal-header"
        style="padding: 16px 20px; background: #fff; border-bottom: 1px solid #e2e8f0; font-weight: 700; display: flex; justify-content: space-between; align-items: center;">
        <span id="modalDateTitle">Add Office Event</span>
        <button type="button" style="background:none; border:none; font-size:1.2rem; cursor:pointer;"
            onclick="document.getElementById('eventModal').close()">✕</button>
    </div>
    <div class="modal-body" style="padding: 24px;">
        <form method="POST">
            <div class="form-group">
                <label>Event / Activity Title</label>
                <input type="text" name="event_title" class="form-control" required
                    placeholder="e.g. Community Outreach">
            </div>
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="event_date" id="modal_event_date" class="form-control" required readonly>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-cancel" style="flex:1;"
                    onclick="document.getElementById('eventModal').close()">Cancel</button>
                <button type="submit" name="add_focal_event" class="btn btn-primary" style="flex:1;">Save Event</button>
            </div>
        </form>
    </div>
</dialog>

<script>
    function openAddEventModal(dateStr) {
        document.getElementById('modal_event_date').value = dateStr;
        document.getElementById('modalDateTitle').innerText = "Add Event for " + dateStr;
        document.getElementById('eventModal').showModal();
    }
</script>

</div> <!-- END OF MAIN CONTENT -->
</body>

</html>