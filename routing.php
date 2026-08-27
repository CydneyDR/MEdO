<?php
require_once 'header.php';

$offices = ["Accounting Office", "Administrative Office", "Agriculture Office", "Assessors Office", "Budget Office", "Business Permit Licensing Office", "CCTV", "Cultural Protection and Promotions Office (MEMS)", "DILG", "Engineering Department", "Gender and Development Office", "General Services Office", "Human Resource Management Office", "JVZ Emergency MHO", "Kalayaan Park Office", "Local Civil Registry Office", "Local Poverty Reduction Affairs Office", "MADAC", "Management Information System Service Office", "Municipal Environment and Natural Resources Office", "Municipal Planning and Development Office", "Municipal Procurement Office", "Municipal Social Welfare and Development", "New Taytay Public Market", "Office of Councilor Boknay Leonardo", "Office of Councilor Joan Calderon", "Office of Councilor Leng Calderon", "Office of Councilor Jeca Villanueva", "Office of Councilor Papoo Cruz", "Office of Councilor SK Zai Villanueva", "Office of Councilor Tobit Cruz", "Old Taytay Public Market", "OSCA", "Persons with Disability Affairs Office", "Public Employment Service Office", "Public Information Office", "Sangguniang Bayan", "Security Office", "Sports Office", "Taytay Disaster Risk Reduction Management Office", "Taytay Fire Brigade", "Taytay Municipal Tricycle Franchising Regulatory Office", "Taytay Youth Development Office", "Treasury Office", "Urban Poor Affairs Office"];

// ETO NA ANG GOOGLE UPLOAD FUNCTION MO (Ginamit ang iyong eksaktong code)
function google_upload($fileTmpPath, $fileName, $fileType)
{
    $fileData = base64_encode(file_get_contents($fileTmpPath));

    $postData = array(
        'fileName' => $fileName,
        'mimeType' => $fileType,
        'fileData' => $fileData
    );

    $url = "https://script.google.com/macros/s/AKfycbzies0l027aLLC1tIsDxc6X68R-ncVeQ9BSG2xAwdFX1J_L8EOPXpIvY3MRVdJMNb-q/exec";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    curl_close($ch);

    // Binago ko nang kaunti para ibalik ang buong array para makuha natin ang fileUrl at fileId
    return json_decode($response, true);
}

// GOOGLE DRIVE RENAME FUNCTION
function renameGoogleDriveFile($fileId, $newFileName)
{
    $postData = array(
        'action' => 'rename',
        'fileId' => $fileId,
        'newFileName' => $newFileName
    );
    $url = "https://script.google.com/macros/s/AKfycbzies0l027aLLC1tIsDxc6X68R-ncVeQ9BSG2xAwdFX1J_L8EOPXpIvY3MRVdJMNb-q/exec";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// PROCESS FORMS
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_routing_paper'])) {
        $stmt = $pdo->prepare("INSERT INTO routing_papers (document_name, status, assigned_to, date_routed, routed_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['document_name'], 'Pending', $_POST['assigned_to'], date('Y-m-d'), $_POST['routed_by']]);
        $id = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO routing_history (paper_id, office_name, status, routed_by, date_routed) VALUES (?, ?, ?, ?, NOW())")
            ->execute([$id, $_POST['assigned_to'], 'Pending', $_POST['routed_by']]);

        echo "<script>window.location.href='routing.php';</script>";
        exit;
    }

    if (isset($_POST['update_routing_status'])) {
        $pdo->prepare("UPDATE routing_papers SET status = ?, assigned_to = ?, routed_by = ? WHERE id = ?")
            ->execute([$_POST['new_status'], $_POST['assigned_to'], $_POST['updated_by'], $_POST['route_update_id']]);

        $pdo->prepare("INSERT INTO routing_history (paper_id, office_name, status, routed_by, date_routed) VALUES (?, ?, ?, ?, NOW())")
            ->execute([$_POST['route_update_id'], $_POST['assigned_to'], $_POST['new_status'], $_POST['updated_by']]);

        echo "<script>window.location.href='routing.php';</script>";
        exit;
    }

    if (isset($_POST['upload_routing_file'])) {
        $paper_id = $_POST['paper_id'];
        if (isset($_FILES['paper_file']) && $_FILES['paper_file']['error'] == 0) {
            $original_name = basename($_FILES['paper_file']['name']);
            $file_type = $_FILES['paper_file']['type'];
            $file_tmp = $_FILES['paper_file']['tmp_name'];

            // Dito ginagamit ang iyong google_upload function
            $result = google_upload($file_tmp, $original_name, $file_type);

            if (isset($result['status']) && $result['status'] == 'success') {
                $db_target_file = $result['fileUrl'];
                $google_file_id = $result['fileId'];

                $pdo->prepare("UPDATE routing_papers SET file_path = ?, file_display_name = ?, file_id = ? WHERE id = ?")
                    ->execute([$db_target_file, $original_name, $google_file_id, $paper_id]);

                $stmtInfo = $pdo->prepare("SELECT assigned_to, routed_by FROM routing_papers WHERE id = ?");
                $stmtInfo->execute([$paper_id]);
                $pInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

                if ($pInfo) {
                    $pdo->prepare("INSERT INTO routing_history (paper_id, office_name, status, routed_by, date_routed) VALUES (?, ?, ?, ?, NOW())")
                        ->execute([$paper_id, $pInfo['assigned_to'], 'File Uploaded: <a href="' . $db_target_file . '" target="_blank">' . $original_name . '</a>', $pInfo['routed_by']]);
                }
            }
        }
        echo "<script>window.location.href='routing.php';</script>";
        exit;
    }

    if (isset($_POST['rename_routing_file'])) {
        $paper_id = $_POST['paper_id'];
        $new_display_name = trim($_POST['new_file_name']);

        if (!empty($new_display_name)) {
            $stmt = $pdo->prepare("SELECT file_id FROM routing_papers WHERE id = ?");
            $stmt->execute([$paper_id]);
            $paper = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($paper && !empty($paper['file_id'])) {
                renameGoogleDriveFile($paper['file_id'], $new_display_name);
            }

            $pdo->prepare("UPDATE routing_papers SET file_display_name = ? WHERE id = ?")
                ->execute([$new_display_name, $paper_id]);
        }
        echo "<script>window.location.href='routing.php';</script>";
        exit;
    }
}

// FETCH DATA
$routing_papers = $pdo->query("SELECT * FROM routing_papers ORDER BY date_routed DESC")->fetchAll(PDO::FETCH_ASSOC);

$status_counts = ['Pending' => 0, 'In Progress' => 0, 'Sent' => 0, 'For Review' => 0, 'For Signing' => 0, 'Approved' => 0, 'Retrieved' => 0];
$count_stmt = $pdo->query("SELECT status, COUNT(*) as count FROM routing_papers GROUP BY status");
$total_docs = 0;
while ($row = $count_stmt->fetch(PDO::FETCH_ASSOC)) {
    $status_counts[$row['status']] = $row['count'];
    $total_docs += $row['count'];
}
?>

<style>
    .top-section {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 25px;
    }

    .top-section .card {
        flex: 1;
        min-width: 320px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        padding: 25px;
    }

    .bottom-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        padding: 25px;
        width: 100%;
        box-sizing: border-box;
    }

    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        table-layout: auto;
        border-collapse: collapse;
        min-width: 800px;
    }

    .data-table th:nth-child(1) {
        width: 30%;
    }

    .data-table th:nth-child(2) {
        width: 22%;
    }

    .data-table th:nth-child(3) {
        width: 15%;
    }

    .data-table th:nth-child(4) {
        width: 13%;
    }

    .data-table th:nth-child(5) {
        width: 20%;
        text-align: center;
    }

    .data-table th {
        padding: 12px 10px;
        border-bottom: 2px solid #eee;
    }

    .data-table td {
        word-wrap: break-word;
        padding: 12px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #f4f4f4;
    }

    .action-container {
        display: flex;
        justify-content: center;
        gap: 8px;
        align-items: center;
        flex-wrap: nowrap;
        white-space: nowrap;
    }

    .btn-action {
        padding: 7px 10px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: 11px;
        font-weight: 700;
        transition: all 0.3s;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn-update {
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
        color: white;
        box-shadow: 0 2px 8px rgba(14, 165, 233, 0.25);
    }

    .btn-update:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
    }

    .btn-history {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.25);
    }

    .btn-history:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
        font-size: 0.9rem;
        color: #555;
    }

    .form-control {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: 0.3s;
        box-sizing: border-box;
    }

    .form-control:focus {
        border-color: #3498db;
        outline: none;
    }

    .btn-submit {
        background: #007bff;
        color: white;
        border: none;
        padding: 12px;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
        width: 100%;
    }

    .btn-submit:hover {
        background: #0056b3;
    }

    .btn-cancel {
        width: 100%;
        margin-top: 10px;
        background: #e9ecef;
        color: #444;
        border: none;
        padding: 12px;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
        box-sizing: border-box;
    }

    .btn-cancel:hover {
        background: #d6d8db;
    }

    dialog {
        border: none;
        border-radius: 15px;
        padding: 0;
        width: 450px;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
    }

    dialog::backdrop {
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }

    .modal-header {
        padding: 20px 25px;
        background: #fff;
        border-bottom: 1px solid #f0f0f0;
        font-weight: 800;
        font-size: 1.2rem;
        color: #2c3e50;
    }

    .modal-body {
        padding: 25px;
        background: #fcfcfc;
    }

    .status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 12px;
        margin-top: 15px;
    }

    .status-box {
        background: #f8f9fa;
        border: 1px solid #eee;
        padding: 15px;
        border-radius: 10px;
        text-align: center;
    }

    .status-box .count {
        display: block;
        font-size: 24px;
        font-weight: 800;
        color: #333;
        line-height: 1;
        margin-bottom: 5px;
    }

    .status-box .label {
        font-size: 11px;
        color: #777;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .search-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .search-input {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        width: 300px;
        font-size: 0.95rem;
    }

    .search-input:focus {
        outline: none;
        border-color: #3498db;
    }
</style>

<h1 class="header-title" style="margin-bottom: 20px;">Document Routing System</h1>

<div class="top-section">
    <div class="card" style="max-width: 500px;">
        <h3 style="margin-top:0; color:#333;">Log New Document</h3>
        <form method="POST">
            <div class="form-group"><label>Document Name</label><input type="text" name="document_name" required
                    class="form-control"></div>
            <div class="form-group"><label>Routed To Office</label><select name="assigned_to" class="form-control">
                    <?php foreach ($offices as $o): ?>
                        <option value="<?php echo $o; ?>"><?php echo $o; ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="form-group"><label>Your Name</label><input type="text" name="routed_by" required
                    class="form-control"></div>
            <button type="submit" name="add_routing_paper" class="btn-submit">Log Document</button>
        </form>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin:0; color:#333;">Routing Summary</h3>
            <span
                style="background: #e0f2fe; color: #0284c7; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">Total:
                <?php echo $total_docs; ?></span>
        </div>

        <div class="status-grid">
            <div class="status-box"><span class="count"
                    style="color: #64748b;"><?php echo $status_counts['Pending']; ?></span><span
                    class="label">Pending</span></div>
            <div class="status-box"><span class="count"
                    style="color: #3b82f6;"><?php echo $status_counts['In Progress']; ?></span><span class="label">In
                    Progress</span></div>
            <div class="status-box"><span class="count"
                    style="color: #8b5cf6;"><?php echo $status_counts['For Review']; ?></span><span class="label">For
                    Review</span></div>
            <div class="status-box"><span class="count"
                    style="color: #f59e0b;"><?php echo $status_counts['For Signing']; ?></span><span class="label">For
                    Signing</span></div>
            <div class="status-box"><span class="count"
                    style="color: #06b6d4;"><?php echo $status_counts['Sent']; ?></span><span class="label">Sent</span>
            </div>
            <div class="status-box"><span class="count"
                    style="color: #10b981;"><?php echo $status_counts['Approved']; ?></span><span
                    class="label">Approved</span></div>
            <div class="status-box"><span class="count"
                    style="color: #14b8a6;"><?php echo $status_counts['Retrieved']; ?></span><span
                    class="label">Retrieved</span></div>
        </div>
    </div>
</div>

<div class="bottom-card">
    <div class="search-container">
        <h3 style="margin:0; color:#333;">Active Documents</h3>
        <input type="text" id="searchInput" class="search-input" placeholder="Search by Document or Office..."
            onkeyup="searchTable()">
    </div>

    <div class="table-container">
        <table class="data-table" id="docTable">
            <thead>
                <tr style="background:#f8f9fa; text-align:left;">
                    <th style="color:#555;">DOCUMENT</th>
                    <th style="color:#555;">OFFICE</th>
                    <th style="color:#555;">STATUS</th>
                    <th style="color:#555;">ROUTED BY</th>
                    <th style="color:#555; text-align:center;">ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routing_papers as $p): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($p['document_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($p['assigned_to']); ?></td>
                        <td><span
                                style="background:#e8f4fd; color:#0056b3; padding:4px 8px; border-radius:6px; font-size:12px; font-weight:600;"><?php echo htmlspecialchars($p['status']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($p['routed_by']); ?></td>
                        <td>
                            <div class="action-container">
                                <button class="btn-action btn-update"
                                    onclick="openUpdate(<?php echo $p['id']; ?>)">Update</button>
                                <button class="btn-action btn-history"
                                    onclick="openHistory(<?php echo $p['id']; ?>)">History</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr id="noResultRow" style="display:none;">
                    <td colspan="5" style="text-align: center; padding: 20px; color: #888;">No matching documents found.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<dialog id="updateModal">
    <div class="modal-header">Update Document Status</div>
    <div class="modal-body">
        <form method="POST">
            <input type="hidden" name="route_update_id" id="modal_id">
            <div class="form-group">
                <label>New Office:</label>
                <select name="assigned_to" class="form-control">
                    <?php foreach ($offices as $o): ?>
                        <option value="<?php echo $o; ?>"><?php echo $o; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status:</label>
                <select name="new_status" class="form-control">
                    <option>Pending</option>
                    <option>In Progress</option>
                    <option>Sent</option>
                    <option>For Review</option>
                    <option>For Signing</option>
                    <option>Approved</option>
                    <option>Retrieved</option>
                </select>
            </div>
            <div class="form-group">
                <label>Updated By:</label>
                <input type="text" name="updated_by" required class="form-control">
            </div>
            <button type="submit" name="update_routing_status" class="btn-submit">Save Changes</button>
            <button type="button" class="btn-cancel" onclick="this.closest('dialog').close()">Cancel</button>
        </form>
    </div>
</dialog>

<dialog id="uploadModal">
    <div class="modal-header">Upload Document File</div>
    <div class="modal-body">
        <form method="POST" action="routing.php" enctype="multipart/form-data">
            <input type="hidden" name="paper_id" id="upload_paper_id">
            <div class="form-group">
                <label style="font-weight: 600; color: #333; margin-bottom: 8px; display: block;">Select File (PDF,
                    Word, Images):</label>
                <input type="file" name="paper_file" required class="form-control"
                    style="padding: 10px; background: #fff; border: 1px solid #38bdf8; border-radius: 8px;">
            </div>
            <button type="submit" name="upload_routing_file" class="btn-submit"
                style="background: #0066ff; margin-top: 10px; padding: 14px; font-size: 1rem; border-radius: 10px;">Upload
                File</button>
            <button type="button" class="btn-cancel"
                onclick="document.getElementById('uploadModal').close(); document.getElementById('historyModal').showModal();"
                style="padding: 14px; font-size: 1rem; border-radius: 10px; background: #e2e8f0; color: #334155;">Cancel</button>
        </form>
    </div>
</dialog>

<dialog id="historyModal">
    <div class="modal-header">Transaction History & Files</div>
    <div class="modal-body" id="histContent" style="max-height: 420px; overflow-y: auto; padding-bottom: 10px;"></div>

    <div style="padding: 20px; background:#fff; border-top:1px solid #f0f0f0; display: flex; gap: 10px;">
        <button type="button" class="btn-submit" style="background: #10b981; margin-top: 0; padding: 10px;"
            onclick="openUploadFromHistory()">+ Upload New File</button>
        <button type="button" class="btn-cancel" style="background:#34495e; color:white; margin-top:0; padding: 10px;"
            onclick="document.getElementById('historyModal').close()">Close</button>
    </div>
</dialog>

<script>
    let currentDocumentId = null;

    function searchTable() {
        let input = document.getElementById("searchInput").value.toUpperCase();
        let table = document.getElementById("docTable");
        let tr = table.getElementsByTagName("tr");
        let visibleCount = 0;

        for (let i = 1; i < tr.length; i++) {
            if (tr[i].id === "noResultRow") continue;

            let tdDoc = tr[i].getElementsByTagName("td")[0];
            let tdOffice = tr[i].getElementsByTagName("td")[1];

            if (tdDoc || tdOffice) {
                let docText = tdDoc.textContent || tdDoc.innerText;
                let officeText = tdOffice.textContent || tdOffice.innerText;

                if (docText.toUpperCase().indexOf(input) > -1 || officeText.toUpperCase().indexOf(input) > -1) {
                    tr[i].style.display = "";
                    visibleCount++;
                } else {
                    tr[i].style.display = "none";
                }
            }
        }

        document.getElementById("noResultRow").style.display = (visibleCount === 0) ? "" : "none";
    }

    function openUpdate(id) {
        document.getElementById('modal_id').value = id;
        document.getElementById('updateModal').showModal();
    }

    function openHistory(id) {
        currentDocumentId = id;
        fetch('get_history.php?id=' + id)
            .then(res => res.text())
            .then(data => {
                document.getElementById('histContent').innerHTML = data;
                document.getElementById('historyModal').showModal();
            });
    }

    function openUploadFromHistory() {
        document.getElementById('historyModal').close();
        document.getElementById('upload_paper_id').value = currentDocumentId;
        document.getElementById('uploadModal').showModal();
    }
</script>

<?php require_once 'footer.php'; ?>