<?php
require __DIR__ . '/includes/init.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Lấy thông tin user hiện tại từ DB để lấy mật khẩu đã băm
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([current_user()['id']]);
    $user_db = $stmt->fetch();

    // 1. Kiểm tra mật khẩu hiện tại có đúng không
    if (!password_verify($current_password, $user_db['password_hash'])) {
        $errors['current_password'] = 'Mật khẩu hiện tại không đúng.';
    }
    
    // 2. Kiểm tra mật khẩu mới
    if (strlen($new_password) < 6) {
        $errors['new_password'] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    }
    
    // 3. Kiểm tra nhập lại mật khẩu mới
    if ($new_password !== $confirm_password) {
        $errors['confirm_password'] = 'Nhập lại mật khẩu mới không khớp.';
    }

    // Nếu không có lỗi thì lưu vào DB
    if (!$errors) {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$new_hash, current_user()['id']]);
        
        set_flash('success', 'Đã đổi mật khẩu thành công. Vui lòng đăng nhập lại bằng mật khẩu mới.');
        
        // Đổi mật khẩu xong bắt đăng xuất
        unset($_SESSION['user']);
        session_regenerate_id(true);
        redirect('login.php');
    }
}

$pageTitle = 'Đổi mật khẩu';
require __DIR__ . '/includes/header.php';
?>
<h2>Đổi mật khẩu</h2>

<form action="<?= url('change-password.php') ?>" method="post" class="form">
    <?= csrf_field() ?>
    <p>
        <label for="current_password">Mật khẩu hiện tại</label><br>
        <input type="password" id="current_password" name="current_password" required>
        <span class="error"><?= e($errors['current_password'] ?? '') ?></span>
    </p>
    <p>
        <label for="new_password">Mật khẩu mới</label><br>
        <input type="password" id="new_password" name="new_password" required minlength="6">
        <span class="error"><?= e($errors['new_password'] ?? '') ?></span>
    </p>
    <p>
        <label for="confirm_password">Nhập lại mật khẩu mới</label><br>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
        <span class="error"><?= e($errors['confirm_password'] ?? '') ?></span>
    </p>
    <p>
        <button type="submit" class="btn btn-accent">Đổi mật khẩu</button>
        <a href="<?= url('profile.php') ?>">Quay lại tài khoản</a>
    </p>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>