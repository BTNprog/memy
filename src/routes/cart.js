import express from 'express';
import pool from '../utils/db.js';
import { requireLogin } from '../middleware/auth.js';

const router = express.Router();

// Initialize cart
const initCart = (req) => {
  if (!req.session.cart) {
    req.session.cart = {};
  }
};

// View cart
router.get('/', requireLogin, async (req, res) => {
  initCart(req);

  try {
    const cartItems = [];
    let total = 0;

    if (Object.keys(req.session.cart).length > 0) {
      const ids = Object.keys(req.session.cart).map(Number);
      const connection = await pool.getConnection();
      const placeholders = ids.map(() => '?').join(',');
      const [products] = await connection.execute(
        `SELECT * FROM products WHERE id IN (${placeholders})`,
        ids
      );
      connection.release();

      for (const product of products) {
        const qty = req.session.cart[product.id] || 0;
        if (qty > 0) {
          const subtotal = qty * parseFloat(product.price);
          cartItems.push({
            ...product,
            quantity: qty,
            subtotal,
          });
          total += subtotal;
        }
      }
    }

    res.render('cart/index', {
      title: 'Your Cart',
      cartItems,
      total: total.toFixed(2),
      user: req.session.user,
      message: req.session.cartMessage || null,
    });

    req.session.cartMessage = null;
  } catch (error) {
    console.error('Cart view error:', error);
    res.status(500).render('error', {
      title: 'Error',
      message: 'Could not load cart',
    });
  }
});

// Add to cart
router.post('/add', requireLogin, (req, res) => {
  initCart(req);

  const productId = parseInt(req.body.product_id, 10);
  if (productId > 0) {
    req.session.cart[productId] = (req.session.cart[productId] || 0) + 1;
  }

  res.redirect('/cart');
});

// Update cart
router.post('/update', requireLogin, (req, res) => {
  initCart(req);

  for (const [id, qty] of Object.entries(req.body.qty || {})) {
    const productId = parseInt(id, 10);
    const quantity = Math.max(0, parseInt(qty, 10));

    if (productId > 0) {
      if (quantity === 0) {
        delete req.session.cart[productId];
      } else {
        req.session.cart[productId] = quantity;
      }
    }
  }

  req.session.cartMessage = 'Cart updated.';
  res.redirect('/cart');
});

// Clear cart
router.post('/clear', requireLogin, (req, res) => {
  req.session.cart = {};
  req.session.cartMessage = 'Cart cleared.';
  res.redirect('/cart');
});

export default router;
