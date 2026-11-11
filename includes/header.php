<?php
$page = basename($_SERVER['SCRIPT_NAME'] ?? '');
$navItems = [
    'index.php'   => 'Авиабилеты',
    'search.php'  => 'Поиск',
    'profile.php' => 'Мои поездки',
];
$u = current_user();
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="UFAvia — сервис поиска и бронирования авиабилетов">
  <title><?= e($pageTitle ?? 'UFAvia') ?></title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="page">

<header class="site-header">
  <div class="container header-inner">
    <a class="logo" href="index.php">
      <span class="logo-mark">✈</span><span>UFAvia</span>
    </a>
    <nav class="nav" aria-label="Основная навигация">
      <?php foreach ($navItems as $file => $label): ?>
        <a href="<?= $file ?>" class="<?= $page === $file ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
      <a href="index.php#help" class="">Помощь</a>
    </nav>
    <div class="header-actions">
      <a class="header-link" href="#">RU</a>
      <?php if ($u): ?>
        <a class="account-button" href="profile.php"><?= e($u['first_name']) ?></a>
      <?php else: ?>
        <a class="account-button" href="login.php">Войти</a>
      <?php endif; ?>
      <button class="mobile-menu" aria-label="Меню">☰</button>
    </div>
  </div>
</header>

<?php if ($msg = flash()): ?>
  <div class="container" style="margin-top:16px;">
    <div class="badge" style="padding:10px 14px;"><?= e($msg) ?></div>
  </div>
<?php endif; ?>

<main>
