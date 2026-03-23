import express from 'express';
import pool from '../utils/db.js';
import { requireAdmin } from '../middleware/auth.js';

const router = express.Router();

// Admin page
router.get('/', requireAdmin, async (req, res) => {
  res.render('admin/index', {
    title: 'Admin - Add Product',
    errors: [],
    success: null,
    name: '',
    description: '',
    price: '',
    image: '',
    user: req.session.user,
  });
});

// Add product
router.post('/', requireAdmin, async (req, res) => {
  const { name, description, price, image } = req.body;
  const errors = [];

  if (!name) {
    errors.push('Please enter a name for the product.');
  }
  if (!description) {
    errors.push('Please enter a description.');
  }
  if (!price || isNaN(parseFloat(price)) || parseFloat(price) < 0) {
    errors.push('Please enter a valid price.');
  }
  if (!image) {
    errors.push('Please enter an image URL.');
  }

  if (errors.length === 0) {
    try {
      const connection = await pool.getConnection();
      await connection.execute(
        'INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)',
        [name, description, parseFloat(price).toFixed(2), image]
      );
      connection.release();

      res.render('admin/index', {
        title: 'Admin - Add Product',
        errors: [],
        success: 'Product added successfully.',
        name: '',
        description: '',
        price: '',
        image: '',
        user: req.session.user,
      });
      return;
    } catch (error) {
      console.error('Product insertion error:', error);
      errors.push('An error occurred while adding the product.');
    }
  }

  res.render('admin/index', {
    title: 'Admin - Add Product',
    errors,
    success: null,
    name,
    description,
    price,
    image,
    user: req.session.user,
  });
});

export default router;
