<?php
/**
 * Include this at the top of every protected admin page.
 * Ensures the visitor is logged in AND has the admin role.
 */
require_once __DIR__ . '/../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . '/admin/login.php');
}
