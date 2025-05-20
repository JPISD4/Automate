
<?php
session_start();

$usersFile = 'users.json';
if (!file_exists($usersFile)) {
    // Create a default admin user with username 'isd' and password 'isd'
    $defaultPassword = password_hash('isd', PASSWORD_DEFAULT);
    file_put_contents($usersFile, json_encode(['isd' => ['password' => $defaultPassword, 'role' => 'admin']], JSON_PRETTY_PRINT));
}
$users = json_decode(file_get_contents($usersFile), true);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (
        isset($users[$username]) &&
        is_array($users[$username]) &&
        isset($users[$username]['password']) &&
        password_verify($password, $users[$username]['password'])
    ) {
        $_SESSION['username'] = $username;
        header('Location: index.php');
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Error Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<div class="center-screen">
    <div class="login-popout-card">
        <div class="card-body p-4">
            <h2 class="login-title">Login to EMS</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" id="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" id="password" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Login</button>
                <button type="button" class="btn btn-secondary w-100 mt-2" onclick="history.back()">Back</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
