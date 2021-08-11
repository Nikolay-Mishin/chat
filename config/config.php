<?php

define('DIR', dirname(__DIR__));

define('PROTOCOL', 'websocket');
define('PROTOCOL_SHORT', 'ws');
define('HOST', 'chat'); // 'localhost/chat'
define('PORT', 8090);
define('IP_LISTEN', '0.0.0.0');

define('SERVER_NAME', 'Workerman');
define('SERVER_FILE', 'server.php');
define('SERVER',  SERVER_NAME . '/' . SERVER_FILE);

define('SERVER_PATH', DIR . '/' . SERVER);
define('SERVER_DIR', dirname(SERVER_PATH));
define('SERVER_START', SERVER_DIR . '/start.php');
define('SERVER_STOP', SERVER_DIR . '/stop.php');
