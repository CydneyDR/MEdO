<?php
function uploadToGoogleDrive($fileTmpPath, $fileName, $fileType) {
    // I-convert ang file sa base64 para maipadala sa Google Script
    $fileData = base64_encode(file_get_contents($fileTmpPath));
    
    $postData = array(
        'fileName' => $fileName,
        'mimeType' => $fileType,
        'fileData' => $fileData
    );
    
    // --> ILAGAY DITO ANG WEB APP URL NA GALING SA GOOGLE APPS SERVICE DEPLOYMENT MO <--
    $url = "https://script.google.com/macros/s/AKfycbzies0l027aLLC1tIsDxc6X68R-ncVeQ9BSG2xAwdFX1J_L8EOPXpIvY3MRVdJMNb-q/exec"; 
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if(isset($result['status']) && $result['status'] == 'success') {
        return $result['fileUrl']; // Ibabalik nito ang mismong link ng file sa Google Drive mo
    } else {
        return false;
    }
}
?>