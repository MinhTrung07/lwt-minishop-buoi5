<?php
require __DIR__ . '/includes/init.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

// Kiểm tra quyền sở hữu đơn
if (!$order || ($order['user_id'] !== current_user()['id'] && !is_admin())) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy đơn hàng';
    require __DIR__ . '/includes/header.php';
    echo '<h2>Không tìm thấy đơn hàng</h2>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// BÀI TẬP 1: Xử lý Hủy đơn bằng giao dịch (transaction)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    if ($order['status'] === 'pending') {
        try {
            $pdo->beginTransaction();
            
            // Đổi trạng thái thành cancelled
            $stmtUpdate = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmtUpdate->execute([$id]);
            
            // Lấy các sản phẩm trong đơn để cộng lại tồn kho
            $stmtItems = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ? FOR UPDATE');
            $stmtItems->execute([$id]);
            $itemsToCancel = $stmtItems->fetchAll();
            
            $stmtAddStock = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
            foreach ($itemsToCancel as $item) {
                $stmtAddStock->execute([$item['quantity'], $item['product_id']]);
            }
            
            $pdo->commit();
            set_flash('success', 'Đã hủy đơn hàng thành công.');
            redirect('order-detail.php?id=' . $id);
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Có lỗi xảy ra khi hủy đơn.');
        }
    }
}

$stmt = $pdo->prepare('SELECT product_name, price, quantity FROM order_items WHERE order_id = ? ORDER BY id');
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$pageTitle = 'Đơn hàng #' . $order['id'];
require __DIR__ . '/includes/header.php';
?>
<h2>Đơn hàng #<?= $order['id'] ?></h2>

<table>
    <tbody>
        <tr>
            <th>Ngày đặt</th>
            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
        </tr>
        <tr>
            <th>Trạng thái</th>
            <td>
                <?= e(order_status_label($order['status'])) ?>
                <!-- Nút hủy đơn hiển thị nếu trạng thái là pending -->
                <?php if ($order['status'] === 'pending'): ?>
                    <form action="<?= url('order-detail.php?id=' . $order['id']) ?>" method="post" style="display:inline; margin-left: 15px;">
                        <?= csrf_field() ?>
                        <button type="submit" name="cancel_order" value="1" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?');" style="padding: 4px 10px; font-size: 0.9em;">Hủy đơn</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Người nhận</th>
            <td><?= e($order['fullname']) ?> - <?= e($order['phone']) ?></td>
        </tr>
        <tr>
            <th>Địa chỉ</th>
            <td><?= e($order['address']) ?></td>
        </tr>
        <tr>
            <th>Ghi chú</th>
            <td><?= nl2br(e($order['note'])) ?></td>
        </tr>
    </tbody>
</table>

<h3>Sản phẩm</h3>
<table>
    <thead>
        <tr>
            <th>Sản phẩm</th>
            <th>Đơn giá</th>
            <th>Số lượng</th>
            <th>Thành tiền</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?></td>
                <td><?= format_price($item['price']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= format_price($item['price'] * $item['quantity']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3">Tổng cộng</th>
            <th><?= format_price($order['total']) ?></th>
        </tr>
    </tfoot>
</table>
<p><a href="<?= url(is_admin() ? 'admin/orders.php' : 'orders.php') ?>">&laquo; Danh sách đơn hàng</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>