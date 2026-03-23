import express from 'express';
import bcrypt from 'bcryptjs';
import pool from '../utils/db.js';
import { requireGuest } from '../middleware/auth.js';

const router = express.Router();

// Login page
router.get('/login', requireGuest, (req, res) => {
  res.render('auth/login', { 
    title: 'Login',
    errors: [],
    email: ''
  });
});

// Login POST
router.post('/login', requireGuest, async (req, res) => {
  const { email, password } = req.body;
  const errors = [];

  if (!email || !validateEmail(email)) {
    errors.push('Please enter a valid email address.');
  }
  if (!password) {
    errors.push('Please enter your password.');
  }

  if (errors.length === 0) {
    try {
      const connection = await pool.getConnection();
      const [users] = await connection.execute(
        'SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1',
        [email]
      );
      connection.release();

      if (users.length > 0) {
        const user = users[0];
        const passwordMatch = await bcrypt.compare(password, user.password_hash);

        if (passwordMatch) {
          req.session.user = {
            id: user.id,
            name: user.name,
            email: user.email,
          };
          return res.redirect('/products');
        }
      }

      errors.push('Invalid email or password.');
    } catch (error) {
      console.error('Login error:', error);
      errors.push('An error occurred. Please try again.');
    }
  }

  res.render('auth/login', { 
    title: 'Login',
    errors,
    email
  });
});

// Register page
router.get('/register', requireGuest, (req, res) => {
  res.render('auth/register', { 
    title: 'Create Account',
    errors: [],
    name: '',
    email: ''
  });
});

// Register POST
router.post('/register', requireGuest, async (req, res) => {
  const { name, email, password, confirm } = req.body;
  const errors = [];

  if (!name) {
    errors.push('Please enter your name.');
  }
  if (!email || !validateEmail(email)) {
    errors.push('Please enter a valid email address.');
  }
  if (!password) {
    errors.push('Please enter a password.');
  }
  if (password !== confirm) {
    errors.push('Passwords do not match.');
  }

  if (errors.length === 0) {
    try {
      const connection = await pool.getConnection();
      
      // Check if email exists
      const [existingUsers] = await connection.execute(
        'SELECT COUNT(*) as count FROM users WHERE email = ?',
        [email]
      );

      if (existingUsers[0].count > 0) {
        errors.push('An account with that email already exists.');
      } else {
        const hash = await bcrypt.hash(password, 10);
        const [result] = await connection.execute(
          'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)',
          [name, email, hash]
        );

        req.session.user = {
          id: result.insertId,
          name,
          email,
        };

        connection.release();
        return res.redirect('/products');
      }
      
      connection.release();
    } catch (error) {
      console.error('Registration error:', error);
      errors.push('An error occurred. Please try again.');
    }
  }

  res.render('auth/register', { 
    title: 'Create Account',
    errors,
    name,
    email
  });
});

// Logout
router.get('/logout', (req, res) => {
  req.session.destroy((err) => {
    if (err) console.error('Session destroy error:', err);
    res.redirect('/auth/login');
  });
});

function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

export default router;
