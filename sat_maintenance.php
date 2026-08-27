<?php
require_once 'db.php';
require_once 'sat_sidebar.php'; // Tinatawag nito yung sidebar, header, at login session
require_once 'email_maintenance.php'; // Isama ang bagong email maintenance function

// Kunin ang mismong pangalan ng satellite office sa database gamit ang $sat_id
$satelliteName = 'Satellite Office #' . $sat_id;
try {
    $satQuery = $pdo->prepare("SELECT office_name FROM satellite_offices WHERE id = ?");
    $satQuery->execute([$sat_id]);
    $satData = $satQuery->fetch(PDO::FETCH_ASSOC);
    if ($satData && isset($satData['office_name'])) {
        $satelliteName = $satData['office_name'];
    }
} catch (PDOException $e) {
    // Fallback kung sakaling magka-issue sa query
}

// SUBMIT MAINTENANCE REPORT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_maint'])) {
    $issueTitle = $_POST['issue_title'];
    $issueDesc = $_POST['issue_description'];

    // 1. I-save sa Database
    $stmt = $pdo->prepare("INSERT INTO satellite_maintenance (satellite_id, issue_title, issue_description, status) VALUES (?, ?, ?, 'Pending')");
    $stmt->execute([$sat_id, $issueTitle, $issueDesc]);
    
    // 2. Magpadala ng Email Notification sa Admin
    $adminEmail = 'Smiletech4edLab@taytayrizal.gov.ph'; 
    @sendEmailMaintenance($adminEmail, $issueTitle, $issueDesc, $satelliteName);

    // 3. Mag-redirect dala ang success flag para pag-load ng page ay lalabas ang flashcard
    echo "<script>window.location.href='sat_maintenance.php?success=1';</script>";
    exit;
}

$pending_maint = $pdo->prepare("SELECT * FROM satellite_maintenance WHERE satellite_id = ? AND status = 'Pending' ORDER BY date_reported ASC");
$pending_maint->execute([$sat_id]); 
$pending_maint = $pending_maint->fetchAll(PDO::FETCH_ASSOC);

$history_maint = $pdo->prepare("SELECT * FROM satellite_maintenance WHERE satellite_id = ? AND status != 'Pending' ORDER BY date_reported DESC");
$history_maint->execute([$sat_id]); 
$history_maint = $history_maint->fetchAll(PDO::FETCH_ASSOC);
$count_pending_maint = count($pending_maint);
?>

<!-- I-load ang SweetAlert2 para sa Flashcard / Popup Display pagkatapos mag-process -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if(isset($_GET['success'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'success',
                title: 'Maintenance Report Submitted',
                text: 'Your maintenance issue has been logged and sent to the administration for review and approval.',
                confirmButtonColor: '#ef4444'
            });
        });
    </script>
<?php endif; ?>

<div class="page-header">Maintenance & Repairs</div>

<div class="grid-2">
    <!-- FORM -->
    <div class="modern-card" style="border-top: 4px solid #ef4444;">
        <div class="card-title"><span style="color: #ef4444;">Report an Issue</span></div>
        <form method="POST">
            <div class="form-group"><label>Issue / Equipment</label><input type="text" name="issue_title" class="form-control" required placeholder="e.g. Broken Aircon in Room 1"></div>
            <div class="form-group"><label>Detailed Description</label><textarea name="issue_description" class="form-control" rows="4" required placeholder="Describe what happened or what's broken..."></textarea></div>
            <button type="submit" name="submit_maint" class="btn btn-danger">Submit Report</button>
        </form>
    </div>

    <!-- PENDING -->
    <div class="modern-card">
        <div class="card-title"><span>Pending Repairs (<span style="color:#ef4444;"><?php echo $count_pending_maint; ?></span>)</span></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr><th>Issue Reported</th><th>Date Reported</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if(count($pending_maint) > 0): ?>
                        <?php foreach($pending_maint as $m): ?>
                        <tr>
                            <td><strong style="color:#ef4444;"><?php echo htmlspecialchars($m['issue_title']); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($m['date_reported'])); ?></td>
                            <td><span class="badge badge-pending">Pending Action</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">No pending repair reports.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- HISTORY -->
<div class="modern-card">
    <div class="card-title"><span>Maintenance History & Action Taken</span></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr><th>Issue Reported</th><th>Admin Remarks / Action Taken</th><th>Status</th></tr></thead>
            <tbody>
                <?php if(count($history_maint) > 0): ?>
                    <?php foreach($history_maint as $h): ?>
                        <?php 
                            $badgeClass = 'badge-resolved';
                            if ($h['status'] == 'In Progress') $badgeClass = 'badge-inprogress';
                        ?>
                    <tr>
                        <td>
                            <strong style="color: #0f172a;"><?php echo htmlspecialchars($h['issue_title']); ?></strong>
                            <div style="font-size: 0.75rem; color:#94a3b8; margin-top:4px;">Reported: <?php echo date('M d, Y', strtotime($h['date_reported'])); ?></div>
                        </td>
                        <td>
                            <div class="notes-box" style="border-left-color: #ef4444;">
                                <?php echo !empty($h['admin_remarks']) ? htmlspecialchars($h['admin_remarks']) : 'No remarks provided.'; ?>
                            </div>
                        </td>
                        <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($h['status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">No maintenance history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div> <!-- END OF MAIN CONTENT -->
</body>
</html>