<?php
session_start();
require 'db.php';
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

// LOGIC: Add Schedule
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_staff_schedule'])) {
    $stmt = $pdo->prepare("INSERT INTO staff_schedules (staff_name, role, assignment, schedule_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['staff_name'], $_POST['role'], $_POST['assignment'], $_POST['schedule_date']]);
    header("Location: staff.php");
    exit;
}

// LOGIC: Mark Done / Move to History
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_schedule'])) {
    $id = $_POST['id'];

    // 1. Get the task data first
    $stmt = $pdo->prepare("SELECT * FROM staff_schedules WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($task) {
        // 2. Insert into the history table
        $history_stmt = $pdo->prepare("INSERT INTO staff_schedules_history (staff_name, role, assignment, schedule_date) VALUES (?, ?, ?, ?)");
        $history_stmt->execute([$task['staff_name'], $task['role'], $task['assignment'], $task['schedule_date']]);

        // 3. Delete from active schedules
        $del_stmt = $pdo->prepare("DELETE FROM staff_schedules WHERE id = ?");
        $del_stmt->execute([$id]);
    }

    header("Location: staff.php");
    exit;
}

// FETCH DATA: Active Tasks
$staff_schedules = $pdo->query("SELECT * FROM staff_schedules ORDER BY schedule_date ASC")->fetchAll(PDO::FETCH_ASSOC);

// FETCH DATA: History / Completed Tasks
$staff_history = $pdo->query("SELECT * FROM staff_schedules_history ORDER BY completed_at DESC")->fetchAll(PDO::FETCH_ASSOC);


// FETCH QUICK STATS FOR WIDGETS
$today_tasks_stmt = $pdo->query("SELECT COUNT(*) FROM staff_schedules WHERE schedule_date = CURDATE()");
$today_tasks_count = $today_tasks_stmt->fetchColumn();

$total_active_stmt = $pdo->query("SELECT COUNT(*) FROM staff_schedules");
$total_active_count = $total_active_stmt->fetchColumn();

require_once 'header.php';
?>

<style>
    /* COMPACT SPACING STYLING */
    .header-title {
        margin-bottom: 5px !important;
    }

    .card-container {
        display: flex;
        flex-direction: column;
        gap: 15px !important;
    }

    .card {
        padding: 20px !important;
        margin-bottom: 0 !important;
    }

    /* MATCHED BUTTON STYLES */
    .btn-action-delete {
        background: #fff;
        color: #ef4444;
        border: 1px solid #fca5a5;
        padding: 6px 14px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-action-delete:hover {
        background: #fef2f2;
        transform: translateY(-2px);
    }
</style>

<div style="margin-bottom: 15px;">
    <h1 class="header-title">Staff Schedule Dashboard</h1>
    <p class="header-sub" style="margin-bottom: 0;">Track, assign, and review reports for the training office staff.</p>
</div>

<div class="card-container">

    <!-- TOP SECTION: FORM (LEFT) + COMPACT WIDGET (RIGHT) -->
    <div style="display: flex; gap: 15px; align-items: stretch; flex-wrap: wrap; width: 100%;">
        <!-- LEFT: ADD ASSIGNMENT FORM -->
        <div class="card" style="flex: 1 1 500px;">
            <h3
                style="display: flex; align-items: center; gap: 10px; margin-top: 0; margin-bottom: 15px; font-size: 16px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                Add New Assignment
            </h3>

            <form method="POST" action=""
                style="background: var(--bg-white); padding: 15px; border-radius: 10px; border: 1px solid var(--border-soft);">
                <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                    <div class="form-group" style="flex: 1; min-width: 180px; margin-bottom: 0;">
                        <label style="font-size: 12px; margin-bottom: 4px;">Staff Name</label>
                        <input type="text" name="staff_name" placeholder="e.g. Juan Dela Cruz" required
                            style="padding: 8px 12px;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 180px; margin-bottom: 0;">
                        <label style="font-size: 12px; margin-bottom: 4px;">Role / Position</label>
                        <input type="text" name="role" placeholder="e.g. IT Instructor" required
                            style="padding: 8px 12px;">
                    </div>
                </div>

                <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                    <div class="form-group" style="flex: 2; min-width: 180px; margin-bottom: 0;">
                        <label style="font-size: 12px; margin-bottom: 4px;">Assignment / Task</label>
                        <input type="text" name="assignment" placeholder="e.g. Facilitate Basic AI Workshop" required
                            style="padding: 8px 12px;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 130px; margin-bottom: 0;">
                        <label style="font-size: 12px; margin-bottom: 4px;">Schedule Date</label>
                        <input type="date" name="schedule_date" required style="padding: 8px 12px;">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 5px;">
                    <button type="submit" name="add_staff_schedule" class="btn-submit"
                        style="padding: 8px 20px; width: auto; font-size: 13px;">Assign Schedule</button>
                </div>
            </form>
        </div>

        <!-- RIGHT: COMPACT SIDE WIDGET -->
        <div class="card"
            style="flex: 0 0 260px; padding: 20px; background: linear-gradient(135deg, var(--corp-navy), var(--corp-blue)); color: white; border: none; box-shadow: 0 6px 12px rgba(10,25,47,0.15); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h4
                    style="margin: 0 0 10px 0; color: var(--accent-main); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                    Today's Overview</h4>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <h2 style="margin: 0; font-size: 36px; font-weight: 800; line-height: 1;">
                        <?php echo $today_tasks_count; ?></h2>
                    <span style="color: #94a3b8; font-size: 12px; line-height: 1.2;">Active<br>Tasks Today</span>
                </div>
            </div>
            <div
                style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px; font-size: 12px; color: #cbd5e1;">
                Total pending tasks: <strong
                    style="color: white; font-size: 13px;"><?php echo $total_active_count; ?></strong>
            </div>
        </div>
    </div> <!-- END TOP SECTION -->

    <!-- MIDDLE SECTION: CURRENT STAFF SCHEDULES TABLE -->
    <div class="card" style="width: 100%; box-sizing: border-box;">
        <h3 style="display: flex; align-items: center; gap: 10px; margin-top: 0; margin-bottom: 15px; font-size: 16px;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            Current / Active Tasks
        </h3>

        <table class="data-table" style="width: 100%; margin-top: 5px;">
            <thead>
                <tr>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 25%;">Staff Name</th>
                    <th style="width: 20%;">Role</th>
                    <th style="width: 30%;">Assignment</th>
                    <th style="width: 10%; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($staff_schedules) > 0): ?>
                    <?php foreach ($staff_schedules as $staff): ?>
                        <tr class="clickable-row">
                            <td>
                                <strong
                                    style="color: var(--corp-navy);"><?php echo date('M d, Y', strtotime($staff['schedule_date'])); ?></strong>
                            </td>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($staff['staff_name']); ?></td>
                            <td>
                                <span
                                    style="background: var(--light-accent); color: #0284c7; padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; border: 1px solid rgba(14, 165, 233, 0.2); display: inline-block;">
                                    <?php echo htmlspecialchars($staff['role']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($staff['assignment']); ?></td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-action-delete"
                                    onclick="openDeleteModal(<?php echo $staff['id']; ?>)">Mark Done</button>
                                <form id="delete-form-<?php echo $staff['id']; ?>" method="POST" style="display:none;">
                                    <input type="hidden" name="id" value="<?php echo $staff['id']; ?>">
                                    <input type="hidden" name="delete_schedule" value="1">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5"
                            style="text-align: center; padding: 25px; color: var(--text-muted); border: 1px dashed var(--border-soft); border-radius: 8px;">
                            No active staff assignments scheduled currently.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- BOTTOM SECTION: HISTORY / OVERALL REPORT -->
    <div class="card" style="width: 100%; box-sizing: border-box; background: #f8fafc; border: 1px solid #e2e8f0;">
        <h3
            style="display: flex; align-items: center; gap: 10px; margin-top: 0; margin-bottom: 15px; font-size: 16px; color: #475569;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M12 2v20"></path>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            Overall Report / Task History
        </h3>

        <table class="data-table" style="width: 100%; margin-top: 5px;">
            <thead>
                <tr>
                    <th style="width: 15%;">Completed On</th>
                    <th style="width: 15%;">Target Date</th>
                    <th style="width: 25%;">Staff Name</th>
                    <th style="width: 15%;">Role</th>
                    <th style="width: 30%;">Completed Task</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($staff_history) > 0): ?>
                    <?php foreach ($staff_history as $history): ?>
                        <tr style="background: #ffffff;">
                            <td>
                                <strong
                                    style="color: #16a34a; font-size: 12px;"><?php echo date('M d, Y', strtotime($history['completed_at'])); ?></strong><br>
                                <span
                                    style="font-size: 11px; color: #94a3b8;"><?php echo date('h:i A', strtotime($history['completed_at'])); ?></span>
                            </td>
                            <td style="color: #64748b; font-size: 13px;">
                                <?php echo date('M d, Y', strtotime($history['schedule_date'])); ?></td>
                            <td style="font-weight: 500; color: #334155;">
                                <?php echo htmlspecialchars($history['staff_name']); ?></td>
                            <td style="color: #64748b; font-size: 13px;"><?php echo htmlspecialchars($history['role']); ?></td>
                            <td style="color: #334155;">
                                <del style="color: #cbd5e1; margin-right: 5px;"></del>
                                <?php echo htmlspecialchars($history['assignment']); ?>
                                <span
                                    style="background: #dcfce7; color: #16a34a; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 8px; font-weight: bold;">DONE</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5"
                            style="text-align: center; padding: 25px; color: var(--text-muted); border: 1px dashed var(--border-soft); border-radius: 8px;">
                            No completed tasks in history yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- PROFESSIONAL DELETE / MARK DONE CONFIRMATION MODAL -->
<div id="deleteModal" class="modal-overlay no-print"
    style="display: none; align-items: center; justify-content: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="modal-box"
        style="width: 420px; text-align: center; padding: 35px 30px; border-radius: 16px; background: #ffffff; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="color: #16a34a; margin: 0 0 12px 0; font-size: 20px; font-weight: 700;">Mark as Done</h3>
        <p style="color: #000000; font-size: 14px; margin-bottom: 30px; line-height: 1.5; font-weight: 500;">Are you
            sure you want to mark this task as completed? It will be moved to the history report.</p>

        <div style="display: flex; gap: 12px; justify-content: center;">
            <button type="button" class="btn-cancel"
                onclick="document.getElementById('deleteModal').style.display='none'"
                style="flex: 1; margin-top:0; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #000; font-weight: 600; cursor: pointer;">Cancel</button>
            <button type="button" id="confirmDeleteBtn"
                style="flex: 1; background: #16a34a; color: #fff; border: 1px solid #15803d; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;"
                onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">Yes, Mark
                Done</button>
        </div>
    </div>
</div>

<script>
    let currentDeleteId = null;

    function openDeleteModal(id) {
        currentDeleteId = id;
        document.getElementById('deleteModal').style.display = 'flex';
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
        if (currentDeleteId !== null) {
            document.getElementById('delete-form-' + currentDeleteId).submit();
        }
    });
</script>

<?php require_once 'footer.php'; ?>