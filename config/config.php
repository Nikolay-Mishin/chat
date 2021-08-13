<?php

define('DIR', dirname(__DIR__));

define('PROTOCOL', 'websocket');
define('PROTOCOL_SHORT', 'ws');
define('HOST', 'chat'); // 'localhost/chat'
define('PORT', 8090);
define('IP_LISTEN', '0.0.0.0');

define('SERVER_NAME', 'Workerman');
define('SERVER_FILE', 'server.php');
define('SERVER_ACTION', SERVER_NAME . '/action.php');
define('SERVER',  SERVER_NAME . '/' . SERVER_FILE);

define('SERVER_PATH', DIR . '/' . SERVER);

/**
 * Распечатывает массив и, если параметр $die = true, завершает выполнение скрипта
 * @param  {array}   $arr Properties from this object will be returned
 * @param  {boolean} $die флаг на завершение выполнения скрипта
 * @return {void}         ничего не возвращает
 */
function debug($arr, bool $die = false): void {
	echo '<pre>' . print_r($arr, true) . '</pre>';
	if ($die) die;
}

$constants = json_encode(get_defined_constants(true)['user']);
$Config = "<script>
	const Config = $constants;
	Object.freeze(Config); // замораживает объект
</script>";
