<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Результаты поиска — UFAvia';

$from = strtoupper(trim($_GET['from'] ?? 'UFA'));
$to   = strtoupper(trim($_GET['to'] ?? 'MOW'));
$date = $_GET['date'] ?? null;
$sort = $_GET['sort'] ?? 'price';
$maxPrice = (int)($_GET['max_price'] ?? 30000);
$noTransfers = isset($_GET['direct_only']);
$airlines = $_GET['airlines'] ?? [];

// псевдокоды городов: MOW — Москва, LED — Петербург, IST — Стамбул
$cityCodes = ['MOW' => 'Москва', 'LED' => 'Санкт-Петербург', 'IST' => 'Стамбул'];
if (isset($cityCodes[$from])) { $from = $cityCodes[$from]; }
if (isset($cityCodes[$to])) { $to = $cityCodes[$to]; }

// корректный диапазон дат: сегодня и позже
$minDate = date('Y-m-d');
if ($date && $date < $minDate) {
    $date = $minDate;
}

// поиск: код аэропорта или название города
$stmt = db()->prepare(
    "SELECT f.*, a.name AS airline_name,
            fa.code AS from_code, fa.city AS from_city,
            ta.code AS to_code, ta.city AS to_city
     FROM flights f
     JOIN airlines a ON a.id = f.airline_id
     JOIN airports fa ON fa.id = f.from_airport_id
     JOIN airports ta ON ta.id = f.to_airport_id
     WHERE f.is_active = 1
       AND (fa.code = :from_code OR fa.city = :from_city)
       AND (ta.code = :to_code OR ta.city = :to_city)
       AND f.departure_date >= :min_date"
);
$stmt->execute([
    'from_code' => mb_substr($from, 0, 3),
    'from_city' => $from,
    'to_code'   => mb_substr($to, 0, 3),
    'to_city'   => $to,
    'min_date'  => $minDate,
]);
$flights = $stmt->fetchAll();

if ($date) {
    $flights = array_values(array_filter($flights, fn($f) => $f['departure_date'] === $date));
}

if ($noTransfers) {
    $flights = array_values(array_filter($flights, fn($f) => (int)$f['transfers'] === 0));
}

if ($airlines) {
    $flights = array_values(array_filter($flights, fn($f) => in_array($f['airline_name'], (array)$airlines, true)));
}

$flights = array_values(array_filter($flights, fn($f) => (float)$f['price'] <= $maxPrice));

usort($flights, function ($a, $b) use ($sort) {
    return match ($sort) {
        'duration' => (int)$a['duration_minutes'] <=> (int)$b['duration_minutes'],
        'departure' => strcmp($a['departure_time'], $b['departure_time']),
        default => (float)$a['price'] <=> (float)$b['price'],
    };
});

$availableAirlines = db()->query('SELECT DISTINCT name FROM airlines ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);

$fromCity = $flights[0]['from_city'] ?? $from;
$toCity = $flights[0]['to_city'] ?? $to;

include __DIR__ . '/includes/header.php';
?>

<main class="container">
  <div class="page-heading">
    <h1><?= e($fromCity) ?> → <?= e($toCity) ?></h1>
    <p><?= e($date ? human_date($date) : 'ближайшие даты') ?> · 1 пассажир · эконом</p>
  </div>
  <div class="search-layout">
    <aside class="filters">
      <form action="search.php" method="get">
        <input type="hidden" name="from" value="<?= e($from) ?>">
        <input type="hidden" name="to" value="<?= e($to) ?>">
        <?php if ($date): ?><input type="hidden" name="date" value="<?= e($date) ?>"><?php endif; ?>
        <div class="filter-group">
          <div class="filter-title">Цена</div>
          <input class="range" type="range" name="max_price" min="1000" max="30000" step="100" value="<?= e($maxPrice) ?>">
          <div class="summary-row"><span>1 000 ₽</span><span><?= e(price($maxPrice)) ?></span></div>
        </div>
        <div class="filter-group">
          <div class="filter-title">Пересадки</div>
          <label class="check"><input type="checkbox" name="direct_only" <?= $noTransfers ? 'checked' : '' ?>> Без пересадок</label>
        </div>
        <div class="filter-group">
          <div class="filter-title">Авиакомпания</div>
          <?php foreach ($availableAirlines as $a): ?>
            <label class="check">
              <input type="checkbox" name="airlines[]" value="<?= e($a) ?>" <?= in_array($a, (array)$airlines, true) ? 'checked' : '' ?>> <?= e($a) ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="filter-group">
          <div class="filter-title">Время вылета</div>
          <label class="check"><input type="checkbox" disabled> 00:00 — 06:00</label>
          <label class="check"><input type="checkbox" disabled> 06:00 — 12:00</label>
          <label class="check"><input type="checkbox" disabled> 12:00 — 18:00</label>
        </div>
        <button class="primary-button full-button" type="submit" style="margin-top:14px;">Применить</button>
      </form>
    </aside>
    <section>
      <div class="results-toolbar">
        <span>Найдено <?= count($flights) ?> <?= count($flights) === 1 ? 'вариант' : 'вариантов' ?></span>
        <form action="search.php" method="get" style="display:flex;gap:8px;">
          <input type="hidden" name="from" value="<?= e($from) ?>">
          <input type="hidden" name="to" value="<?= e($to) ?>">
          <?php if ($date): ?><input type="hidden" name="date" value="<?= e($date) ?>"><?php endif; ?>
          <?php if ($noTransfers): ?><input type="hidden" name="direct_only" value="1"><?php endif; ?>
          <select class="sort" name="sort" onchange="this.form.submit()">
            <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>По цене</option>
            <option value="departure" <?= $sort === 'departure' ? 'selected' : '' ?>>По времени вылета</option>
            <option value="duration" <?= $sort === 'duration' ? 'selected' : '' ?>>По длительности</option>
          </select>
        </form>
      </div>
      <div class="flight-list">
        <?php if (!$flights): ?>
          <article class="flight-card"><p style="color:var(--muted);">Ничего не найдено. Попробуйте изменить фильтры или дату.</p></article>
        <?php endif; ?>
        <?php foreach ($flights as $f): ?>
          <article class="flight-card">
            <div class="airline">
              <span class="airline-logo"><?= e(mb_substr($f['airline_name'], 0, 1)) ?></span>
              <?= e($f['airline_name']) ?>
              <span class="badge"><?= $f['transfers'] == 0 ? 'Прямой' : 'Пересадка' ?></span>
            </div>
            <div class="flight-main">
              <div>
                <span class="big-time"><?= e(mb_substr($f['departure_time'], 0, 5)) ?></span>
                <span class="airport"> · <?= e($f['from_code']) ?></span>
                <div class="airport"><?= e($f['from_city']) ?></div>
              </div>
              <div class="flight-duration">
                <?= e(human_duration((int)$f['duration_minutes'])) ?>
                <div><?= $f['transfers'] == 0 ? 'без пересадок' : 'с пересадками' ?></div>
              </div>
              <div>
                <span class="big-time"><?= e(mb_substr($f['arrival_time'], 0, 5)) ?></span>
                <span class="airport"> · <?= e($f['to_code']) ?></span>
                <div class="airport"><?= e($f['to_city']) ?></div>
              </div>
            </div>
            <div class="flight-extra">
              <span><?= (int)$f['seats_left'] > 0 ? 'Осталось ' . (int)$f['seats_left'] . ' мест' : 'Мест нет' ?></span>
              <span><?= e($f['baggage']) ?></span>
              <strong>от <?= e(price($f['price'])) ?></strong>
            </div>
            <a class="primary-button select-flight" href="flight.php?id=<?= (int)$f['id'] ?>" style="display:grid;place-items:center;margin-top:14px;">Выбрать</a>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
