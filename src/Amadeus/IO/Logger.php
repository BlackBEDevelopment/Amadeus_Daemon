<?php

namespace Amadeus\IO;

use Amadeus\Config\Config;
use Amadeus\Network\Reactor;
use Amadeus\Process;
use Swoole\Coroutine;

/**
 * Class Logger
 * @package Amadeus\IO
 */
class Logger
{

    /**
     *
     */
    const LOG_INFORM = 0;
    /**
     *
     */
    const LOG_WARNING = 1;
    /**
     *
     */
    const LOG_ERROR = 2;
    /**
     *
     */
    const LOG_DANGER = 3;
    /**
     *
     */
    const LOG_PANIC = 4;
    /**
     *
     */
    const LOG_DEADLY = 5;
    /**
     *
     */
    const LOG_FATAL = 6;
    /**
     *
     */
    const LOG_DEAD = 7;
    /**
     *
     */
    const LOG_SUCCESS = 233;
    /**
     * @var bool
     */
    private static $shutUp = false;
    /**
     * @var
     */
    /**
     * @var
     */
    private static
        $err,
        $exc;

    /**
     * @return bool
     */
    public static function register(): bool
    {
        self::printLine('|                   Amadeus                   |');
        self::PrintLine('|                                         v' . Config::get('daemon_api_version') . '  |');
        self::$err = set_error_handler(['Amadeus\IO\Error\ErrorHandler', 'onError']);
        self::$exc = set_exception_handler(['Amadeus\IO\Exception\ExceptionHandler', 'onException']);
        self::printLine('Successfully registered', 233);
        return true;
    }

    /**
     * @param $Message
     * @param int $Level
     */
    public static function printLine($Message, int $Level = 0)
    {
        if (self::$shutUp) {
            return false;
        }
        if (is_array($Message)) {
            file_put_contents('Amadeus.log', $log = "[" . date("H:i:s") . " " . self::GetLevel($Level) . "] " . @debug_backtrace()[1]['class'] . @debug_backtrace()[1]['type'] . @debug_backtrace()[1]['function'] . ": " . json_encode($Message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents('Amadeus.log', $log = "[" . date("H:i:s") . " " . self::GetLevel($Level) . "] " . @debug_backtrace()[1]['class'] . @debug_backtrace()[1]['type'] . @debug_backtrace()[1]['function'] . ": " . $Message . PHP_EOL, FILE_APPEND);
        }
        if (Process::getStatus() == true) {
            Reactor::sendRdms('newLog', array('data' => $log));
        }
        if (self::getLevel($Level) == "  FATAL") {
            //exit("THE DAEMON DIES BECAUSE AN FATAL ERROR OCCURRED".PHP_EOL);
            self::printLine("THE DAEMON DIES BECAUSE AN FATAL ERROR OCCURRED", 7);
            @unlink('Amadeus.pid');
            exit;
        }
        return true;
    }

    /**
     * @param int $Level
     * @return string
     */
    private static function getLevel(int $Level): string
    {
        switch ($Level) {
            case 0:
                $stype = " INFORM";
                break;
            case 1:
                $stype = "WARNING";
                break;
            case 2:
                $stype = "  ERROR";
                break;
            case 3:
                $stype = " DANGER";
                break;
            case 4:
                $stype = "  PANIC";
                break;
            case 5:
                $stype = " DEADLY";
                break;
            case 6:
                $stype = "  FATAL";
                break;
            case 7:
                $stype = "   DEAD";
                break;
            case 233:
                $stype = "SUCCESS";
                break;
            default:
                $stype = " INFORM";
        }
        return $stype;
    }

    /**
     * @return bool
     */
    public static function shutUp(): bool
    {
        self::$shutUp = true;
        set_error_handler(self::$err);
        set_exception_handler(self::$exc);
        return true;
    }

    /**
     * @return string
     */
    public static function getLog(): string
    {
        return file_get_contents('Amadeus.log');
    }
}

?>