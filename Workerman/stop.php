<?php

require_once __DIR__ . '/../vendor/autoload.php';

use worker\Chat;

session_start();

Chat::stop();
