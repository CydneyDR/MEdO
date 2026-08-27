<?php
require_once 'db.php';
require_once 'sat_sidebar.php'; // Tinatawag nito yung sidebar, header, at login session
require_once 'email_requisition.php'; // Isama ang email function

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

// SUBMIT REQUISITION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_req'])) {
    $itemName = $_POST['item_name'];
    $quantity = $_POST['quantity'];
    $dateRequested = $_POST['date_requested'];

    // 1. I-save sa Database
    $stmt = $pdo->prepare("INSERT INTO satellite_requisitions (satellite_id, item_name, quantity, status, date_requested) VALUES (?, ?, ?, 'Pending', ?)");
    $stmt->execute([$sat_id, $itemName, $quantity, $dateRequested]);
    
    // 2. Magpadala ng Email Notification sa Admin
    $adminEmail = 'Smiletech4edLab@taytayrizal.gov.ph'; 
    @sendEmailRequisition($adminEmail, $itemName, $quantity, $dateRequested, $satelliteName);

    // 3. Mag-redirectdala ang success flag para pag-load ng page ay lalabas ang flashcard
    echo "<script>window.location.href='sat_index.php?success=1';</script>";
    exit;
}

// FETCH DATA
$pending_reqs = $pdo->prepare("SELECT * FROM satellite_requisitions WHERE satellite_id = ? AND status = 'Pending' ORDER BY date_requested ASC");
$pending_reqs->execute([$sat_id]); 
$pending_reqs = $pending_reqs->fetchAll(PDO::FETCH_ASSOC);

$history_reqs = $pdo->prepare("SELECT * FROM satellite_requisitions WHERE satellite_id = ? AND status != 'Pending' ORDER BY date_requested DESC");
$history_reqs->execute([$sat_id]); 
$history_reqs = $history_reqs->fetchAll(PDO::FETCH_ASSOC);
$count_pending_req = count($pending_reqs);
?>

<!-- I-load ang SweetAlert2 para sa Flashcard / Popup Display pagkatapos mag-process -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if(isset($_GET['success'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
    icon: 'success',
    title: 'Requisition Submitted Successfully',
    text: 'Your request has been logged and sent to the administration for review and approval.',
    confirmButtonColor: '#2563eb'
});
        });
    </script>
<?php endif; ?>

<div class="page-header">Supplies & Requisition</div>

<div class="grid-2">
    <!-- FORM -->
    <div class="modern-card">
        <div class="card-title"><span>Request New Supplies</span></div>
        <form method="POST">
            <div class="form-group"><label>Item Description / Name</label><input type="text" name="item_name" class="form-control" required placeholder="e.g. 5 Reams of Bond Paper"></div>
            <div class="form-group"><label>Quantity Needed</label><input type="number" name="quantity" class="form-control" required min="1"></div>
            <div class="form-group"><label>Date Needed</label><input type="date" name="date_requested" class="form-control" required></div>
            <button type="submit" name="submit_req" class="btn btn-primary">Submit Request</button>
        </form>
    </div>

    <!-- PENDING -->
    <div class="modern-card">
        <div class="card-title"><span>Pending Approval (<span style="color:#f97316;"><?php echo $count_pending_req; ?></span>)</span></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr><th>Item (Qty)</th><th>Date Needed</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if(count($pending_reqs) > 0): ?>
                        <?php foreach($pending_reqs as $r): ?>
                        <tr>
                            <td><strong style="color: #0f172a;"><?php echo htmlspecialchars($r['item_name']); ?></strong> <span style="color:#64748b;">(x<?php echo $r['quantity']; ?>)</span></td>
                            <td><?php echo date('M d, Y', strtotime($r['date_requested'])); ?></td>
                            <td><span class="badge badge-pending">Pending Review</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">No pending supplies requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- HISTORY -->
<div class="modern-card">
    <div class="card-title"><span>Requisition History & Admin Notes</span></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr><th>Requested Item</th><th>Admin Remarks / Notes</th><th>Final Status</th></tr></thead>
            <tbody>
                <?php if(count($history_reqs) > 0): ?>
                    <?php foreach($history_reqs as $h): ?>
                        <?php 
                            $badgeClass = 'badge-approved';
                            if ($h['status'] == 'Declined') $badgeClass = 'badge-declined';
                            if ($h['status'] == 'Partially Approved') $badgeClass = 'badge-partial';
                        ?>
                    <tr>
                        <td>
                            <strong style="color: #0f172a;"><?php echo htmlspecialchars($h['item_name']); ?></strong> <span style="color:#64748b;">(x<?php echo $h['quantity']; ?>)</span>
                            <div style="font-size: 0.75rem; color:#94a3b8; margin-top:4px;">Requested: <?php echo date('M d, Y', strtotime($h['date_requested'])); ?></div>
                        </td>
                        <td>
                            <div class="notes-box">
                                <?php echo !empty($h['admin_remarks']) ? htmlspecialchars($h['admin_remarks']) : 'No remarks provided.'; ?>
                            </div>
                        </td>
                        <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($h['status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">No supplies history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div> <!-- END OF MAIN CONTENT -->
</body>
</html>