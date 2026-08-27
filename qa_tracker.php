<?php
session_start();
require 'db.php';
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

// ==========================================
// 1. AUTO-CREATE & UPDATE QA TABLES
// ==========================================
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS qa_programs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        program_title VARCHAR(255) NOT NULL,
        submission_date DATE,
        impl_date DATE,
        implementing_office VARCHAR(255),
        evaluator VARCHAR(255),
        project_director VARCHAR(255),
        flagship_project VARCHAR(10) DEFAULT 'Yes',
        overall_status VARCHAR(50) DEFAULT 'Pending',
        evaluation_data TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $columns = $pdo->query("SHOW COLUMNS FROM qa_programs LIKE 'evaluation_data'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE qa_programs ADD COLUMN evaluation_data TEXT");
    }
} catch (PDOException $e) {
}

// Handle New Program Creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_qa_program'])) {
    $stmt = $pdo->prepare("INSERT INTO qa_programs (program_title, submission_date, impl_date, implementing_office, evaluator, project_director, flagship_project, overall_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['program_title'],
        $_POST['submission_date'],
        $_POST['impl_date'],
        $_POST['implementing_office'],
        $_POST['evaluator'],
        $_POST['project_director'],
        $_POST['flagship_project'],
        'Pending'
    ]);
    header("Location: qa_tracker.php");
    exit;
}

// Handle Rubric/Evaluation Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_evaluation'])) {
    $program_id = $_POST['program_id'];

    $stmt = $pdo->prepare("UPDATE qa_programs SET program_title=?, submission_date=?, impl_date=?, implementing_office=?, evaluator=?, project_director=?, flagship_project=?, overall_status=? WHERE id=?");
    $stmt->execute([
        $_POST['program_title'],
        $_POST['submission_date'],
        $_POST['impl_date'],
        $_POST['implementing_office'],
        $_POST['evaluator'],
        $_POST['project_director'],
        $_POST['flagship_project'],
        $_POST['overall_status'],
        $program_id
    ]);

    $eval_payload = json_encode($_POST['mqls'] ?? []);
    $stmt_eval = $pdo->prepare("UPDATE qa_programs SET evaluation_data = ? WHERE id = ?");
    $stmt_eval->execute([$eval_payload, $program_id]);

    header("Location: qa_tracker.php?view=" . $program_id);
    exit;
}

// Handle Delete Program
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM qa_programs WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    header("Location: qa_tracker.php");
    exit;
}

$programs = [];
try {
    $programs = $pdo->query("SELECT * FROM qa_programs ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}

$active_program = null;
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT * FROM qa_programs WHERE id = ?");
    $stmt->execute([$_GET['view']]);
    $active_program = $stmt->fetch(PDO::FETCH_ASSOC);
    $saved_eval = json_decode($active_program['evaluation_data'] ?? '{}', true);
}

require_once 'header.php';
?>

<!-- BALANCED READABLE PRINT & SCREEN STYLING -->
<style>
    @media print {
        @page {
            size: legal landscape;
            margin: 10mm 15mm;
        }

        body * {
            visibility: hidden;
        }

        .print-qa-section,
        .print-qa-section * {
            visibility: visible;
        }

        .print-qa-section {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            background: white;
            padding: 0;
            font-size: 11px;
            /* Mas malinaw at madaling basahin na font size */
        }

        .no-print {
            display: none !important;
        }

        .qa-table {
            font-size: 11px !important;
            margin-bottom: 10px !important;
        }

        .qa-table th,
        .qa-table td {
            padding: 5px 8px !important;
        }

        .form-input-clean,
        .qa-status-select {
            font-size: 11px !important;
            padding: 2px !important;
        }
    }

    .qa-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        margin-bottom: 20px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
    }

    .qa-table th,
    .qa-table td {
        border: 1px solid #cbd5e1;
        padding: 8px 12px;
        vertical-align: middle;
        color: #000;
    }

    .qa-table th {
        background: #f8fafc;
        color: #000;
        font-weight: 700;
        text-align: left;
    }

    .qa-section-header {
        background: #0a192f !important;
        color: white !important;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }

    .form-input-clean {
        width: 100%;
        border: none;
        background: transparent;
        font-family: inherit;
        font-size: 13px;
        padding: 4px;
        outline: none;
        color: #000;
    }

    .form-input-clean:focus {
        background: #f8fafc;
    }

    .qa-status-select {
        padding: 4px 6px;
        font-size: 12px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #000;
        cursor: pointer;
        outline: none;
        border-radius: 4px;
    }

    /* MATCHING TEXT ACTION BUTTONS DESIGN */
    .action-container {
        display: flex;
        gap: 8px;
        justify-content: center;
        align-items: center;
    }

    .btn-action-text {
        padding: 6px 14px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-size: 11px;
        font-weight: 700;
        transition: all 0.2s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn-action-update {
        background: #0ea5e9;
        color: white;
        box-shadow: 0 2px 5px rgba(14, 165, 233, 0.3);
    }

    .btn-action-update:hover {
        background: #0284c7;
        transform: translateY(-2px);
    }

    .btn-action-delete {
        background: #fff;
        color: #ef4444;
        border: 1px solid #fca5a5;
    }

    .btn-action-delete:hover {
        background: #fef2f2;
        transform: translateY(-2px);
    }

    /* TAB NAVIGATION */
    .qa-tab-btn {
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

    .qa-tab-btn:hover {
        color: var(--corp-navy);
        background: rgba(14, 165, 233, 0.05);
    }

    .qa-active-tab {
        color: var(--accent-main);
        border-bottom: 3px solid var(--accent-main);
    }
</style>

<div class="print-qa-section">

    <?php if ($active_program):
        $ev = $saved_eval ?? [];

        function renderGenRow($label, $nameInput, $type, $val, $ev, $statusKey)
        {
            $currentStatus = $ev[$statusKey] ?? 'Pending';
            echo '<tr>';
            echo '<td><strong>' . $label . '</strong></td>';
            if ($type == 'date') {
                echo '<td><input type="date" name="' . $nameInput . '" class="form-input-clean" value="' . $val . '"></td>';
            } else if ($type == 'select') {
                echo '<td><select name="' . $nameInput . '" class="qa-status-select"><option ' . ($val == 'Yes' ? 'selected' : '') . '>Yes</option><option ' . ($val == 'No' ? 'selected' : '') . '>No</option></select></td>';
            } else {
                echo '<td><input type="text" name="' . $nameInput . '" class="form-input-clean" value="' . htmlspecialchars($val) . '"></td>';
            }

            echo '<td align="center"><select name="mqls[' . $statusKey . ']" class="qa-status-select">';
            foreach (['Pending', 'Approved', 'Needs Revision'] as $st) {
                $sel = ($currentStatus == $st) ? 'selected' : '';
                echo "<option $sel>$st</option>";
            }
            echo '</select></td>';

            echo '<td><input type="text" name="mqls[' . $statusKey . '_rem]" class="form-input-clean" value="' . htmlspecialchars($ev[$statusKey . '_rem'] ?? '') . '"></td>';
            echo '<td><input type="text" name="mqls[' . $statusKey . '_link]" class="form-input-clean" value="' . htmlspecialchars($ev[$statusKey . '_link'] ?? '') . '"></td>';
            echo '</tr>';
        }
        ?>
        <!-- ==========================================
             DETAILED MQLS EVALUATION SHEET VIEW
        =========================================== -->
        <div class="no-print"
            style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <a href="qa_tracker.php" class="btn-cancel"
                style="text-decoration: none; padding: 8px 16px; display: inline-block; color: #000;"><i
                    class="fas fa-arrow-left"></i> Back to Program List</a>
            <button type="button" class="btn-submit" onclick="window.print()"><i class="fas fa-print"></i> Print QA
                Sheet</button>
        </div>

        <form method="POST" action="qa_tracker.php?view=<?php echo $active_program['id']; ?>">
            <input type="hidden" name="program_id" value="<?php echo $active_program['id']; ?>">

            <div style="text-align: center; margin-bottom: 15px;">
                <h2 style="margin: 0; color: #000; font-size: 16px; font-weight: 800;">MUNICIPAL QUALITY LEARNING STANDARDS
                    (MQLS) QUALITY ASSURANCE RUBRICS</h2>
            </div>

            <!-- GENERAL INFORMATION TABLE -->
            <table class="qa-table">
                <thead>
                    <tr>
                        <th style="width: 30%;">General Information</th>
                        <th style="width: 25%;">Details</th>
                        <th style="width: 15%; text-align: center;">Status</th>
                        <th style="width: 18%;">Remarks</th>
                        <th style="width: 12%;">Evidence/Reference Links</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    renderGenRow('Program/Training Title', 'program_title', 'text', $active_program['program_title'], $ev, 'st_title');
                    renderGenRow('Date submitted for QA', 'submission_date', 'date', $active_program['submission_date'], $ev, 'st_sub');
                    renderGenRow('Proposed date for implementation', 'impl_date', 'date', $active_program['impl_date'], $ev, 'st_impl');
                    renderGenRow('Implementing Office', 'implementing_office', 'text', $active_program['implementing_office'], $ev, 'st_office');
                    renderGenRow('Evaluator', 'evaluator', 'text', $active_program['evaluator'], $ev, 'st_eval');
                    renderGenRow('Program/Training/Project Director', 'project_director', 'text', $active_program['project_director'], $ev, 'st_dir');
                    renderGenRow('Flagship Project', 'flagship_project', 'select', $active_program['flagship_project'], $ev, 'st_flag');
                    ?>
                </tbody>
            </table>

            <!-- MQLS CHECKLIST TABLE -->
            <table class="qa-table">
                <thead>
                    <tr>
                        <th colspan="5"
                            style="background: #0a192f; color: white; font-size: 13px; text-align: center; text-transform: uppercase;">
                            MQLS Checklist</th>
                    </tr>
                    <tr>
                        <th style="width: 45%;">Indicator / Criteria</th>
                        <th style="width: 12%; text-align: center;">Scores (1-5)</th>
                        <th style="width: 15%; text-align: center;">Status</th>
                        <th style="width: 18%;">Remarks</th>
                        <th style="width: 10%;">Evidence/Reference Links</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    function renderRow($num, $text, $ev)
                    {
                        $sKey = "s" . str_replace(['.', ' '], '_', $num);
                        $stKey = "st" . str_replace(['.', ' '], '_', $num);
                        $rKey = "r" . str_replace(['.', ' '], '_', $num);
                        $lKey = "l" . str_replace(['.', ' '], '_', $num);
                        $stVal = $ev[$stKey] ?? 'Pending';

                        echo '<tr>';
                        echo '<td style="color:#000;"><strong>' . $num . '</strong>. ' . $text . '</td>';
                        echo '<td align="center"><input type="number" min="1" max="5" name="mqls[' . $sKey . ']" class="form-input-clean score-input" style="text-align:center; font-weight:700; color:#000;" value="' . htmlspecialchars($ev[$sKey] ?? '') . '" oninput="calcScores()"></td>';
                        echo '<td align="center"><select name="mqls[' . $stKey . ']" class="qa-status-select"><option ' . ($stVal == 'Pending' ? 'selected' : '') . '>Pending</option><option ' . ($stVal == 'Met' ? 'selected' : '') . '>Met</option><option ' . ($stVal == 'Unmet' ? 'selected' : '') . '>Unmet</option></select></td>';
                        echo '<td><input type="text" name="mqls[' . $rKey . ']" class="form-input-clean" value="' . htmlspecialchars($ev[$rKey] ?? '') . '"></td>';
                        echo '<td><input type="text" name="mqls[' . $lKey . ']" class="form-input-clean" value="' . htmlspecialchars($ev[$lKey] ?? '') . '"></td>';
                        echo '</tr>';
                    }
                    ?>

                    <tr>
                        <td colspan="5" class="qa-section-header">1. Needs-Responsiveness & Goal-Orientedness</td>
                    </tr>
                    <?php
                    renderRow('1.1', 'Training is needed based on Training Needs Assessment or recommendation from requesting party.', $ev);
                    renderRow('1.2', 'POI Explicitly includes SDGs targeted.', $ev);
                    renderRow('1.3', 'Local industries and or local impact are included and defined.', $ev);
                    ?>

                    <tr>
                        <td colspan="5" class="qa-section-header">2. Trainers' Qualifications</td>
                    </tr>
                    <?php
                    renderRow('2.1', "The SME/Trainer has the relevant Bachelor's Degree/Training/Certification to perform or train.", $ev);
                    renderRow('2.2', 'The trainer has relevant training experience to discuss the Subject Matter.', $ev);
                    ?>

                    <tr>
                        <td colspan="5" class="qa-section-header">3. Learning Resources</td>
                    </tr>
                    <?php
                    renderRow('3.1', 'The learning materials are explicitly identified in the POI.', $ev);
                    renderRow('3.2', 'The Learning Resources are available for deployment and are in good condition.', $ev);
                    renderRow('3.3', 'The Learning Resources are appropriate for the modality of the Training Program.', $ev);
                    ?>

                    <tr>
                        <td colspan="5" class="qa-section-header">4. Learning Outcomes (SMART)</td>
                    </tr>
                    <?php
                    renderRow('4.1', 'Are the learning outcomes Specific, Measurable, Attainable, Realistic, & Timebound?', $ev);
                    ?>

                    <tr>
                        <td colspan="5" class="qa-section-header">5. Content Accuracy & Relevance</td>
                    </tr>
                    <?php
                    renderRow('5.1', 'Are the contents accurate and verifiable?', $ev);
                    renderRow('5.2', 'Are the contents relevant to the Subject Matter?', $ev);
                    renderRow('5.3', 'Do the training materials cite the source/s of information?', $ev);
                    ?>

                    <tr>
                        <td colspan="5" class="qa-section-header">6. Principle of Alignment (Core)</td>
                    </tr>
                    <?php
                    renderRow('6.1', 'Are the outcomes aligned with the contents of the Training Module?', $ev);
                    renderRow('6.2', 'Are the contents of the Training Material aligned with the Assessment Tool used?', $ev);
                    renderRow('6.3', 'Is the Assessment Tool used appropriate to measure the learning outcomes achievement?', $ev);
                    ?>

                    <tr>
                        <td colspan="5" class="qa-section-header">7. Learners Support Systems</td>
                    </tr>
                    <?php
                    renderRow('7.1', 'Are the LSS Appropriate for the delivery of the training program?', $ev);
                    renderRow('7.2', 'The Training Program explicitly included Learners Support Systems available.', $ev);
                    ?>

                    <!-- TOTAL SCORE / QA STATUS 1 -->
                    <tr>
                        <td colspan="2" align="right" style="background: #f8fafc; font-weight: bold; color:#000;">Total
                            Score:</td>
                        <td align="center" style="background: #fecdd3; font-weight: 800; font-size: 14px; color:#000;"><span
                                id="totalMqlsScore">0</span> /85</td>
                        <td colspan="2" style="font-size: 10px; color: #000; line-height: 1.3;">
                            69-85 - Pass (Meets or exceeds quality standards)<br>
                            51-68 - Needs Improvement (Acceptable with gaps)<br>
                            0-50 - Fail (Below minimum QA standard)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" align="right" style="background: #f8fafc; font-weight: bold; color:#000;">Overall QA
                            Status:</td>
                        <td colspan="3">
                            <select name="overall_status" class="qa-status-select" style="width: 250px;">
                                <option <?php if ($active_program['overall_status'] == 'Pending')
                                    echo 'selected'; ?>>Pending
                                </option>
                                <option <?php if ($active_program['overall_status'] == 'Pass')
                                    echo 'selected'; ?>>Pass
                                </option>
                                <option <?php if ($active_program['overall_status'] == 'Needs Improvement')
                                    echo 'selected'; ?>>Needs Improvement</option>
                                <option <?php if ($active_program['overall_status'] == 'Fail')
                                    echo 'selected'; ?>>Fail
                                </option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- DURING & AFTER IMPLEMENTATION EVALUATION & MONITORING -->
            <table class="qa-table">
                <thead>
                    <tr>
                        <th colspan="5"
                            style="background: #0a192f; color: white; font-size: 13px; text-align: center; text-transform: uppercase;">
                            During & After Implementation Evaluation & Monitoring</th>
                    </tr>
                    <tr>
                        <th style="width: 45%;">Indicator / Criteria</th>
                        <th style="width: 12%; text-align: center;">Scores (1-5)</th>
                        <th style="width: 15%; text-align: center;">Status</th>
                        <th style="width: 18%;">Remarks</th>
                        <th style="width: 10%;">Evidence/Links</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    renderRow('imp.1', 'Instructional Delivery', $ev);
                    renderRow('imp.2', 'Evaluation & Continuous Improvement', $ev);
                    renderRow('imp.3', 'Feedback', $ev);
                    ?>

                    <!-- TOTAL SCORE / QA STATUS 2 -->
                    <tr>
                        <td colspan="2" align="right" style="background: #f8fafc; font-weight: bold; color:#000;">Total
                            Score:</td>
                        <td align="center" style="background: #fecdd3; font-weight: 800; font-size: 14px; color:#000;"><span
                                id="totalImpScore">0</span> /15</td>
                        <td colspan="2" style="font-size: 10px; color: #000; line-height: 1.3;">
                            11-15 - Pass | 6-10 - Needs Improvement | 0-5 - Fail
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" align="right" style="background: #f8fafc; font-weight: bold; color:#000;">QA Status:
                        </td>
                        <td colspan="3">
                            <input type="text" name="mqls[imp_qa_status]" class="qa-status-select" style="width: 250px;"
                                value="<?php echo htmlspecialchars($ev['imp_qa_status'] ?? 'N/A'); ?>">
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- SIGNATURE BLOCK -->
            <table class="qa-table" style="margin-top: 15px; border: none;">
                <tr>
                    <td style="border: none; width: 50%; padding-right: 20px;">
                        <div style="border-top: 1px solid #000; padding-top: 5px; margin-top: 20px;">
                            <strong style="font-size: 12px; display: block; margin-bottom: 3px;">Prepared by:</strong>
                            <input type="text" name="mqls[prepared_name]" class="form-input-clean"
                                placeholder="Enter Name..."
                                value="<?php echo htmlspecialchars($ev['prepared_name'] ?? ''); ?>"
                                style="font-weight: bold; border-bottom: 1px dotted #94a3b8; margin-bottom: 3px;">
                            <input type="text" name="mqls[prepared_desig]" class="form-input-clean"
                                placeholder="Enter Designation..."
                                value="<?php echo htmlspecialchars($ev['prepared_desig'] ?? ''); ?>"
                                style="font-size: 11px; color: #555;">
                        </div>
                    </td>
                    <td style="border: none; width: 50%; padding-left: 20px;">
                        <div style="border-top: 1px solid #000; padding-top: 5px; margin-top: 20px;">
                            <strong style="font-size: 12px; display: block; margin-bottom: 3px;">Approved by:</strong>
                            <input type="text" name="mqls[approved_name]" class="form-input-clean"
                                placeholder="Enter Name..."
                                value="<?php echo htmlspecialchars($ev['approved_name'] ?? ''); ?>"
                                style="font-weight: bold; border-bottom: 1px dotted #94a3b8; margin-bottom: 3px;">
                            <input type="text" name="mqls[approved_desig]" class="form-input-clean"
                                placeholder="Enter Designation..."
                                value="<?php echo htmlspecialchars($ev['approved_desig'] ?? ''); ?>"
                                style="font-size: 11px; color: #555;">
                        </div>
                    </td>
                </tr>
            </table>

            <div style="text-align: right; margin-top: 20px; margin-bottom: 30px;" class="no-print">
                <button type="submit" name="update_evaluation" class="btn-submit" style="padding: 12px 30px;"><i
                        class="fas fa-save"></i> Save Full QA Rubrics</button>
            </div>
        </form>

        <script>
            function calcScores() {
                let mqlsTotal = 0;
                let impTotal = 0;
                document.querySelectorAll('.score-input').forEach(input => {
                    let val = parseInt(input.value) || 0;
                    if (input.name.includes('imp')) {
                        impTotal += val;
                    } else {
                        mqlsTotal += val;
                    }
                });
                document.getElementById('totalMqlsScore').innerText = mqlsTotal;
                document.getElementById('totalImpScore').innerText = impTotal;
            }
            window.onload = calcScores;
        </script>

    <?php else: ?>
        <!-- ==========================================
             TABS: LIST OF PROGRAMS & ANALYTICS REPORTS
        =========================================== -->
        <div class="no-print"
            style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 class="header-title">Quality Assurance (QA) Tracker</h1>
                <p class="header-sub" style="margin-bottom: 0;">Municipal Quality Learning Standards (MQLS) compliance &
                    program analytics.</p>
            </div>
            <button class="btn-submit" onclick="document.getElementById('addQaModal').style.display='flex'">+ New Program
                Entry</button>
        </div>

        <!-- NAVIGATION TABS -->
        <div class="no-print"
            style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid var(--border-soft); padding-bottom: 10px;">
            <button class="qa-tab-btn qa-active-tab" onclick="switchQaTab(event, 'programsTab')">All Training
                Programs</button>
            <button class="qa-tab-btn" onclick="switchQaTab(event, 'analyticsTab')">Analytics & QA Compliance
                Reports</button>
        </div>

        <!-- TAB 1: PROGRAMS LIST -->
        <div id="programsTab" class="qa-tab-content">
            <div class="card" style="padding: 0; overflow-x: auto;">
                <table class="data-table" style="min-width: 1000px;">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Program / Training Title</th>
                            <th style="width: 25%;">Implementing Office</th>
                            <th style="width: 20%;">Implementation Date</th>
                            <th style="width: 20%; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($programs) > 0): ?>
                            <?php foreach ($programs as $p): ?>
                                <tr>
                                    <td><strong
                                            style="color: var(--corp-navy);"><?php echo htmlspecialchars($p['program_title']); ?></strong><br><small
                                            style="color:#64748b;">Status:
                                            <?php echo htmlspecialchars($p['overall_status']); ?></small></td>
                                    <td><?php echo htmlspecialchars($p['implementing_office']); ?></td>
                                    <td><?php echo htmlspecialchars($p['impl_date']); ?></td>
                                    <td class="no-print" style="text-align: center;">
                                        <div class="action-container">
                                            <!-- MATCHING UPDATE & DELETE BUTTONS -->
                                            <a href="qa_tracker.php?view=<?php echo $p['id']; ?>"
                                                class="btn-action-text btn-action-update">Update</a>
                                            <button type="button" class="btn-action-text btn-action-delete"
                                                onclick="openDeleteModal(<?php echo $p['id']; ?>)">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    No QA training programs recorded yet. Click <strong>+ New Program Entry</strong> to start.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: ANALYTICS & COMPLIANCE REPORTS -->
        <div id="analyticsTab" class="qa-tab-content" style="display: none;">
            <?php
            $total_progs = count($programs);
            $completed_progs = 0;
            $passed_qa = 0;
            $needs_imp = 0;
            $failed_qa = 0;

            foreach ($programs as $p) {
                if ($p['overall_status'] == 'Completed / Finished' || $p['overall_status'] == 'Pass') {
                    $completed_progs++;
                }
                if ($p['overall_status'] == 'Pass')
                    $passed_qa++;
                if ($p['overall_status'] == 'Needs Improvement')
                    $needs_imp++;
                if ($p['overall_status'] == 'Fail')
                    $failed_qa++;
            }
            ?>
            <div style="display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap;">
                <div class="exec-card"
                    style="flex:1; min-width: 200px; background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #cbd5e1;">
                    <p style="margin:0; font-size: 12px; font-weight:700; color:#000;">TOTAL REGISTERED PROGRAMS</p>
                    <h2 style="margin: 5px 0 0 0; font-size: 26px; color:#000;"><?php echo $total_progs; ?></h2>
                </div>
                <div class="exec-card"
                    style="flex:1; min-width: 200px; background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #cbd5e1;">
                    <p style="margin:0; font-size: 12px; font-weight:700; color:#000;">COMPLETED / FINISHED PROGRAMS</p>
                    <h2 style="margin: 5px 0 0 0; font-size: 26px; color:#10b981;"><?php echo $completed_progs; ?></h2>
                </div>
                <div class="exec-card"
                    style="flex:1; min-width: 200px; background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #cbd5e1;">
                    <p style="margin:0; font-size: 12px; font-weight:700; color:#000;">MET QUALITY STANDARDS (PASS)</p>
                    <h2 style="margin: 5px 0 0 0; font-size: 26px; color:#0284c7;"><?php echo $passed_qa; ?></h2>
                </div>
            </div>

            <div class="card" style="padding: 20px;">
                <h3
                    style="margin-top:0; color: #000; font-size: 16px; border-bottom: 1px solid #cbd5e1; padding-bottom: 10px;">
                    Program Quality Compliance & Completion Report</h3>
                <table class="data-table" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>Program Title</th>
                            <th>Implementing Office</th>
                            <th>Implementation Date</th>
                            <th>Current Status</th>
                            <th>QA Compliance & Standing</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($programs as $p): ?>
                            <tr>
                                <td><strong style="color: #000;"><?php echo htmlspecialchars($p['program_title']); ?></strong>
                                </td>
                                <td style="color:#000;"><?php echo htmlspecialchars($p['implementing_office']); ?></td>
                                <td style="color:#000;"><?php echo htmlspecialchars($p['impl_date']); ?></td>
                                <td style="color:#000;"><?php echo htmlspecialchars($p['overall_status']); ?></td>
                                <td>
                                    <?php
                                    if ($p['overall_status'] == 'Pass')
                                        echo '<span style="color: #10b981; font-weight: bold;"><i class="fas fa-check-circle"></i> Met Quality Standards (Pass)</span>';
                                    else if ($p['overall_status'] == 'Needs Improvement')
                                        echo '<span style="color: #f59e0b; font-weight: bold;"><i class="fas fa-exclamation-triangle"></i> Acceptable with Gaps</span>';
                                    else if ($p['overall_status'] == 'Fail')
                                        echo '<span style="color: #ef4444; font-weight: bold;"><i class="fas fa-times-circle"></i> Below Minimum Standard</span>';
                                    else
                                        echo '<span style="color: #000;"><i class="fas fa-clock"></i> Pending Rubric Evaluation</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL: ADD PROGRAM -->
        <div id="addQaModal" class="modal-overlay no-print">
            <div class="modal-box" style="width: 500px; background: #fff;">
                <h3 style="color: #000; margin-top:0;">Add New Training Program</h3>
                <form method="POST" action="">
                    <div class="form-group"><label style="color:#000;">Program / Training Title</label><input type="text"
                            name="program_title" required></div>
                    <div class="form-group"><label style="color:#000;">Implementing Office</label><input type="text"
                            name="implementing_office" placeholder="e.g. STTI / SMILE"></div>
                    <div style="display: flex; gap: 10px;">
                        <div class="form-group" style="flex:1;"><label style="color:#000;">Submission Date</label><input
                                type="date" name="submission_date"></div>
                        <div class="form-group" style="flex:1;"><label style="color:#000;">Implementation Date</label><input
                                type="date" name="impl_date"></div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <div class="form-group" style="flex:1;"><label style="color:#000;">Evaluator</label><input
                                type="text" name="evaluator"></div>
                        <div class="form-group" style="flex:1;"><label style="color:#000;">Project Director</label><input
                                type="text" name="project_director"></div>
                    </div>
                    <div class="form-group"><label style="color:#000;">Flagship Project?</label><select
                            name="flagship_project">
                            <option>Yes</option>
                            <option>No</option>
                        </select></div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel"
                            onclick="document.getElementById('addQaModal').style.display='none'">Cancel</button>
                        <button type="submit" name="save_qa_program" class="btn-submit">Create Program</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: EXACT MATCH DELETE CONFIRMATION DIALOG -->
        <div id="deleteModal" class="modal-overlay no-print"
            style="display: none; align-items: center; justify-content: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
            <div class="modal-box"
                style="width: 420px; text-align: center; padding: 35px 30px; border-radius: 16px; background: #ffffff; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                <h3 style="color: #ef4444; margin: 0 0 12px 0; font-size: 20px; font-weight: 700;">Delete Record</h3>
                <p style="color: #000000; font-size: 14px; margin-bottom: 30px; line-height: 1.5; font-weight: 500;">Are you
                    sure you want to permanently delete this training program record?</p>

                <form method="POST" action="qa_tracker.php">
                    <input type="hidden" name="delete_id" id="delete_record_id">
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <button type="button" class="btn-cancel"
                            onclick="document.getElementById('deleteModal').style.display='none'"
                            style="flex: 1; margin-top:0; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #000; font-weight: 600; cursor: pointer;">Cancel</button>
                        <button type="submit"
                            style="flex: 1; background: #fff; color: #ef4444; border: 1px solid #fca5a5; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;"
                            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">Yes,
                            Delete</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function switchQaTab(evt, tabId) {
                document.querySelectorAll('.qa-tab-content').forEach(c => c.style.display = 'none');
                document.querySelectorAll('.qa-tab-btn').forEach(b => b.classList.remove('qa-active-tab'));
                document.getElementById(tabId).style.display = 'block';
                evt.currentTarget.classList.add('qa-active-tab');
            }

            function openDeleteModal(id) {
                document.getElementById('delete_record_id').value = id;
                document.getElementById('deleteModal').style.display = 'flex';
            }
        </script>
    <?php endif; ?>

</div>

<?php require_once 'footer.php'; ?>