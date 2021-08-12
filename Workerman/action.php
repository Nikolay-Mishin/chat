<?php

require_once __DIR__ . '/../vendor/autoload.php';

use worker\Chat;

$action = $_POST['action'] ?? null;
$pkey = $_POST['pkey'] ?? null;

if ($action) {
    switch ($action) {
		case('start'):
			Chat::$action();
			break;
		case('stop'):
			Chat::$action($pkey);
			break;
	}
}
