<?php
require_once("./database/db.php");

session_start();
if (!isset($_SESSION['user_id']) || time() > $_SESSION['expires_at'] || empty($_SESSION['is_admin'])) {
    header("Location: index.php");
    exit;
}

$totalUsers = execute_query("SELECT COUNT(*) AS count FROM users")[0]['count'];
$totalFiles = execute_query("SELECT COUNT(*) AS count FROM files")[0]['count'];
$activeFiles = execute_query("SELECT COUNT(*) AS count FROM files WHERE expires_at IS NULL OR expires_at > NOW()")[0]['count'];
$expiredFiles = $totalFiles - $activeFiles;

$recentFiles = execute_query("
    SELECT f.id, f.original_name, f.expires_at, u.username
    FROM files f
    JOIN users u ON f.user_id = u.id
    ORDER BY f.id DESC
    LIMIT 10
");
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="./dashboard.css" />
    <style>
        .stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin: 30px auto;
            max-width: 700px;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 25px 35px;
            text-align: center;
            min-width: 150px;
        }
        .stat-card h3 {
            margin: 0 0 8px 0;
            font-size: 2rem;
            color: #3498db;
        }
        .stat-card p {
            margin: 0;
            color: #666;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <h2>Admin Dashboard</h2>

    <div class="stats">
        <div class="stat-card">
            <h3><?php echo (int)$totalUsers; ?></h3>
            <p>Total Users</p>
        </div>
        <div class="stat-card">
            <h3><?php echo (int)$totalFiles; ?></h3>
            <p>Total Files</p>
        </div>
        <div class="stat-card">
            <h3><?php echo (int)$activeFiles; ?></h3>
            <p>Active Files</p>
        </div>
        <div class="stat-card">
            <h3><?php echo (int)$expiredFiles; ?></h3>
            <p>Expired Files</p>
        </div>
    </div>

    <?php if (!empty($recentFiles)): ?>
    <h3 style="text-align:center;">Recent Files</h3>
    <table cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Filename</th>
                <th>Uploaded By</th>
                <th>Expires At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentFiles as $file): ?>
            <tr>
                <td><?php echo htmlspecialchars($file['original_name']); ?></td>
                <td><?php echo htmlspecialchars($file['username']); ?></td>
                <td>
                    <?php
                        echo $file['expires_at']
                            ? htmlspecialchars(date('Y-m-d H:i', strtotime($file['expires_at'])))
                            : 'Never';
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="text-align: center;">No files uploaded yet.</p>
    <?php endif; ?>

    <form method="post" action="./auth/logout.php">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
