<?php

namespace worker;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

class Process {
    
    public int $terminate_after = 5; // seconds after process is terminated
    public $process;
    public ?string $pkey;
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
    public string $cwd = '/tmp';
    // Массив переменных окружения для запускаемой команды или null, если требуется использовать то же самое окружение, что и у текущего PHP-процесса.
    public array $env = array('some_option' => 'aeiou');

    public string $output;
    public $result;

    public function __construct(string $cmd, ?string $key = null, ?array $descriptorspec = null, ?string $cwd = null, ?array $env = null, ?int $terminate_after = null) {
        $this->pkey = $key;
        $this->cmd = $cmd;
        $this->descriptorspec = $descriptorspec ?? $this->descriptorspec;
        $this->cwd = $cwd ?? $this->cwd;
        $this->env = $env ?? $this->env;
        $this->terminate_after = $this->terminate_after ?? $terminate_after;

        $this->process = proc_open($this->cmd, $this->descriptorspec, $this->pipes, /*$this->cwd, $this->env*/);

        //usleep($this->terminate_after * 1000000); // wait for 5 seconds

        if (is_resource($this->process)) {
            // $pipes теперь выглядит так:
            // 0 => записывающий обработчик, подключённый к дочернему stdin
            // 1 => читающий обработчик, подключённый к дочернему stdout
            // Вывод сообщений об ошибках будет добавляться в error-output.txt

            $write = '<?php print_r($_ENV); ?>';
            //fwrite($this->pipes[0], $write);
            //$this->output = stream_get_contents($this->pipes[1]);
            //debug($this->output);

            $this->pstatus = proc_get_status($this->process);
            $this->pid = $this->pstatus['pid'];

            // terminate the process
            $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
            //debug('Process terminated after: '.$time);
            
            /*
            Результатом выполнения данного примера будет что-то подобное:
            Array
            (
                [some_option] => aeiou
                [PWD] => /tmp
                [SHLVL] => 1
                [_] => /usr/local/bin/php
            )
            */
        }
    }

    public static function add(string $cmd, ?string $key = null, ?array $descriptorspec = null, ?string $cwd = null, ?array $env = null, ?int $terminate_after = null): self {
        $process = new self($cmd, $key, $descriptorspec, $cwd, $env, $terminate_after);
        if (!isset($_SESSION['process'])) {
            $_SESSION['process'] = [];
        }
        debug($process);
        return $_SESSION['process'][$process->getPid()] = $process;
    }
    
    public static function killProc($pkey) {
        if ($process = self::getProcess($pkey)) {
            debug(self::isRun($process->pid));
            $process->kill();
            debug($process);
            debug(self::isRun($process->pid));
            debug("команда вернула $process->result");
        }
    }

    public static function getProcess($pkey): ?self {
        $process_list = self::getProcessList();
        return $process_list[$pkey] ?? null;
    }

    public static function getProcessList(): array {
        return $_SESSION['process'] ?? [];
    }

    public static function getTaskList(): array {
        exec("tasklist /fo list", $out);
        debug($out);
        return $_SESSION['process'] ?? [];
    }

    public static function clean() {
        if (isset($_SESSION['process'])) {
            unset($_SESSION['process']);
        }
    }

    /** tasklist [/s <computer> [/u [<domain>\]<username> [/p <password>]]] [{/m <module> | /svc | /v}] [/fo {table | list | csv}] [/nh] [/fi <filter> [/fi <filter> [ ... ]]]
    * /fo {table | list | csv}	Указывает формат, используемый для выходных данных. Допустимые значения: Table, List и CSV. Формат выходных данных по умолчанию — Table.
    * /Fi <filter>	Указывает типы процессов, включаемых в запрос или исключаемых из него. Можно использовать более одного фильтра или использовать подстановочный знак ( \ ) для указания всех задач или имен изображений. Допустимые фильтры перечислены в разделе имена фильтров, операторы и значения этой статьи.
    */
            
    /**
    * taskkill [/s <computer> [/u [<domain>\]<username> [/p [<password>]]]] {[/fi <filter>] [...] [/pid <processID> | /im <imagename>]} [/f] [/t]
    * /f	Указывает, что процессы принудительно завершены. Этот параметр не учитывается для удаленных процессов; все удаленные процессы принудительно завершены.
    * /t	Завершает указанный процесс и все дочерние процессы, запущенные этим процессом.
    * /PID <processID>	Указывает идентификатор процесса для завершения процесса.
    */
    public static function isRun(int $pid): bool {
        if (stripos(php_uname('s'), 'win') > -1) {
            exec("tasklist /fo list /fi \"pid eq $pid\"", $out);
            debug($out);
            if (count($out) > 1) {
                return true;
            }
        }
        elseif (posix_kill(intval($pid), 0)) {
            return true;
        }
        return false;
    }

    /**
    * You can use the proc_ functions to get better control.
    * You will find it in the manual. Below you find code you might find useful.
    * It works only under windows, you need a different kill routine on linux.
    * he script terminates the (else endless running) ping process after approximatly 5 seconds.
    */
    public function kill() {
        if (isset($_SESSION['process']) && isset($_SESSION['process'][$this->getPid()])) {
            unset($_SESSION['process'][$this->getPid()]);
        }

        if (is_resource($this->process)) {
            $return_value = proc_terminate($this->process);
            // Важно закрывать все каналы перед вызовом proc_close во избежание мёртвой блокировки
            foreach ($this->pipes as $pipe) {
                if (is_resource($pipe)) fclose($pipe);
            }
            $return_value2 = proc_close($this->process);
            return $this->result = "$return_value, $return_value2";
        }

        // вместо proc_terminate($this->process);
        stripos(php_uname('s'), 'win') > -1 ? exec("taskkill /f /t /pid $pid", $this->result) : exec("kill -9 $pid", $this->result);
        return $this->result;
        //posix_kill($this->pid, SIGKILL);
    }
    
    public function getPid() {
        return $this->pkey ?? $this->pid;
    }

}
