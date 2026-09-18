<?php
require_once __DIR__ . '/helpers.php';
boot();

$_SESSION = [];
session_destroy();
redirect('login.php');
