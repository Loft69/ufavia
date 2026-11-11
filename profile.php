<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pageTitle = 'Личный кабинет — UFAvia';

$u = current_user();
$saved = false;

// обновление профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'profile') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($firstName === '' || $lastName === '') {
        flash('Имя и фамилия не могут быть пустыми.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Укажите корректный e-mail.');
    } else {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $u['id']]);
        if ($stmt->fetch()) {
            flash('Этот e-mail уже занят другим пользователем.');
        } else {
            db()->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?')
                ->execute([$firstName, $lastName, $email, $phone ?: null, $u['id']]);
            flash('Профиль обновлён.');
            header('Location: profile.php');
            exit;
        }
    }
}

// ближайшая поездка
$stmt = db()->prepare(
    "SELECT o.*, f.flight_number, f.departure_date, f.departure_time, f.arrival_time,
            fa.code AS from_code, fa.city AS from_city,
            ta.code AS to_code, ta.city AS to_city
     FROM orders o
     JOIN flights f ON f.id = o.flight_id
     JOIN airports fa ON fa.id = f.from_airport_id
     JOIN airports ta ON ta.id = f.to_airport_id
     WHERE o.user_id = ? AND o.status = 'paid' AND f.departure_date >= CURDATE()
     ORDER BY f.departure_date ASC, f.departure_time ASC LIMIT 1"
);
$stmt->execute([$u['id']]);
$upcoming = $stmt->fetch();

// история заказов
$stmt = db()->prepare(
    'SELECT o.*, f.flight_number, f.departure_date,
            fa.city AS from_city, ta.city AS to_city
     FROM orders o
     JOIN flights f ON f.id = o.flight_id
     JOIN airports fa ON fa.id = f.from_airport_id
     JOIN airports ta ON ta.id = f.to_airport_id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC'
);
$stmt->execute([$u['id']]);
$history = $stmt->fetchAll();

$initials = mb_substr($u['first_name'], 0, 1) . mb_substr($u['last_name'], 0, 1);
include __DIR__ . '/includes/header.php';
?>

<main class="container">
  <div class="page-heading"><h1>Личный кабинет</h1><p>Ваши поездки и данные пассажиров.</p></div>
  <div class="profile-layout">
    <aside class="profile-nav">
      <a class="active" href="profile.php">Мои поездки</a>
      <a href="#contacts">Пассажиры</a>
      <a href="#settings">Настройки</a>
      <a href="logout.php" class="logout-link">Выйти</a>
    </aside>
    <section>
      <div class="profile-head">
        <div class="profile-avatar"><?= e($initials) ?></div>
        <div><h2><?= e($u['first_name'] . ' ' . $u['last_name']) ?></h2><p style="color:var(--muted);"><?= e($u['email']) ?></p></div>
      </div>

      <div class="section-form">
        <h2>Ближайшая поездка</h2>
        <?php if ($upcoming): ?>
          <div class="ticket-route">
            <div><div class="city"><?= e($upcoming['from_code']) ?></div><small><?= e(human_date($upcoming['departure_date'])) ?> · <?= e(mb_substr($upcoming['departure_time'], 0, 5)) ?></small></div>
            <div class="arrow">→</div>
            <div><div class="city"><?= e($upcoming['to_code']) ?></div><small><?= e(human_date($upcoming['departure_date'])) ?> · <?= e(mb_substr($upcoming['arrival_time'], 0, 5)) ?></small></div>
            <span class="badge">Билет оформлен</span>
          </div>
          <p style="margin-top:12px;"><a href="success.php?order=<?= (int)$upcoming['id'] ?>" style="color:var(--blue);font-weight:600;">Показать билет</a></p>
        <?php else: ?>
          <p style="color:var(--muted);">Ближайших поездок нет. <a href="search.php" style="color:var(--blue);">Найти рейс</a></p>
        <?php endif; ?>
      </div>

      <div class="section-form">
        <h2>История заказов</h2>
        <?php if (!$history): ?>
          <p style="color:var(--muted);">Заказов пока нет.</p>
        <?php endif; ?>
        <div class="booking-history">
          <?php foreach ($history as $h): ?>
            <div class="history-card">
              <div>
                <div class="history-route"><?= e($h['from_city']) ?> → <?= e($h['to_city']) ?></div>
                <div class="history-date"><?= e(human_date($h['departure_date'])) ?> · <?= e($h['flight_number']) ?></div>
              </div>
              <strong><?= e(price($h['total'])) ?></strong>
              <span class="badge"><?= e($h['status'] === 'paid' ? 'Оплачен' : $h['status']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="section-form" id="contacts">
        <h2>Пассажиры</h2>
        <div class="history-card">
          <div>
            <strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong>
            <div class="history-date"><?= e($u['document_type'] ?? 'Паспорт РФ') ?> · <?= e($u['document_number'] ? '•••• ' . mb_substr($u['document_number'], -6) : 'документ не указан') ?></div>
          </div>
        </div>
      </div>

      <div class="section-form" id="settings">
        <h2>Настройки профиля</h2>
        <form method="post">
          <input type="hidden" name="action" value="profile">
          <div class="form-grid">
            <div class="form-field"><label>Имя</label><input name="first_name" value="<?= e($u['first_name']) ?>" required></div>
            <div class="form-field"><label>Фамилия</label><input name="last_name" value="<?= e($u['last_name']) ?>" required></div>
            <div class="form-field full"><label>E-mail</label><input type="email" name="email" value="<?= e($u['email']) ?>" required></div>
            <div class="form-field full"><label>Телефон</label><input name="phone" value="<?= e($u['phone'] ?? '') ?>" placeholder="+7 900 000-00-00"></div>
          </div>
          <button class="primary-button full-button" type="submit">Сохранить</button>
        </form>
      </div>
    </section>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
