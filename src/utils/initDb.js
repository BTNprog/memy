import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

const createDB = async () => {
  const connection = await mysql.createConnection({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
  });

  try {
    const dbName = process.env.DB_NAME || 'memy_shop';
    
    // Create database
    await connection.execute(
      `CREATE DATABASE IF NOT EXISTS \`${dbName}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`
    );
    console.log(`Database \`${dbName}\` created or already exists.`);

    // Create pool connected to the new database
    const pool = mysql.createPool({
      host: process.env.DB_HOST || 'localhost',
      user: process.env.DB_USER || 'root',
      password: process.env.DB_PASS || '',
      database: dbName,
    });

    // Create tables
    const queries = [
      `CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`,

      `CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        image VARCHAR(255) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`,

      `CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`,

      `CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`,
    ];

    const poolConnection = await pool.getConnection();
    for (const query of queries) {
      await poolConnection.execute(query);
    }
    console.log('Tables created successfully.');

    // Insert sample products
    const sampleProducts = [
      {
        name: 'Classic T-Shirt',
        description: 'A comfortable cotton t-shirt available in multiple sizes.',
        price: 19.99,
        image: 'https://via.placeholder.com/400x300?text=T-Shirt',
      },
      {
        name: 'Sneaker Shoes',
        description: 'Lightweight sneakers for everyday wear.',
        price: 79.99,
        image: 'https://via.placeholder.com/400x300?text=Sneakers',
      },
      {
        name: 'Coffee Mug',
        description: 'A ceramic mug to keep your coffee warm.',
        price: 12.50,
        image: 'https://via.placeholder.com/400x300?text=Mug',
      },
      {
        name: 'Wireless Headphones',
        description: 'Noise-cancelling headphones with long battery life.',
        price: 129.99,
        image: 'https://via.placeholder.com/400x300?text=Headphones',
      },
      {
        name: 'Stainless Water Bottle',
        description: 'Keeps drinks cold or hot for hours.',
        price: 14.99,
        image: 'https://via.placeholder.com/400x300?text=Water+Bottle',
      },
      {
        name: 'Portable Charger',
        description: 'Fast charging power bank for all devices.',
        price: 34.99,
        image: 'https://via.placeholder.com/400x300?text=Charger',
      },
    ];

    for (const product of sampleProducts) {
      await poolConnection.execute(
        'INSERT IGNORE INTO products (name, description, price, image) VALUES (?, ?, ?, ?)',
        [product.name, product.description, product.price, product.image]
      );
    }
    console.log('Sample products inserted.');

    poolConnection.release();
    await pool.end();
    await connection.end();
    console.log('Database initialization complete!');
  } catch (error) {
    console.error('Error initializing database:', error);
    process.exit(1);
  }
};

createDB();
