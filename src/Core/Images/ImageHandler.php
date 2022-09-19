<?php

namespace Glavfinans\Core\Images;

use DateTimeImmutable;
use Glavfinans\Core\Entity\ImageOptimization\ImageOptimization;
use Glavfinans\Core\Entity\ImageOptimization\ImageOptimizationStatus;
use Imagick;
use ImagickException;

/**
 * Компонент для обработки изображений
 * не использовать напрямую, управление объектом осуществляется только через ImageService
 */
class ImageHandler
{
    /** @var ImageOptimization $imageOptimization */
    protected ImageOptimization $imageOptimization;

    /** @var int $defaultMaxDimension - Максимальное расширение по умолчанию */
    protected int $defaultMaxDimension = 1500;

    /** @var array $errors - Описание ошибки обработки файла */
    protected array $errors;

    /**
     * @param Imagick $image
     */
    public function __construct(
        protected Imagick $image
    ) {}

    /**
     * Создаёт объект ImageOptimization
     * Необходим для записи в базу изображений до и после оптимизации
     * Обязателен при ресайзе
     *
     * @throws ImagickException
     */
    protected function createImageOptimization(): void
    {
        $this->imageOptimization = new ImageOptimization();
        $this->imageOptimization->setDate(new DateTimeImmutable());
        $this->imageOptimization->setPath($this->image->getImageFilename());
        $this->imageOptimization->setSizeBefore(filesize($this->image->getImageFilename()));
        $this->imageOptimization->setSizeAfter($this->imageOptimization->getSizeBefore());
    }

    /**
     * Обрезать изображение
     *
     * @param int|null $maxDimension
     * @return bool
     * @throws ImagickException
     */
    public function resize(?int $maxDimension = null): bool
    {
        $this->createImageOptimization();

        if (null === $maxDimension) {
            $maxDimension = $this->defaultMaxDimension;
        }

        $height = $this->image->getImageHeight();
        $width = $this->image->getImageWidth();
        $oldFileSize = filesize($this->image->getImageFilename());

        $this->imageOptimization->setWidthBefore($width);
        $this->imageOptimization->setHeightBefore($height);
        $this->imageOptimization->setWidthAfter($width);
        $this->imageOptimization->setHeightAfter($height);

        if ($width <= $maxDimension && $height <= $maxDimension) {
            $isSuccessSave = $this->fileReplacement();
            $this->setStatusImageOptimization($isSuccessSave, $oldFileSize);

            return $isSuccessSave;
        }

        if ($width > $height) {
            $this->image->thumbnailImage($maxDimension, 0);
        } else {
            $this->image->thumbnailImage(0, $maxDimension);
        }

        $this->imageOptimization->setWidthAfter($this->image->getImageWidth());
        $this->imageOptimization->setHeightAfter($this->image->getImageHeight());

        $isSuccessSave = $this->fileReplacement();
        $this->setStatusImageOptimization($isSuccessSave, $oldFileSize);

        return $isSuccessSave;
    }

    /**
     * Установить статус обработки изображения
     *
     * @param bool $isSuccessSave
     * @param int $oldFileSize
     */
    protected function setStatusImageOptimization(bool $isSuccessSave, int $oldFileSize): void
    {
        $this->imageOptimization->setStatus(new ImageOptimizationStatus(ImageOptimizationStatus::STATUS_FAILED));
        if ($isSuccessSave) {
            $this->imageOptimization->setStatus(new ImageOptimizationStatus(ImageOptimizationStatus::STATUS_SUCCESS));
            $this->imageOptimization->setSizeAfter($oldFileSize);
        }
    }

    /**
     * Сохранение нового файла и удаление старого
     *
     * @param bool $isPreview
     * @return bool
     * @throws ImagickException
     */
    private function fileReplacement(bool $isPreview = false): bool
    {
        /** Сохраняем путь к старому файлу, который будем заменять */
        $filePath = $this->image->getImageFilename();

        /** Новое имя файла - Пример: modify.2.jpg */
        $newFileName = sprintf('modify.%s', basename($filePath));

        /** Получаем директорию файла */
        $newPathOnly = dirname($this->image->getImageFilename());

        /** Если создаём превью, то добавляем к пути папку min */
        if ($isPreview) {
            $newPathOnly .= '/min/';
            $filePath = $newPathOnly . basename($filePath);
        }

        /** Новое имя файла с полным путём */
        $newFilePath = sprintf('%s/%s', $newPathOnly, $newFileName);

        /** Сохраняем новый файл */
        $result = $this->image->writeImage($newFilePath);

        /** Освобождаем память */
        $this->image->clear();

        $isSuccessSave = true;
        if (!$result) {
            $this->setErrors(
                sprintf(
                    "Не удалось сохранить файл %s",
                    $filePath
                )
            );
            $isSuccessSave = false;
        }

        if ($isSuccessSave && !file_exists($newFilePath)) {
            $this->setErrors(sprintf('Не удалось сохранить файл при обработке изображения %s', $newFilePath));
            $isSuccessSave = false;
        }

        /** Удаляем старый файл */
        if ($isSuccessSave && file_exists($filePath) && !unlink($filePath)) {
            $this->setErrors(sprintf('Не удалось удалить старый файл после обработки %s', $filePath));
            $isSuccessSave = false;
        }

        /** Переименовываем новый */
        if ($isSuccessSave && !rename($newFilePath, $filePath)) {
            $this->setErrors(
                sprintf(
                    'Не удалось переименовать файл %s to %s',
                    $newFilePath, $filePath
                )
            );
            $isSuccessSave = false;
        }

        return $isSuccessSave;
    }

    /**
     * Поворот изображения на $degrees градусов
     *
     * @param int $degrees
     * @return bool
     * @throws ImagickException
     */
    public function rotate(int $degrees): bool
    {
        $this->image->rotateImage('white', $degrees);

        return $this->fileReplacement();
    }

    /**
     * Заменяем превьюшку
     *
     * @return bool
     * @throws ImagickException
     */
    public function replacePreview(): bool
    {
        $this->image->cropThumbnailImage($this->getPreviewSize(), $this->getPreviewSize());

        return $this->fileReplacement(true);
    }

    /**
     * Создать превью изображения
     * В параметре передаётся полный путь куда сохранить с именем файла
     *
     * @param string $previewPath
     * @return bool
     * @throws ImagickException
     */
    public function createPreview(string $previewPath): bool
    {
        $this->image->cropThumbnailImage($this->getPreviewSize(), $this->getPreviewSize());

        return $this->image->writeImage($previewPath);
    }

    /**
     * Возвращает размер квадратной превьюшки
     *
     * @return int
     */
    protected function getPreviewSize(): int
    {
        return 100;
    }

    /**
     * Получить объект ImageOptimization
     *
     * @return ImageOptimization
     */
    public function getImageOptimization(): ImageOptimization
    {
        return $this->imageOptimization;
    }

    /**
     * Установка описания ошибки
     *
     * @param string $message
     */
    protected function setErrors(string $message): void
    {
        $this->errors[] = $message;
    }

    /**
     * Получить массив ошибок
     *
     * @return array
     */
    protected function getErrors(): array
    {
        return $this->errors;
    }
}
