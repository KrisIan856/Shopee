<?php
// includes/session.php  — Session bootstrap + helper functions

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Auth helpers ──────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function requireLogin(string $redirect = '/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . $redirect);
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

// ── Flash messages ────────────────────────────────────────────
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ── CSRF ──────────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid CSRF token']));
    }
}

// ── Formatting ────────────────────────────────────────────────
function peso(float $n): string {
    return '₱' . number_format($n, 0);
}

function soldFormat(int $n): string {
    if ($n >= 1000) return round($n / 1000, 1) . 'k';
    return (string)$n;
}

function stars(float $r): string {
    $full  = (int)floor($r);
    $half  = ($r - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    return str_repeat('★', $full) . str_repeat('½', $half) . str_repeat('☆', $empty);
}

function discountPct(float $orig, float $price): int {
    if ($orig <= 0) return 0;
    return (int)round((1 - $price / $orig) * 100);
}

function sanitize(string $s): string {
    return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
}

// ── Cart count (session cache) ────────────────────────────────
function cartCount(): int {
    if (!isLoggedIn()) return 0;
    if (isset($_SESSION['cart_count'])) return $_SESSION['cart_count'];
    try {
        $pdo = getDB();
        $st  = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id=?');
        $st->execute([$_SESSION['user_id']]);
        $_SESSION['cart_count'] = (int)$st->fetchColumn();
    } catch (Exception $e) {
        $_SESSION['cart_count'] = 0;
    }
    return $_SESSION['cart_count'];
}
