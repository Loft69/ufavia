<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Выбор рейса — UFAvia';

$flightId = (int)($_GET['id'] ?? 0);
$flight = find_flight($flightId);

if ($flight === null) {
    http_response_code(404);
    flash('Рейс не найден.');
    header('Location: search.php');
    exit;
}

save_selected_flight($flightId);

include __DIR__ . '/includes/header.php';
$dep = mb_substr($flight['departure_time'], 0, 5);
$arr = mb_substr($flight['arrival_time'], 0, 5);
$total = (float)$flight['price'] + SERVICE_FEE;
?>

<main class="container">
  <div class="breadcrumbs">Поиск → <?= e($flight['from_city']) ?> — <?= e($flight['to_city']) ?> → Выбор рейса</div>
  <div class="detail-layout">
    <section>
      <article class="detail-card">
        <div class="detail-title"><?= e($flight['airline_name']) ?> · Рейс <?= e($flight['flight_number']) ?></div>
        <div class="timeline">
          <div class="timeline-row">
            <div class="timeline-time"><?= e($dep) ?></div>
            <div class="timeline-place">
              <strong><?= e($flight['from_city']) ?> (<?= e($flight['from_code']) ?>)</strong>
              <span><?= e(human_date($flight['departure_date'])) ?>, <?= e($flight['from_name']) ?></span>
            </div>
          </div>
          <div class="timeline-row">
            <div class="timeline-time"><?= e($arr) ?></div>
            <div class="timeline-place">
              <strong><?= e($flight['to_city']) ?> (<?= e($flight['to_code']) ?>)</strong>
              <span><?= e(human_date($flight['departure_date'])) ?>, <?= e($flight['to_name']) ?></span>
            </div>
          </div>
        </div>
        <div style="margin-top:25px;color:var(--muted);">
          В пути <?= e(human_duration((int)$flight['duration_minutes'])) ?> ·
          <?= (int)$flight['transfers'] === 0 ? 'прямой рейс' : 'с пересадками' ?> ·
          <?= e($flight['aircraft']) ?>
        </div>
      </article>
      <article class="detail-card">
        <div class="detail-title">Что входит в тариф</div>
        <div class="baggage">
          <div class="baggage-item"><strong>Ручная кладь</strong><span>до 10 кг</span></div>
          <div class="baggage-item"><strong>Багаж</strong><span><?= str_contains($flight['baggage'], 'Багаж') ? 'включён' : 'не включён' ?></span></div>
          <div class="baggage-item"><strong>Выбор места</strong><span>за доплату</span></div>
        </div>
      </article>
      <article class="detail-card">
        <div class="detail-title">Условия бронирования</div>
        <p style="color:var(--muted);">Билет можно оформить онлайн. Условия обмена и возврата зависят от тарифа авиакомпании. Перед оплатой пользователь увидит полные условия.</p>
      </article>
    </section>
    <aside class="sticky-summary">
      <div class="summary-card">
        <h3>Ваш билет</h3>
        <div class="summary-row"><span><?= e($flight['from_city']) ?> → <?= e($flight['to_city']) ?></span><strong>1 пассажир</strong></div>
        <div class="summary-row"><span>Тариф</span><span><?= e($flight['cabin']) ?></span></div>
        <div class="summary-row"><span>Сбор UFAvia</span><span><?= e(price(SERVICE_FEE)) ?></span></div>
        <div class="summary-total"><span>Итого</span><span><?= e(price($total)) ?></span></div>
        <a class="primary-button full-button" href="cart.php" style="display:grid;place-items:center;">Купить авиабилет</a>
      </div>
    </aside>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
