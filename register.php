<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/db_connect.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username === '' || $password === '' || $password2 === '') {
        $error = '❌ Semua field harus diisi!';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = '❌ Username harus 3–50 karakter!';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = '❌ Username hanya boleh huruf, angka, dan underscore!';
    } elseif (strlen($password) < 6) {
        $error = '❌ Password minimal 6 karakter!';
    } elseif ($password !== $password2) {
        $error = '❌ Konfirmasi password tidak cocok!';
    } else {
        $conn = get_db_connection(true);
        $check = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $check->bind_param('s', $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = '❌ Username sudah digunakan!';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $role   = 'user';
            $stmt   = $conn->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $username, $hashed, $role);

            if ($stmt->execute()) {
                $success = '✅ Akun berhasil dibuat! Silakan login.';
            } else {
                $error = '❌ Gagal membuat akun, coba lagi.';
            }
            $stmt->close();
        }

        $check->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VEXO | Register</title>
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
                <p>Buat Akun Baru</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message show"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message show"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Masukkan username" autocomplete="off" required
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Minimal 6 karakter" required>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input type="password" name="password2" placeholder="Ulangi password" required>
                </div>
                <button type="submit" class="btn-login">Daftar →</button>
            </form>
            <?php endif; ?>

            <p class="auth-switch">Sudah punya akun? <a href="login.php">Login di sini</a></p>
        </div>
    </div>
</body>
</html>
