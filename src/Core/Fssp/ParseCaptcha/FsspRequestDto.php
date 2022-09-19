<?php

namespace Glavfinans\Core\Fssp\ParseCaptcha;

use DateTimeInterface;

/**
 * Данные для передачи в ФССП для запроса исполнительных производств
 */
class FsspRequestDto implements FsspRequestDtoInterface
{
    /**
     * @param int $clientId
     * @param string $lastName
     * @param string $firstName
     * @param string|null $middleName
     * @param DateTimeInterface $birthDate
     * @param int $regionCode
     * @param int|null $requestId
     */
    public function __construct(
        protected int               $clientId,
        protected string            $lastName,
        protected string            $firstName,
        protected ?string           $middleName,
        protected DateTimeInterface $birthDate,
        protected int               $regionCode,
        protected ?int              $requestId = null,
    ) {}

    /**
     * @inheritDoc
     */
    public function getClientId(): int
    {
        return $this->clientId;
    }

    /**
     * @inheritDoc
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @inheritDoc
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @inheritDoc
     */
    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    /**
     * @inheritDoc
     */
    public function getBirthDate(): DateTimeInterface
    {
        return $this->birthDate;
    }

    /**
     * @inheritDoc
     */
    public function getRegionInFsspFormat(): int
    {
        return $this->regionCode;
    }

    /**
     * @inheritDoc
     */
    public function getRequestId(): ?int
    {
        return $this->requestId;
    }
}
