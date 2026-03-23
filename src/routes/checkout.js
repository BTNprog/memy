import express from 'express';
import pool from '../utils/db.js';
import { requireLogin } from '../middleware/auth.js';

const router = express.Router();

// Checkout page
router.get('/', requireLogin, async (req, res) => {
  const cart = req.session.cart || {};

  if (Object.keys(cart).length === 0) {
    return res.redirect('/products');
  }

  try {
    const items = [];
    let total = 0;

    const ids = Object.keys(cart).map(Number);
    const connection = await pool.getConnection();
    const placeholders = ids.map(() => '?').join(',');
    const [products] = await connection.execute(
      `SELECT * FROM products WHERE id IN (${placeholders})`,
      ids
    );
    connection.release();

    for (const product of products) {
      const qty = cart[product.id] || 0;
      if (qty > 0) {
        const subtotal = qty * parseFloat(product.price);
        items.push({
          ...product,
          quantity: qty,
          subtotal,
        });
        total += subtotal;
      }
    }

    res.render('checkout/index', {
      title: 'Checkout',
      items,
      total: total.toFixed(2),
      user: req.session.user,
      errors: [],
      name: '',
      email: '',
    });
  } catch (error) {
    console.error('Checkout page error:', error);
    res.status(500).render('error', {
      title: 'Error',
      message: 'Could not load checkout',
    });
  }
});

// Place order
router.post('/', requireLogin, async (req, res) => {
  const { name, email } = req.body;
  const cart = req.session.cart || {};
  const errors = [];

  if (!name) {
    errors.push('Please enter your name.');
  }
  if (!email || !validateEmail(email)) {
    errors.push('Please enter a valid email address.');
  }

  if (errors.length === 0) {
    try {
      const items = [];
      let total = 0;

      const ids = Object.keys(cart).map(Number);
      const connection = await pool.getConnection();
      const placeholders = ids.map(() => '?').join(',');
      const [products] = await connection.execute(
        `SELECT * FROM products WHERE id IN (${placeholders})`,
        ids
      );

      for (const product of products) {
        const qty = cart[product.id] || 0;
        if (qty > 0) {
          const subtotal = qty * parseFloat(product.price);
          items.push({
            id: product.id,
            quantity: qty,
            price: product.price,
          });
          total += subtotal;
        }
      }

      // Insert order
      const [orderResult] = await connection.execute(
        'INSERT INTO orders (customer_name, customer_email, total) VALUES (?, ?, ?)',
        [name, email, total.toFixed(2)]
      );

      const orderId = orderResult.insertId;

      // Insert order items
      for (const item of items) {
        await connection.execute(
          'INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)',
          [orderId, item.id, item.quantity, item.price]
        );
      }

      connection.release();

      // Clear cart
      req.session.cart = {};

      return res.redirect(`/checkout/success?order_id=${orderId}`);
    } catch (error) {
      console.error('Order placement error:', error);
      errors.push('An error occurred while placing the order.');
    }
  }

  if (errors.length > 0) {
    const items = [];
    let total = 0;

    const ids = Object.keys(cart).map(Number);
    if (ids.length > 0) {
      const connection = await pool.getConnection();
      const placeholders = ids.map(() => '?').join(',');
      const [products] = await connection.execute(
        `SELECT * FROM products WHERE id IN (${placeholders})`,
        ids
      );
      connection.release();

      for (const product of products) {
        const qty = cart[product.id] || 0;
        if (qty > 0) {
          const subtotal = qty * parseFloat(product.price);
          items.push({
            ...product,
            quantity: qty,
            subtotal,
          });
          total += subtotal;
        }
      }
    }

    return res.render('checkout/index', {
      title: 'Checkout',
      items,
      total: total.toFixed(2),
      user: req.session.user,
      errors,
      name,
      email,
    });
  }
});

// Success page
router.get('/success', requireLogin, async (req, res) => {
  const orderId = req.query.order_id;

  if (!orderId) {
    return res.redirect('/products');
  }

  try {
    const connection = await pool.getConnection();
    const [orders] = await connection.execute(
      'SELECT * FROM orders WHERE id = ? LIMIT 1',
      [orderId]
    );

    const [items] = await connection.execute(
      'SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?',
      [orderId]
    );

    connection.release();

    if (orders.length === 0) {
      return res.redirect('/products');
    }

    const order = orders[0];

    res.render('checkout/success', {
      title: 'Order Confirmed',
      order,
      items,
      user: req.session.user,
    });
  } catch (error) {
    console.error('Success page error:', error);
    res.status(500).render('error', {
      title: 'Error',
      message: 'Could not load order details',
    });
  }
});

function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

export default router;
