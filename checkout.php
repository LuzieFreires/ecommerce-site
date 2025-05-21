<?php
require_once 'config/db.php';

function callAuthorizeNetApi($request) {
    $ch = curl_init("https://apitest.authorize.net/xml/v1/request.api"); // sandbox URL
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        curl_close($ch);
        return ['error' => curl_error($ch)];
    }
    curl_close($ch);
    return json_decode($response, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get product info
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$_POST['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Product not found");
    }

    $quantity = max(1, (int)$_POST['quantity']);
    $total = $product['price'] * $quantity;

    // Validate required fields
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $card_number = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $expiration = $_POST['expiration'] ?? '';
    $cvv = $_POST['cvv'] ?? '';

    $error = '';

    if (!$email || !$address || !$card_number || !$expiration || !$cvv) {
        $error = "Please fill in all required fields.";
    }

    if (!$error) {
        $api_login_id = '5t8T6g4tS76';      
        $transaction_key = '987ZE7TcC5bvp4m4'; 


        // Format expiration date as YYYY-MM (allow MM/YY or MM/YYYY)
        if (preg_match('/^(\d{2})\/(\d{2,4})$/', $expiration, $m)) {
        $exp_year = strlen($m[2]) === 4 ? substr($m[2], 2) : $m[2];
        $expiration_formatted = "{$m[1]} $exp_year";
        } else {
        $expiration_formatted = $expiration;
        }

        $request = [
            "createTransactionRequest" => [
                "merchantAuthentication" => [
                    "name" => $api_login_id,
                    "transactionKey" => $transaction_key
                ],
                "transactionRequest" => [
                    "transactionType" => "authCaptureTransaction",
                    "amount" => number_format($total, 2, '.', ''),
                    "payment" => [
                        "creditCard" => [
                            "cardNumber" => $card_number,
                            "expirationDate" => $expiration_formatted,
                            "cardCode" => $cvv
                        ]
                    ],
                    "order" => [
                        "invoiceNumber" => "INV" . time(),
                        "description" => $product['name']
                    ],
                    "customer" => [
                        "email" => $email
                    ],
                    "billTo" => [
                        "firstName" => '', 
                        "lastName" => '',
                        "address" => $address,
                        "email" => $email
                    ]
                ]
            ]
        ];

        $response = callAuthorizeNetApi($request);

        if (isset($response['error'])) {
            $error = "Curl error: " . $response['error'];
        } elseif (isset($response['transactionResponse']['responseCode']) && $response['transactionResponse']['responseCode'] == "1") {
            $transId = $response['transactionResponse']['transId'];
            $authCode = $response['transactionResponse']['authCode'] ?? '';
            $avsResult = $response['transactionResponse']['avsResultCode'] ?? '';
            $cardLast4 = substr($card_number, -4);

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare('INSERT INTO orders (guest_email, total_amount, shipping_address, transaction_id, status) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$email, $total, $address, $transId, 'completed']);
                $orderId = $pdo->lastInsertId();

                $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
                $stmt->execute([$orderId, $product['id'], $quantity, $product['price']]);

                $stmt = $pdo->prepare('INSERT INTO transactions (transaction_id, amount, description, customer_name, customer_email, card_last4, status, auth_code, avs_response) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $transId,
                    $total,
                    $product['name'],
                    '', 
                    $email,
                    $cardLast4,
                    'approved',
                    $authCode,
                    $avsResult
                ]);

                $pdo->commit();
                header('Location: pages/thank_you.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Order processing failed: " . $e->getMessage();
            }
        } else {
            $errorMsg = $response['transactionResponse']['errors'][0]['errorText'] ?? 'Payment declined or error occurred';
            $error = "Payment failed: " . $errorMsg;
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
    <?php if (!empty($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($_POST['product_id'] ?? ''); ?>">
        <input type="hidden" name="quantity" value="<?php echo htmlspecialchars($quantity ?? 1); ?>">

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Shipping Address:</label>
            <textarea name="address" required><?php echo htmlspecialchars($address ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>Card Number:</label>
            <input type="text" name="card_number" required value="<?php echo htmlspecialchars($card_number ?? ''); ?>" maxlength="16" pattern="\d{13,16}">
        </div>

        <div class="form-group">
            <label>Expiration Date (MM/YY):</label>
            <input type="text" name="expiration" required value="<?php echo htmlspecialchars($expiration ?? ''); ?>" pattern="\d{2}/\d{2}" placeholder="MM/YY" title="Format: MM/YY">
        </div>

        <div class="form-group">
            <label>CVV:</label>
            <input type="text" name="cvv" required value="<?php echo htmlspecialchars($cvv ?? ''); ?>" maxlength="4" pattern="\d{3,4}">
        </div>

        <div class="form-group">
            <label>Total Amount: $<?php echo number_format($total ?? 0, 2); ?></label>
        </div>

        <button type="submit">Complete Purchase</button>
    </form>
</body>
</html>
