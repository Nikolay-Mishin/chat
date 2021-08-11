<?php

namespace worker;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

class Process {
    
    public static array $process_list = []; // сюда будем складывать все процессы

    public int $terminate_after = 5; // seconds after process is terminated
    public $process;
    public int $pid;
    public array $pstatus;

    public string $cmd;
    public array $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin - канал, из которого дочерний процесс будет читать
        //1 => array("pipe", "w"),  // stdout - канал, в который дочерний процесс будет записывать
        //2 => array("file", "error-output.txt", "a+") // stderr - файл для записи
    );
    public ?array $pipes;
    // Рабочая директория команды. Это должен быть абсолютный путь к директории или null, если требуется использовать директорию по умолчанию (рабочая директория текущего процесса PHP).
    public string $cwd = '/';
    // Массив переменных окружения для запускаемой команды или null, если требуется использовать то же самое окружение, что и у текущего PHP-процесса.
    public array $env = array('some_option' => 'aeiou');

    public string $output = '';
    public string $result = '';

    public function __construct(string $cmd, ?array $descriptorspec = null, ?string $cwd = null, ?array $env = null, ?int $terminate_after = null) {
        $this->cmd = $cmd;
        $this->descriptorspec = $descriptorspec ?? $this->descriptorspec;
        $this->cwd = $cwd ?? $this->cwd;
        $this->env = $env ?? $this->env;
        $this->terminate_after = $this->terminate_after ?? $terminate_after;

        $this->process = proc_open($this->cmd, $this->descriptorspec, $this->pipes, /*$this->cwd, $this->env*/);

        print_r($this->process);

        //usleep($this->terminate_after * 1000000); // wait for 5 seconds

        if (is_resource($this->process)) {
            // $pipes теперь выглядит так:
            // 0 => записывающий обработчик, подключённый к дочернему stdin
            // 1 => читающий обработчик, подключённый к дочернему stdout
            // Вывод сообщений об ошибках будет добавляться в error-output.txt

            fwrite($this->pipes[0], '<?php print_r($_ENV); ?>');
            //debug($this->output = stream_get_contents($pipes[1]));

            $this->pstatus = proc_get_status($this->process);
            debug('$pstatus: ');
            debug($this->pstatus);
            $this->pid = $this->pstatus['pid'];

            // Важно закрывать все каналы перед вызовом proc_close во избежание мёртвой блокировки
            $this->kill();

            debug("команда вернула $this->result\n");

            // terminate the process
            $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
            debug('Process terminated after: '.$time);

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

    public static function add(string $cmd, ?array $descriptorspec = null, ?string $cwd = null, ?array $env = null, ?int $terminate_after = null): self {
        $process = new self($cmd, $descriptorspec, $cwd, $env, $terminate_after);
        return self::$process_list[$process->pid] = $process;
    }

    /**
    * You can use the proc_ functions to get better control.
    * You will find it in the manual. Below you find code you might find useful.
    * It works only under windows, you need a different kill routine on linux.
    * he script terminates the (else endless running) ping process after approximatly 5 seconds.
    */
    public function kill(): string {
        $return_value = proc_terminate($this->process);
        //$return_value = stripos(php_uname('s'), 'win') > -1 ? exec("taskkill /F /T /PID $this->pid") : exec("kill -9 $this->pid"); // вместо proc_terminate($this->process);
        foreach ($this->pipes as $pipe) {
            fclose($pipe);
        }
        $return_value2 = proc_close($this->process);
        return $this->result = "$return_value, $return_value2";
    }

}
