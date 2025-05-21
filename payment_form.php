<?php
require 'authnet_aim.php';

// Simple database connection
$db = new mysqli('localhost', 'root', '', 'payment_processor');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authnet_api_login = "5t8T6g4tS76";
    $authnet_api_trans = "987ZE7TcC5bvp4m4";
    $aim = new AuthnetAIM($authnet_api_login, $authnet_api_trans, true);

    $pay_details = [
        "x_delim_data"     => "TRUE",
        "x_delim_char"     => "|",
        "x_relay_response" => "FALSE",
        "x_url"            => "FALSE",
        "x_version"        => "3.1",
        "x_method"         => "CC",
        "x_type"           => "AUTH_CAPTURE",
        "x_card_num"       => $_POST['card_number'],
        "x_exp_date"       => $_POST['exp_date'],
        "x_card_code"      => $_POST['cvv'],
        "x_amount"         => $_POST['amount'],
        "x_description"    => $_POST['description'],
        "x_first_name"     => $_POST['first_name'],
        "x_last_name"      => $_POST['last_name'],
        "x_email"         => $_POST['email']
    ];

    try {
        $aim->do_apicall($pay_details);
        
        if ($aim->isApproved()) {

            $transaction_id = $aim->getTransactionID();
            $amount = $_POST['amount'];
            $description = $_POST['description'];
            $customer_name = $_POST['first_name'] . ' ' . $_POST['last_name'];
            $customer_email = $_POST['email'];
            $card_last4 = substr($_POST['card_number'], -4);
            $status = 'approved';
            $auth_code = $aim->getAuthCode();
            
            $stmt = $db->prepare("INSERT INTO transactions (
                transaction_id, amount, description, customer_name, 
                customer_email, card_last4, status, auth_code
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param(
                "sdssssss",
                $transaction_id,
                $amount,
                $description,
                $customer_name,
                $customer_email,
                $card_last4,
                $status,
                $auth_code
            );
            
            $stmt->execute();
            $message = "Payment successful! Transaction ID: " . $transaction_id;
        } else {
            $message = "Payment failed: " . $aim->getResponseText();
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Form</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #4CAF50; color: white; padding: 10px 15px; border: none; cursor: pointer; }
        button:hover { background: #45a049; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #dff0d8; color: #3c763d; }
        .error { background: #f2dede; color: #a94442; }
    </style>
</head>
<body>
    <h1>Make a Payment</h1>
    
    <?php if (!empty($message)): ?>
        <div class="message <?php echo (isset($aim) && $aim->isApproved()) ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" required>
        </div>
        
        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" id="description" name="description" required>
        </div>
        
        <div class="form-group">
            <label for="amount">Amount ($)</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
        </div>
        
        <div class="form-group">
            <label for="card_number">Card Number</label>
            <input type="text" id="card_number" name="card_number" required>
        </div>
        
        <div class="form-group">
            <label for="exp_date">Expiration Date (MM/YY)</label>
            <input type="text" id="exp_date" name="exp_date" placeholder="MM/YY" required>
        </div>
        
        <div class="form-group">
            <label for="cvv">CVV</label>
            <input type="text" id="cvv" name="cvv" required>
        </div>
        
        <button type="submit">Submit Payment</button>
    </form>
    
    <p><a href="transactions.php">View All Transactions</a></p>
</body>
</html>