<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pageTitle = 'Оформление заказа — UFAvia';

$flight = selected_flight();
if ($flight === null) {
    flash('Сначала выберите рейс.');
    header('Location: search.php');
    exit;
}

$u = current_user();
$errors = [];

// доп. услуги: ключ => [название, цена]
$extras = [
    'insurance' => ['Страховка путешествия', 590],
    'transfer'  => ['Трансфер из аэропорта', 1200],
];

$paymentMethods = ['card' => 'Банковская карта', 'sbp' => 'СБП'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $birth = trim($_POST['birth_date'] ?? '');
    $docType = trim($_POST['document_type'] ?? 'Паспорт РФ');
    $docNumber = trim($_POST['document_number'] ?? '');
    $payment = $_POST['payment'] ?? 'card';
    $chosen = array_keys(array_intersect_key($extras, array_flip((array)($_POST['extras'] ?? []))));

    if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $birth)) {
        $errors[] = 'Укажите дату рождения в формате ДД.ММ.ГГГГ.';
    }
    if ($docNumber === '') {
        $errors[] = 'Укажите номер документа.';
    }
    if (!isset($paymentMethods[$payment])) {
        $payment = 'card';
    }

    if (!$errors) {
        $extrasPrice = array_sum(array_map(fn($k) => $extras[$k][1], $chosen));
        $ticketPrice = (float)$flight['price'];
        $total = $ticketPrice + SERVICE_FEE + $extrasPrice;

        $ticketNumber = generate_ticket_number();
        $birthSql = DateTime::createFromFormat('d.m.Y', $birth)->format('Y-m-d');

        db()->beginTransaction();
        try {
            $stmt = db()->prepare(
                'INSERT INTO orders
                    (user_id, flight_id, ticket_number, passenger_first_name, passenger_last_name,
                     passenger_birth_date, passenger_document, contact_email, contact_phone,
                     extras, payment_method, ticket_price, service_fee, extras_price, total, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "paid")'
            );
            $stmt->execute([
                $u['id'],
                $flight['id'],
                $ticketNumber,
                $_SESSION['cart']['first_name'] ?? $u['first_name'],
                $_SESSION['cart']['last_name'] ?? $u['last_name'],
                $birthSql,
                $docType . ' · ' . $docNumber,
                $_SESSION['cart']['email'] ?? $u['email'],
                $_SESSION['cart']['phone'] ?? $u['phone'],
                json_encode($chosen, JSON_UNESCAPED_UNICODE),
                $payment,
                $ticketPrice,
                SERVICE_FEE,
                $extrasPrice,
                $total,
            ]);

            // уменьшаем остаток мест на рейсе
            db()->prepare('UPDATE flights SET seats_left = GREATEST(seats_left - 1, 0) WHERE id = ?')
                ->execute([$flight['id']]);

            db()->commit();
        } catch (Throwable $ex) {
            db()->rollBack();
            throw $ex;
        }

        $_SESSION['last_order_id'] = (int)db()->lastInsertId();
        unset($_SESSION['selected_flight'], $_SESSION['cart']);
        header('Location: success.php');
        exit;
    }
}

$ticketPrice = (float)$flight['price'];
$total = $ticketPrice + SERVICE_FEE;
include __DIR__ . '/includes/header.php';
?>

<main class="container">
  <div class="page-heading">
    <h1>Оформление заказа</h1>
    <p><?= e($flight['from_city']) ?> → <?= e($flight['to_city']) ?> · <?= e(human_date($flight['date'])) ?> · 1 пассажир</p>
  </div>
  <div class="checkout-layout">
    <section>
      <?php foreach ($errors as $err): ?>
        <p style="color:var(--red);margin-bottom:10px;"><?= e($err) ?></p>
      <?php endforeach; ?>
      <div class="section-form">
        <h2>Данные пассажира</h2>
        <form method="post" id="checkout-form">
          <div class="form-grid">
            <div class="form-field"><label>Имя</label><input value="<?= e($_SESSION['cart']['first_name'] ?? $u['first_name']) ?>" readonly></div>
            <div class="form-field"><label>Фамилия</label><input value="<?= e($_SESSION['cart']['last_name'] ?? $u['last_name']) ?>" readonly></div>
            <div class="form-field"><label>Дата рождения</label><input name="birth_date" placeholder="ДД.ММ.ГГГГ" pattern="\d{2}\.\d{2}\.\d{4}" required></div>
            <div class="form-field">
              <label>Гражданство</label>
              <select name="citizenship">
                <option>Россия</option>
                <option>Другая страна</option>
              </select>
            </div>
            <div class="form-field">
              <label>Документ</label>
              <select name="document_type">
                <option>Паспорт РФ</option>
                <option>Заграничный паспорт</option>
              </select>
            </div>
            <div class="form-field"><label>Номер документа</label><input name="document_number" placeholder="0000 000000" required></div>
          </div>
          <div class="section-form" style="margin-top:14px;border:0;padding:0;">
            <h2>Дополнительные услуги</h2>
            <div class="option-list">
              <?php foreach ($extras as $key => [$label, $cost]): ?>
                <label class="option">
                  <span><input type="checkbox" name="extras[]" value="<?= e($key) ?>" onchange="updateTotal()"> <?= e($label) ?></span>
                  <strong>+<?= e(price($cost)) ?></strong>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="section-form" style="margin-top:14px;border:0;padding:0;">
            <h2>Способ оплаты</h2>
            <div class="option-list">
              <?php foreach ($paymentMethods as $key => $label): ?>
                <label class="option">
                  <span><input type="radio" name="payment" value="<?= e($key) ?>" <?= $key === 'card' ? 'checked' : '' ?>> <?= e($label) ?></span>
                  <strong>Без комиссии</strong>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </form>
      </div>
    </section>
    <aside class="sticky-summary"><div class="summary-card">
      <h3>Ваш заказ</h3>
      <div class="summary-row"><span>Билет</span><span><?= e(price($ticketPrice)) ?></span></div>
      <div class="summary-row"><span>Сервисный сбор</span><span><?= e(price(SERVICE_FEE)) ?></span></div>
      <div class="summary-row"><span>Доп. услуги</span><span id="extras-sum">0 ₽</span></div>
      <div class="summary-total"><span>Итого</span><span id="total-sum"><?= e(price($total)) ?></span></div>
      <button class="primary-button yellow-button full-button" type="submit" form="checkout-form" style="display:grid;place-items:center;">Оплатить <span id="pay-amount"><?= e(price($total)) ?></span></button>
      <p style="color:var(--muted);font-size:12px;margin-top:10px;">Нажимая кнопку, вы подтверждаете согласие с условиями продажи.</p>
    </div></aside>
  </div>
</main>

<script>
function updateTotal() {
    const base = <?= (int)$ticketPrice ?> + <?= (int)SERVICE_FEE ?>;
    const extrasMap = <?= json_encode(array_map(fn($x) => $x[1], $extras)) ?>;
    let extras = 0;
    document.querySelectorAll('input[name="extras[]"]:checked').forEach(cb => {
        extras += extrasMap[cb.value] || 0;
    });
    const total = base + extras;
    document.getElementById('extras-sum').textContent = extras.toLocaleString('ru-RU') + ' ₽';
    document.getElementById('total-sum').textContent = total.toLocaleString('ru-RU') + ' ₽';
    document.getElementById('pay-amount').textContent = total.toLocaleString('ru-RU') + ' ₽';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
