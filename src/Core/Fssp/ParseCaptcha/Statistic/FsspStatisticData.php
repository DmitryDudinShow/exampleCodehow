<?php

namespace Glavfinans\Core\Fssp\ParseCaptcha\Statistic;

use DateTimeInterface;

/**
 * Обёртка для данных о статистике запросов в ФССП
 */
class FsspStatisticData
{
    /**
     * @param DateTimeInterface $date
     * @param int $count
     * @param float $speedParse
     */
    public function __construct(
        protected DateTimeInterface $date,
        protected int $count,
        protected float $speedParse,
    ) { }

    /**
     * Получить дату отправленных запросов
     *
     * @return DateTimeInterface
     */
    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    /**
     * Количество отправленных запросов на дату
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Получить скорость парсинга в определённую дату
     *
     * @return float
     */
    public function getSpeedParse(): float
    {
        return $this->speedParse;
    }
}
