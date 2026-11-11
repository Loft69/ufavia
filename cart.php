<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pageTitle = 'Корзина — UFAvia';

$flight = selected_flight();
if ($flight === null) {
    flash('Сначала выберите рейс.');
    header('Location: search.php');
    exit;
}

$u = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($first === '' || $last === '') {
        $errors[] = 'Укажите имя и фамилию пассажира.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Укажите корректный e-mail.';
    }

    if (!$errors) {
        $_SESSION['cart'] = [
            'first_name' => $first,
            'last_name' => $last,
            'phone' => $phone,
            'email' => $email,
        ];
        header('Location: checkout.php');
        exit;
    }
} else {
    $first = $u['first_name'];
    $last = $u['last_name'];
    $phone = $u['phone'] ?? '';
    $email = $u['email'];
}

$total = (float)$flight['price'] + SERVICE_FEE;
include __DIR__ . '/includes/header.php';
?>

<main class="container">
  <div class="page-heading"><h1>Корзина</h1><p>Проверьте выбранные билеты перед оформлением.</p></div>
  <div class="cart-layout">
    <section>
      <article class="ticket-card">
        <div class="ticket-header">
          <strong><?= e($flight['from_city']) ?> → <?= e($flight['to_city']) ?></strong>
          <span class="badge">Выбран</span>
        </div>
        <div class="ticket-route">
          <div><div class="city"><?= e(mb_substr($flight['dep_time'], 0, 5)) ?></div><small><?= e($flight['from_code']) ?> · <?= e(human_date($flight['date'])) ?></small></div>
          <div class="arrow">→</div>
          <div><div class="city"><?= e(mb_substr($flight['arr_time'], 0, 5)) ?></div><small><?= e($flight['to_code']) ?> · <?= e(human_date($flight['date'])) ?></small></div>
          <div><strong><?= e(price($total)) ?></strong><small style="display:block;color:var(--muted);"><?= e($flight['cabin']) ?></small></div>
        </div>
      </article>
      <article class="section-form">
        <h2>Контактная информация</h2>
        <?php foreach ($errors as $err): ?>
          <p style="color:var(--red);margin-bottom:10px;"><?= e($err) ?></p>
        <?php endforeach; ?>
        <form method="post" id="cart-form">
          <div class="form-grid">
            <div class="form-field"><label>Имя</label><input name="first_name" value="<?= e($first) ?>" placeholder="Иван" required></div>
            <div class="form-field"><label>Фамилия</label><input name="last_name" value="<?= e($last) ?>" placeholder="Иванов" required></div>
            <div class="form-field"><label>Телефон</label><input name="phone" value="<?= e($phone) ?>" placeholder="+7 900 000-00-00"></div>
            <div class="form-field"><label>E-mail</label><input type="email" name="email" value="<?= e($email) ?>" placeholder="mail@example.com" required></div>
          </div>
          <button class="primary-button full-button" type="submit">Сохранить и продолжить</button>
        </form>
      </article>
    </section>
    <aside class="sticky-summary"><div class="summary-card">
      <h3>Итого</h3>
      <div class="summary-row"><span>Билет</span><span><?= e(price($flight['price'])) ?></span></div>
      <div class="summary-row"><span>Сервисный сбор</span><span><?= e(price(SERVICE_FEE)) ?></span></div>
      <div class="summary-total"><span>К оплате</span><span><?= e(price($total)) ?></span></div>
      <button class="primary-button full-button" type="submit" form="cart-form" style="display:grid;place-items:center;">Продолжить</button>
    </div></aside>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
