<?php
require_once '../config/db.php'; 

$stmt = $pdo->query('SELECT * FROM products');
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>eCommerce Site</title>
    <style>
        .product { border: 1px solid #ddd; padding: 10px; margin: 10px; display: inline-block; }
        .cart-form { margin-top: 10px; }
    </style>
</head>
<body>
  <h1>Our Products</h1>
    <?php foreach($products as $product): ?>
        <div class="product">
            <h2><?= htmlspecialchars($product['name']) ?></h2>
            <p><?= htmlspecialchars($product['description']) ?></p>
            <p>Price: $<?= number_format($product['price'], 2) ?></p>
            <form class="cart-form" action="../checkout.php" method="post">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <input type="number" name="quantity" value="1" min="1" required>
                <button type="submit">Buy Now</button>
            </form>
        </div>
    <?php endforeach; ?>
</body>
</html>