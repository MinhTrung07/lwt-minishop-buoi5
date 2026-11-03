<?php
require __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$email = '';

// BÀI TẬP TỔNG KẾT MỤC 5: KHÓA ĐĂNG NHẬP
$max_attempts = 5;
$lockout_time = 5 * 60; // 5 phút tính bằng giây

// Kiểm tra xem user có đang bị khóa không
$is_locked = false;
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $max_attempts) {
    $time_since_last_attempt = time() - $_SESSION['last_attempt_time'];
    if ($time_since_last_attempt < $lockout_time) {
        $is_locked = true;
        $remaining = ceil(($lockout_time - $time_since_last_attempt) / 60);
        $error = "Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau $remaining phút nữa.";
    } else {
        // Đã hết thời gian khóa, reset lại số lần đếm
        $_SESSION['login_attempts'] = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, fullname, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Đăng nhập thành công: xóa lịch sử đăng nhập sai
        unset($_SESSION['login_attempts']);
        unset($_SESSION['last_attempt_time']);
        
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => $user['id'],
            'fullname' => $user['fullname'],
            'role'     => $user['role'],
        ];
        
        $returnUrl = $_SESSION['return_url'] ?? url('index.php');
        unset($_SESSION['return_url']);
        
        set_flash('success', 'Xin chào ' . $user['fullname'] . '!');
        header('Location: ' . $returnUrl);
        exit;
    }

    // Đăng nhập sai: Tăng bộ đếm
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['last_attempt_time'] = time();

    if ($_SESSION['login_attempts'] >= $max_attempts) {
        $error = "Bạn đã đăng nhập sai $max_attempts lần. Tài khoản bị tạm khóa 5 phút.";
        $is_locked = true;
    } else {
        $error = 'Email hoặc mật khẩu không đúng. Cảnh báo: Bạn còn ' . ($max_attempts - $_SESSION['login_attempts']) . ' lần thử.';
    }
}

$pageTitle = 'Đăng nhập';
require __DIR__ . '/includes/header.php';
?>

<h2>Đăng nhập</h2>

<form action="<?= url('login.php') ?>" method="post" class="form">
    <?= csrf_field() ?>
    <?php if ($error): ?>
        <p class="alert alert-error"><?= e($error) ?></p>
    <?php endif; ?>
    
    <p>
        <label for="email">Email</label><br>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required <?= $is_locked ? 'disabled' : '' ?>>
    </p>
    <p>
        <label for="password">Mật khẩu</label><br>
        <input type="password" id="password" name="password" required <?= $is_locked ? 'disabled' : '' ?>>
    </p>
    <p>
        <button type="submit" class="btn" <?= $is_locked ? 'disabled' : '' ?>>Đăng nhập</button>
    </p>
    <p>Chưa có tài khoản? <a href="<?= url('register.php') ?>">Đăng ký</a></p>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>