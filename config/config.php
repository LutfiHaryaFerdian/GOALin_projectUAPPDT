<?php
session_start();

// Database configuration
require_once 'database.php';

// Site configuration
define('SITE_NAME', 'GOALin Futsal');
define('SITE_URL', 'http://localhost/goalin-futsal');
define('UPLOAD_PATH', 'uploads/');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

function requireUser() {
    if (!isUser()) {
        header('Location: index.php');
        exit();
    }
}

function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function formatTime($time) {
    return date('H:i', strtotime($time));
}

function getStatusBadgeClass($status) {
    switch($status) {
        case 'pending': return 'bg-warning text-dark';
        case 'confirmed': return 'bg-success';
        case 'cancelled': return 'bg-danger';
        case 'completed': return 'bg-info';
        case 'paid': return 'bg-success';
        case 'refunded': return 'bg-danger';
        default: return 'bg-secondary';
    }
}
?>
