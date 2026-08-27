<?php
// Isama ang iyong upload function dito o i-require kung nasaan man nakalagay
require_once 'db.php'; // Palitan kung kinakailangan

function uploadToGoogleDrive($fileTmpPath, $fileName, $fileType)
{
    $fileData = base64_encode(file_get_contents($fileTmpPath));

    $postData = array(
        'fileName' => $fileName,
        'mimeType' => $fileType,
        'fileData' => $fileData
    );

    $url = "https://script.google.com/macros/s/AKfycbxhCKD0pl07RC_95UGKHQkjp80QNmUTRcqfikFkiuWVo_kgpa0BF44ZVwrnlGv2nQ1t/exec";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Mahalaga para sa Google Script redirects
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return "CURL Error: " . $error_msg;
    }

    curl_close($ch);
    return $response; // I-return muna ang buong raw response para makita natin
}

$test_result = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['test_file'])) {
    $fileTmpPath = $_FILES['test_file']['tmp_name'];
    $fileName = $_FILES['test_file']['name'];
    $fileType = $_FILES['test_file']['type'];

    $test_result = uploadToGoogleDrive($fileTmpPath, $fileName, $fileType);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Google Drive Upload Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f8fafc;
            padding: 40px;
            color: #334155;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            margin: auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
        }

        button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background: #2563eb;
        }

        .response-box {
            margin-top: 20px;
            background: #0f172a;
            color: #38bdf8;
            padding: 15px;
            border-radius: 8px;
            word-break: break-all;
            font-family: monospace;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

    <div class="card">
        <h2>Google Drive Upload Tester</h2>
        <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 20px;">Subukang mag-upload ng maliit na imahe o
            dokumento para makita kung pumasok sa Google Drive.</p>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Pumili ng File:</label>
                <input type="file" name="test_file" required>
            </div>
            <button type="submit">Test Upload Now</button>
        </form>

        <?php if (!empty($test_result)): ?>
            <div class="response-box">
                <strong>Google Response:</strong><br>
                <?php echo htmlspecialchars($test_result); ?>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>