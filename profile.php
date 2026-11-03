<?php
require __DIR__ . '/includes/init.php';
require_login();

$errors = [];
$user_id = current_user()['id'];

// Lấy thông tin hiện tại từ CSDL để điền sẵn vào form
$stmt = $pdo->prepare('SELECT fullname, phone, address FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$account = $stmt->fetch();

$fullname = $account['fullname'];
$phone = $account['phone'];
$address = $account['address'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Kiểm tra dữ liệu
    if (mb_strlen($fullname) < 2 || mb_strlen($fullname) > 100) {
        $errors['fullname'] = 'Họ tên phải có từ 2 đến 100 ký tự.';
    }
    if (!preg_match('/^0[0-9]{9}$/', $phone)) {
        $errors['phone'] = 'Số điện thoại gồm 10 chữ số, bắt đầu bằng số 0.';
    }
    
    // Nếu không có lỗi, lưu vào bảng users
    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE users SET fullname = ?, phone = ?, address = ? WHERE id = ?');
        $stmt->execute([$fullname, $phone, $address, $user_id]);
        
        // Cập nhật lại tên mới trong Session để đổi tên trên thanh điều hướng
        $_SESSION['user']['fullname'] = $fullname;
        
        set_flash('success', 'Cập nhật thông tin thành công.');
        redirect('profile.php');
    }
}

$pageTitle = 'Tài khoản của tôi';
require __DIR__ . '/includes/header.php';
?>
<h2>Tài khoản của tôi</h2>

<form action="<?= url('profile.php') ?>" method="post" class="form">
    <?= csrf_field() ?>
    <p>
        <label for="fullname">Họ và tên</label><br>
        <input type="text" id="fullname" name="fullname" value="<?= e($fullname) ?>" required>
        <span class="error"><?= e($errors['fullname'] ?? '') ?></span>
    </p>
    <p>
        <label for="phone">Số điện thoại</label><br>
        <input type="tel" id="phone" name="phone" value="<?= e($phone) ?>" required>
        <span class="error"><?= e($errors['phone'] ?? '') ?></span>
    </p>
    <p>
        <label for="address">Địa chỉ</label><br>
        <textarea id="address" name="address" rows="3"><?= e($address) ?></textarea>
    </p>
    <p>
        <button type="submit" class="btn btn-accent">Lưu thay đổi</button>
    </p>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>