<?php

define('DIR', dirname(__DIR__).'/');

define('PROTOCOL', 'websocket');
define('PROTOCOL_SHORT', 'ws');
define('HOST', 'chat'); // 'localhost/chat'
define('PORT', 8090);
define('IP_LISTEN', '0.0.0.0');

define('SERVER_ACTION', '/Workerman/action.php');
define('SERVER_PATH', 'Workerman/server.php');
define('SERVER',  DIR.SERVER_PATH);

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

$consts = json_encode(get_defined_constants(true)['user']);
$Config = "<script>
	const Config = $consts;
	Object.freeze(Config); // замораживает объект
</script>";

require_once dirname(__DIR__).'/vendor/autoload.php';
