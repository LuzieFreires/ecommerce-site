<?php
require_once('../config/db.php');

$stmt = $pdo->query('SELECT * FROM products');
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>Products</title></head>
<body>
<h1>Products</h1>

<?php foreach ($products as $p): ?>
    <h2><?php echo htmlspecialchars($p['name']); ?></h2>
    <p><?php echo htmlspecialchars($p['description']); ?></p>
    <p>Price: $<?php echo number_format($p['price'], 2); ?></p>
    <form action="../checkout.php" method="post">
        <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
        Quantity: <input type="number" name="quantity" value="1" min="1">
        <button type="submit">Buy Now</button>
    </form>
    <hr>
<?php endforeach; ?>

</body>
</html>
