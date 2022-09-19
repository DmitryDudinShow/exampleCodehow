<?php

namespace Glavfinans\Core\Fssp\ParseCaptcha;

use DateTimeInterface;

/**
 * Интерфейс данных для передачи в ФССП
 */
interface FsspRequestDtoInterface
{
    /**
     * Получить ID клиента
     *
     * @return int
     */
    public function getClientId(): int;

    /**
     * Получить Фамилию
     *
     * @return string
     */
    public function getLastName(): string;

    /**
     * Получить имя
     *
     * @return string
     */
    public function getFirstName(): string;

    /**
     * Получить отчество
     * 
     * @return string|null
     */
    public function getMiddleName(): ?string;

    /**
     * Получить дату рождения
     * 
     * @return DateTimeInterface
     */
    public function getBirthDate(): DateTimeInterface;

    /**
     * Получить регион в формате ФССП
     *
     * @return int
     */
    public function getRegionInFsspFormat(): int;

    /**
     * Получить ID запроса, если такой есть
     *
     * @return int|null
     */
    public function getRequestId(): ?int;
}
