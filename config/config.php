<?php

define('PROTOCOL', 'websocket');
define('PROTOCOL_SHORT', 'ws');
define('HOST', 'chat'); // 'localhost/chat'
define('PORT', 8090);
define('IP_LISTEN', '0.0.0.0');
define('SERVER_PATH', '/server.php');
define('WebForMyself', 'WebForMyself' . SERVER_PATH);
define('Workerman', 'Workerman' . '/worker.php');
define('Ratchet', 'Ratchet' . SERVER_PATH);
define('SERVER', Workerman);
