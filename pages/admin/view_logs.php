<<<<<<< HEAD
<?php
require_once __DIR__ . '/../../init.php';

// Nur für Admins
session_start();
if(!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die('Zugriff verweigert');
}

$logDir = __DIR__ . '/../logs/';
$logFiles = glob($logDir . '*.log');
rsort($logFiles); // Neueste zuerst

$selectedLog = isset($_GET['log']) ? basename($_GET['log']) : '';
$logContent = '';

if($selectedLog && file_exists($logDir . $selectedLog)) {
    $logContent = file_get_contents($logDir . $selectedLog);
    $logContent = htmlspecialchars($logContent);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Log Viewer</title>
    <style>
        body { background: #1a1a1a; color: #fff; font-family: monospace; padding: 20px; }
        select { padding: 10px; font-size: 16px; margin-bottom: 20px; }
        pre { background: #252525; padding: 20px; border: 1px solid #444; overflow-x: auto; }
        .error { color: #e74c3c; }
        .warning { color: #f39c12; }
        .info { color: #3498db; }
        .critical { color: #c0392b; font-weight: bold; }
    </style>
</head>
<body>
    <h1>📋 Log Viewer</h1>
    
    <form method="GET">
        <select name="log" onchange="this.form.submit()">
            <option value="">-- Log-Datei auswählen --</option>
            <?php foreach($logFiles as $file): ?>
                <?php $filename = basename($file); ?>
                <option value="<?php echo $filename; ?>" <?php echo $selectedLog == $filename ? 'selected' : ''; ?>>
                    <?php echo $filename; ?> (<?php echo human_filesize(filesize($file)); ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    
    <?php if($logContent): ?>
        <pre><?php
        // Zeilen einfärben basierend auf Level
        $lines = explode("\n", $logContent);
        foreach($lines as $line) {
            if(strpos($line, '[ERROR]') !== false) {
                echo '<span class="error">' . $line . '</span>' . "\n";
            } elseif(strpos($line, '[WARNING]') !== false) {
                echo '<span class="warning">' . $line . '</span>' . "\n";
            } elseif(strpos($line, '[INFO]') !== false) {
                echo '<span class="info">' . $line . '</span>' . "\n";
            } elseif(strpos($line, '[CRITICAL]') !== false) {
                echo '<span class="critical">' . $line . '</span>' . "\n";
            } else {
                echo $line . "\n";
            }
        }
        ?></pre>
    <?php endif; ?>
</body>
</html>

<?php
function human_filesize($bytes, $decimals = 2) {
    $size = array('B','KB','MB','GB','TB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
}
=======
<?php
require_once __DIR__ . '/../../init.php';

// Nur für Admins
session_start();
if(!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die('Zugriff verweigert');
}

$logDir = __DIR__ . '/../logs/';
$logFiles = glob($logDir . '*.log');
rsort($logFiles); // Neueste zuerst

$selectedLog = isset($_GET['log']) ? basename($_GET['log']) : '';
$logContent = '';

if($selectedLog && file_exists($logDir . $selectedLog)) {
    $logContent = file_get_contents($logDir . $selectedLog);
    $logContent = htmlspecialchars($logContent);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Log Viewer</title>
    <style>
        body { background: #1a1a1a; color: #fff; font-family: monospace; padding: 20px; }
        select { padding: 10px; font-size: 16px; margin-bottom: 20px; }
        pre { background: #252525; padding: 20px; border: 1px solid #444; overflow-x: auto; }
        .error { color: #e74c3c; }
        .warning { color: #f39c12; }
        .info { color: #3498db; }
        .critical { color: #c0392b; font-weight: bold; }
    </style>
</head>
<body>
    <h1>📋 Log Viewer</h1>
    
    <form method="GET">
        <select name="log" onchange="this.form.submit()">
            <option value="">-- Log-Datei auswählen --</option>
            <?php foreach($logFiles as $file): ?>
                <?php $filename = basename($file); ?>
                <option value="<?php echo $filename; ?>" <?php echo $selectedLog == $filename ? 'selected' : ''; ?>>
                    <?php echo $filename; ?> (<?php echo human_filesize(filesize($file)); ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    
    <?php if($logContent): ?>
        <pre><?php
        // Zeilen einfärben basierend auf Level
        $lines = explode("\n", $logContent);
        foreach($lines as $line) {
            if(strpos($line, '[ERROR]') !== false) {
                echo '<span class="error">' . $line . '</span>' . "\n";
            } elseif(strpos($line, '[WARNING]') !== false) {
                echo '<span class="warning">' . $line . '</span>' . "\n";
            } elseif(strpos($line, '[INFO]') !== false) {
                echo '<span class="info">' . $line . '</span>' . "\n";
            } elseif(strpos($line, '[CRITICAL]') !== false) {
                echo '<span class="critical">' . $line . '</span>' . "\n";
            } else {
                echo $line . "\n";
            }
        }
        ?></pre>
    <?php endif; ?>
</body>
</html>

<?php
function human_filesize($bytes, $decimals = 2) {
    $size = array('B','KB','MB','GB','TB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
}
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
?>