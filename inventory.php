<?php
session_start();
require 'db.php';
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

// ==========================================
// 1. AUTO-CREATE ALL INVENTORY TABLES
// ==========================================
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ict_equipment (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), brand VARCHAR(100), asset_no VARCHAR(100), specs TEXT, category VARCHAR(100), serial_no VARCHAR(100), prop_no VARCHAR(100), qty INT, unit VARCHAR(50), status VARCHAR(100), location VARCHAR(100), responsible VARCHAR(255), purch_date DATE, cost DECIMAL(10,2), warranty DATE)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS general_supplies (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), category VARCHAR(100), location VARCHAR(100), supplier VARCHAR(255), qty INT, unit VARCHAR(50), min_stock INT, cost DECIMAL(10,2), expiry DATE, remarks TEXT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_transactions (id INT AUTO_INCREMENT PRIMARY KEY, item_type VARCHAR(50), item_name VARCHAR(255), trans_type VARCHAR(50), qty INT, trans_date DATE, handled_by VARCHAR(255), remarks TEXT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS maintenance_logs (id INT AUTO_INCREMENT PRIMARY KEY, asset_no VARCHAR(100), equipment_name VARCHAR(255), technician VARCHAR(255), maint_type VARCHAR(100), cost DECIMAL(10,2), status VARCHAR(50), log_date DATE, remarks TEXT)");
} catch (PDOException $e) {
}

// ==========================================
// 2. FORM SUBMISSIONS (ADD/UPDATE/DELETE)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_ict'])) {
    $stmt = $pdo->prepare("INSERT INTO ict_equipment (name, category, asset_no, location, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['category'], $_POST['asset_no'], $_POST['location'], $_POST['status']]);
    header("Location: inventory.php");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_ict'])) {
    $stmt = $pdo->prepare("UPDATE ict_equipment SET name=?, category=?, asset_no=?, location=?, status=? WHERE id=?");
    $stmt->execute([$_POST['name'], $_POST['category'], $_POST['asset_no'], $_POST['location'], $_POST['status'], $_POST['id']]);
    header("Location: inventory.php");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_ict'])) {
    $pdo->prepare("DELETE FROM ict_equipment WHERE id = ?")->execute([$_POST['id']]);
    header("Location: inventory.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_supply'])) {
    $stmt = $pdo->prepare("INSERT INTO general_supplies (name, category, location, qty, min_stock) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['category'], $_POST['location'], (int) $_POST['qty'], (int) $_POST['min_stock']]);
    header("Location: inventory.php");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_supply'])) {
    $stmt = $pdo->prepare("UPDATE general_supplies SET name=?, category=?, location=?, qty=?, min_stock=? WHERE id=?");
    $stmt->execute([$_POST['name'], $_POST['category'], $_POST['location'], (int) $_POST['qty'], (int) $_POST['min_stock'], $_POST['id']]);
    header("Location: inventory.php");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_supply'])) {
    $pdo->prepare("DELETE FROM general_supplies WHERE id = ?")->execute([$_POST['id']]);
    header("Location: inventory.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_transaction'])) {
    $item_info = explode('|', $_POST['item_data']);
    $id = $item_info[0];
    $name = $item_info[1];
    $type = $item_info[2];
    $trans_type = $_POST['trans_type'];
    $qty = (int) $_POST['qty'];
    if ($type == 'Supply') {
        if ($trans_type == 'Stock In')
            $pdo->prepare("UPDATE general_supplies SET qty = qty + ? WHERE id = ?")->execute([$qty, $id]);
        else if ($trans_type == 'Stock Out')
            $pdo->prepare("UPDATE general_supplies SET qty = qty - ? WHERE id = ?")->execute([$qty, $id]);
    }
    $stmt = $pdo->prepare("INSERT INTO inventory_transactions (item_type, item_name, trans_type, qty, trans_date, handled_by, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$type, $name, $trans_type, $qty, $_POST['trans_date'], $_POST['handled_by'], $_POST['remarks']]);
    header("Location: inventory.php");
    exit;
}

// ==========================================
// 3. FETCH DATA & ANALYTICS METRICS
// ==========================================
$ict_items = [];
$supplies = [];
$transactions = [];
try {
    $ict_items = $pdo->query("SELECT * FROM ict_equipment ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $supplies = $pdo->query("SELECT * FROM general_supplies ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $transactions = $pdo->query("SELECT * FROM inventory_transactions ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

    $total_ict = count($ict_items);
    $total_sup = count($supplies);
    $total_trans = count($transactions);

    $serviceable_count = 0;
    $repair_count = 0;
    foreach ($ict_items as $i) {
        if (strpos(strtolower($i['status']), 'repair') !== false)
            $repair_count++;
        else
            $serviceable_count++;
    }

    $low_stock_count = 0;
    foreach ($supplies as $s) {
        if ($s['qty'] <= $s['min_stock'])
            $low_stock_count++;
    }
} catch (PDOException $e) {
}

require_once 'header.php';
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* CLEAN PRINT VIEW STYLING */
    @media print {
        @page {
            size: portrait;
            margin: 10mm;
        }

        body * {
            visibility: hidden;
        }

        .print-section,
        .print-section * {
            visibility: visible;
        }

        .print-section {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            background: white;
            padding: 0;
        }

        .no-print {
            display: none !important;
        }

        .print-header {
            display: block !important;
        }

        .print-footer {
            display: block !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #cbd5e1 !important;
            margin-bottom: 0 !important;
        }

        .data-table th {
            background: #f1f5f9 !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
        }
    }

    /* SIDE-BY-SIDE BUTTONS STYLING */
    .action-container {
        display: flex;
        gap: 6px;
        justify-content: center;
        align-items: center;
    }

    .exec-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }

    .exec-card {
        background: white;
        padding: 22px;
        border-radius: 14px;
        border: 1px solid var(--border-soft);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    .exec-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background: var(--accent-main);
    }

    .exec-card h3 {
        margin: 0;
        font-size: 28px;
        font-weight: 800;
        color: var(--corp-navy);
    }

    .exec-card p {
        margin: 0;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .exec-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: var(--light-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent-main);
        font-size: 20px;
    }

    .analytics-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 25px;
    }

    @media(max-width: 900px) {
        .analytics-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="print-section">
    <!-- PRINT HEADER -->
    <div style="display: none; text-align: center; margin-bottom: 25px;" class="print-header">
        <h2 style="margin: 0; color: #0a192f; font-size: 20px; font-weight: 800;">SMILE I.T. LAB - DIGITAL
            TRANSFORMATION CENTER</h2>
        <p style="margin: 4px 0; font-size: 12px; color: #64748b;">Taytay Training Institute Office • Official Inventory
            Summary Report</p>
        <hr style="border: 0; border-top: 2px solid #0ea5e9; margin: 12px 0;">
    </div>

    <div class="no-print" style="margin-bottom: 25px;">
        <h1 class="header-title">Inventory & Asset Management</h1>
        <p class="header-sub" style="margin-bottom: 0;">Comprehensive system tracking and training office analytics
            overview.</p>
    </div>

    <!-- TABS NAVIGATION -->
    <div class="no-print"
        style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid var(--border-soft); padding-bottom: 10px; overflow-x: auto;">
        <button class="tab-btn active-tab" onclick="openTab(event, 'ictTab')">ICT Equipment</button>
        <button class="tab-btn" onclick="openTab(event, 'suppliesTab')">General Supplies</button>
        <button class="tab-btn" onclick="openTab(event, 'transactionsTab')">Transactions</button>
        <button class="tab-btn" onclick="openTab(event, 'reportsTab')">Reports & Analytics</button>
    </div>

    <!-- TAB 1: ICT EQUIPMENT -->
    <div id="ictTab" class="tab-content" style="display: block;">
        <div class="no-print"
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <select class="form-control" style="width: auto; padding: 8px;"
                onchange="filterTable('ictTable', this.value)">
                <option value="All">All Locations (Overall)</option>
                <option value="Central Office">Central Office</option>
                <option value="Satellite - San Isidro">Satellite - San Isidro</option>
                <option value="Satellite - Dolores">Satellite - Dolores</option>
                <option value="Satellite - ROES">Satellite - ROES</option>
            </select>
            <button class="btn-submit" onclick="document.getElementById('addIctModal').style.display='flex'">+ Add ICT
                Equipment</button>
        </div>
        <div class="card" style="padding: 0; overflow-x: auto;">
            <table class="data-table" id="ictTable">
                <thead>
                    <tr>
                        <th style="width: 15%;">Asset ID</th>
                        <th style="width: 30%;">Equipment Name</th>
                        <th style="width: 25%;">Location</th>
                        <th style="width: 15%;">Status</th>
                        <th class="no-print" style="width: 15%; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ict_items as $item): ?>
                        <tr data-location="<?php echo htmlspecialchars($item['location']); ?>">
                            <td><strong
                                    style="color: var(--accent-main);"><?php echo htmlspecialchars($item['asset_no']); ?></strong>
                            </td>
                            <td><strong
                                    style="color: var(--corp-navy);"><?php echo htmlspecialchars($item['name']); ?></strong><br><small><?php echo htmlspecialchars($item['category']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($item['location']); ?></td>
                            <td><span class="status-badge"><?php echo htmlspecialchars($item['status']); ?></span></td>
                            <td class="no-print" style="text-align: center;">
                                <div class="action-container">
                                    <button type="button" class="btn-update-action"
                                        onclick='editIct(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8"); ?>)'>Update</button>
                                    <button type="button" class="btn-danger-action"
                                        onclick="openDeleteModal(<?php echo $item['id']; ?>, 'ict')">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: GENERAL SUPPLIES -->
    <div id="suppliesTab" class="tab-content" style="display: none;">
        <div class="no-print"
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <select class="form-control" style="width: auto; padding: 8px;"
                onchange="filterTable('suppliesTable', this.value)">
                <option value="All">All Locations (Overall)</option>
                <option value="Central Office">Central Office</option>
                <option value="Satellite - San Isidro">Satellite - San Isidro</option>
            </select>
            <button class="btn-submit" onclick="document.getElementById('addSupplyModal').style.display='flex'">+ Add
                General Supply</button>
        </div>
        <div class="card" style="padding: 0; overflow-x: auto;">
            <table class="data-table" id="suppliesTable">
                <thead>
                    <tr>
                        <th style="width: 30%;">Item Name</th>
                        <th style="width: 25%;">Location</th>
                        <th style="width: 15%; text-align: center;">Stock Status</th>
                        <th style="width: 15%; text-align: center;">Qty</th>
                        <th class="no-print" style="width: 15%; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($supplies as $item): ?>
                        <tr data-location="<?php echo htmlspecialchars($item['location']); ?>">
                            <td><strong
                                    style="color: var(--corp-navy);"><?php echo htmlspecialchars($item['name']); ?></strong><br><small><?php echo htmlspecialchars($item['category']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($item['location']); ?></td>
                            <td style="text-align: center;">
                                <?php
                                if ($item['qty'] <= 0)
                                    echo '<span style="color: #ef4444; font-weight:bold;">Out of Stock</span>';
                                else if ($item['qty'] <= $item['min_stock'])
                                    echo '<span style="color: #f59e0b; font-weight:bold;">Low Stock</span>';
                                else
                                    echo '<span style="color: #10b981; font-weight:bold;">In Stock</span>';
                                ?>
                            </td>
                            <td style="text-align: center;"><strong><?php echo $item['qty']; ?></strong></td>
                            <td class="no-print" style="text-align: center;">
                                <div class="action-container">
                                    <button type="button" class="btn-update-action"
                                        onclick='editSupply(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8"); ?>)'>Update</button>
                                    <button type="button" class="btn-danger-action"
                                        onclick="openDeleteModal(<?php echo $item['id']; ?>, 'supply')">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: TRANSACTIONS -->
    <div id="transactionsTab" class="tab-content" style="display: none;">
        <div class="no-print"
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <input type="text" id="transSearch" class="form-control" placeholder="Search transactions..."
                style="width: 250px; padding: 8px;" onkeyup="searchTransactions()">
            <button class="btn-submit" onclick="document.getElementById('transModal').style.display='flex'">+ Record
                Transaction</button>
        </div>
        <div class="card" style="padding: 0; overflow-x: auto;">
            <table class="data-table" id="transTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Handled By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $trans): ?>
                        <tr>
                            <td><?php echo $trans['trans_date']; ?></td>
                            <td><strong><?php echo $trans['trans_type']; ?></strong></td>
                            <td><?php echo htmlspecialchars($trans['item_name']); ?></td>
                            <td><strong><?php echo $trans['qty']; ?></strong></td>
                            <td><?php echo htmlspecialchars($trans['handled_by']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 4: REPORTS & ANALYTICS -->
    <div id="reportsTab" class="tab-content" style="display: none;">

        <!-- STAT CARDS -->
        <div class="exec-grid">
            <div class="exec-card">
                <div>
                    <p>Total ICT Assets</p>
                    <h3><?php echo $total_ict; ?></h3>
                </div>
                <div class="exec-icon"><i class="fas fa-desktop"></i></div>
            </div>
            <div class="exec-card" style="border-left-color: #10b981;">
                <div>
                    <p>General Supplies</p>
                    <h3><?php echo $total_sup; ?></h3>
                </div>
                <div class="exec-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i
                        class="fas fa-boxes"></i></div>
            </div>
            <div class="exec-card" style="border-left-color: #f59e0b;">
                <div>
                    <p>Low Stock Alerts</p>
                    <h3><?php echo $low_stock_count; ?></h3>
                </div>
                <div class="exec-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i
                        class="fas fa-exclamation-triangle"></i></div>
            </div>
            <div class="exec-card" style="border-left-color: #6366f1;">
                <div>
                    <p>Total Transactions</p>
                    <h3><?php echo $total_trans; ?></h3>
                </div>
                <div class="exec-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i
                        class="fas fa-exchange-alt"></i></div>
            </div>
        </div>

        <!-- CHARTS SECTION -->
        <div class="analytics-row">
            <div class="card" style="padding: 20px;">
                <h4 style="color: var(--corp-navy); margin-top: 0; margin-bottom: 15px;">ICT Asset Status Breakdown</h4>
                <div style="height: 240px; display: flex; justify-content: center;"><canvas id="ictChart"></canvas>
                </div>
            </div>
            <div class="card"
                style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h4 style="color: var(--corp-navy); margin-top: 0; margin-bottom: 10px;">Office Resource Summary
                    </h4>
                    <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                        Total inventory tracking is active across central and satellite locations. Currently managing
                        <strong><?php echo $serviceable_count; ?></strong> serviceable units and monitoring stock levels
                        closely.
                    </p>
                </div>
                <div
                    style="background: var(--bg-white); padding: 12px; border-radius: 8px; border: 1px solid var(--border-soft); font-size: 12px; color: var(--corp-navy);">
                    <strong>Status:</strong> All Systems Operational<br>
                    <span style="color: var(--text-muted);">Report Date: <?php echo date('F d, Y'); ?></span>
                </div>
            </div>
        </div>

        <!-- PRINT / EXPORT CONTROL -->
        <div class="card no-print"
            style="display: flex; justify-content: space-between; align-items: center; padding: 20px;">
            <div>
                <h4 style="margin: 0 0 5px 0; color: var(--corp-navy);">Print Official Inventory Report</h4>
                <p style="margin: 0; font-size: 13px; color: var(--text-muted);">Generates a clean summary sheet ready
                    for filing.</p>
            </div>
            <button type="button" class="btn-submit" onclick="window.print()" style="padding: 12px 30px;"><i
                    class="fas fa-print"></i> Print Official Report</button>
        </div>

        <!-- SIGNATURE BLOCK (Visible only when printing) -->
        <div style="display: none; margin-top: 40px;" class="print-footer">
            <div style="display: flex; justify-content: space-between;">
                <div style="text-align: center; width: 220px; border-top: 1px solid #000; padding-top: 5px;">
                    <strong style="font-size: 12px;">Prepared By</strong><br><span
                        style="font-size: 10px; color: #555;">Inventory Custodian</span>
                </div>
                <div style="text-align: center; width: 220px; border-top: 1px solid #000; padding-top: 5px;">
                    <strong style="font-size: 12px;">Approved By</strong><br><span
                        style="font-size: 10px; color: #555;">Training Office Head</span>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ==========================================
     MODALS SECTION
=========================================== -->
<div id="addIctModal" class="modal-overlay no-print">
    <div class="modal-box">
        <h3 style="color: var(--corp-navy); margin-top:0;">Register ICT Equipment</h3>
        <form method="POST">
            <div class="form-group"><label>Asset ID</label><input type="text" name="asset_no" required></div>
            <div class="form-group"><label>Equipment Name</label><input type="text" name="name" required></div>
            <div class="form-group"><label>Category</label><input type="text" name="category" required></div>
            <div class="form-group"><label>Assigned Location</label><select name="location" required>
                    <option>Central Office</option>
                    <option>Satellite - San Isidro</option>
                    <option>Satellite - Dolores</option>
                    <option>Satellite - ROES</option>
                </select></div>
            <div class="form-group"><label>Status</label><select name="status" required>
                    <option>Serviceable</option>
                    <option>For Repair</option>
                    <option>Unserviceable</option>
                </select></div>
            <div class="modal-actions"><button type="button" class="btn-cancel"
                    onclick="this.closest('.modal-overlay').style.display='none'">Cancel</button><button type="submit"
                    name="save_ict" class="btn-submit">Save</button></div>
        </form>
    </div>
</div>

<div id="updateIctModal" class="modal-overlay no-print">
    <div class="modal-box">
        <h3 style="color: var(--corp-navy); margin-top:0;">Update ICT Equipment</h3>
        <form method="POST">
            <input type="hidden" name="id" id="upd_ict_id">
            <div class="form-group"><label>Asset ID</label><input type="text" name="asset_no" id="upd_ict_asset"
                    required></div>
            <div class="form-group"><label>Equipment Name</label><input type="text" name="name" id="upd_ict_name"
                    required></div>
            <div class="form-group"><label>Category</label><input type="text" name="category" id="upd_ict_cat" required>
            </div>
            <div class="form-group"><label>Assigned Location</label><select name="location" id="upd_ict_loc" required>
                    <option>Central Office</option>
                    <option>Satellite - San Isidro</option>
                    <option>Satellite - Dolores</option>
                    <option>Satellite - ROES</option>
                </select></div>
            <div class="form-group"><label>Status</label><select name="status" id="upd_ict_stat" required>
                    <option>Serviceable</option>
                    <option>For Repair</option>
                    <option>Unserviceable</option>
                </select></div>
            <div class="modal-actions"><button type="button" class="btn-cancel"
                    onclick="this.closest('.modal-overlay').style.display='none'">Cancel</button><button type="submit"
                    name="update_ict" class="btn-submit">Update</button></div>
        </form>
    </div>
</div>

<div id="addSupplyModal" class="modal-overlay no-print">
    <div class="modal-box">
        <h3 style="color: var(--corp-navy); margin-top:0;">Add General Supply</h3>
        <form method="POST">
            <div class="form-group"><label>Item Name</label><input type="text" name="name" required></div>
            <div class="form-group"><label>Category</label><input type="text" name="category" required></div>
            <div class="form-group"><label>Location</label><select name="location" required>
                    <option>Central Office</option>
                    <option>Satellite - San Isidro</option>
                    <option>Satellite - Dolores</option>
                    <option>Satellite - ROES</option>
                </select></div>
            <div style="display: flex; gap: 10px;">
                <div class="form-group" style="flex:1;"><label>Initial Qty</label><input type="number" name="qty"
                        value="0" required></div>
                <div class="form-group" style="flex:1;"><label>Minimum Stock</label><input type="number"
                        name="min_stock" value="10" required></div>
            </div>
            <div class="modal-actions"><button type="button" class="btn-cancel"
                    onclick="this.closest('.modal-overlay').style.display='none'">Cancel</button><button type="submit"
                    name="save_supply" class="btn-submit">Save</button></div>
        </form>
    </div>
</div>

<div id="updateSupplyModal" class="modal-overlay no-print">
    <div class="modal-box">
        <h3 style="color: var(--corp-navy); margin-top:0;">Update Supply Item</h3>
        <form method="POST">
            <input type="hidden" name="id" id="upd_sup_id">
            <div class="form-group"><label>Item Name</label><input type="text" name="name" id="upd_sup_name" required>
            </div>
            <div class="form-group"><label>Category</label><input type="text" name="category" id="upd_sup_cat" required>
            </div>
            <div class="form-group"><label>Location</label><select name="location" id="upd_sup_loc" required>
                    <option>Central Office</option>
                    <option>Satellite - San Isidro</option>
                    <option>Satellite - Dolores</option>
                    <option>Satellite - ROES</option>
                </select></div>
            <div style="display: flex; gap: 10px;">
                <div class="form-group" style="flex:1;"><label>Current Qty</label><input type="number" name="qty"
                        id="upd_sup_qty" required></div>
                <div class="form-group" style="flex:1;"><label>Minimum Stock</label><input type="number"
                        name="min_stock" id="upd_sup_min" required></div>
            </div>
            <div class="modal-actions"><button type="button" class="btn-cancel"
                    onclick="this.closest('.modal-overlay').style.display='none'">Cancel</button><button type="submit"
                    name="update_supply" class="btn-submit">Update</button></div>
        </form>
    </div>
</div>

<div id="transModal" class="modal-overlay no-print">
    <div class="modal-box" style="width: 500px;">
        <h3 style="color: var(--corp-navy); margin-top:0;">Record Transaction</h3>
        <form method="POST">
            <div class="form-group"><label>Transaction Type</label><select name="trans_type" required>
                    <option value="Stock In">Stock In (Add)</option>
                    <option value="Stock Out">Stock Out (Deduct)</option>
                </select></div>
            <div class="form-group"><label>Select Item</label><select name="item_data" required>
                    <optgroup label="General Supplies">
                        <?php foreach ($supplies as $s)
                            echo "<option value='{$s['id']}|{$s['name']}|Supply'>{$s['name']} (Stock: {$s['qty']})</option>"; ?>
                    </optgroup>
                </select></div>
            <div style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;"><label>Quantity</label><input type="number" name="qty"
                        required></div>
                <div class="form-group" style="flex: 1;"><label>Date</label><input type="date" name="trans_date"
                        value="<?php echo date('Y-m-d'); ?>" required></div>
            </div>
            <div class="form-group"><label>Handled By / Remarks</label><input type="text" name="handled_by" required>
            </div>
            <div class="modal-actions"><button type="button" class="btn-cancel"
                    onclick="document.getElementById('transModal').style.display='none'">Cancel</button><button
                    type="submit" name="process_transaction" class="btn-submit">Save Transaction</button></div>
        </form>
    </div>
</div>

<!-- PROFESSIONAL DELETE CONFIRMATION MODAL -->
<div id="deleteModal" class="modal-overlay no-print"
    style="display: none; align-items: center; justify-content: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="modal-box"
        style="width: 420px; text-align: center; padding: 35px 30px; border-radius: 16px; background: #ffffff; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="color: #ef4444; margin: 0 0 12px 0; font-size: 20px; font-weight: 700;">Delete Record</h3>
        <p style="color: #000000; font-size: 14px; margin-bottom: 30px; line-height: 1.5; font-weight: 500;">Are you
            sure you want to permanently delete this inventory record?</p>

        <form id="deleteForm" method="POST">
            <input type="hidden" name="id" id="delete_record_id">
            <input type="hidden" name="" id="delete_type_input" value="">
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn-cancel"
                    onclick="document.getElementById('deleteModal').style.display='none'"
                    style="flex: 1; margin-top:0; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #000; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" id="deleteSubmitBtn"
                    style="flex: 1; background: #fff; color: #ef4444; border: 1px solid #fca5a5; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;"
                    onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">Yes,
                    Delete</button>
            </div>
        </form>
    </div>
</div>

<style>
    .tab-btn {
        background: transparent;
        color: var(--text-muted);
        border: none;
        font-size: 14px;
        font-weight: 600;
        padding: 10px 20px;
        cursor: pointer;
        transition: 0.3s;
        border-bottom: 3px solid transparent;
        border-radius: 4px 4px 0 0;
        white-space: nowrap;
    }

    .tab-btn:hover {
        color: var(--corp-navy);
        background: rgba(14, 165, 233, 0.05);
    }

    .active-tab {
        color: var(--accent-main);
        border-bottom: 3px solid var(--accent-main);
    }

    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        display: inline-block;
        border: 1px solid rgba(0, 0, 0, 0.05);
        background: #f1f5f9;
        color: var(--corp-navy);
    }

    .action-container {
        display: flex;
        gap: 8px;
        justify-content: center;
        align-items: center;
    }

    .btn-update-action {
        padding: 6px 12px;
        background: var(--accent-gradient);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        transition: all 0.2s ease;
    }

    .btn-update-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(14, 165, 233, 0.3);
    }

    .btn-danger-action {
        background: #fff;
        color: #ef4444;
        border: 1px solid #fca5a5;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-danger-action:hover {
        background: #fef2f2;
        transform: translateY(-2px);
    }
</style>

<script>
    function openTab(evt, tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active-tab'));
        document.getElementById(tabName).style.display = 'block';
        evt.currentTarget.classList.add('active-tab');
    }

    function filterTable(tableId, location) {
        document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
            row.style.display = (location === "All" || row.getAttribute('data-location') === location) ? '' : 'none';
        });
    }

    function searchTransactions() {
        let input = document.getElementById('transSearch').value.toLowerCase();
        document.querySelectorAll('#transTable tbody tr').forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(input) ? '' : 'none';
        });
    }

    function editIct(item) {
        document.getElementById('upd_ict_id').value = item.id;
        document.getElementById('upd_ict_asset').value = item.asset_no;
        document.getElementById('upd_ict_name').value = item.name;
        document.getElementById('upd_ict_cat').value = item.category;
        document.getElementById('upd_ict_loc').value = item.location;
        document.getElementById('upd_ict_stat').value = item.status;
        document.getElementById('updateIctModal').style.display = 'flex';
    }

    function editSupply(item) {
        document.getElementById('upd_sup_id').value = item.id;
        document.getElementById('upd_sup_name').value = item.name;
        document.getElementById('upd_sup_cat').value = item.category;
        document.getElementById('upd_sup_loc').value = item.location;
        document.getElementById('upd_sup_qty').value = item.qty;
        document.getElementById('upd_sup_min').value = item.min_stock;
        document.getElementById('updateSupplyModal').style.display = 'flex';
    }

    function openDeleteModal(id, type) {
        document.getElementById('delete_record_id').value = id;
        let submitBtn = document.getElementById('deleteSubmitBtn');
        let deleteForm = document.getElementById('deleteForm');

        // Set appropriate name attribute for backend deletion handling
        if (type === 'ict') {
            submitBtn.setAttribute('name', 'delete_ict');
        } else {
            submitBtn.setAttribute('name', 'delete_supply');
        }

        document.getElementById('deleteModal').style.display = 'flex';
    }

    // Chart.js Setup
    window.onload = function () {
        new Chart(document.getElementById('ictChart'), {
            type: 'doughnut',
            data: {
                labels: ['Serviceable', 'For Repair / Maintenance'],
                datasets: [{
                    data: [<?php echo $serviceable_count; ?>, <?php echo $repair_count; ?>,],
                    backgroundColor: ['#10b981', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    };
</script>

<?php require_once 'footer.php'; ?>