<?php

declare(strict_types=1);
/**
 * This file is part of OpenSwoole IDE Helper.
 * @link     https://openswoole.com
 * @contact  hello@openswoole.com
 * @license  https://github.com/openswoole/library/blob/master/LICENSE
 */
namespace Swoole\Coroutine;

class System
{
    /**
     * @param string $domain [required]
     * @param int $family [optional] = \AF_INET
     * @param float $timeout [optional] = -1
     */
    public static function gethostbyname(string $domain, int $family = \AF_INET, float $timeout = -1)
    {
    }

    /**
     * @param string $domain [required]
     * @param float $timeout [optional] = 5
     */
    public static function dnsLookup(string $domain, float $timeout = 5)
    {
    }

    /**
     * @param string $command [required]
     * @param bool $get_error_stream [optional] = false
     */
    public static function exec(string $command, bool $get_error_stream = false)
    {
    }

    /**
     * @param int $seconds [required]
     */
    public static function sleep(int $seconds): bool
    {
    }

    /**
     * @param int $milliseconds [required]
     */
    public static function usleep(int $milliseconds): bool
    {
    }

    /**
     * @param string $domain [required]
     * @param int $family [optional] = \AF_INET
     * @param int $sockType [optional] = \SOCK_STREAM
     * @param int $protocol [optional] = \STREAM_IPPROTO_TCP
     * @param string $service [optional] = null
     * @param float $timeout [optional] = -1
     */
    public static function getaddrinfo(string $domain, int $family = \AF_INET, int $sockType = \SOCK_STREAM, int $protocol = \STREAM_IPPROTO_TCP, string $service = null, float $timeout = -1)
    {
    }

    /**
     * @param string $path [required]
     */
    public static function statvfs(string $path)
    {
    }

    /**
     * @param string $filename [required]
     * @param int $flags [optional] = 0
     */
    public static function readFile(string $filename, int $flags = 0)
    {
    }

    /**
     * @param string $filename [required]
     * @param string $data [required]
     * @param int $flags [optional] = 0
     */
    public static function writeFile(string $filename, string $data, int $flags = 0)
    {
    }

    /**
     * @param float $timeout [optional] = -1
     */
    public static function wait(float $timeout = -1)
    {
    }

    /**
     * @param int $pid [required]
     * @param float $timeout [optional] = -1
     */
    public static function waitPid(int $pid, float $timeout = -1)
    {
    }

    /**
     * @param int $signalNum [required]
     * @param float $timeout [optional] = -1
     */
    public static function waitSignal(int $signalNum, float $timeout = -1): bool
    {
    }

    /**
     * @param mixed $fd [required]
     * @param int $events [required]
     * @param float $timeout [optional] = -1
     */
    public static function waitEvent($fd, int $events, float $timeout = -1)
    {
    }

    /**
     * @param mixed $handle [required]
     * @param int $length [optional] = 0
     */
    public static function fread($handle, int $length = 0)
    {
    }

    /**
     * @param mixed $handle [required]
     * @param string $data [required]
     * @param int $length [optional] = 0
     */
    public static function fwrite($handle, string $data, int $length = 0)
    {
    }

    /**
     * @param mixed $handle [required]
     */
    public static function fgets($handle): string
    {
    }
}
