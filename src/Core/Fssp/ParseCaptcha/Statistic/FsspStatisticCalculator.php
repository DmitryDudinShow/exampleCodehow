<?php

namespace Glavfinans\Core\Fssp\ParseCaptcha\Statistic;

use DateTimeImmutable;
use Exception;
use Glavfinans\Core\Entity\FsspRequest\FsspRequestRepositoryInterface;

/**
 * Калькулятор статистики парсинга сайта ФССП на исполнительные производства
 */
class FsspStatisticCalculator
{
    /**
     * @param FsspRequestRepositoryInterface $fsspRequestRepository
     */
    public function __construct(
        protected FsspRequestRepositoryInterface $fsspRequestRepository,
    ) {}

    /**
     * Получить данные статистики по запросам ФССП за определённое количество дней
     *
     * @param int $countDays
     *
     * @return FsspStatisticDataCollection
     * @throws Exception
     */
    public function getRequestParse(int $countDays = 7): FsspStatisticDataCollection
    {
        $requests = $this->fsspRequestRepository->findAllSuccessParseGroupByDays(countDays: $countDays);

        $collection = new FsspStatisticDataCollection();

        foreach ($requests as $request) {
            $collection->add(
                data: new FsspStatisticData(
                          date:       new DateTimeImmutable(datetime: $request['date']),
                          count:      $request['count'],
                          speedParse: $this->getSpeedParseInHours(count: (int)$request['count']),
                      )
            );
        }

        return $collection->sortByDate();
    }

    /**
     * Получить среднюю скорость парсинга ФССП в час
     *
     * @param int $count
     *
     * @return float
     */
    private function getSpeedParseInHours(int $count): float
    {
        return round(num: $count / 24, precision: 2);
    }
}
