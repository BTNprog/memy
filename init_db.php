<?php
// init_db.php
// Run this once to create the database schema and sample products.

require_once __DIR__ . '/config.php';

// Create database if it doesn't exist (requires permission).
$root = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($root->connect_errno) {
    die('Could not connect to MySQL: ' . $root->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$root->query($sql)) {
    die('Error creating database: ' . $root->error);
}

// Ensure we are using the correct DB connection.
$mysqli->select_db(DB_NAME);

$queries = [
    // users table (for authentication)
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // products table
    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        image VARCHAR(255) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // orders table
    "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // order_items table
    "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
];

foreach ($queries as $q) {
    if (!$mysqli->query($q)) {
        die('Schema error: ' . $mysqli->error);
    }
}

// Insert sample products (no duplicates)
$sampleProducts = [
    [
        'name' => 'Classic T-Shirt',
        'description' => 'A comfortable cotton t-shirt available in multiple sizes.',
        'price' => '19.99',
        'image' => 'https://via.placeholder.com/400x300?text=T-Shirt',
    ],
    [
        'name' => 'Sneaker Shoes',
        'description' => 'Lightweight sneakers for everyday wear.',
        'price' => '79.99',
        'image' => 'https://via.placeholder.com/400x300?text=Sneakers',
    ],
    [
        'name' => 'Coffee Mug',
        'description' => 'A ceramic mug to keep your coffee warm.',
        'price' => '12.50',
        'image' => 'https://via.placeholder.com/400x300?text=Mug',
    ],
    [
        'name' => 'Wireless Headphones',
        'description' => 'Noise-cancelling headphones with long battery life.',
        'price' => '129.99',
        'image' => 'https://via.placeholder.com/400x300?text=Headphones',
    ],
    [
        'name' => 'Stainless Water Bottle',
        'description' => 'Keeps drinks cold or hot for hours.',
        'price' => '14.99',
        'image' => 'https://via.placeholder.com/400x300?text=Water+Bottle',
    ],
    [
        'name' => 'Notebook',
        'description' => 'A 120-page ruled notebook for notes and sketches.',
        'price' => '9.99',
        'image' => 'https://via.placeholder.com/400x300?text=Notebook',
    ],
];

$stmt = $mysqli->prepare('SELECT COUNT(*) FROM products WHERE name = ?');
$insert = $mysqli->prepare('INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)');

foreach ($sampleProducts as $p) {
    $stmt->bind_param('s', $p['name']);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->free_result();

    if ($count === 0) {
        $insert->bind_param('ssds', $p['name'], $p['description'], $p['price'], $p['image']);
        $insert->execute();
    }
}

$stmt->close();
$insert->close();

// Insert a sample user (no duplicates)
$sampleUserEmail = 'user@example.com';
$sampleUserName = 'Sample User';
$sampleUserPassword = 'password';
$sampleUserHash = password_hash($sampleUserPassword, PASSWORD_DEFAULT);

$userCheck = $mysqli->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
$userInsert = $mysqli->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
$userCheck->bind_param('s', $sampleUserEmail);
$userCheck->execute();
$userCheck->bind_result($userCount);
$userCheck->fetch();
$userCheck->free_result();

if ($userCount === 0) {
    $userInsert->bind_param('sss', $sampleUserName, $sampleUserEmail, $sampleUserHash);
    $userInsert->execute();
}

$userCheck->close();
$userInsert->close();

echo "✅ Database initialized successfully.\n";
echo "Navigate to index.php to start shopping.\n";
