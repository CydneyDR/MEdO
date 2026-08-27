<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

// LOGIC: Save Note
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_note'])) {
    $new_note = trim($_POST['office_note']);
    if (!empty($new_note)) {
        $stmt = $pdo->prepare("INSERT INTO office_notes (note_text) VALUES (:note)");
        $stmt->execute(['note' => $new_note]);
    }
    header("Location: index.php");
    exit;
}

// LOGIC: Delete Note
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_note_id'])) {
    $stmt = $pdo->prepare("DELETE FROM office_notes WHERE id = :id");
    $stmt->execute(['id' => $_POST['delete_note_id']]);
    header("Location: index.php");
    exit;
}

// FETCH DATA
$notes_stmt = $pdo->query("SELECT * FROM office_notes ORDER BY created_at DESC LIMIT 5");
$saved_notes = $notes_stmt->fetchAll(PDO::FETCH_ASSOC);

$upcoming_stmt = $pdo->query("SELECT id, title, event_date, start_time, description FROM bookings WHERE event_date >= CURDATE() ORDER BY event_date ASC, start_time ASC LIMIT 4");
$upcoming_events = $upcoming_stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
    <div>
        <h1 class="header-title" style="font-size: 32px;">Training Office Dashboard</h1>
        <p class="header-sub" style="margin-bottom: 0;">Welcome back, <strong
                style="color: var(--main-blue);"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong>.
        </p>
    </div>
</div>

<!-- FIX 1: Added align-items: flex-start; so the cards don't stretch to match each other's height -->
<div class="card-container" style="align-items: flex-start;">

    <!-- EVENTS SECTION -->
    <div class="card" style="flex: 1.2;">
        <h3 style="display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            Upcoming Events
        </h3>
        <div style="padding: 0;">
            <?php if (count($upcoming_events) > 0): ?>
                <?php foreach ($upcoming_events as $event):
                    $dateObj = new DateTime($event['event_date']);
                    $month = $dateObj->format('M');
                    $day = $dateObj->format('d');
                    $time = date('h:i A', strtotime($event['start_time']));
                    $safeTitle = htmlspecialchars(addslashes($event['title']));
                    $safeDesc = htmlspecialchars(addslashes($event['description'] ?? ''));
                    $formattedTime = $dateObj->format('M d, Y') . ' • ' . $time;
                    ?>
                    <div class="clickable-event" style="border-left: 4px solid var(--accent-blue);"
                        onclick="openViewModal('<?php echo $event['id']; ?>', '<?php echo $safeTitle; ?>', '<?php echo $formattedTime; ?>', '<?php echo $safeDesc; ?>')">
                        <div class="date-badge" style="background: #e0f2fe; border-color: #bae6fd;">
                            <span class="month" style="color: #0369a1;"><?php echo $month; ?></span>
                            <span class="day" style="color: #0c4a6e;"><?php echo $day; ?></span>
                        </div>
                        <div>
                            <strong
                                style="display: block; color: var(--text-main); font-size: 16px; margin-bottom: 6px; font-weight: 700;"><?php echo htmlspecialchars($event['title']); ?></strong>
                            <span class="time-badge" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                                    style="vertical-align: text-top; margin-right: 3px;" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?php echo $time; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div
                    style="text-align: center; padding: 40px 20px; background: #f8fafc; border-radius: 10px; border: 1px dashed #cbd5e1;">
                    <p style="color: var(--text-muted); font-size: 14px; margin: 0;">No upcoming events currently scheduled.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- NOTES SECTION -->
    <div class="card" style="flex: 1.5;">
        <h3 style="display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            Office Notes & Memos
        </h3>

        <form method="POST" action=""
            style="background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
            <div class="form-group" style="margin-bottom: 10px;">
                <textarea name="office_note" class="note-textarea"
                    placeholder="Broadcast an important note or memo to the team..." required
                    style="border: none; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); height: 80px;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" name="save_note" class="btn-submit"
                    style="padding: 8px 16px; font-size: 13px;">Publish Note</button>
            </div>
        </form>

        <!-- FIX 2: Added max-height and overflow-y: auto; so this specific area scrolls if there are too many notes -->
        <div
            style="display: flex; flex-direction: column; gap: 12px; max-height: 400px; overflow-y: auto; padding-right: 5px;">
            <?php if (count($saved_notes) > 0): ?>
                <?php foreach ($saved_notes as $note): ?>
                    <div
                        style="background: #ffffff; border: 1px solid var(--border-color); border-left: 4px solid #f59e0b; padding: 15px 20px; border-radius: 10px; transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div
                                    style="width: 32px; height: 32px; border-radius: 50%; background: #fffbeb; display: flex; align-items: center; justify-content: center; color: #d97706; font-weight: bold; font-size: 14px;">
                                    <?php echo substr($_SESSION['username'] ?? 'A', 0, 1); ?>
                                </div>
                                <div>
                                    <span style="display: block; font-size: 13px; font-weight: 700; color: #334155;">Admin
                                        Announcement</span>
                                    <small
                                        style="color: #94a3b8; font-weight: 500; font-size: 11px;"><?php echo date('F d, Y • h:i A', strtotime($note['created_at'])); ?></small>
                                </div>
                            </div>
                            <!-- DELETE BUTTON -->
                            <button type="button" onclick="openDeleteNoteModal(<?php echo $note['id']; ?>)" title="Delete Note"
                                style="background: transparent; border: none; color: #cbd5e1; font-size: 16px; cursor: pointer; padding: 5px; transition: color 0.2s;"
                                onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#cbd5e1'">✕</button>
                        </div>
                        <div
                            style="font-size: 14px; line-height: 1.6; color: #475569; padding-left: 40px; word-break: break-word;">
                            <?php echo nl2br(htmlspecialchars($note['note_text'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div
                    style="text-align: center; padding: 30px; color: var(--text-muted); font-size: 14px; border: 1px dashed #cbd5e1; border-radius: 10px;">
                    No recent memos or notes have been posted.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>