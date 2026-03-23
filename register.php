<?php
// register.php
session_start();
require_once __DIR__ . '/config.php';

requireGuest();

$errors = [];
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($name === '') {
        $errors[] = 'Please enter your name.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors[] = 'Please enter a password.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($exists);
        $stmt->fetch();
        $stmt->close();

        if ($exists > 0) {
            $errors[] = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $mysqli->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
            $insert->bind_param('sss', $name, $email, $hash);
            $insert->execute();

            $_SESSION['user_id'] = (int) $insert->insert_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            header('Location: index.php');
            exit;
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Create account</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="container">
    <header class="header">
      <div>
        <h1>Create account</h1>
        <p>Register to start shopping.</p>
      </div>
      <div>
        <a class="button secondary" href="login.php">Sign in</a>
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

    <div class="card">
      <form method="post" action="register.php">
        <div class="form-group">
          <label for="name">Name</label>
          <input id="name" name="name" type="text" value="<?php echo esc($name); ?>" required />
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?php echo esc($email); ?>" required />
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required />
        </div>
        <div class="form-group">
          <label for="confirm">Confirm password</label>
          <input id="confirm" name="confirm" type="password" required />
        </div>
        <button class="button" type="submit">Create account</button>
      </form>
    </div>
  </div>
</body>
</html>
