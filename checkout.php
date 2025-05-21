<?php
require_once ('config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$_POST['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $quantity = (int)$_POST['quantity'];
    $total = $product['price'] * $quantity;
    
    if (isset($_POST['process_payment'])) {
        $transaction_id = 'TRANS_' . time() . rand(1000, 9999);
        $payment_success = true;
        
        if ($payment_success) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO orders (guest_email, total_amount, shipping_address, transaction_id, status) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([
                    $_POST['email'],
                    $total,
                    $_POST['address'],
                    $transaction_id,
                    'completed'
                ]);
                $orderId = $pdo->lastInsertId();

                $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
                $stmt->execute([$orderId, $product['id'], $quantity, $product['price']]);

                $pdo->commit();
                header('Location: pages/thank_you.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Order processing failed";
            }
        } else {
            $error = "Payment processing failed";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <style>
        .form-group { margin: 10px 0; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Checkout</h1>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    
    <form method="post">
        <input type="hidden" name="product_id" value="<?php echo $_POST['product_id']; ?>">
        <input type="hidden" name="quantity" value="<?php echo $quantity; ?>">
        
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>Shipping Address:</label>
            <textarea name="address" required></textarea>
        </div>
        
        <div class="form-group">
            <label>Card Number:</label>
            <input type="text" name="card_number" value="4111111111111111" readonly>
            <small>(Demo: Using test card number)</small>
        </div>
        
        <div class="form-group">
            <label>Expiration Date:</label>
            <input type="text" value="12/25" readonly>
            <small>(Demo: Using test expiration)</small>
        </div>
        
        <div class="form-group">
            <label>CVV:</label>
            <input type="text" value="123" readonly>
            <small>(Demo: Using test CVV)</small>
        </div>
        
        <div class="form-group">
            <label>Total Amount: $<?php echo number_format($total, 2); ?></label>
        </div>
        
        <button type="submit" name="process_payment">Complete Purchase</button>
    </form>
</body>
</html>