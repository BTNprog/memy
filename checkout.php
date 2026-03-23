<?php
// checkout.php
session_start();
require_once __DIR__ . '/config.php';

requireLogin();

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: index.php');
    exit;
}

$errors = [];
$customerName = trim($_POST['name'] ?? '');
$customerEmail = trim($_POST['email'] ?? '');

// Load cart items for display and calculation
$items = [];
$total = 0.0;
$ids = implode(',', array_map('intval', array_keys($cart)));
$result = $mysqli->query("SELECT * FROM products WHERE id IN ($ids)");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $id = (int) $row['id'];
        $qty = (int) ($cart[$id] ?? 0);
        if ($qty <= 0) {
            continue;
        }
        $row['quantity'] = $qty;
        $row['subtotal'] = $qty * (float) $row['price'];
        $total += $row['subtotal'];
        $items[] = $row;
    }
    $result->free();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($customerName === '') {
        $errors[] = 'Please enter your name.';
    }
    if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare('INSERT INTO orders (customer_name, customer_email, total) VALUES (?, ?, ?)');
        $stmt->bind_param('ssd', $customerName, $customerEmail, $total);
        $stmt->execute();
        $orderId = $stmt->insert_id;
        $stmt->close();

        $itemStmt = $mysqli->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
        foreach ($items as $item) {
            $itemStmt->bind_param('iiid', $orderId, $item['id'], $item['quantity'], $item['price']);
            $itemStmt->execute();
        }
        $itemStmt->close();

        // Clear cart
        $_SESSION['cart'] = [];

        header('Location: success.php?order_id=' . $orderId);
        exit;
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Checkout</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="container">
    <header class="header">
      <div>
        <h1>Checkout</h1>
        <p>Enter your details and confirm your order.</p>
      </div>
      <div style="display: flex; gap: 10px; align-items: center;">
        <?php $user = currentUser(); ?>
        <span style="font-size: 0.9rem; color: #fff;">Hello, <?php echo esc($user['name'] ?? 'Guest'); ?></span>
        <a class="button secondary" href="cart.php">Back to cart</a>
        <a class="button secondary" href="logout.php">Logout</a>
      </div>
    </header>

    <?php if (!empty($errors)): ?>
      <div class="alert">
        <ul style="margin:0; padding-left: 18px;">
          <?php foreach ($errors as $error): ?>
            <li><?php echo esc($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card">
      <h2>Order Summary</h2>
      <table class="table">
        <thead>
          <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td style="display: flex; gap: 10px; align-items: center;">
                <img src="<?php echo esc($item['image']); ?>" alt="<?php echo esc($item['name']); ?>" style="width: 72px; height: 72px; object-fit: cover; border-radius: 6px;" />
                <span><?php echo esc($item['name']); ?></span>
              </td>
              <td><?php echo (int) $item['quantity']; ?></td>
              <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2" style="text-align:right;"><strong>Total:</strong></td>
            <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
          </tr>
        </tfoot>
      </table>

      <form method="post" action="checkout.php" style="margin-top: 18px;">
        <div class="form-group">
          <label for="name">Name</label>
          <input id="name" name="name" type="text" value="<?php echo esc($customerName); ?>" required />
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?php echo esc($customerEmail); ?>" required />
        </div>
        <button class="button" type="submit">Place Order</button>
      </form>
    </div>
  </div>
</body>
</html>
