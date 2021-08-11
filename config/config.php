<?php

define('DIR', dirname(__DIR__));

define('PROTOCOL', 'websocket');
define('PROTOCOL_SHORT', 'ws');
define('HOST', 'chat'); // 'localhost/chat'
define('PORT', 8090);
define('IP_LISTEN', '0.0.0.0');

define('SERVER_FILE', 'server.php');
define('WebForMyself', 'WebForMyself/' . SERVER_FILE);
define('Workerman', 'Workerman/' . SERVER_FILE);
define('Ratchet', 'Ratchet/' . SERVER_FILE);
define('SERVER',  Workerman);
define('SERVER_PATH', DIR . '/' . SERVER);
