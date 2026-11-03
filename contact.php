<?php
require __DIR__ . '/includes/init.php';

$errors = [];
$fullname = '';
$email = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (mb_strlen($fullname) < 2) {
        $errors['fullname'] = 'Họ tên quá ngắn.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không hợp lệ.';
    }
    if (mb_strlen($subject) < 5) {
        $errors['subject'] = 'Tiêu đề cần ít nhất 5 ký tự.';
    }
    if (mb_strlen($message) < 10) {
        $errors['message'] = 'Nội dung tin nhắn cần ít nhất 10 ký tự.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO contacts (fullname, email, subject, message) VALUES (?, ?, ?, ?)');
        $stmt->execute([$fullname, $email, $subject, $message]);
        
        set_flash('success', 'Tin nhắn của bạn đã được gửi. Chúng tôi sẽ phản hồi sớm nhất.');
        redirect('contact.php');
    }
}

$pageTitle = 'Liên hệ';
require __DIR__ . '/includes/header.php';
?>
<h2>Liên hệ với chúng tôi</h2>
<form action="<?= url('contact.php') ?>" method="post" class="form">
    <?= csrf_field() ?>
    <p>
        <label for="fullname">Họ tên</label><br>
        <input type="text" id="fullname" name="fullname" value="<?= e($fullname) ?>" required>
        <span class="error"><?= e($errors['fullname'] ?? '') ?></span>
    </p>
    <p>
        <label for="email">Email</label><br>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
        <span class="error"><?= e($errors['email'] ?? '') ?></span>
    </p>
    <p>
        <label for="subject">Tiêu đề</label><br>
        <input type="text" id="subject" name="subject" value="<?= e($subject) ?>" required>
        <span class="error"><?= e($errors['subject'] ?? '') ?></span>
    </p>
    <p>
        <label for="message">Nội dung</label><br>
        <textarea id="message" name="message" rows="5" required><?= e($message) ?></textarea>
        <span class="error"><?= e($errors['message'] ?? '') ?></span>
    </p>
    <p>
        <button type="submit" class="btn btn-accent">Gửi tin nhắn</button>
    </p>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>