<?php
namespace App\Core\Logger;

/**
 * Logger interface
 * This interface defines the contract that all logger implementations must follow.
 */

interface LoggerInterface
{
    public function log(int $level, string $message, array $context = []): void;
    public function emergency(string $message, array $context = []): void;
    public function alert(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function notice(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void;
}