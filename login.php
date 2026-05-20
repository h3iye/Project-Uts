<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = '❌ Username dan password harus diisi!';
    } else {
        $conn = get_db_connection(true);
        $stmt = $conn->prepare('SELECT id, password, role FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $row['role'];
                $stmt->close();
                $conn->close();
                
                if ($row['role'] === 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            }
        }
        
        $stmt->close();
        $conn->close();
        $error = '❌ Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VEXO | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-orb login-orb-1"></div>
    <div class="login-orb login-orb-2"></div>
    <div class="login-orb login-orb-3"></div>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <div class="logo-icon"><span>👕</span></div>
                <h1>VEXO<span>.</span></h1>
                <p>Streetwear Apparel</p>
            </div>
            <?php if ($error): ?>
                <div id="errorMessage" class="error-message show"><?php echo htmlspecialchars($error); ?></div>
            <?php else: ?>
                <div id="errorMessage" class="error-message">❌ Invalid username or password!</div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter your username" autocomplete="off" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn-login">Login →</button>
            </form>
            <p class="auth-switch">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
        </div>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>