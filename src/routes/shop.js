import express from 'express';
import pool from '../utils/db.js';
import { requireLogin } from '../middleware/auth.js';

const router = express.Router();

// Product listing
router.get('/products', requireLogin, async (req, res) => {
  try {
    const connection = await pool.getConnection();
    const [products] = await connection.execute(
      'SELECT * FROM products ORDER BY id'
    );
    connection.release();

    const cartCount = Object.values(req.session.cart || {}).reduce((a, b) => a + b, 0);

    res.render('shop/index', { 
      title: 'Memy Shop',
      products,
      cartCount
    });
  } catch (error) {
    console.error('Product listing error:', error);
    res.status(500).render('error', { 
      title: 'Error',
      message: 'Could not load products'
    });
  }
});

export default router;
