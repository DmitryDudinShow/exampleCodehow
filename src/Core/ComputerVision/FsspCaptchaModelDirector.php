<?php

namespace Glavfinans\Core\ComputerVision;

use ImagickException;

/**
 * Управление различными моделями обработки изображения для улучшения читаемости
 */
class FsspCaptchaModelDirector
{
    /**
     * @param FsspCaptchaModel $model
     */
    public function __construct(protected FsspCaptchaModel $model) { }

    /**
     * Основной алгоритм оптимизации изображения
     * Подходит для капч ФССП
     * Ресайзит на 0.7, затем 0.4, затем 0.53, и на ряд чисел пока не найдёт 5 символов
     *
     * @param string $hash
     *
     * @return array
     * @throws ImagickException
     */
    public function optimalModel(string $hash): array
    {
        $factories = [0.7, 0.4, 0.53];
        $factories += [...range(start: 0.3, end: 2.7, step: 0.1)];

        /** Если длина не соответствует 5 - перебираем все ресайзы, и ждём пока количество совпадёт */
        do {
            $factor = array_shift(array: $factories);
            [$cleanImage, $code] = $this->model->getHashAndCode(hash: $hash, factor: $factor);
        } while (5 !== mb_strlen(string: $code) && !empty($factories));

        return [$code, $cleanImage];
    }
}
