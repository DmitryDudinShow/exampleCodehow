<?php

namespace Glavfinans\Core\Fssp\ParseCaptcha\Statistic;

use Glavfinans\Core\Collection;

/**
 * Коллекция данных по статистике парсинга по дням
 */
class FsspStatisticDataCollection extends Collection
{
    /**
     * Добавить данные по дню в статистику
     *
     * @param FsspStatisticData $data
     *
     * @return void
     */
    public function add(FsspStatisticData $data): void
    {
        $this->addObject(object: $data);
    }

    /**
     * Сортировка по дате
     *
     * @return $this
     */
    public function sortByDate(): self
    {
        $this->sortObjectsByField(field: 'Date', direction: false);

        return $this;
    }
}
