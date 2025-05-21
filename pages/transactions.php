<?php
// Simple database connection
$db = new mysqli('localhost', 'root', '', 'ecommerce');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$result = $db->query("SELECT * FROM transactions ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transaction History</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status-completed { color: green; }
        .status-failed { color: red; }
        .success-message { color: green; font-weight: bold; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Transaction History</h1>
    
    <?php if (isset($_GET['payment']) && $_GET['payment'] === 'success'): ?>
        <p class="success-message">Thank you! Your payment was successful.</p>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Transaction ID</th>
                <th>Customer</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Card</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                <td><?php echo htmlspecialchars($row['customer_name']); ?><br><?php echo htmlspecialchars($row['customer_email']); ?></td>
                <td><?php echo htmlspecialchars($row['description']); ?></td>
                <td>$<?php echo number_format($row['amount'], 2); ?></td>
                <td>**** <?php echo htmlspecialchars($row['card_last4']); ?></td>
                <td class="status-<?php echo strtolower(htmlspecialchars($row['status'])); ?>">
                    <?php echo htmlspecialchars($row['status']); ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <p><a href="products.php">Continue Shopping</a></p>
</body>
</html>
