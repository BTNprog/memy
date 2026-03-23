<?php
// login.php
session_start();
require_once __DIR__ . '/config.php';

requireGuest();

$errors = [];
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: index.php');
            exit;
        }

        $errors[] = 'Invalid email or password.';
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="container">
    <header class="header">
      <div>
        <h1>Login</h1>
        <p>Sign in to start shopping.</p>
      </div>
      <div>
        <a class="button secondary" href="register.php">Create account</a>
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
      <form method="post" action="login.php">
        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?php echo esc($email); ?>" required />
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required />
        </div>
        <button class="button" type="submit">Log in</button>
      </form>
    </div>
  </div>
</body>
</html>
