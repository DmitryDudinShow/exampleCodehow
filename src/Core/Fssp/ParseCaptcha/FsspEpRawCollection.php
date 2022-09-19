<?php

namespace Glavfinans\Core\Fssp\ParseCaptcha;

use DateFmt;
use DateTimeImmutable;
use DateTimeInterface;
use Glavfinans\Core\Collection;
use Glavfinans\Core\Fssp\FsspResponseRawInterface;

/**
 * Коллекция сырых данных по исполнительным производствам, спрашенных с сайта ФССП
 */
class FsspEpRawCollection extends Collection implements FsspResponseRawInterface
{
    /** @var int $clientId - ID Клиента */
    protected int $clientId;

    /** @var int|null $requestId - ID запроса, если нужен */
    protected ?int $requestId;

    /**
     * Добавить ИП в коллекцию
     *
     * @param FsspEpRaw $ep
     *
     * @return void
     */
    public function add(FsspEpRaw $ep): void
    {
        $this->addObject(object: $ep);
    }

    /**
     * Массовое добавление объектов
     *
     * @param FsspEpRaw ...$eps
     *
     * @return void
     */
    public function massAdd(FsspEpRaw ...$eps): void
    {
        foreach ($eps as $ep) {
            $this->add(ep: $ep);
        }
    }

    /**
     * Получить ID клиента
     *
     * @return int
     */
    public function getClientId(): int
    {
        return $this->clientId;
    }

    /**
     * Установить ID клиента
     *
     * @param int $clientId
     *
     * @return void
     */
    public function setClientId(int $clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * Установить ID запроса, если нужен
     *
     * @param int|null $requestId
     *
     * @return void
     */
    public function setRequestId(?int $requestId): void
    {
        $this->requestId = $requestId;
    }

    /**
     * Получить ID запроса
     *
     * @return int|null
     */
    public function getRequestId(): ?int
    {
        return $this->requestId;
    }

    /**
     * @inheritDoc
     */
    public function isErrorTask(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getFsspEps(): array
    {
        $result = [];

        /** @var FsspEpRaw $fsspEp */
        foreach ($this->getAll() as $fsspEp) {
            $result[] = [
              'name' => $fsspEp->getName(),
              'exe_production' => $fsspEp->getExeProduction(),
              'details' => $fsspEp->getDetails(),
              'subject' => $fsspEp->getSubject(),
              'department' => $fsspEp->getDepartment(),
              'bailiff' => $fsspEp->getBailiff(),
              'ip_end' => $fsspEp->getEpEnd(),
            ];
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getTaskStart(): ?string
    {
        return (new DateTimeImmutable())->format(format: DateFmt::DT_DB);
    }

    /**
     * @inheritDoc
     */
    public function getTaskEnd(): ?string
    {
        return (new DateTimeImmutable())->format(format: DateFmt::DT_DB);
    }

    /**
     * @inheritDoc
     */
    public function isRequestByClientFIO(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isRequestByNumEp(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getNumberEp(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getTaskEndAsDate(): DateTimeInterface
    {
        return new DateTimeImmutable();
    }
}
