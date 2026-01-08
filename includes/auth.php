<?php
/**
 * Authentication & Security Functions
 * Fixed Domain Validation
 */

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: user_dashboard.php');
        exit();
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// --- FIXED VALIDATION FUNCTION ---
function validateUrl($url) {
    // 1. Remove protocol prefixes (http://, https://, etc.)
    $clean = preg_replace('#^https?://#', '', $url);
    
    // 2. Remove trailing slash (e.g., google.com/)
    $clean = rtrim($clean, '/');
    
    // 3. Check if it is a valid IP address
    if (filter_var($clean, FILTER_VALIDATE_IP)) {
        return $clean;
    }
    
    // 4. Check if it is a valid Domain Name using a simpler, standard Regex
    // Allows: google.com, sub.google.com, my-site.org
    if (preg_match('/^(?!-)[A-Za-z0-9-]+([\-\.]{1}[a-z0-9]+)*\.[A-Za-z]{2,6}$/', $clean)) {
        return $clean;
    }

    // 5. Fallback: PHP's built-in URL validator
    // We prepend http:// temporarily because filter_var requires a scheme to validate domains properly
    if (filter_var('http://' . $clean, FILTER_VALIDATE_URL)) {
        $parsed = parse_url('http://' . $clean);
        if (isset($parsed['host'])) {
            return $parsed['host'];
        }
    }
    
    return false;
}
// -------------------------------

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>
