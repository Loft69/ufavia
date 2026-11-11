<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Авиабилеты — UFAvia';

$destinations = db()->query(
    "SELECT fa.city AS from_city, fa.code AS from_code, ta.city AS to_city, ta.code AS to_code,
            MIN(f.price) AS min_price
     FROM flights f
     JOIN airports fa ON fa.id = f.from_airport_id
     JOIN airports ta ON ta.id = f.to_airport_id
     WHERE f.is_active = 1 AND f.departure_date >= CURDATE()
     GROUP BY fa.code, ta.code
     ORDER BY min_price ASC LIMIT 4"
)->fetchAll();

$popular = db()->query(
    "SELECT f.id, f.flight_number, f.departure_time, f.arrival_time, f.duration_minutes, f.transfers, f.price,
            a.name AS airline, fa.code AS from_code, ta.code AS to_code, f.cabin, f.baggage, f.departure_date
     FROM flights f
     JOIN airlines a ON a.id = f.airline_id
     JOIN airports fa ON fa.id = f.from_airport_id
     JOIN airports ta ON ta.id = f.to_airport_id
     WHERE f.is_active = 1 AND f.departure_date >= CURDATE()
     ORDER BY f.price ASC LIMIT 2"
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container">
    <h1>Куда полетим дальше?</h1>
    <p class="hero-subtitle">Сравниваем цены на авиабилеты и помогаем найти удобный рейс.</p>

    <form class="search-card" action="search.php" method="get">
      <div class="trip-tabs">
        <button class="trip-tab active" type="button">Туда-обратно</button>
        <button class="trip-tab" type="button">В одну сторону</button>
      </div>
      <div class="search-grid">
        <div class="field"><span class="field-label">Откуда</span><span class="field-value">Уфа · UFA</span></div>
        <div class="field"><span class="field-label">Куда</span><span class="field-value">Москва · MOW</span></div>
        <div class="field"><span class="field-label">Туда</span><span class="field-value">12 октября</span></div>
        <div class="field"><span class="field-label">Обратно</span><span class="field-value">19 октября</span></div>
        <div class="field"><span class="field-label">Пассажиры</span><span class="field-value">1 пассажир</span></div>
        <button class="primary-button" type="submit">Найти</button>
      </div>
    </form>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-title"><h2>Популярные направления</h2><a href="search.php">Все направления</a></div>
    <div class="cards destination-grid">
      <?php foreach ($destinations as $d): ?>
        <a class="destination-card" href="search.php?from=<?= e($d['from_code']) ?>&to=<?= e($d['to_code']) ?>">
          <img class="destination-image" src="images/destinations/<?= e(destination_image($d['to_code'])) ?>" alt="<?= e($d['to_city']) ?>">
          <div class="destination-info">
            <strong><?= e($d['from_city']) ?> → <?= e($d['to_city']) ?></strong>
            <span>от <?= e(price($d['min_price'])) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="help">
  <div class="container">
    <div class="section-title"><h2>Дешёвые билеты</h2><a href="search.php">Посмотреть все</a></div>
    <div class="cards">
      <?php foreach ($popular as $f): ?>
        <div class="flight-mini">
          <div>
            <div class="flight-route">
              <strong class="flight-time"><?= e(mb_substr($f['departure_time'], 0, 5)) ?></strong>
              <span><?= e($f['from_code']) ?></span>
              <span class="flight-line"></span>
              <span><?= e($f['to_code']) ?></span>
              <strong class="flight-time"><?= e(mb_substr($f['arrival_time'], 0, 5)) ?></strong>
            </div>
            <div class="flight-meta">
              <?= e(human_date($f['departure_date'])) ?> ·
              <?= e(human_duration((int)$f['duration_minutes'])) ?> ·
              <?= $f['transfers'] == 0 ? 'прямой рейс' : 'с пересадками' ?>
            </div>
          </div>
          <div><strong><?= e($f['airline']) ?></strong><div class="flight-meta"><?= e($f['cabin']) ?> · <?= e($f['baggage']) ?></div></div>
          <div><div class="price"><?= e(price($f['price'])) ?> <small>за пассажира</small></div></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-title"><h2>Пассажиры о UFAvia</h2></div>
    <div class="cards review-grid">
      <article class="review-card"><div class="review-stars">★★★★★</div><p>«Удобно сравнивать рейсы. Всё необходимое видно сразу, без десятка окон.»</p><div class="reviewer"><span class="avatar">АК</span>Анна К.</div></article>
      <article class="review-card"><div class="review-stars">★★★★★</div><p>«Нашёл прямой рейс до Москвы дешевле, чем ожидал. Понравилась простая выдача.»</p><div class="reviewer"><span class="avatar">МС</span>Максим С.</div></article>
      <article class="review-card"><div class="review-stars">★★★★☆</div><p>«Хороший вариант для быстрого поиска. Фильтры понятные, цена не прячется.»</p><div class="reviewer"><span class="avatar">ЕП</span>Елена П.</div></article>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
