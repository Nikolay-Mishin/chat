<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use worker\Chat;

session_start();

$action = $_POST['action'] ?? null;
Chat::$action();
