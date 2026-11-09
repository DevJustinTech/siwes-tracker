<?php
require_once __DIR__ . '/lib/helpers.php';
boot();

$_SESSION = [];
session_destroy();
redirect('login.php');
