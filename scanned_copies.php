<?php
// ==========================================
// SCANNED COPIES (DRIVE) LOGIC
// ==========================================
$base_dir = 'uploads/';
if (!file_exists($base_dir)) { mkdir($base_dir, 0777, true); } // Create folder kung wala pa

// Payagan ang spaces at slash (para sa subfolders)
$current_dir = isset($_GET['dir']) ? preg_replace('/[^a-zA-Z0-9_ \-\/]/', '', $_GET['dir']) : '';

// Kunin ang current folder mula sa Hidden Input kung may Form Submit
if (isset($_POST['current_dir'])) {
    $current_dir = preg_replace('/[^a-zA-Z0-9_ \-\/]/', '', $_POST['current_dir']);
}

$current_path = $base_dir . ($current_dir ? $current_dir . '/' : '');

// A. Gawa ng Folder
if (isset($_POST['create_folder'])) {
    $folder_name = preg_replace('/[^a-zA-Z0-9_ \-]/', '_', $_POST['folder_name']);
    if (!empty($folder_name)) {
        if (!file_exists($current_path . $folder_name)) {
            mkdir($current_path . $folder_name, 0777, true);
        }
    }
    $redirect = 'routing.php' . ($current_dir ? "?dir=" . urlencode($current_dir) : "");
    echo "<script>window.location.href='$redirect';</script>"; exit;
}

// B. Upload File
if (isset($_POST['upload_file'])) {
    if (isset($_FILES['scanned_file']) && $_FILES['scanned_file']['error'] == 0) {
        $file_name = basename($_FILES['scanned_file']['name']);
        $file_name = preg_replace('/[^a-zA-Z0-9_.\-]/', '_', $file_name); 
        
        if (!file_exists($current_path)) {
            mkdir($current_path, 0777, true);
        }
        
        move_uploaded_file($_FILES['scanned_file']['tmp_name'], $current_path . $file_name);
    }
    $redirect = 'routing.php' . ($current_dir ? "?dir=" . urlencode($current_dir) : "");
    echo "<script>window.location.href='$redirect';</script>"; exit;
}
?>

<style>
    /* Formal Drive Styles */
    .drive-container { border: 1px solid #ced4da; border-radius: 8px; padding: 15px; background: #ffffff; height: 180px; overflow-y: auto; margin-bottom: 15px; box-shadow: inset 0 1px 4px rgba(0,0,0,0.02); }
    
    .drive-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #eaeaea; }
    
    /* Formal Go Back Button */
    .btn-back { display: inline-flex; align-items: center; gap: 6px; background: #f8f9fa; border: 1px solid #ced4da; color: #495057; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: 0.2s; }
    .btn-back:hover { background: #e2e6ea; border-color: #dae0e5; }
    
    /* File/Folder Items */
    .folder-item, .file-item { display: flex; align-items: center; padding: 8px 10px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 500; color: #333; transition: 0.2s; margin-bottom: 2px; }
    .folder-item:hover, .file-item:hover { background: #f1f3f5; }
    .file-item { color: #495057; font-weight: 400; padding-left: 20px; /* Indented file */ }
    
    /* Clean SVG Icons */
    .svg-icon { width: 18px; height: 18px; flex-shrink: 0; }
    .folder-icon { color: #4c6ef5; /* Professional Blue */ }
    .file-icon { color: #868e96; /* Professional Gray */ }
</style>

<h3 style="margin-top:0; color:#333; font-size: 1.15rem; display: flex; justify-content: space-between; align-items: center;">
    <span>Scanned Copies</span>
    <span style="font-size:0.75rem; color:#888; font-weight:normal; background:#f1f3f5; padding:4px 8px; border-radius:4px;">
        Location: Root <?php echo $current_dir ? ' / ' . htmlspecialchars($current_dir) : ''; ?>
    </span>
</h3>

<div class="drive-container">
    <?php
    // BACK BUTTON LOGIC
    if ($current_dir) {
        $parent_dir = dirname($current_dir);
        if ($parent_dir == '.' || $parent_dir == '\\') $parent_dir = '';
        $back_url = "routing.php" . ($parent_dir ? "?dir=".urlencode($parent_dir) : "");
        
        echo "<div class='drive-header'>
                <button onclick=\"window.location.href='{$back_url}'\" class='btn-back'>
                    <svg class='svg-icon' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><line x1='19' y1='12' x2='5' y2='12'></line><polyline points='12 19 5 12 12 5'></polyline></svg>
                    Go Back
                </button>
              </div>";
    }

    // READ FILES AND FOLDERS
    if (file_exists($current_path)) {
        $items = scandir($current_path);
        $has_items = false;

        // SVG Icons Setup
        $folder_svg = "<svg class='svg-icon folder-icon' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z'></path></svg>";
        $file_svg = "<svg class='svg-icon file-icon' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z'></path><polyline points='13 2 13 9 20 9'></polyline></svg>";

        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $has_items = true;
            $full_item_path = $current_path . $item;
            
            if (is_dir($full_item_path)) {
                $next_dir = $current_dir ? $current_dir . '/' . $item : $item;
                echo "<div class='folder-item' onclick=\"window.location.href='routing.php?dir=" . urlencode($next_dir) . "'\">
                        {$folder_svg} <span style='margin-left:10px;'>{$item}</span>
                      </div>";
            } else {
                echo "<div class='file-item' onclick=\"window.open('{$full_item_path}', '_blank')\">
                        {$file_svg} <span style='margin-left:10px;'>{$item}</span>
                      </div>";
            }
        }
        
        if (!$has_items) { 
            echo "<div style='text-align:center; padding:30px 10px; color:#adb5bd; font-size:0.9rem; font-style:italic;'>This folder is currently empty.</div>"; 
        }
    }
    ?>
</div>

<div class="drive-buttons">
    <button class="btn-drive btn-folder" onclick="document.getElementById('folderModal').showModal()" style="background:#e9ecef; color:#333; border: 1px solid #ced4da;">+ New Folder</button>
    <button class="btn-drive btn-file" onclick="document.getElementById('uploadModal').showModal()" style="background:#2c3e50; color:#fff;">+ Upload File</button>
</div>

<!-- MODAL: Create Folder -->
<dialog id="folderModal">
    <div class="modal-header">Create New Folder</div>
    <div class="modal-body">
        <form method="POST">
            <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($current_dir); ?>">
            <div class="form-group">
                <label>Folder Name:</label>
                <input type="text" name="folder_name" required class="form-control" placeholder="e.g. Finance Reports">
            </div>
            <button type="submit" name="create_folder" class="btn-submit">Create Folder</button>
            <button type="button" class="btn-cancel" onclick="this.closest('dialog').close()">Cancel</button>
        </form>
    </div>
</dialog>

<!-- MODAL: Upload File -->
<dialog id="uploadModal">
    <div class="modal-header">Upload Document</div>
    <div class="modal-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($current_dir); ?>">
            <div class="form-group">
                <label>Select File (PDF, Word, Images):</label>
                <input type="file" name="scanned_file" required class="form-control" style="padding: 7px;">
            </div>
            <button type="submit" name="upload_file" class="btn-submit">
                Upload to <?php echo $current_dir ? htmlspecialchars(basename($current_dir)) : 'Root Folder'; ?>
            </button>
            <button type="button" class="btn-cancel" onclick="this.closest('dialog').close()">Cancel</button>
        </form>
    </div>
</dialog>
