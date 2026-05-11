<?php
// category.php  —  redirect to search with category filter
require_once __DIR__ . '/config/db.php';
$cat = trim($_GET['cat'] ?? '');
header('Location: ' . SITE_URL . '/search.php' . ($cat ? '?cat=' . urlencode($cat) : ''));
exit;
