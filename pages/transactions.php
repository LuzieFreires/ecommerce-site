<?php
require_once('../config/db.php');

$stmt = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head><title>Transactions</title></head>
<body>
<h1>Transaction History</h1>

<?php if (count($transactions) > 0): ?>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>Date</th>
        <th>Transaction ID</th>
        <th>Customer</th>
        <th>Description</th>
        <th>Amount</th>
        <th>Card</th>
        <th>Status</th>
    </tr>
    <?php foreach ($transactions as $txn): ?>
    <tr>
        <td><?php echo htmlspecialchars($txn['created_at']); ?></td>
        <td><?php echo htmlspecialchars($txn['transaction_id']); ?></td>
        <td><?php echo htmlspecialchars($txn['customer_name']); ?><br><?php echo htmlspecialchars($txn['customer_email']); ?></td>
        <td><?php echo htmlspecialchars($txn['description']); ?></td>
        <td>$<?php echo number_format($txn['amount'], 2); ?></td>
        <td>**** <?php echo htmlspecialchars($txn['card_last4']); ?></td>
        <td><?php echo htmlspecialchars($txn['status']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
<p>No transactions found.</p>
<?php endif; ?>

<p><a href="products.php">Back to Products</a></p>

</body>
</html>
