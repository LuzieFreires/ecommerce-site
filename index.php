<?php
require_once 'db.php';

$stmt = $pdo->query('SELECT * FROM products');
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple eCommerce</title>
    <style>
        .product { border: 1px solid #ddd; padding: 10px; margin: 10px; display: inline-block; }
        .cart-form { margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Our Products</h1>
    <?php foreach($products as $product): ?>
        <div class="product">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="200">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <p><?php echo htmlspecialchars($product['description']); ?></p>
            <p>Price: $<?php echo number_format($product['price'], 2); ?></p>
            <form class="cart-form" action="checkout.php" method="post">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="number" name="quantity" value="1" min="1">
                <button type="submit">Buy Now</button>
            </form>
        </div>
    <?php endforeach; ?>
</body>
</html>