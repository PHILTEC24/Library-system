<?php
if (!defined('BASE_URL')) define('BASE_URL', '/library-system');
session_start();
session_destroy();
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
