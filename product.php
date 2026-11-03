<?php
require __DIR__ . '/includes/init.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.id = ?'
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy sản phẩm';
    require __DIR__ . '/includes/header.php';
    echo '<h2>Không tìm thấy sản phẩm</h2>';
    echo '<p><a href="' . url('index.php') . '">&laquo; Quay lại danh sách sản phẩm</a></p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$stmtRelated = $pdo->prepare('SELECT id, name, price, image FROM products WHERE category_id = ? AND id <> ? LIMIT 3');
$stmtRelated->execute([$product['category_id'], $product['id']]);
$relatedProducts = $stmtRelated->fetchAll();

$pageTitle = $product['name'];
require __DIR__ . '/includes/header.php';
?>
<article class="product-detail">
    <div class="product-image">
        <img src="<?= url($product['image']) ?>" alt="<?= e($product['name']) ?>">
    </div>

    <div class="product-info">
        <h2><?= e($product['name']) ?></h2>
        <p class="price">Giá: <strong><?= format_price($product['price']) ?></strong></p>
        <p><?= nl2br(e($product['description'])) ?></p>

        <h3>Thông tin sản phẩm</h3>
        <table>
            <tbody>
                <tr>
                    <th>Mã sản phẩm</th>
                    <td><?= $product['id'] ?></td>
                </tr>
                <tr>
                    <th>Danh mục</th>
                    <td><?= e($product['category_name']) ?></td>
                </tr>
                <tr>
                    <th>Tình trạng</th>
                    <td><?= $product['stock'] > 0 ? 'Còn ' . $product['stock'] . ' sản phẩm' : 'Hết hàng' ?></td>
                </tr>
            </tbody>
        </table>
        <?php if ($product['stock'] > 0): ?>
            <form action="<?= url('cart-add.php') ?>" method="post" class="cart-actions">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>">
                <button type="submit" class="btn btn-accent">Thêm vào giỏ</button>
            </form>
        <?php endif; ?>
        <p><a href="<?= url('index.php') ?>">&laquo; Quay lại danh sách sản phẩm</a></p>
    </div>
</article>

<?php if ($relatedProducts): ?>
    <section style="margin-top: 40px; border-top: 1px solid var(--border); padding-top: 20px;">
        <h3>Sản phẩm cùng danh mục</h3>
        <div class="product-list" style="grid-template-columns: repeat(3, 1fr);">
            <?php foreach ($relatedProducts as $relProduct): ?>
                <article class="product-card">
                    <img src="<?= url($relProduct['image']) ?>" alt="<?= e($relProduct['name']) ?>">
                    <h4 style="margin: 12px 0 4px; font-size: 1rem;"><?= e($relProduct['name']) ?></h4>
                    <p class="price" style="margin: 0 0 12px;"><?= format_price($relProduct['price']) ?></p>
                    <a href="<?= url('product.php?id=' . $relProduct['id']) ?>" class="btn" style="font-size: 0.9rem; padding: 6px 12px;">Xem chi tiết</a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>