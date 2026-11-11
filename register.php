<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Регистрация — UFAvia';

$errors = [];
$firstName = $lastName = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($firstName === '' || $lastName === '') {
        $errors[] = 'Укажите имя и фамилию.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Укажите корректный e-mail.';
    }
    if (mb_strlen($password) < 6) {
        $errors[] = 'Пароль должен быть не короче 6 символов.';
    }
    if ($password !== $password2) {
        $errors[] = 'Пароли не совпадают.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Пользователь с таким e-mail уже зарегистрирован.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO users (email, password_hash, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $firstName, $lastName, $phone ?: null]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)db()->lastInsertId();
        flash('Регистрация прошла успешно. Добро пожаловать!');
        header('Location: profile.php');
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>

<main class="container">
  <div style="max-width:560px;margin:40px auto 70px;">
    <div class="section-form">
      <h2>Регистрация</h2>
      <?php foreach ($errors as $err): ?>
        <p style="color:var(--red);margin-bottom:10px;"><?= e($err) ?></p>
      <?php endforeach; ?>
      <form method="post">
        <div class="form-grid">
          <div class="form-field"><label>Имя</label><input name="first_name" value="<?= e($firstName) ?>" placeholder="Иван" required></div>
          <div class="form-field"><label>Фамилия</label><input name="last_name" value="<?= e($lastName) ?>" placeholder="Иванов" required></div>
          <div class="form-field full"><label>E-mail</label><input type="email" name="email" value="<?= e($email) ?>" placeholder="mail@example.com" required></div>
          <div class="form-field full"><label>Телефон</label><input name="phone" value="<?= e($phone) ?>" placeholder="+7 900 000-00-00"></div>
          <div class="form-field"><label>Пароль</label><input type="password" name="password" placeholder="минимум 6 символов" required></div>
          <div class="form-field"><label>Повторите пароль</label><input type="password" name="password2" placeholder="••••••••" required></div>
        </div>
        <button class="primary-button full-button" type="submit">Создать аккаунт</button>
      </form>
      <p style="color:var(--muted);margin-top:14px;">
        Уже есть аккаунт? <a href="login.php" style="color:var(--blue);font-weight:600;">Войти</a>
      </p>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
