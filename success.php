<?php
// success.php
require_once __DIR__ . '/config.php';

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
$order = null;
$items = [];

if ($orderId > 0) {
    $stmt = $mysqli->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order) {
        $stmt = $mysqli->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?');
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Order Completed</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="container">
    <header class="header">
      <div>
        <h1>Order Complete</h1>
        <p>Thank you for your purchase!</p>
      </div>
      <div>
        <a class="button secondary" href="index.php">Continue shopping</a>
      </div>
    </header>

    <?php if (!$order): ?>
      <div class="card">
        <p>We could not find your order. Please return to the <a href="index.php">shop</a>.</p>
      </div>
    <?php else: ?>
      <div class="card">
        <p><strong>Order #<?php echo (int) $order['id']; ?></strong></p>
        <p>Placed on: <?php echo esc($order['created_at']); ?></p>
        <p>Order total: <strong>$<?php echo number_format($order['total'], 2); ?></strong></p>
      </div>

      <div class="card">
        <h2>Items</h2>
        <table class="table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Qty</th>
              <th>Price</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?php echo esc($item['name']); ?></td>
                <td><?php echo (int) $item['quantity']; ?></td>
                <td>$<?php echo number_format($item['price'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
