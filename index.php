<?php
require __DIR__ . '/includes/init.php';

$categoryId = (int) ($_GET['category'] ?? 0);
$keyword = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? '';

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();

$sql = 'SELECT id, name, price, image FROM products';
$where = [];
$params = [];

if ($categoryId > 0) {
    $where[] = 'category_id = ?';
    $params[] = $categoryId;
}
if ($keyword !== '') {
    $where[] = 'name LIKE ?';
    $params[] = '%' . $keyword . '%';
}
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

if ($sort === 'price_asc') {
    $sql .= ' ORDER BY price ASC';
} elseif ($sort === 'price_desc') {
    $sql .= ' ORDER BY price DESC';
} else {
    $sql .= ' ORDER BY id';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Trang chủ';
require __DIR__ . '/includes/header.php';
?>
<section>
    <h2>Sản phẩm</h2>

    <form action="<?= url('index.php') ?>" method="get" class="search-form">
        <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="Tìm sản phẩm...">
        <?php if ($categoryId > 0): ?>
            <input type="hidden" name="category" value="<?= $categoryId ?>">
        <?php endif; ?>
        
        <select name="sort">
            <option value="">Sắp xếp mặc định</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
        </select>
        
        <button type="submit" class="btn">Tìm</button>
    </form>

    <p class="category-bar">
        <a href="<?= url('index.php') ?>" class="<?= $categoryId === 0 ? 'active' : '' ?>">Tất cả</a>
        <?php foreach ($categories as $category): ?>
            <a href="<?= url('index.php?category=' . $category['id']) ?>"
               class="<?= $categoryId === $category['id'] ? 'active' : '' ?>"><?= e($category['name']) ?></a>
        <?php endforeach; ?>
    </p>

    <p><strong>Tìm thấy <?= count($products) ?> sản phẩm</strong></p>

    <?php if (!$products): ?>
        <p>Không tìm thấy sản phẩm nào.</p>
    <?php endif; ?>

    <div class="product-list">
        <?php foreach ($products as $product): ?>
            <article class="product-card">
                <img src="<?= url($product['image']) ?>" alt="<?= e($product['name']) ?>">
                <h3><?= e($product['name']) ?></h3>
                <p class="price"><?= format_price($product['price']) ?></p>
                <a href="<?= url('product.php?id=' . $product['id']) ?>" class="btn">Xem chi tiết</a>
                <form action="<?= url('cart-add.php') ?>" method="post" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <button type="submit" class="btn btn-accent">Thêm vào giỏ</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>