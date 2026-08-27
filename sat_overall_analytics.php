<?php
require_once 'header.php';
require_once 'db.php';

// 1. KUNIN ANG MGA FILTER VALUES (FROM, TO, SATELLITE ID)
$filter_from = isset($_GET['from']) ? $_GET['from'] : date('Y-01-01');
$filter_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-t');
$filter_office = isset($_GET['office_id']) ? $_GET['office_id'] : 'all';

// Kunin ang listahan ng mga satellite offices para sa dropdown filter
$satellites_list = $pdo->query("SELECT id, office_name FROM satellite_offices ORDER BY office_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Base WHERE clause para sa query gamit ang petsa
$where_sql = "DATE(date_registered) BETWEEN ? AND ?";
$params = [$filter_from, $filter_to];

if ($filter_office !== 'all') {
    $where_sql .= " AND satellite_id = ?";
    $params[] = $filter_office;
}

// 2. TOTAL USERS / LOGS (BASE SA FILTER)
$total_users_stmt = $pdo->prepare("SELECT COUNT(*) FROM satellite_students WHERE " . $where_sql);
$total_users_stmt->execute($params);
$total_users = $total_users_stmt->fetchColumn();

// 3. UNIQUE VS REPEAT CLIENTELE (BASE SA FILTER)
$unique_stmt = $pdo->prepare("SELECT COUNT(DISTINCT student_name) FROM satellite_students WHERE " . $where_sql);
$unique_stmt->execute($params);
$unique_clients = $unique_stmt->fetchColumn();
$repeat_clients = $total_users - $unique_clients;
if ($repeat_clients < 0) $repeat_clients = 0;

// 4. SEX DISAGGREGATED DATA (BASE SA FILTER)
$sex_stmt = $pdo->prepare("SELECT sex, COUNT(*) as count FROM satellite_students WHERE " . $where_sql . " GROUP BY sex");
$sex_stmt->execute($params);
$sex_data = $sex_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$male_count = $sex_data['Male'] ?? 0;
$female_count = $sex_data['Female'] ?? 0;
$other_sex_count = $sex_data['Prefer Not to Say'] ?? 0;

// 5. AGE BRACKET COMPUTATION (BASE SA FILTER)
$age_stmt = $pdo->prepare("SELECT age FROM satellite_students WHERE " . $where_sql);
$age_stmt->execute($params);
$age_records = $age_stmt->fetchAll(PDO::FETCH_COLUMN);

$age_brackets = ['0-16' => 0, '17-30' => 0, '31-45' => 0, 'Above 45' => 0];
foreach ($age_records as $age) {
    $a = intval($age);
    if ($a <= 16) $age_brackets['0-16']++;
    elseif ($a <= 30) $age_brackets['17-30']++;
    elseif ($a <= 45) $age_brackets['31-45']++;
    else $age_brackets['Above 45']++;
}

// 6. SERVICES AVAILED BREAKDOWN (BASE SA FILTER)
$serv_stmt = $pdo->prepare("SELECT service_availed FROM satellite_students WHERE " . $where_sql);
$serv_stmt->execute($params);
$serv_records = $serv_stmt->fetchAll(PDO::FETCH_COLUMN);

$services_counts = [];
foreach ($serv_records as $serv_str) {
    $srvs = explode(',', $serv_str);
    foreach ($srvs as $srv) {
        $srv = trim($srv);
        if (!empty($srv)) {
            if (!isset($services_counts[$srv])) $services_counts[$srv] = 0;
            $services_counts[$srv]++;
        }
    }
}

// 7. SECTORS BREAKDOWN (BASE SA FILTER)
$sect_stmt = $pdo->prepare("SELECT sector, COUNT(*) as count FROM satellite_students WHERE " . $where_sql . " GROUP BY sector");
$sect_stmt->execute($params);
$sectors_data = $sect_stmt->fetchAll(PDO::FETCH_ASSOC);

// 8. BARANGAY / LOCATION BREAKDOWN (BASE SA FILTER)
$brgy_stmt = $pdo->prepare("SELECT barangay, COUNT(*) as count FROM satellite_students WHERE " . $where_sql . " GROUP BY barangay");
$brgy_stmt->execute($params);
$barangay_data = $brgy_stmt->fetchAll(PDO::FETCH_ASSOC);

// 9. PER SATELLITE OFFICE BREAKDOWN
$per_office_stmt = $pdo->prepare("
    SELECT s.office_name, COUNT(st.id) as total_logs 
    FROM satellite_offices s 
    LEFT JOIN satellite_students st ON s.id = st.satellite_id AND DATE(st.date_registered) BETWEEN ? AND ?
    GROUP BY s.id, s.office_name
");
$per_office_stmt->execute([$filter_from, $filter_to]);
$per_office_data = $per_office_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* PAGE HEADER */
    .page-header {
        font-size: 1.8rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
    }

    /* MODERN CARD */
    .modern-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
        padding: 28px;
        margin-bottom: 24px;
        width: 100%;
        box-sizing: border-box;
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

    /* EXACT REFERENCE FILTER FORM DESIGN (SIDE-BY-SIDE) */
    .filter-form-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        display: flex;
        gap: 15px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .filter-box-field {
        flex: 1;
        min-width: 180px;
    }

    .filter-box-field label {
        display: block;
        font-size: 0.8rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control {
        width: 100%;
        padding: 11px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        font-size: 0.95rem;
        box-sizing: border-box;
        background: #ffffff;
        font-family: inherit;
        color: #0f172a;
        outline: none;
        transition: all 0.2s;
    }

    .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .btn-filter-custom {
        background: #3b82f6;
        color: white;
        padding: 11px 24px;
        border-radius: 10px;
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        border: none;
        text-align: center;
        transition: all 0.2s;
        height: 45px;
        white-space: nowrap;
    }

    .btn-filter-custom:hover {
        background: #2563eb;
    }

    .btn-back {
        background: #e2e8f0;
        color: #475569;
        padding: 10px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: bold;
        transition: 0.2s;
    }
    .btn-back:hover { background: #cbd5e1; }

    /* DASHBOARD SUMMARY BOXES */
    .dash-summary-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 24px;
    }

    .dash-box {
        background: #1e293b;
        color: white;
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .dash-box h4 {
        margin: 0 0 8px 0;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #94a3b8;
    }

    .dash-box .number {
        font-size: 2rem;
        font-weight: 800;
        color: #38bdf8;
        margin: 0;
    }

    /* CHARTS & TABLES GRID */
    .chart-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 24px;
        margin-top: 24px;
    }

    .chart-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .chart-card h3 {
        font-size: 0.95rem;
        color: #0f172a;
        margin-top: 0;
        margin-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: center;
        width: 100%;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 8px;
    }

    .chart-container {
        position: relative;
        width: 100%;
        max-width: 320px;
        height: 260px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th {
        background: #f8fafc;
        padding: 12px 14px;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }

    .data-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        font-size: 0.9rem;
    }
</style>

<div class="page-header">
    <span>Overall Data Visualization & Analytics</span>
    <a href="satellite.php" class="btn-back">← Back to Dashboard</a>
</div>

<div class="modern-card" style="border-top: 4px solid #3b82f6;">
    <div class="card-title">
        <span>Analytics Filters & Records Query</span>
    </div>

    <!-- SIDE-BY-SIDE FILTER FORM -->
    <form method="GET" class="filter-form-box">
        <div class="filter-box-field">
            <label>From Date:</label>
            <input type="date" name="from" value="<?php echo htmlspecialchars($filter_from); ?>" class="form-control">
        </div>
        <div class="filter-box-field">
            <label>To Date:</label>
            <input type="date" name="to" value="<?php echo htmlspecialchars($filter_to); ?>" class="form-control">
        </div>
        <div class="filter-box-field">
            <label>Office / Satellite:</label>
            <select name="office_id" class="form-control">
                <option value="all">All Offices (Overall)</option>
                <?php foreach ($satellites_list as $sat): ?>
                    <option value="<?php echo $sat['id']; ?>" <?php echo ($filter_office == $sat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sat['office_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn-filter-custom">Filter Report</button>
        </div>
    </form>

    <!-- SUMMARY NUMBERS BOXES -->
    <div class="dash-summary-row">
        <div class="dash-box">
            <h4>Total Users</h4>
            <p class="number"><?php echo $total_users; ?></p>
        </div>
        <div class="dash-box">
            <h4>New / Unique Individuals</h4>
            <p class="number"><?php echo $unique_clients; ?></p>
        </div>
        <div class="dash-box">
            <h4>Repeat Clientele</h4>
            <p class="number"><?php echo $repeat_clients; ?></p>
        </div>
    </div>

    <!-- CHARTS GRID -->
    <div class="chart-grid">
        
        <!-- 1. Per Satellite Office (Bar Chart) -->
        <div class="chart-card" style="grid-column: 1 / -1;">
            <h3>Logs per Satellite Office</h3>
            <div style="width: 100%; height: 200px; position: relative;">
                <canvas id="officeLogsChart"></canvas>
            </div>
        </div>

        <!-- 2. Client Demographics (Doughnut Chart) -->
        <div class="chart-card">
            <h3>Client Demographics (Unique vs Repeat)</h3>
            <div class="chart-container">
                <canvas id="demographicsChart"></canvas>
            </div>
        </div>

        <!-- 3. Sex-Disaggregated Data (Pie Chart) -->
        <div class="chart-card">
            <h3>Sex-Disaggregated Data of Clienteles</h3>
            <div class="chart-container">
                <canvas id="sexChart"></canvas>
            </div>
        </div>

        <!-- 4. Age Bracket (Bar Chart) -->
        <div class="chart-card">
            <h3>Age Bracket Breakdown</h3>
            <div class="chart-container">
                <canvas id="ageChart"></canvas>
            </div>
        </div>

        <!-- 5. Services Availed Table -->
        <div class="chart-card" style="align-items: stretch;">
            <h3>Services Availed</h3>
            <div class="table-wrapper" style="max-height: 260px; overflow-y: auto;">
                <table class="data-table">
                    <thead><tr><th>Service Name</th><th style="text-align: right;">Count</th></tr></thead>
                    <tbody>
                        <?php if (count($services_counts) > 0): ?>
                            <?php foreach ($services_counts as $s_name => $s_cnt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s_name); ?></td>
                                    <td style="text-align: right; font-weight: bold; color: #2563eb;"><?php echo $s_cnt; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align: center; color: #94a3b8; padding: 20px;">No data available</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 6. Sectors Breakdown Table -->
        <div class="chart-card" style="align-items: stretch;">
            <h3>Sectors Breakdown</h3>
            <div class="table-wrapper" style="max-height: 260px; overflow-y: auto;">
                <table class="data-table">
                    <thead><tr><th>Sector</th><th style="text-align: right;">Count</th></tr></thead>
                    <tbody>
                        <?php if (count($sectors_data) > 0): ?>
                            <?php foreach ($sectors_data as $sec): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sec['sector']); ?></td>
                                    <td style="text-align: right; font-weight: bold; color: #8b5cf6;"><?php echo $sec['count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align: center; color: #94a3b8; padding: 20px;">No data available</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 7. Location / Barangay Table -->
        <div class="chart-card" style="grid-column: 1 / -1; align-items: stretch;">
            <h3>Location / Barangay Distribution</h3>
            <div class="table-wrapper" style="max-height: 300px; overflow-y: auto;">
                <table class="data-table">
                    <thead><tr><th>Barangay / Municipality</th><th style="text-align: right;">Total Clients</th></tr></thead>
                    <tbody>
                        <?php if (count($barangay_data) > 0): ?>
                            <?php foreach ($barangay_data as $brg): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($brg['barangay']); ?></td>
                                    <td style="text-align: right; font-weight: bold; color: #10b981;"><?php echo $brg['count']; ?> clients</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align: center; color: #94a3b8; padding: 20px;">No location data available</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    // 1. Client Demographics (Unique vs Repeat)
    const ctxDemo = document.getElementById('demographicsChart').getContext('2d');
    new Chart(ctxDemo, {
        type: 'doughnut',
        data: {
            labels: ['New / Unique Individuals', 'Repeat Clientele'],
            datasets: [{
                data: [<?php echo $unique_clients; ?>, <?php echo $repeat_clients; ?>],
                backgroundColor: ['#3b82f6', '#ef4444'],
                borderWidth: 1
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // 2. Sex-Disaggregated Data
    const ctxSex = document.getElementById('sexChart').getContext('2d');
    new Chart(ctxSex, {
        type: 'pie',
        data: {
            labels: ['Male', 'Female', 'Prefer Not to Say'],
            datasets: [{
                data: [<?php echo $male_count; ?>, <?php echo $female_count; ?>, <?php echo $other_sex_count; ?>],
                backgroundColor: ['#3b82f6', '#ef4444', '#eab308'],
                borderWidth: 1
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // 3. Logs per Satellite Office (Bar Chart)
    const offices = <?php echo json_encode(array_column($per_office_data, 'office_name')); ?>;
    const officeLogs = <?php echo json_encode(array_column($per_office_data, 'total_logs')); ?>;
    
    const ctxOffice = document.getElementById('officeLogsChart').getContext('2d');
    new Chart(ctxOffice, {
        type: 'bar',
        data: {
            labels: offices,
            datasets: [{
                label: 'Total Logs',
                data: officeLogs,
                backgroundColor: '#3b82f6',
                borderRadius: 6
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // 4. Age Bracket Bar Chart
    const ageLabels = Object.keys(<?php echo json_encode($age_brackets); ?>);
    const ageValues = Object.values(<?php echo json_encode($age_brackets); ?>);

    const ctxAge = document.getElementById('ageChart').getContext('2d');
    new Chart(ctxAge, {
        type: 'bar',
        data: {
            labels: ageLabels,
            datasets: [{
                data: ageValues,
                backgroundColor: '#10b981',
                borderRadius: 6
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>

<?php require_once 'footer.php'; ?>