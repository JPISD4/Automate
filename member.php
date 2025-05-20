
<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'isd') {
    header('Location: login.php');
    exit;
}

$usersFile = "users.json";
$users = [];
if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true) ?: [];
}

// Handle Add, Edit, Delete actions
$action = $_GET['action'] ?? '';
$message = '';
$error = '';

function save_users($usersFile, $users) {
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
}

// Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? 'user');

    if ($username === '' || $password === '') {
        $error = "Username and password are required.";
    } elseif (isset($users[$username])) {
        $error = "Username already exists.";
    } else {
        $users[$username] = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role
        ];
        save_users($usersFile, $users);
        $message = "User '$username' added successfully.";
    }
}

// Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $username = $_POST['edit_username'];
    $role = trim($_POST['role'] ?? 'user');
    $password = $_POST['password'] ?? '';

    if (!isset($users[$username]) || !is_array($users[$username])) {
        $error = "User does not exist or is malformed.";
    } else {
        if ($password !== '') {
            $users[$username]['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $users[$username]['role'] = $role;
        save_users($usersFile, $users);
        $message = "User '$username' updated successfully.";
    }
}

// Delete User
if ($action === 'delete' && isset($_GET['username'])) {
    $delUser = $_GET['username'];
    if ($delUser === 'isd') {
        $error = "Cannot delete the admin user.";
    } elseif (isset($users[$delUser])) {
        unset($users[$delUser]);
        save_users($usersFile, $users);
        $message = "User '$delUser' deleted.";
        // Prevent resubmission
        header("Location: member.php?msg=" . urlencode($message));
        exit;
    } else {
        $error = "User not found.";
    }
}

// For displaying messages after redirect
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// For editing
$editUser = null;
if ($action === 'edit' && isset($_GET['username']) && isset($users[$_GET['username']]) && is_array($users[$_GET['username']])) {
    $editUser = $_GET['username'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - Error Manager System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .modern-header {
            margin: 1.5rem 0 1.5rem 0;
            font-weight: bold;
            font-size: 2.2rem;
            text-align: center;
            background: linear-gradient(90deg, #BB40F0 0%, #BB40F0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
            letter-spacing: 2px;
            text-shadow: 0 2px 12px rgba(180,180,255,0.08);
            user-select: none;
        }
        .search-box {
            max-width: 350px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="modern-header">User Management</h1>
    <div class="mb-3">
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to Main</a>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($editUser): ?>
        <!-- Edit User Form -->
        <div class="card mb-4">
            <div class="card-header">Edit User: <strong><?= htmlspecialchars($editUser) ?></strong></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="edit_username" value="<?= htmlspecialchars($editUser) ?>">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($editUser) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" name="password" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <option value="user" <?= (is_array($users[$editUser]) && ($users[$editUser]['role'] ?? 'user') === 'user') ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= (is_array($users[$editUser]) && ($users[$editUser]['role'] ?? 'user') === 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                    <a href="member.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Add User Form -->
        <div class="card mb-4">
            <div class="card-header">Add New User</div>
            <div class="card-body">
                <form method="post" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-success">Add User</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search Box -->
    <div class="search-box">
        <input type="text" id="userSearch" class="form-control" placeholder="Search user...">
    </div>

    <!-- User List Table -->
    <div class="card">
        <div class="card-header">Existing Users</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0" id="usersTable">
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Password Hash</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $uname => $udata): ?>
                    <tr>
                        <td><?= htmlspecialchars($uname) ?></td>
                        <td>
                            <?= is_array($udata) && isset($udata['role']) ? htmlspecialchars($udata['role']) : 'user' ?>
                        </td>
                        <td style="font-size:0.85em;word-break:break-all;">
                            <?= is_array($udata) && isset($udata['password']) ? htmlspecialchars($udata['password']) : '' ?>
                        </td>
                        <td>
                            <a href="member.php?action=edit&username=<?= urlencode($uname) ?>" class="btn btn-sm btn-primary">Edit</a>
                            <?php if ($uname !== 'isd'): ?>
                                <a href="member.php?action=delete&username=<?= urlencode($uname) ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to delete user <?= htmlspecialchars($uname) ?>?');">Delete</a>
                            <?php else: ?>
                                <span class="text-muted">Protected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="4" class="text-center">No users found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="cip-footer" style="text-align:center;margin-top:2rem;color:#888;font-size:0.95rem;">
    &copy; 2025 CIP/LTI - Toshiba Information Equipment Phil. Inc.
</div>
<script>
    // Simple client-side search for the user table
    document.getElementById('userSearch').addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#usersTable tbody tr');
        rows.forEach(row => {
            const username = row.cells[0].textContent.toLowerCase();
            if (username.indexOf(filter) > -1) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>
