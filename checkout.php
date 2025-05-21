<?php
require_once 'config/db.php';

$error = '';
$total = 0;
$product = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $error = 'Product not found.';
    } else {
        $total = $product['price'] * $quantity;
    }

    if (isset($_POST['process_payment']) && !$error) {
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (!$email || !$address) {
            $error = 'Email and address are required.';
        } else {
            $transaction_id = 'TRANS_' . time() . rand(1000, 9999);

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO orders (guest_email, total_amount, shipping_address, transaction_id, status) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$email, $total, $address, $transaction_id, 'completed']);
                $orderId = $pdo->lastInsertId();

                $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
                $stmt->execute([$orderId, $product['id'], $quantity, $product['price']]);

                $stmt = $pdo->prepare('INSERT INTO transactions (transaction_id, customer_name, customer_email, description, amount, card_last4, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([
                    $transaction_id,
                    $email,
                    $email,
                    "Purchase of {$product['name']} x $quantity",
                    $total,
                    '1234', 
                    'completed'
                ]);

                $pdo->commit();

                header('Location: pages/thank_you.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Order processing failed.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
</head>
<body>
<h1>Checkout</h1>

<?php if ($error): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if ($product): ?>
<form method="post">
    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
    <input type="hidden" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>">

    <p>Product: <?php echo htmlspecialchars($product['name']); ?></p>
    <p>Quantity: <?php echo htmlspecialchars($quantity); ?></p>
    <p>Total: $<?php echo number_format($total, 2); ?></p>

    <p>Email:<br><input type="email" name="email" required></p>
    <p>Shipping Address:<br><textarea name="address" required></textarea></p>

    <p>Card Number:<br><input type="text" name="card_number"></p>
    <p>Expiration Date (MM/YY):<br><input type="text" name="expiration"></p>
    <p>CVV:<br><input type="text" name="cvv"></p>

    <button type="submit" name="process_payment">Complete Purchase</button>
</form>
<?php else: ?>
    <p>Invalid product selection.</p>
<?php endif; ?>

</body>
</html>
