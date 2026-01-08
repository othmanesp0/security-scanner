<?php
/**
 * Index Page - Redirects to appropriate dashboard
 * Educational Security Scanner Dashboard
 */

require_once 'includes/auth.php';

if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: user_dashboard.php');
    }
} else {
    header('Location: login.php');
}
exit();
?>
