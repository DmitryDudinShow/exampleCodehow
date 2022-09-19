<?php

namespace Glavfinans\Core\Fssp\Captcha;

use DateTimeImmutable;
use DomainException;
use Glavfinans\Core\Exception\NotFoundException;

/**
 * Фабрика для сборки объекта FsspCaptcha из таблицы fssp_captcha
 */
class FsspCaptchaFactory
{
    /**
     * Создать объект ФССП Капчи из массива с предварительной валидацией
     *
     * @param array $captchaData
     *
     * @return FsspCaptcha
     * @throws NotFoundException
     */
    public function make(array $captchaData): FsspCaptcha
    {
        $this->validate(captchaData: $captchaData);

        return new FsspCaptcha(
            uuid:        $captchaData['uuid'],
            createdAt:   new DateTimeImmutable(datetime: $captchaData['created_at']),
            isResolved:  $captchaData['is_resolved'],
            hash:        $captchaData['hash'],
            handCode:    $captchaData['hand_code'],
            machineCode: $captchaData['machine_code']
        );
    }

    /**
     * Создать коллекцию ФССП каптч
     *
     * @param array $captchaMultiplicity
     *
     * @return FsspCaptchaCollection
     * @throws NotFoundException
     */
    public function makeCollection(array $captchaMultiplicity): FsspCaptchaCollection
    {
        $collection = new FsspCaptchaCollection();

        foreach ($captchaMultiplicity as $captchaData) {
            if (!is_array(value: $captchaData)) {
                throw new DomainException(message: 'Ошибка при сборке FsspCaptcha. Пришли некорректные данные', code: 422);
            }

            $collection->add(captcha: $this->make(captchaData: $captchaData));
        }

        return $collection;
    }

    /**
     * Валидация данных для корректной сборки объекта
     *
     * @param array $captchaData
     *
     * @return void
     * @throws NotFoundException
     */
    private function validate(array $captchaData): void
    {
        /** Проверка на необходимые поля */
        $needAttributes = ['uuid', 'created_at', 'is_resolved', 'hash'];

        foreach ($needAttributes as $attribute) {
            if (!isset($captchaData[$attribute])) {
                throw new NotFoundException(
                    message: 'Ошибка при сборке FsspCaptcha. При получении данных отсутствует параметр ' . $attribute,
                    code:    422,
                );
            }
        }

        /** Проверка формата даты */
        if (!(new DateTimeImmutable(datetime: $captchaData['created_at']))) {
            throw new DomainException(
                message: 'Ошибка при сборке FsspCaptcha. Пришёл неверный формат даты у капчи с uuid: ' . $captchaData['uuid'],
                code:    422,
            );
        }
    }
}
