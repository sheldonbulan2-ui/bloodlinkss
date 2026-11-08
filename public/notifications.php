<?php
require __DIR__ . '/../php/guard.php';
requirePage();
// Notifications on web have been removed per requirements
header('Location: dashboard.php');
exit;
