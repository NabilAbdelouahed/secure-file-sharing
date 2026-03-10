<?php
require_once("./database/db.php");

session_start();
if (!isset($_SESSION['user_id']) || time() > $_SESSION['expires_at'] || empty($_SESSION['is_admin'])) {
    header("Location: index.php");
    exit;
}

$adminUser = execute_query("SELECT username FROM users WHERE id = ?", [$_SESSION['user_id']]);
$adminUsername = $adminUser[0]['username'] ?? 'Admin';

$totalUsers = execute_query("SELECT COUNT(*) AS count FROM users")[0]['count'];
$totalFiles = execute_query("SELECT COUNT(*) AS count FROM files")[0]['count'];
$activeFiles = execute_query("SELECT COUNT(*) AS count FROM files WHERE expires_at IS NULL OR expires_at > (NOW() AT TIME ZONE 'UTC')")[0]['count'];
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
    <title>Admin - SecureShare</title>
    <link rel="stylesheet" type="text/css" href="./dashboard.css" />
</head>
<body>
    <nav class="topbar">
        <div class="topbar-brand">SecureShare <span style="font-size:0.75rem;color:#64748b;margin-left:0.5rem;">Admin</span></div>
        <div class="topbar-actions">
            <span class="topbar-user"><?php echo htmlspecialchars($adminUsername); ?></span>
            <form method="post" action="./auth/logout.php" style="margin:0">
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </nav>

    <main class="container">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo (int)$totalUsers; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo (int)$totalFiles; ?></div>
                <div class="stat-label">Total Files</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo (int)$activeFiles; ?></div>
                <div class="stat-label">Active Files</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo (int)$expiredFiles; ?></div>
                <div class="stat-label">Expired Files</div>
            </div>
        </div>

        <!-- Recent Files -->
        <div class="card">
            <h3 class="section-title">Recent Files</h3>
            <?php if (!empty($recentFiles)): ?>
            <div class="table-wrapper">
                <table>
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
                                <?php if ($file['expires_at']): ?>
                                  <span class="utc-date" data-utc="<?php echo htmlspecialchars($file['expires_at']); ?>"></span>
                                <?php else: ?>
                                  Never
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="empty-state">No files uploaded yet.</p>
            <?php endif; ?>
        </div>

        <script>
        document.querySelectorAll('.utc-date').forEach(function(el) {
          var utc = el.getAttribute('data-utc');
          var date = new Date(utc + 'Z');
          var year = date.getFullYear();
          var month = String(date.getMonth() + 1).padStart(2, '0');
          var day = String(date.getDate()).padStart(2, '0');
          var hours = String(date.getHours()).padStart(2, '0');
          var minutes = String(date.getMinutes()).padStart(2, '0');
          el.textContent = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes;
        });
        </script>

        <!-- Change Password -->
        <div class="card">
            <h3 class="section-title">Change Password</h3>
            <form id="changePassword" method="post" action="./auth/change_password.php">
                <div class="form-group">
                    <input type="password" name="current_password" class="form-control" placeholder="Current password" required>
                </div>
                <div class="form-group">
                    <input type="password" name="new_password" class="form-control" placeholder="New password (min 8 chars)" required minlength="8">
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
            <?php if (isset($_SESSION['password_status'])): ?>
                <div class="alert <?php echo strpos($_SESSION['password_status'], 'successfully') !== false ? 'alert-success' : 'alert-error'; ?>" style="margin-top: 1rem;">
                    <?php echo htmlspecialchars($_SESSION['password_status']); unset($_SESSION['password_status']); ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
