<?php

namespace Glavfinans\Core\Fssp;

use DateTimeInterface;

/**
 * Контракт для сырого ответа от ФССП по исполнительным производствам
 */
interface FsspResponseRawInterface
{
    /**
     * Ошибка по Task(отсутствует статус или дата окончания)
     *
     * @return bool
     */
    public function isErrorTask(): bool;

    /**
     * Возвращает массив с данными исполнительных производств
     *
     * @return array
     */
    public function getFsspEps(): array;

    /**
     * Получить дату начала задачи
     *
     * @return string|null
     */
    public function getTaskStart(): ?string;

    /**
     * Получить дату окончания задачи
     *
     * @return string|null
     */
    public function getTaskEnd(): ?string;

    /**
     * Тип запроса "по ФИО клиента"
     *
     * @return bool
     */
    public function isRequestByClientFIO(): bool;

    /**
     * Типа запроса "по номеру ИП"
     *
     * @return bool
     */
    public function isRequestByNumEp(): bool;

    /**
     * Получить номер ИП
     *
     * @return string
     */
    public function getNumberEp(): string;

    /**
     * Получить дату окончания такси в виде объекта времени
     *
     * @return DateTimeInterface
     */
    public function getTaskEndAsDate(): DateTimeInterface;
}
