<?php

require_once __DIR__ . '/../vendor/autoload.php';

use worker\Chat;

$action = $_POST['action'] ?? null;
Chat::$action();
