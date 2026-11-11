<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Вход — UFAvia';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Укажите корректный e-mail.';
    }
    if ($password === '') {
        $errors[] = 'Введите пароль.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $back = $_GET['back'] ?? 'profile.php';
            if (!preg_match('/^[a-z0-9_.-]+\.php/i', $back)) {
                $back = 'profile.php';
            }
            header('Location: ' . $back);
            exit;
        }
        $errors[] = 'Неверный e-mail или пароль.';
    }
}

include __DIR__ . '/includes/header.php';
?>

<main class="container">
  <div style="max-width:460px;margin:40px auto 70px;">
    <div class="section-form">
      <h2>Вход в личный кабинет</h2>
      <?php foreach ($errors as $err): ?>
        <p style="color:var(--red);margin-bottom:10px;"><?= e($err) ?></p>
      <?php endforeach; ?>
      <form method="post" class="form-grid" style="grid-template-columns:1fr;">
        <div class="form-field full">
          <label>E-mail</label>
          <input type="email" name="email" value="<?= e($email) ?>" placeholder="mail@example.com" required>
        </div>
        <div class="form-field full">
          <label>Пароль</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <button class="primary-button full-button" type="submit">Войти</button>
      </form>
      <p style="color:var(--muted);margin-top:14px;">
        Нет аккаунта? <a href="register.php" style="color:var(--blue);font-weight:600;">Зарегистрируйтесь</a>
      </p>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
