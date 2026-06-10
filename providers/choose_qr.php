<?php
session_start();

$artifactsDir = 'C:/Users/LENOVO/.gemini/antigravity/brain/b531985c-d5c0-417f-824e-d29f5d0bba38';
$targetPath = 'c:/xampp/htdocs/HomeEase/assets/images/admin_gcash_qr.png';

// Create a temp folder under HomeEase/assets/images/temp_media/ to expose the artifacts to the web page
$webTempDir = 'c:/xampp/htdocs/HomeEase/assets/images/temp_media';
if (!is_dir($webTempDir)) {
    mkdir($webTempDir, 0755, true);
}

// Copy all media__ and admin_gcash_qr files to the web temp folder
$files = scandir($artifactsDir);
foreach ($files as $file) {
    if (strpos($file, 'media__') === 0 || strpos($file, 'admin_gcash_qr') === 0) {
        copy($artifactsDir . '/' . $file, $webTempDir . '/' . $file);
    }
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_file'])) {
    $selectedFile = basename($_POST['selected_file']);
    $src = $webTempDir . '/' . $selectedFile;
    if (file_exists($src)) {
        if (copy($src, $targetPath)) {
            $message = "<div style='color: green; font-weight: bold; margin-bottom: 20px;'>Successfully set $selectedFile as the active GCash QR code! Please refresh your My Earnings page to verify.</div>";
        } else {
            $message = "<div style='color: red; font-weight: bold; margin-bottom: 20px;'>Failed to copy $selectedFile.</div>";
        }
    } else {
        $message = "<div style='color: red; font-weight: bold; margin-bottom: 20px;'>File not found.</div>";
    }
}

// Read the copied files in the temp folder to show them
$tempFiles = [];
if (is_dir($webTempDir)) {
    $tFiles = scandir($webTempDir);
    foreach ($tFiles as $tf) {
        if ($tf !== '.' && $tf !== '..') {
            $tempFiles[] = $tf;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Select Admin GCash QR Code Image</title>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f9f9fb; padding: 30px; color: #333; }
        h1 { font-size: 24px; color: #D4790A; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .card { background: white; border: 1px solid #ddd; padding: 15px; border-radius: 12px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .card img { max-width: 100%; height: 200px; object-fit: contain; border-radius: 8px; border: 1px solid #eee; margin-bottom: 10px; }
        .card button { background: #D4790A; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .card button:hover { background: #b86504; }
    </style>
</head>
<body>
    <h1>Select the Original GCash QR Code Image</h1>
    <p>We found the following images from your chat submissions. Select the one you want to use as the admin's GCash QR code:</p>
    
    <?= $message ?>

    <form method="POST" id="selectForm">
        <input type="hidden" name="selected_file" id="selectedFile">
        <div class="grid">
            <?php foreach ($tempFiles as $tf): ?>
                <div class="card">
                    <img src="../assets/images/temp_media/<?= htmlspecialchars($tf) ?>" alt="Image">
                    <div style="font-size: 11px; color: #777; margin-bottom: 10px; word-break: break-all;"><?= htmlspecialchars($tf) ?></div>
                    <button type="button" onclick="selectImage('<?= htmlspecialchars($tf) ?>')">Use This Image</button>
                </div>
            <?php endforeach; ?>
        </div>
    </form>

    <script>
        function selectImage(filename) {
            document.getElementById('selectedFile').value = filename;
            document.getElementById('selectForm').submit();
        }
    </script>
</body>
</html>
