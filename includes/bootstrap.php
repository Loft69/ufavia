<?php
require_once __DIR__ . '/config.php';

session_start();

// редирект со старых .html-адресов на .php
$uri = $_SERVER['REQUEST_URI'] ?? '';
$script = basename(parse_url($uri, PHP_URL_PATH) ?? '');
if (preg_match('/\.html$/', $script)) {
    $php = preg_replace('/\.html$/', '.php', $script);
    $query = parse_url($uri, PHP_URL_QUERY);
    header('Location: ' . $php . ($query ? '?' . $query : ''));
    exit;
}
