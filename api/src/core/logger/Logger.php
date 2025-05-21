<?php

namespace App\Core\Logger;

use App\Core\Logger\LoggerFactory;
use App\Core\Logger\LoggerInterface;

class Logger implements LoggerInterface
{
    private static ?LoggerInterface $instance = null;
    private static ?LoggerInterface $loggerInstance = null;

    public function __construct()
    {
        if (self::$loggerInstance === null) {
            self::$loggerInstance = LoggerFactory::getInstance();
        }
    }

    public static function getInstance(): LoggerInterface
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function configure(array $config): void
    {
        LoggerFactory::configure($config);
    }

    public function log(int $level, string $message, array $context = []): void
    {
        self::$loggerInstance->log($level, $message, $context);
    }

    public function emergency(string $message, array $context = []): void
    {
        self::$loggerInstance->emergency($message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        self::$loggerInstance->alert($message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        self::$loggerInstance->critical($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        self::$loggerInstance->error($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        self::$loggerInstance->warning($message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        self::$loggerInstance->notice($message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        self::$loggerInstance->info($message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        self::$loggerInstance->debug($message, $context);
    }
}

/**
 * Global logger helper function
 * 
 * @return \App\Core\Logger\LoggerInterface
 */
function logger(): LoggerInterface
{
    return Logger::getInstance();
}

/**
 * Configure the logger
 * 
 * @param array $config Configuration options
 * @return void
 */
function configure_logger(array $config): void
{
    Logger::configure($config);
} 