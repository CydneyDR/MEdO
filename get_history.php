<?php
require_once 'db.php';

$paper_id = $_GET['id'] ?? 0;

$stmtPaper = $pdo->prepare("SELECT * FROM routing_papers WHERE id = ?");
$stmtPaper->execute([$paper_id]);
$paper = $stmtPaper->fetch(PDO::FETCH_ASSOC);

// KUKUNIN ANG HISTORY PERO TATANGGALIN (EXCLUDE) YUNG MAY STATUS NA "File Uploaded..."
$stmtHistory = $pdo->prepare("SELECT * FROM routing_history WHERE paper_id = ? AND status NOT LIKE 'File Uploaded:%' ORDER BY id DESC");
$stmtHistory->execute([$paper_id]);
$history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ATTACHED FILE SECTION -->
<div style="background: #f8fafc; padding: 18px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e2e8f0;">
  <h4 style="margin: 0 0 12px 0; color: #1e293b; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
      stroke-linejoin="round" viewBox="0 0 24 24">
      <path
        d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48">
      </path>
    </svg>
    Attached Document
  </h4>

  <?php if (!empty($paper['file_path'])): ?>
    <div style="display: flex; flex-direction: column; gap: 12px;">
      <!-- FILE NAME AND VIEW BUTTON -->
      <div
        style="display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 12px 15px; border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
        <span
          style="font-size: 0.9rem; font-weight: 600; color: #0f172a; word-break: break-all; display:flex; align-items:center; gap: 8px;">
          📄 <?php echo htmlspecialchars($paper['file_display_name'] ?? basename($paper['file_path'])); ?>
        </span>
        <a href="<?php echo htmlspecialchars($paper['file_path']); ?>" target="_blank"
          style="background: #8b5cf6; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 11px; font-weight: bold; white-space: nowrap; box-shadow: 0 2px 4px rgba(139,92,246,0.2); transition: 0.2s;">View
          File</a>
      </div>

      <!-- RENAME FORM -->
      <form method="POST" action="routing.php" style="display: flex; gap: 10px; margin-top: 5px; align-items: center;">
        <input type="hidden" name="paper_id" value="<?php echo $paper['id']; ?>">

        <!-- ITO YUNG DINAGDAG PARA SURE NA GAGANA ANG RENAME KAHIT I-ENTER MO LANG -->
        <input type="hidden" name="rename_routing_file" value="1">

        <input type="text" name="new_file_name"
          value="<?php echo htmlspecialchars($paper['file_display_name'] ?? basename($paper['file_path'])); ?>" required
          placeholder="Enter new file name..."
          style="font-size: 0.9rem; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; flex: 1; outline: none; transition: border-color 0.2s;"
          onfocus="this.style.borderColor='#38bdf8'" onblur="this.style.borderColor='#cbd5e1'">

        <button type="submit"
          style="background: #0ea5e9; color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px; white-space: nowrap; box-shadow: 0 2px 4px rgba(14,165,233,0.2); transition: 0.2s;"
          onmouseover="this.style.background='#0284c7'" onmouseout="this.style.background='#0ea5e9'">
          Rename File
        </button>
      </form>
    </div>
  <?php else: ?>
    <div style="text-align: center; padding: 15px; background: #fff; border: 1px dashed #cbd5e1; border-radius: 8px;">
      <p style="margin: 0; font-size: 0.85rem; color: #64748b; font-style: italic;">No file attached yet for this
        document.</p>
    </div>
  <?php endif; ?>
</div>

<!-- ROUTING TRAIL / TIMELINE -->
<h4 style="margin: 0 0 15px 0; color: #1e293b; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
  <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
    stroke-linejoin="round" viewBox="0 0 24 24">
    <polyline points="12 8 12 12 14 14"></polyline>
    <circle cx="12" cy="12" r="10"></circle>
  </svg>
  Routing Trail
</h4>

<ul style="list-style: none; padding: 0; margin: 0; position: relative;">
  <!-- Vertical line for timeline effect -->
  <div style="position: absolute; left: 7px; top: 10px; bottom: 10px; width: 2px; background: #e2e8f0; z-index: 0;">
  </div>

  <?php if (count($history) > 0): ?>
    <?php foreach ($history as $index => $h): ?>
      <li style="position: relative; padding-left: 28px; margin-bottom: 20px; z-index: 1;">

        <!-- Timeline Dot (Blue if newest, Gray if old) -->
        <div
          style="position: absolute; left: 0; top: 6px; width: 14px; height: 14px; border-radius: 50%; background: <?php echo ($index === 0) ? '#3b82f6' : '#cbd5e1'; ?>; border: 3px solid #fff; box-shadow: 0 0 0 1px <?php echo ($index === 0) ? '#3b82f6' : '#cbd5e1'; ?>;">
        </div>

        <!-- Timeline Content Card -->
        <div
          style="background: #fff; border: 1px solid #f1f5f9; padding: 15px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">

          <div style="font-weight: 700; color: #0f172a; font-size: 0.95rem; margin-bottom: 6px;">
            <?php echo htmlspecialchars($h['office_name']); ?>
          </div>

          <div style="font-size: 0.85rem; color: #475569; margin-bottom: 8px;">
            Status: <span
              style="font-weight: 700; color: #0284c7; background: #f0f9ff; padding: 3px 8px; border-radius: 6px; border: 1px solid #bae6fd;">
              <?php echo htmlspecialchars($h['status']); ?>
            </span>
          </div>

          <!-- Date and Routed By -->
          <div
            style="font-size: 0.75rem; color: #64748b; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 8px; margin-top: 4px;">
            <span>Routed By: <strong
                style="color: #475569;"><?php echo htmlspecialchars($h['routed_by']); ?></strong></span>
            <span style="display: flex; align-items: center; gap: 4px;">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
              <?php echo date('M d, Y - h:i A', strtotime($h['date_routed'])); ?>
            </span>
          </div>
        </div>

      </li>
    <?php endforeach; ?>
  <?php else: ?>
    <p style="margin-left: 20px; font-size: 0.85rem; color: #64748b;">No routing history found.</p>
  <?php endif; ?>
</ul>