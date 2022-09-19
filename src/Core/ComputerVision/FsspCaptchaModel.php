<?php

namespace Glavfinans\Core\ComputerVision;

use Imagick;
use ImagickException;
use LogicException;
use thiagoalessio\TesseractOCR\TesseractOCR;

/**
 * Модель обработки изображения для улучшения читаемости
 */
class FsspCaptchaModel
{
    /**
     * @param Imagick $imagick
     */
    public function __construct(protected Imagick $imagick) { }

    /**
     * Получить очищенное изображение с коррекцией адаптации изображения b разгаданный код
     *
     * @param string $hash
     * @param float|null $factor
     *
     * @return array
     * @throws ImagickException
     */
    public function getHashAndCode(string $hash, ?float $factor = 0.7): array
    {
        if (!stristr(haystack: $hash, needle: ',')) {
            throw new LogicException(message: 'Пришёл некорректный хэш на обработку изображения', code: 422);
        }

        /** Получаем хэш без начальных мета-данных */
        [, $blob] = explode(separator: ',', string: $hash);

        /** Заполняем объект Imagick на основе данных blob */
        $this->imagick->readImageBlob(image: base64_decode(string: $blob));

        $height = $this->imagick->getImageHeight();
        $width = $this->imagick->getImageWidth();

        /** Ресайзим изображение */
        $this->imagick->adaptiveResizeImage(columns: $width * $factor, rows: $height * $factor);

        /** Установка градации серого цвета */
        $this->imagick->setImageColorspace(colorspace: Imagick::COLORSPACE_GRAY);

        /** Размытие по Гауссу */
        $this->imagick->adaptiveBlurImage(radius: 5, sigma: 1, channel: Imagick::CHANNEL_BLUE);

        /** Добавляем контаста */
        $this->imagick->contrastImage(sharpen: true);

        /** Уменьшаем спекл-шум */
        $this->imagick->despeckleImage();

        /** Клонируем объект Imagick, и убиваем состояние из свойства объекта (Так надо) */
        $imagick = clone $this->imagick;
        $this->imagick->destroy();

        $code = $this->getCode(imagick: $imagick);

        return [$imagick, $code];
    }

    /**
     * Получить код по изображению
     *
     * @param Imagick $imagick
     *
     * @return string
     * @throws ImagickException
     */
    protected function getCode(Imagick $imagick): string
    {
        $ocr = new TesseractOCR();

        /** Передача изображения Тессерактору */
        $ocr->imageData(image: $imagick->getImagesBlob(), size: $imagick->getImageLength());

        /** Правила для выборки символов (Русские буквы и цифры) */
        $allowSymbols = [...$this->getRusLetters(), ...range(start: 0, end: 9)];

        /**
         * Установка правил извлечения текста:
         * 1) Язык - Русский
         * 2) Отключаем обработку через временные файлы для оптимизации - todo пока вырубил для теста
         * 3) Добавляем белый список из Русских символов и цифр
         * 4) Используем оптимальную сегментацию
         * 5) Ограничиваем в 1 поток
         */
        $code = $ocr->lang('rus')/**->withoutTempFiles()*/->allowList($allowSymbols)->psm(6)/**->threadLimit(1)*/->run();

        /** Очистка кода от ненужностей */
        return str_replace(search: [' '], replace: '', subject: $code);
    }

    /**
     * Получить массивом Русские симмволы, который есть в капче (Получены методом научного тыка)
     *
     * @return string[]
     */
    private function getRusLetters(): array
    {
        return [
            'А', 'а', 'Б', 'б', 'В', 'в', 'Г', 'г', 'Д', 'д', 'Е', 'е', 'Ж', 'ж', 'З', 'з',
            'И', 'и', 'К', 'к', 'Л', 'л', 'М', 'м', 'Н', 'н',  'П', 'п', 'Р', 'р',
            'С', 'с', 'Т', 'т', 'У', 'у', 'Ф', 'ф', 'Х', 'х', 'Ц', 'ц', 'Ч', 'ч', 'Ш', 'ш', 'Щ', 'щ',
            'Ъ', 'ъ', 'Ы', 'ы', 'Ь', 'ь', 'Э', 'э', 'Ю', 'ю',
        ];
    }
}
