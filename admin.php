<?php
// admin.php
session_start();
require_once __DIR__ . '/config.php';

requireLogin();
requireAdmin();

$errors = [];
$success = null;

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = trim($_POST['price'] ?? '');
$image = trim($_POST['image'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($name === '') {
        $errors[] = 'Please enter a name for the product.';
    }
    if ($description === '') {
        $errors[] = 'Please enter a description.';
    }
    if ($price === '' || !is_numeric($price) || (float) $price < 0) {
        $errors[] = 'Please enter a valid price.';
    }
    if ($image === '') {
        $errors[] = 'Please enter an image URL.';
    }

    if (empty($errors)) {
        $storedImage = $image;
        if (preg_match('#^https?://#i', $image)) {
            $downloaded = downloadImageFromUrl($image);
            if ($downloaded === false) {
                $errors[] = 'Could not download the image. Make sure it is a valid image URL (jpg/png/gif/webp).';
            } else {
                $storedImage = $downloaded;
            }
        }

        if (empty($errors)) {
            $stmt = $mysqli->prepare('INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssds', $name, $description, $price, $storedImage);
            $stmt->execute();
            $stmt->close();

            $success = 'Product added successfully.';
            $name = '';
            $description = '';
            $price = '';
            $image = '';
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin - Add Product</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="container">
    <header class="header">
      <div>
        <h1>Admin</h1>
        <p>Add a new product to the store.</p>
      </div>
      <div style="display: flex; gap: 10px; align-items: center;">
        <a class="button secondary" href="index.php">Back to shop</a>
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

    <?php if ($success): ?>
      <div class="alert" style="background: #daf7dc; color: #0b6623;">
        <?php echo esc($success); ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <form method="post" action="admin.php">
        <div class="form-group">
          <label for="name">Name</label>
          <input id="name" name="name" type="text" value="<?php echo esc($name); ?>" required />
        </div>
        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description" rows="4" required><?php echo esc($description); ?></textarea>
        </div>
        <div class="form-group">
          <label for="price">Price</label>
          <input id="price" name="price" type="number" step="0.01" min="0" value="<?php echo esc($price); ?>" required />
        </div>
        <div class="form-group">
          <label for="image">Image URL</label>
          <input id="image" name="image" type="url" value="<?php echo esc($image); ?>" required />
        </div>
        <button class="button" type="submit">Add product</button>
      </form>
    </div>
  </div>
</body>
</html>
