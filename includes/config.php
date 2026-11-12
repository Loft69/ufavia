<?php
// параметры БД (Open Server: root без пароля)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ufavia');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'UFAvia');
define('SERVICE_FEE', 300); // сервисный сбор за бронирование, ₽

// подключение к БД (одиночка, PDO)
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function price($value): string
{
    return number_format((float)$value, 0, ',', ' ') . ' ₽';
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function require_login(): void
{
    if (current_user() === null) {
        $back = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: login.php?back=' . $back);
        exit;
    }
}

// данные выбранного рейса, сохранённые в сессии на шаге "Выбрать"
function selected_flight(): ?array
{
    return $_SESSION['selected_flight'] ?? null;
}

function save_selected_flight(int $flightId): array
{
    $flight = find_flight($flightId);
    if ($flight === null) {
        return [];
    }
    $_SESSION['selected_flight'] = [
        'id'           => (int)$flight['id'],
        'flight_number' => $flight['flight_number'],
        'airline'      => $flight['airline_name'],
        'aircraft'     => $flight['aircraft'],
        'from_city'    => $flight['from_city'],
        'from_code'    => $flight['from_code'],
        'from_name'    => $flight['from_name'],
        'to_city'      => $flight['to_city'],
        'to_code'      => $flight['to_code'],
        'to_name'      => $flight['to_name'],
        'date'         => $flight['departure_date'],
        'dep_time'     => $flight['departure_time'],
        'arr_time'     => $flight['arrival_time'],
        'duration'     => (int)$flight['duration_minutes'],
        'transfers'    => (int)$flight['transfers'],
        'price'        => (float)$flight['price'],
        'cabin'        => $flight['cabin'],
        'baggage'      => $flight['baggage'],
        'seats_left'   => (int)$flight['seats_left'],
    ];
    return $_SESSION['selected_flight'];
}

// строка поиска: откуда/куда/когда
function search_params(): array
{
    return $_SESSION['search'] ?? [
        'from'   => 'UFA',
        'to'     => 'MOW',
        'date'   => null,
        'trip'   => 'round',
    ];
}

// картинки направлений на главной (по коду аэропорта прилёта)
function destination_image(string $toCode): string
{
    static $map = ['VKO' => 'moscow.svg', 'SVO' => 'moscow.svg', 'DME' => 'moscow.svg',
        'AER' => 'sochi.svg', 'MRV' => 'sochi.svg', 'IST' => 'istanbul.svg', 'LED' => 'spb.svg'];
    return $map[$toCode] ?? 'moscow.svg';
}

// "MOW" встречается у нескольких московских аэропортов — считаем городом
function resolve_airport_group(string $code): array
{
    $stmt = db()->prepare(
        'SELECT id, code, city FROM airports WHERE code = ? OR (city = (SELECT city FROM airports WHERE code = ?) AND ? IN ("MOW", "LED", "IST"))'
    );
    $stmt->execute([$code, $code, $code]);
    $rows = $stmt->fetchAll();
    if (!$rows) {
        return [];
    }
    return $rows;
}

function find_flight(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT f.*, a.name AS airline_name, a.aircraft,
                fa.code AS from_code, fa.city AS from_city, fa.name AS from_name,
                ta.code AS to_code, ta.city AS to_city, ta.name AS to_name
         FROM flights f
         JOIN airlines a ON a.id = f.airline_id
         JOIN airports fa ON fa.id = f.from_airport_id
         JOIN airports ta ON ta.id = f.to_airport_id
         WHERE f.id = ? AND f.is_active = 1'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function flash(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $msg = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $msg;
}

function human_date(?string $date): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    $months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
        'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    return (int)date('j', $ts) . ' ' . $months[(int)date('n', $ts) - 1];
}

function human_duration(int $minutes): string
{
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h === 0) {
        return $m . ' мин';
    }
    return $h . ' ч ' . sprintf('%02d', $m) . ' мин';
}

function generate_ticket_number(): string
{
    return 'UF-' . random_int(10000, 99999);
}
