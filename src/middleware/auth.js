export const requireLogin = (req, res, next) => {
  if (!req.session.user) {
    return res.redirect('/auth/login');
  }
  next();
};

export const requireGuest = (req, res, next) => {
  if (req.session.user) {
    return res.redirect('/products');
  }
  next();
};

export const requireAdmin = (req, res, next) => {
  if (!req.session.user) {
    return res.redirect('/auth/login');
  }
  
  const isAdmin = req.session.user.email === 'user@example.com';
  if (!isAdmin) {
    return res.redirect('/products');
  }
  
  next();
};
