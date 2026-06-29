<?php
// includes/auth.php — session helpers

if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(string $role = ''): void {
    if (!isLoggedIn()) {
        header('Location: /ccrims/index.php');
        exit;
    }
    if ($role && ($_SESSION['user_role'] ?? '') !== $role) {
        header('Location: /ccrims/index.php?error=unauthorized');
        exit;
    }
}

function requireStaff(): void {
    if (!isLoggedIn() || !in_array($_SESSION['user_role'] ?? '', ['Officer','Analyst','Forensic Expert','Admin'])) {
        header('Location: /ccrims/index.php?error=unauthorized');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'   => $_SESSION['user_id']   ?? '',
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? '',
        'type' => $_SESSION['user_type'] ?? '',
    ];
}

function logout(): void {
    $_SESSION = [];
    session_destroy();
    header('Location: /ccrims/index.php');
    exit;
}
