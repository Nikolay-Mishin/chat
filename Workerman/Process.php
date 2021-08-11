<?php

namespace worker;

require_once __DIR__ . '/../vendor/autoload.php';

// Подключаем библиотеку Workerman
use Workerman\Lib\Timer;
use Workerman\Worker;

require_once __DIR__ . '/../config/config.php';

class Process {

    public static int $terminate_after = 5; // seconds after process is terminated
    public static array $process_list = []; // сюда будем складывать все процессы

    public $process;
    public int $pid;
    public array $pstatus;

    public string $cmd;
    public array $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin - канал, из которого дочерний процесс будет читать
        //1 => array("pipe", "w"),  // stdout - канал, в который дочерний процесс будет записывать
        //2 => array("file", "error-output.txt", "a+") // stderr - файл для записи
    );
    public array $pipes;
    // Рабочая директория команды. Это должен быть абсолютный путь к директории или null, если требуется использовать директорию по умолчанию (рабочая директория текущего процесса PHP).
    public string $cwd = '/';
    // Массив переменных окружения для запускаемой команды или null, если требуется использовать то же самое окружение, что и у текущего PHP-процесса.
    public array $env = array('some_option' => 'aeiou');

    public function __construct(string $cmd) {
        $this->process = proc_open($cmd, $this->descriptorspec, $this->pipes, /*$this->cwd, $this->env*/);

        //
        //usleep($this->terminate_after * 1000000); // wait for 5 seconds

        if (is_resource($this->process)) {
            // $pipes теперь выглядит так:
            // 0 => записывающий обработчик, подключённый к дочернему stdin
            // 1 => читающий обработчик, подключённый к дочернему stdout
            // Вывод сообщений об ошибках будет добавляться в error-output.txt

            fwrite($this->pipes[0], '<?php print_r($_ENV); ?>');
            //echo stream_get_contents($pipes[1]);
            //echo '<br>';

            $this->pstatus = proc_get_status($this->process);
            echo '$pstatus: ';
            echo '<br>';
            print_r($this->pstatus);
            $this->pid = $this->pstatus['pid'];

            // Важно закрывать все каналы перед вызовом proc_close во избежание мёртвой блокировки
            //$return_value = $this->kill(); // вместо proc_terminate($this->process);

            echo '<br>';
            echo "команда вернула $return_value\n";

            // terminate the process
            $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
            echo '<br>Process terminated after: '.$time;

            /*
            Результатом выполнения данного примера будет что-то подобное:
            Array
            (
                [some_option] => aeiou
                [PWD] => /tmp
                [SHLVL] => 1
                [_] => /usr/local/bin/php
            )
            команда вернула 0
            */
        }
    }

    /**
    * You can use the proc_ functions to get better control.
    * You will find it in the manual. Below you find code you might find useful.
    * It works only under windows, you need a different kill routine on linux.
    * he script terminates the (else endless running) ping process after approximatly 5 seconds.
    */
    public function kill(): string {
        $return_value = proc_terminate($this->process);
        //$return_value = stripos(php_uname('s'), 'win') > -1 ? exec("taskkill /F /T /PID $this->pid") : exec("kill -9 $this->pid");
        fclose($pipes[0]);
        //fclose($this->pipes[1]);
        $return_value2 = proc_close($this->process);
        return "$return_value, $return_value2";
    }

}
