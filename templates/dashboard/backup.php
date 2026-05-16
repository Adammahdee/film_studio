<?php
require_once __DIR__ . "/includes/auth_check.php";
require_once __DIR__ . "/config/db.php";

// Quick admin check
$stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if ($user['role'] !== 'ADMIN') { die("Unauthorized"); }

$msg = "";

// --- Handle Backup Export ---
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) { $tables[] = $row[0]; }

    $sqlScript = "-- Film Studio Backup\n\n";
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW CREATE TABLE $table");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $sqlScript .= "\n\nDROP TABLE IF EXISTS `$table`;\n";
        $sqlScript .= $row[1] . ";\n\n";

        $stmt = $conn->query("SELECT * FROM $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $keys = array_keys($row);
            $values = array_values($row);
            $vals = array_map(function($v) use ($conn) { return $v === null ? "NULL" : $conn->quote($v); }, $values);
            $sqlScript .= "INSERT INTO $table (" . implode(", ", $keys) . ") VALUES (" . implode(", ", $vals) . ");\n";
        }
    }

    // Prepend foreign key check disables to ensure smooth restoration
    $sqlScript = "SET FOREIGN_KEY_CHECKS = 0;\n" . $sqlScript . "\nSET FOREIGN_KEY_CHECKS = 1;";

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i-s') . '.sql"');
    echo $sqlScript;
    exit();
}

// --- Handle Restore Import ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
    try {
        $sql = file_get_contents($_FILES['backup_file']['tmp_name']);
        
        // Ensure the connection handles multi-statements correctly
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
        $conn->exec($sql);
        $msg = '<div class="alert alert-success">Database restored successfully!</div>';
    } catch (Exception $e) {
        $msg = '<div class="alert alert-danger">Restore failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

require_once __DIR__ . "/includes/header.php";
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-warning">
                <h5 class="card-title mb-0">Backup & Restore</h5>
            </div>
            <div class="card-body">
                <?= $msg ?>
                
                <div class="mb-4">
                    <h6>Export Data</h6>
                    <p class="text-muted small">Download a full SQL dump of your current database.</p>
                    <a href="?action=export" class="btn btn-success w-100">Download Backup (.sql)</a>
                </div>
                
                <hr>
                
                <div class="mt-4">
                    <h6>Restore Data</h6>
                    <p class="text-muted small text-danger">Warning: This will overwrite current data if tables already exist.</p>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" name="backup_file" class="form-control" accept=".sql" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure? This may overwrite existing data.')">
                            Upload and Restore
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="/index.php?page=settings" class="btn btn-link">Back to Settings</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>