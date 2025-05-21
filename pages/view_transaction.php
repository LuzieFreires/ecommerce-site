<?php
require_once '../config/db.php';

try {
    $stmt = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC");
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching transactions: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transaction Records</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Transaction Records</h1>
    
    <?php if (count($transactions) > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Transaction ID</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Card (last 4)</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            <?php foreach ($transactions as $txn): ?>
            <tr>
                <td><?= htmlspecialchars($txn['id']) ?></td>
                <td><?= htmlspecialchars($txn['transaction_id']) ?></td>
                <td>$<?= number_format($txn['amount'], 2) ?></td>
                <td><?= htmlspecialchars($txn['description']) ?></td>
                <td><?= htmlspecialchars($txn['customer_name']) ?></td>
                <td><?= htmlspecialchars($txn['customer_email']) ?></td>
                <td><?= htmlspecialchars($txn['card_last4']) ?></td>
                <td><?= htmlspecialchars($txn['status']) ?></td>
                <td><?= htmlspecialchars($txn['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No transactions found.</p>
    <?php endif; ?>
</body>
</html>
