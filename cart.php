<?php
// cart.php
session_start();
require_once __DIR__ . '/config.php';

requireLogin();

$errors = [];
$success = null;

function redirectToCart(): void
{
    header('Location: cart.php');
    exit;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? null;

if ($action === 'add') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    if ($productId > 0) {
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + 1;
        $success = 'Product added to cart.';
    }
    redirectToCart();
}

if ($action === 'update') {
    foreach ($_POST['qty'] ?? [] as $id => $qty) {
        $id = (int) $id;
        $qty = max(0, (int) $qty);
        if ($id > 0) {
            if ($qty === 0) {
                unset($_SESSION['cart'][$id]);
            } else {
                $_SESSION['cart'][$id] = $qty;
            }
        }
    }
    $success = 'Cart updated.';
    redirectToCart();
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    $success = 'Cart cleared.';
    redirectToCart();
}

$cart = $_SESSION['cart'];
$cartItems = [];
$total = 0.0;

if (!empty($cart)) {
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $query = "SELECT * FROM products WHERE id IN ($ids)";
    $result = $mysqli->query($query);
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
            $cartItems[] = $row;
        }
        $result->free();
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Your Cart</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="container">
    <header class="header">
      <div>
        <h1>Your Cart</h1>
        <p>Review your items before checkout.</p>
      </div>
      <div style="display: flex; gap: 10px; align-items: center;">
        <?php $user = currentUser(); ?>
        <span style="font-size: 0.9rem; color: #fff;">Hello, <?php echo esc($user['name'] ?? 'Guest'); ?></span>
        <a class="button secondary" href="index.php">Continue shopping</a>
        <a class="button secondary" href="logout.php">Logout</a>
      </div>
    </header>

    <?php if ($success): ?>
      <div class="alert"><?php echo esc($success); ?></div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
      <div class="card">
        <p>Your cart is empty. <a href="index.php">Browse products</a>.</p>
      </div>
    <?php else: ?>
      <form method="post" action="cart.php">
        <input type="hidden" name="action" value="update" />

        <table class="table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Price</th>
              <th>Qty</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cartItems as $item): ?>
              <tr>
                <td>
                  <img src="<?php echo esc(productImageUrl($item['image'])); ?>" alt="<?php echo esc($item['name']); ?>" style="max-width: 80px; max-height: 80px; display: block; margin-bottom: 8px;" />
                  <?php echo esc($item['name']); ?>
                </td>
                <td>$<?php echo number_format($item['price'], 2); ?></td>
                <td>
                  <input type="number" name="qty[<?php echo (int) $item['id']; ?>]" value="<?php echo (int) $item['quantity']; ?>" min="0" style="width: 70px;" />
                </td>
                <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" style="text-align:right;"><strong>Total:</strong></td>
              <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
            </tr>
          </tfoot>
        </table>

        <div style="margin-top: 14px; display: flex; gap: 10px; flex-wrap: wrap;">
          <button class="button" type="submit">Update cart</button>
          <button class="button secondary" type="submit" name="action" value="clear">Clear cart</button>
          <a class="button" href="checkout.php">Proceed to Checkout</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
