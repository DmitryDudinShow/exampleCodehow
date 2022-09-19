<?php

namespace Glavfinans\Core\Fssp\Captcha;

use Glavfinans\Core\Collection;

/**
 * Коллекция каптч ФССП
 */
class FsspCaptchaCollection extends Collection
{
    /** @var int $countSuccessResolved - Счётчик успешно разгаданных каптч */
    protected int $countSuccessResolved = 0;

    /**
     * Добавить капчу в коллекцию
     *
     * @param FsspCaptcha $captcha
     *
     * @return void
     */
    public function add(FsspCaptcha $captcha): void
    {
        $this->addObject(object: $captcha);
    }

    /**
     * Увеличить счётчик успешно разгаданных каптч на единицу
     *
     * @return void
     */
    public function incrementResolvedCounter(): void
    {
        $this->countSuccessResolved++;
    }

    /**
     * Получить количество успешно разгаданных каптч
     *
     * @return int
     */
    public function getCountSuccessResolved(): int
    {
        return $this->countSuccessResolved;
    }

    /**
     * Получить процент успешно разгаданных каптч
     *
     * @return float
     */
    public function getPercentSuccessResolved(): float
    {
        if (0 === count($this)) {
            return 0;
        }

        return round(num: ($this->countSuccessResolved / count($this)) * 100, precision: 2);

    }
}
