<?php

namespace Glavfinans\Core\Fssp\Captcha;

use ClickHouseDB\Client as ClientCH;
use LogicException;

/**
 * Сервис для сборки каптч при парсинге сайта ФССП
 */
class FsspCaptchaGatherer
{
    /** @var string|null $captchaBase64 - Хэш каптчи */
    private ?string $captchaBase64 = null;

    /** @var string|null $code - Код, который мы попытались разгадать */
    private ?string $code = null;

    /**
     * @param ClientCH $clientCH
     */
    public function __construct(private ClientCH $clientCH) {}

    /**
     * Установка новой каптчи и кода разгадки
     *
     * @param string $captcha
     * @param string $code
     *
     * @return void
     */
    public function set(string $captcha, string $code): void
    {
        $this->captchaBase64 = $captcha;
        $this->code = $code;
    }

    /**
     * Возвращает true - если объект чистый
     *
     * @return bool
     */
    public function isClearObject(): bool
    {
        return null === $this->captchaBase64 && null === $this->code;
    }

    /**
     * Сохраняем результат каптчи в ClickHouse (Разгадано/Не разгадано)
     *
     * @param bool $isResolved
     *
     * @return FsspCaptchaGatherer
     */
    public function saveCaptchaWithResult(bool $isResolved): self
    {
        /** Такого быть не должно */
        if ($this->isClearObject()) {
            throw new LogicException(message: 'Неожиданное поведение. Пытается сохранить каптчу, который нет');
        }

        $fsspCaptcha = [
            'hash' => $this->captchaBase64,
            'machine_code' => $this->code,
            'is_resolved' => (int)$isResolved,
        ];

        $this->clientCH->insertAssocBulk(tableName: '`fssp_captcha`', values: $fsspCaptcha);
        $this->clear();

        return $this;
    }

    /**
     * Очищаем свойства объекта
     *
     * @return void
     */
    private function clear(): void
    {
        $this->code = null;
        $this->captchaBase64 = null;
    }
}
