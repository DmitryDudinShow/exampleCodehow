<?php

namespace Glavfinans\Core\Logger;

use DateFmt;
use DateTimeImmutable;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Фабрика для создания логера
 */
class MonologFactory
{
    /**
     * @var array
     */
    private static array $instance = [];

    /** Singleton */
    protected function __construct() { }

    /**
     * Создание объекта логгера Monolog
     *
     * @return LoggerInterface
     */
    public static function getRestructuring(): LoggerInterface
    {
        return self::getInstanceByName(pathName: 'restructuring');
    }

    /**
     * Создание объекта логгера Парсинг Фссп
     *
     * @return LoggerInterface
     */
    public static function getFsspParse(): LoggerInterface
    {
        return self::getInstanceByName(pathName: 'fsspParse');
    }

    /**
     * Создание объекта логгера по имени
     *
     * @param string $pathName
     *
     * @return LoggerInterface
     */
    private static function getInstanceByName(string $pathName): LoggerInterface
    {
        $fileNameDate = (new DateTimeImmutable())->format(format: DateFmt::D_DB);

        $corePath = __DIR__ . '/../../../';

        if (!isset(self::$instance[$pathName])) {
            $fileName = "$corePath/headquarter/protected/runtime/log/$pathName/$fileNameDate.log";
            $handlers = [new StreamHandler(stream: $fileName)];

            self::$instance[$pathName] = new Logger(name: 'post', handlers: $handlers);
        }

        return self::$instance[$pathName];
    }
}
