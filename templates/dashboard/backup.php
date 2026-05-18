<?php

use App\Core\Csrf;
use App\Core\ErrorHandler;

// Role check (ADMIN only)
$role = $_SESSION['role'] ?? null;

if ($role !== 'ADMIN') {
    ErrorHandler::render403();
    exit();
}

$msg = "";

/**
 * EXPORT DATABASE
 */
if (isset($_GET['action']) && $_GET['action'] === 'export') {

    try {
        $tables = [];
        $result = $pdo->query("SHOW TABLES");

        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql = "-- System Backup Export\n\n";

        foreach ($tables as $table) {

            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
            $sql .= "\nDROP TABLE IF EXISTS `$table`;\n";
            $sql .= $create[1] . ";\n\n";

            $data = $pdo->query("SELECT * FROM `$table`");

            while ($row = $data->fetch(PDO::FETCH_ASSOC)) {

                $keys = array_keys($row);
                $vals = array_map(fn($v) =>
                    $v === null ? "NULL" : $pdo->quote($v),
                    array_values($row)
                );

                $sql .= "INSERT INTO `$table` (" . implode(",", $keys) . ")
                         VALUES (" . implode(",", $vals) . ");\n";
            }
        }

        $sql = "SET FOREIGN_KEY_CHECKS=0;\n" . $sql . "\nSET FOREIGN_KEY_CHECKS=1;";

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename=backup_' . date('Y-m-d_H-i-s') . '.sql');

        echo $sql;
        exit();

    } catch (PDOException $e) {
        error_log("Backup export failed: " . $e->getMessage());
        $msg = "Backup export failed.";
    }
}

/**
 * RESTORE DATABASE
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {

    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        ErrorHandler::render403();
        exit();
    }

    try {
        $sql = file_get_contents($_FILES['backup_file']['tmp_name']);

        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $pdo->exec($sql);

        $msg = "Database restored successfully.";

    } catch (Exception $e) {
        error_log("Restore failed: " . $e->getMessage());
        $msg = "Restore operation failed.";
    }
}
?>

<!-- VIEW ONLY -->
<div class="container py-4">

    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow-sm">

                <div class="card-header bg-warning">
                    <h5 class="mb-0">Backup & Restore</h5>
                </div>

                <div class="card-body">

                    <?php if ($msg): ?>
                        <div class="alert alert-info">
                            <?= htmlspecialchars($msg) ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <h6>Export Database</h6>
                        <a href="?action=export" class="btn btn-success w-100">
                            Download Backup
                        </a>
                    </div>

                    <hr>

                    <div>
                        <h6>Restore Database</h6>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= Csrf::generateToken() ?>">

                            <input type="file" name="backup_file"
                                   class="form-control mb-3"
                                   accept=".sql"
                                   required>

                            <button type="submit"
                                    class="btn btn-danger w-100"
                                    onclick="return confirm('This will overwrite data. Continue?')">
                                Restore Backup
                            </button>
                        </form>

                    </div>

                </div>

                <div class="card-footer text-center">
                    <a href="<?= url('settings') ?>">Back to Settings</a>
                </div>

            </div>

        </div>
    </div>

</div>