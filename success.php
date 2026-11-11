<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pageTitle = 'Заказ оформлен — UFAvia';

$orderId = (int)($_SESSION['last_order_id'] ?? ($_GET['order'] ?? 0));
if ($orderId === 0) {
    header('Location: profile.php');
    exit;
}

$stmt = db()->prepare(
    'SELECT o.*, f.flight_number, f.departure_date, f.departure_time, f.arrival_time,
            fa.code AS from_code, fa.city AS from_city,
            ta.code AS to_code, ta.city AS to_city
     FROM orders o
     JOIN flights f ON f.id = o.flight_id
     JOIN airports fa ON fa.id = f.from_airport_id
     JOIN airports ta ON ta.id = f.to_airport_id
     WHERE o.id = ? AND o.user_id = ?'
);
$stmt->execute([$orderId, current_user()['id']]);
$order = $stmt->fetch();

if ($order === null) {
    http_response_code(404);
    header('Location: profile.php');
    exit;
}

unset($_SESSION['last_order_id']);
include __DIR__ . '/includes/header.php';
$passenger = $order['passenger_first_name'] . ' ' . $order['passenger_last_name'];
$ts = strtotime($order['departure_date']);
$months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
    'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
$dateFull = date('j', $ts) . ' ' . $months[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
?>

<main class="container">
  <section class="success">
    <div class="success-icon">✓</div>
    <h1>Билет успешно оформлен</h1>
    <p>Оплата прошла успешно. Электронный билет отправлен на <?= e($order['contact_email']) ?>.</p>
    <div class="boarding-pass">
      <div class="boarding-top">
        <div><strong>UFAvia</strong><div style="color:var(--muted);font-size:12px;">Электронный билет</div></div>
        <div class="boarding-code">№ <?= e($order['ticket_number']) ?></div>
      </div>
      <div class="boarding-main">
        <div>
          <div style="display:flex;align-items:center;gap:25px;margin-bottom:24px;">
            <div><small>ОТКУДА</small><div style="font-size:28px;font-weight:700;"><?= e($order['from_code']) ?></div><span><?= e($order['from_city']) ?></span></div>
            <div style="color:var(--blue);font-size:25px;">✈</div>
            <div><small>КУДА</small><div style="font-size:28px;font-weight:700;"><?= e($order['to_code']) ?></div><span><?= e($order['to_city']) ?></span></div>
          </div>
          <div class="summary-row"><span>Дата</span><strong><?= e($dateFull) ?></strong></div>
          <div class="summary-row"><span>Вылет</span><strong><?= e(mb_substr($order['departure_time'], 0, 5)) ?></strong></div>
          <div class="summary-row"><span>Прилёт</span><strong><?= e(mb_substr($order['arrival_time'], 0, 5)) ?></strong></div>
          <div class="summary-row"><span>Рейс</span><strong><?= e($order['flight_number']) ?></strong></div>
          <div class="summary-row"><span>Пассажир</span><strong><?= e($passenger) ?></strong></div>
          <div class="summary-row"><span>Оплачено</span><strong><?= e(price($order['total'])) ?></strong></div>
        </div>
        <div><div class="qr" aria-label="QR-код"></div><small style="display:block;text-align:center;margin-top:10px;color:var(--muted);"><?= e($order['ticket_number']) ?></small></div>
      </div>
    </div>
    <div style="margin-top:22px;display:flex;justify-content:center;gap:10px;flex-wrap:wrap;">
      <button class="secondary-button" onclick="window.print()">Распечатать билет</button>
      <a class="primary-button" href="profile.php" style="display:grid;place-items:center;padding:0 18px;">В личный кабинет</a>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
