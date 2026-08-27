<?php
require_once 'header.php';
require_once 'db.php';

// PROCESS FORMS (POST REQUESTS)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. ADD SATELLITE OFFICE & ACCOUNT
    if (isset($_POST['add_satellite'])) {
        $stmt = $pdo->prepare("INSERT INTO satellite_offices (office_name, location, username, password, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['office_name'], $_POST['location'], $_POST['username'], $_POST['password'], $_POST['status']]);
        echo "<script>window.location.href='satellite.php';</script>";
        exit;
    }

    // 2. ADD SCHEDULE
    if (isset($_POST['add_schedule'])) {
        $stmt = $pdo->prepare("INSERT INTO satellite_schedules (satellite_id, event_title, event_date, status) VALUES (?, ?, ?, 'Upcoming')");
        $stmt->execute([$_POST['satellite_id'], $_POST['event_title'], $_POST['event_date']]);
        echo "<script>window.location.href='satellite.php';</script>";
        exit;
    }

    // 3. UPDATE REQUISITION STATUS
    if (isset($_POST['update_req_status'])) {
        $stmt = $pdo->prepare("UPDATE satellite_requisitions SET status = ?, admin_remarks = ? WHERE id = ?");
        $stmt->execute([$_POST['new_status'], $_POST['admin_remarks'], $_POST['req_id']]);
        echo "<script>window.location.href='satellite.php';</script>";
        exit;
    }

    // 4. UPDATE MAINTENANCE STATUS
    if (isset($_POST['update_maint_status'])) {
        $stmt = $pdo->prepare("UPDATE satellite_maintenance SET status = ?, admin_remarks = ? WHERE id = ?");
        $stmt->execute([$_POST['new_status'], $_POST['admin_remarks'], $_POST['maint_id']]);
        echo "<script>window.location.href='satellite.php';</script>";
        exit;
    }
}

// FETCH DATA
$satellites = $pdo->query("SELECT * FROM satellite_offices ORDER BY date_added DESC")->fetchAll(PDO::FETCH_ASSOC);

$pending_reqs_data = $pdo->query("SELECT r.*, s.office_name FROM satellite_requisitions r JOIN satellite_offices s ON r.satellite_id = s.id WHERE r.status = 'Pending' ORDER BY r.date_requested ASC")->fetchAll(PDO::FETCH_ASSOC);
$history_reqs_data = $pdo->query("SELECT r.*, s.office_name FROM satellite_requisitions r JOIN satellite_offices s ON r.satellite_id = s.id WHERE r.status != 'Pending' ORDER BY r.date_requested DESC")->fetchAll(PDO::FETCH_ASSOC);

$pending_maint_data = $pdo->query("SELECT m.*, s.office_name FROM satellite_maintenance m JOIN satellite_offices s ON m.satellite_id = s.id WHERE m.status = 'Pending' ORDER BY m.date_reported ASC")->fetchAll(PDO::FETCH_ASSOC);
$history_maint_data = $pdo->query("SELECT m.*, s.office_name FROM satellite_maintenance m JOIN satellite_offices s ON m.satellite_id = s.id WHERE m.status != 'Pending' ORDER BY m.date_reported DESC")->fetchAll(PDO::FETCH_ASSOC);

$schedules = $pdo->query("SELECT sch.*, s.office_name FROM satellite_schedules sch JOIN satellite_offices s ON sch.satellite_id = s.id ORDER BY sch.event_date ASC")->fetchAll(PDO::FETCH_ASSOC);

// STATS
$total_satellites = count($satellites);
$pending_reqs_count = count($pending_reqs_data);
$pending_maint_count = count($pending_maint_data);
?>

<style>
    /* MAIN LAYOUT */
    .dashboard-header {
        font-size: 1.8rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    .grid-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
        margin-bottom: 30px;
    }

    /* CARDS */
    .modern-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
        padding: 24px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card-title {
        margin-top: 0;
        color: #0f172a;
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 12px;
    }

    /* STATS WIDGETS */
    .stats-container {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 30px;
    }

    .stat-box {
        flex: 1;
        min-width: 220px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 24px;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .stat-box .info h3 {
        margin: 0;
        font-size: 2.2rem;
        color: #0f172a;
        font-weight: 800;
        line-height: 1.1;
    }

    .stat-box .info p {
        margin: 6px 0 0 0;
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* TABLES */
    .table-wrapper {
        width: 100%;
        overflow-x: auto;
        border-radius: 8px;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 550px;
    }

    .data-table th {
        background: #f8fafc;
        padding: 14px 16px;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0;
    }

    .data-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        font-size: 0.9rem;
    }

    /* BADGES */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-active {
        background: #dcfce7;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }

    .badge-maintenance {
        background: #fef3c7;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    .badge-pending {
        background: #ffedd5;
        color: #ea580c;
        border: 1px solid #fed7aa;
    }

    .badge-approved {
        background: #dbeafe;
        color: #2563eb;
        border: 1px solid #bfdbfe;
    }

    .badge-declined {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    /* FORMS */
    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 0.95rem;
        box-sizing: border-box;
        background: #f8fafc;
        transition: all 0.3s;
    }

    .form-control:focus {
        outline: none;
        border-color: #0ea5e9;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    /* BEAUTIFIED BUTTONS */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        text-decoration: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(37, 99, 235, 0.4);
    }

    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(16, 185, 129, 0.4);
    }

    .btn-cancel {
        background: #e2e8f0;
        color: #475569;
    }

    .btn-cancel:hover {
        background: #cbd5e1;
        transform: translateY(-2px);
    }

    .btn-action {
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
        color: white;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(14, 165, 233, 0.25);
        transition: all 0.3s ease;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
    }

    /* MODALS */
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

    .modal-header {
        padding: 20px 24px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 800;
        font-size: 1.15rem;
    }

    .modal-body {
        padding: 24px;
        background: #fcfcfc;
    }
</style>

<div class="dashboard-header">
    <div style="display: flex; align-items: center; gap: 10px;">
        <div style="background: #eff6ff; padding: 8px; border-radius: 10px; color: #2563eb; display: flex;">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
            </svg>
        </div>
        Satellite Offices System
    </div>
    <!-- Grouped buttons together inside a container -->
    <div style="display: flex; align-items: center; gap: 12px;">
        <a href="sat_overall_analytics.php" class="btn"
            style="background: #8b5cf6; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: bold; box-shadow: 0 4px 10px rgba(139,92,246,0.3);">
            View Overall Analytics</a>
        <a href="sat_login.php" target="_blank" class="btn btn-success">Go to Employee Portal ↗</a>
    </div>
</div>

<!-- TOP STATS -->
<div class="stats-container">
    <div class="stat-box">
        <div class="info">
            <h3><?php echo $total_satellites; ?></h3>
            <p>Total Satellites</p>
        </div>
    </div>
    <div class="stat-box">
        <div class="info">
            <h3><?php echo $pending_reqs_count; ?></h3>
            <p>Pending Requisitions</p>
        </div>
    </div>
    <div class="stat-box">
        <div class="info">
            <h3><?php echo $pending_maint_count; ?></h3>
            <p>Pending Repairs</p>
        </div>
    </div>
</div>

<div class="grid-container">

    <!-- 1. SATELLITE MONITORING -->
    <div class="modern-card">
        <div class="card-title">
            <span>Satellite Offices List</span>
            <button class="btn btn-primary" style="padding: 8px 16px; font-size: 0.75rem;"
                onclick="document.getElementById('addSatelliteModal').showModal()">+ New Office</button>
        </div>
        <div class="table-wrapper" style="max-height: 350px; overflow-y: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Office Name</th>
                        <th>Location</th>
                        <th>Login Username</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($satellites as $sat): ?>
                        <tr>
                            <td><strong
                                    style="color: #0f172a;"><?php echo htmlspecialchars($sat['office_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($sat['location']); ?></td>
                            <td style="color: #2563eb; font-weight: 600;"><?php echo htmlspecialchars($sat['username']); ?>
                            </td>
                            <td>
                                <span
                                    class="badge <?php echo ($sat['status'] == 'Active') ? 'badge-active' : 'badge-maintenance'; ?>">
                                    <?php echo htmlspecialchars($sat['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. PENDING REQUISITIONS -->
    <div class="modern-card">
        <div class="card-title"><span>Pending Requisitions</span></div>
        <div class="table-wrapper" style="max-height: 300px; overflow-y: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date Needed</th>
                        <th>Satellite</th>
                        <th>Item (Qty)</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pending_reqs_data) > 0): ?>
                        <?php foreach ($pending_reqs_data as $req): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($req['date_requested'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($req['office_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($req['item_name']); ?> <span
                                        style="color:#64748b;">(x<?php echo $req['quantity']; ?>)</span></td>
                                <td style="text-align: center;">
                                    <button class="btn-action"
                                        onclick="openReqAction(<?php echo $req['id']; ?>, '<?php echo addslashes($req['item_name']); ?>', '<?php echo addslashes($req['office_name']); ?>', <?php echo $req['quantity']; ?>)">Review</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #94a3b8; padding: 15px;">No pending
                                requisitions.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. PENDING MAINTENANCE / REPAIRS -->
    <div class="modern-card" style="border: 1px solid #fecaca;">
        <div class="card-title"><span>Pending Maintenance & Repairs</span></div>
        <div class="table-wrapper" style="max-height: 300px; overflow-y: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date Reported</th>
                        <th>Satellite</th>
                        <th>Issue & Description</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pending_maint_data) > 0): ?>
                        <?php foreach ($pending_maint_data as $maint): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($maint['date_reported'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($maint['office_name']); ?></strong></td>
                                <td>
                                    <strong
                                        style="color: #ef4444;"><?php echo htmlspecialchars($maint['issue_title']); ?></strong><br>
                                    <span
                                        style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($maint['issue_description']); ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <button class="btn-action"
                                        onclick="openMaintAction(<?php echo $maint['id']; ?>, '<?php echo addslashes($maint['issue_title']); ?>', '<?php echo addslashes($maint['office_name']); ?>')">Update</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #94a3b8; padding: 15px;">No pending
                                maintenance issues.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 4. HISTORY: REQUISITIONS & MAINTENANCE -->
    <div class="modern-card">
        <div class="card-title"><span>Actions History (Admin Notes)</span></div>

        <h4 style="color:#0f172a; margin-bottom:10px;">Requisition History</h4>
        <div class="table-wrapper" style="max-height: 250px; overflow-y: auto; margin-bottom: 20px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Satellite</th>
                        <th>Item</th>
                        <th>Admin Notes</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history_reqs_data as $hReq): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($hReq['office_name']); ?></td>
                            <td><?php echo htmlspecialchars($hReq['item_name']); ?></td>
                            <td style="font-size:0.8rem; font-style:italic;">
                                <?php echo htmlspecialchars($hReq['admin_remarks']); ?>
                            </td>
                            <td><span class="badge badge-approved"><?php echo htmlspecialchars($hReq['status']); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h4 style="color:#0f172a; margin-bottom:10px;">Maintenance History</h4>
        <div class="table-wrapper" style="max-height: 250px; overflow-y: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Satellite</th>
                        <th>Issue</th>
                        <th>Admin Notes (Action Taken)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history_maint_data as $hMaint): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($hMaint['office_name']); ?></td>
                            <td><?php echo htmlspecialchars($hMaint['issue_title']); ?></td>
                            <td style="font-size:0.8rem; font-style:italic;">
                                <?php echo htmlspecialchars($hMaint['admin_remarks']); ?>
                            </td>
                            <td><span class="badge badge-approved"><?php echo htmlspecialchars($hMaint['status']); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. CALENDAR -->
    <div class="modern-card">
        <div class="card-title">
            <span>Satellite Calendar</span>
            <button class="btn btn-primary" style="padding: 8px 16px; font-size: 0.75rem;"
                onclick="document.getElementById('addScheduleModal').showModal()">+ Add Event</button>
        </div>
        <div class="table-wrapper" style="max-height: 300px; overflow-y: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Satellite</th>
                        <th>Event Title</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $sched): ?>
                        <tr>
                            <td><strong><?php echo date('M d, Y', strtotime($sched['event_date'])); ?></strong></td>
                            <td><?php echo htmlspecialchars($sched['office_name']); ?></td>
                            <td><?php echo htmlspecialchars($sched['event_title']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ================= MODALS ================= -->

<!-- REQUISITION ACTION -->
<dialog id="reqActionModal">
    <div class="modal-header">Process Requisition Request</div>
    <div class="modal-body">
        <form method="POST">
            <input type="hidden" name="req_id" id="action_req_id">
            <p id="req_details" style="font-weight:bold; color:#0f172a; margin-bottom:15px; font-size: 1.1rem;"></p>
            <div class="form-group"><label>Action</label>
                <select name="new_status" class="form-control">
                    <option value="Approved">Approve Fully</option>
                    <option value="Partially Approved">Partially Approve</option>
                    <option value="Declined">Decline / Out of Stock</option>
                </select>
            </div>
            <div class="form-group"><label>Admin Notes</label><textarea name="admin_remarks" class="form-control"
                    rows="3" required placeholder="Add any details about the approval/decline..."></textarea></div>
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn-cancel" onclick="document.getElementById('reqActionModal').close()"
                    style="flex:1;">Cancel</button>
                <button type="submit" name="update_req_status" class="btn btn-primary" style="flex:1;">Save
                    Actions</button>
            </div>
        </form>
    </div>
</dialog>

<!-- MAINTENANCE ACTION -->
<dialog id="maintActionModal">
    <div class="modal-header">Process Maintenance Issue</div>
    <div class="modal-body">
        <form method="POST">
            <input type="hidden" name="maint_id" id="action_maint_id">
            <p id="maint_details" style="font-weight:bold; color:#ef4444; margin-bottom:15px; font-size: 1.1rem;"></p>
            <div class="form-group"><label>Update Status</label>
                <select name="new_status" class="form-control">
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                </select>
            </div>
            <div class="form-group"><label>Admin Notes (Action Taken / Planned)</label><textarea name="admin_remarks"
                    class="form-control" rows="3" required
                    placeholder="State actions taken to resolve the issue..."></textarea></div>
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn-cancel"
                    onclick="document.getElementById('maintActionModal').close()" style="flex:1;">Cancel</button>
                <button type="submit" name="update_maint_status" class="btn btn-primary" style="flex:1;">Save
                    Update</button>
            </div>
        </form>
    </div>
</dialog>

<!-- ADD SATELLITE MODAL -->
<dialog id="addSatelliteModal">
    <div class="modal-header">Add Satellite Office</div>
    <div class="modal-body">
        <form method="POST">
            <div class="form-group"><label>Office Name</label><input type="text" name="office_name" required
                    class="form-control"></div>
            <div class="form-group"><label>Location</label><input type="text" name="location" required
                    class="form-control"></div>
            <div style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;"><label>Username</label><input type="text" name="username"
                        required class="form-control"></div>
                <div class="form-group" style="flex:1;"><label>Password</label><input type="password" name="password"
                        required class="form-control"></div>
            </div>
            <div class="form-group"><label>Status</label>
                <select name="status" class="form-control">
                    <option value="Active">Active</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn-cancel"
                    onclick="document.getElementById('addSatelliteModal').close()" style="flex:1;">Cancel</button>
                <button type="submit" name="add_satellite" class="btn btn-primary" style="flex:1;">Save Office</button>
            </div>
        </form>
    </div>
</dialog>

<!-- ADD SCHEDULE MODAL -->
<dialog id="addScheduleModal">
    <div class="modal-header">Add Event Schedule</div>
    <div class="modal-body">
        <form method="POST">
            <div class="form-group"><label>Satellite</label>
                <select name="satellite_id" required class="form-control">
                    <?php foreach ($satellites as $sat) {
                        echo "<option value='{$sat['id']}'>{$sat['office_name']}</option>";
                    } ?>
                </select>
            </div>
            <div class="form-group"><label>Event Title</label><input type="text" name="event_title" required
                    class="form-control"></div>
            <div class="form-group"><label>Event Date</label><input type="date" name="event_date" required
                    class="form-control"></div>
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn-cancel"
                    onclick="document.getElementById('addScheduleModal').close()" style="flex:1;">Cancel</button>
                <button type="submit" name="add_schedule" class="btn btn-primary" style="flex:1;">Save Schedule</button>
            </div>
        </form>
    </div>
</dialog>

<script>
    function openReqAction(id, item, office, qty) {
        document.getElementById('action_req_id').value = id;
        document.getElementById('req_details').innerText = "Request: " + qty + "x " + item + " (" + office + ")";
        document.getElementById('reqActionModal').showModal();
    }
    function openMaintAction(id, issue, office) {
        document.getElementById('action_maint_id').value = id;
        document.getElementById('maint_details').innerText = "Issue: " + issue + " (" + office + ")";
        document.getElementById('maintActionModal').showModal();
    }
</script>

<?php require_once 'footer.php'; ?>