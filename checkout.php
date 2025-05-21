<?php
require_once 'db.php';
require_once 'vendor/autoload.php'; // You'll need to install Authorize.net SDK via Composer

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get product details
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$_POST['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $quantity = (int)$_POST['quantity'];
    $total = $product['price'] * $quantity;
    
    if (isset($_POST['process_payment'])) {
        // Process payment through Authorize.net
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName(AUTHORIZENET_API_LOGIN_ID);
        $merchantAuthentication->setTransactionKey(AUTHORIZENET_TRANSACTION_KEY);

        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($_POST['card_number']);
        $creditCard->setExpirationDate($_POST['exp_year'] . "-" . $_POST['exp_month']);
        $creditCard->setCardCode($_POST['cvv']);

        $paymentType = new AnetAPI\PaymentType();
        $paymentType->setCreditCard($creditCard);

        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($total);
        $transactionRequestType->setPayment($paymentType);

        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setTransactionRequest($transactionRequestType);

        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
            // Save order to database
            $pdo->beginTransaction();
            try {
                // Create order
                $stmt = $pdo->prepare('INSERT INTO orders (guest_email, total_amount, shipping_address, transaction_id, status) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([
                    $_POST['email'],
                    $total,
                    $_POST['address'],
                    $response->getTransactionResponse()->getTransId(),
                    'completed'
                ]);
                $orderId = $pdo->lastInsertId();

                // Create order item
                $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
                $stmt->execute([$orderId, $product['id'], $quantity, $product['price']]);

                $pdo->commit();
                header('Location: thank_you.php');
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
            <input type="text" name="card_number" required>
        </div>
        
        <div class="form-group">
            <label>Expiration Date:</label>
            <select name="exp_month" required>
                <?php for($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></option>
                <?php endfor; ?>
            </select>
            <select name="exp_year" required>
                <?php for($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>CVV:</label>
            <input type="text" name="cvv" required>
        </div>
        
        <div class="form-group">
            <label>Total Amount: $<?php echo number_format($total, 2); ?></label>
        </div>
        
        <button type="submit" name="process_payment">Complete Purchase</button>
    </form>
</body>
</html>