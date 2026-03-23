# Simple PHP E‑commerce Demo

A minimal **PHP + MySQL** shop site with product listing, cart, and checkout.

## 🛠 Requirements

- PHP 7.4+ (with mysqli)
- MySQL / MariaDB
- Web server (Apache/IIS/Nginx) pointing at this folder

## 🚀 Quick Start

1. Update database credentials in `config.php`.
2. Run the database initializer once:

```sh
# via browser
http://localhost/memy/init_db.php

# or via CLI
php init_db.php
```

3. Open in browser:

```
http://localhost/memy/index.php
```

## 🧩 Files

- `index.php` — product listing + add-to-cart
- `cart.php` — view/update cart
- `checkout.php` — place order
- `success.php` — order confirmation
- `config.php` — database connection
- `init_db.php` — create schema + sample products
- `style.css` — basic styling
