<?php

namespace Glavfinans\Core\Fssp\Captcha;

use DateTimeInterface;

/**
 * Капча ФССП (Данные объекта хранятся в ClickHouse fssp_captcha)
 */
class FsspCaptcha
{
    /** @var string $cleanImage Хэш очищенного изображения */
    private string $cleanImage = '';

    /** @var bool $isSuccess - Разгадана ли капча */
    private bool $isSuccess = false;

    /**
     * @param string $uuid - Уникальный идентификатор
     * @param DateTimeInterface $createdAt - Дата создания записи
     * @param bool $isResolved - Решена ли капча
     * @param string $hash - Хэш изображения
     * @param string|null $handCode - Разгаданный код ручками
     * @param string|null $machineCode - Разгаданный машинной код
     */
    public function __construct(
        private string            $uuid,
        private DateTimeInterface $createdAt,
        private bool              $isResolved,
        private string            $hash,
        private ?string           $handCode = null,
        private ?string           $machineCode = null,
    ) {}

    /**
     * Получить UUID
     *
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Получить решённый код
     *
     * @return string|null
     */
    public function getHandCode(): ?string
    {
        return $this->handCode;
    }

    /**
     * Получить дату создания
     *
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Получить хэш изображения
     *
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Решена ли капча?
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->isResolved;
    }

    /**
     * Установить код
     *
     * @param string $handCode
     *
     * @return void
     */
    public function setHandCode(string $handCode): void
    {
        $this->handCode = $handCode;
    }

    /**
     * @return string
     */
    public function getCleanImage(): string
    {
        return $this->cleanImage;
    }

    /**
     * @param string $cleanImage
     */
    public function setCleanImage(string $cleanImage): void
    {
        $this->cleanImage = $cleanImage;
    }

    /**
     * @return string|null
     */
    public function getMachineCode(): ?string
    {
        return $this->machineCode;
    }

    /**
     * @param string $machineCode
     */
    public function setMachineCode(string $machineCode): void
    {
        $this->machineCode = $machineCode;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    /**
     * @param bool $isSuccess
     */
    public function setIsSuccess(bool $isSuccess): void
    {
        $this->isSuccess = $isSuccess;
    }

    /**
     * Сверка разгаданной капчи с правильным ответов в регистронезависимом формате
     *
     * @return bool
     */
    public function checkSuccess(): bool
    {
        return mb_strtolower(string: $this->getMachineCode()) === mb_strtolower(string: $this->getHandCode());
    }
}
