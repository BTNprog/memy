<?php
// index.php
session_start();
require_once __DIR__ . '/config.php';

requireLogin();

$products = [];
$result = $mysqli->query('SELECT * FROM products ORDER BY id');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $result->free();
}

$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = array_sum(array_values($_SESSION['cart']));
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Simple Shop</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="container">
    <header class="header">
      <div>
        <h1>Memy Shop<h1>
        <p>Browse products and add them to your cart.</p>
      </div>
      <div style="display: flex; gap: 10px; align-items: center;">
        <a class="button secondary" href="cart.php">Cart (<?php echo $cartCount; ?>)</a>
        <?php if (isAdmin()): ?>
          <a class="button secondary" href="admin.php">Admin</a>
        <?php endif; ?>
        <a class="button secondary" href="logout.php">Logout</a>
      </div>
    </header>

    <div class="grid">
      <?php foreach ($products as $product): ?>
        <article class="card product">
          <img src="<?php echo esc(productImageUrl($product['image'])); ?>" alt="<?php echo esc($product['name']); ?>" />
          <h2><?php echo esc($product['name']); ?></h2>
          <p><?php echo esc($product['description']); ?></p>
          <p><strong>$<?php echo number_format($product['price'], 2); ?></strong></p>

          <form action="cart.php" method="post">
            <input type="hidden" name="action" value="add" />
            <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>" />
            <button class="button" type="submit">Add to cart</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
