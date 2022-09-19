<?php

namespace Glavfinans\Core\Images;

use DateFmt;
use DateTimeImmutable;
use Glavfinans\Core\Entity\ImageOptimization\ImageOptimization;
use Glavfinans\Core\Entity\ImageOptimization\IImageOptimizationRepository;
use Glavfinans\Core\Exception\NotFoundException;
use Glavfinans\Core\Filesystem\FilesystemFactory;
use Imagick;
use ImagickException;
use League\Flysystem\FilesystemException;

/**
 * Сервис для работы с изображениями
 */
class ImageService
{
    /** @var int $maxSizeImage - Максимальный размер изображения */
    protected int $maxSizeImage = 1000000;

    /**
     * @param IImageOptimizationRepository $imageOptimizationRepository
     */
    public function __construct(
        protected IImageOptimizationRepository $imageOptimizationRepository
    ) {}

    /**
     * Обрезать фотографию. Используется как единая точка для ресайза
     * Возвращает объект ImageOptimization, который при необходимости можно сохранить в БД снаружи
     * Если $maxDimension не задан - используется 1500px
     *
     * @param string $filePath
     * @param int|null $maxDimension
     * @return ImageOptimization
     * @throws ImagickException
     */
    public function resize(string $filePath, ?int $maxDimension = null): ImageOptimization
    {
        $imageHandler = $this->getImageHandler($filePath);
        $imageHandler->resize($maxDimension);

        return $imageHandler->getImageOptimization();
    }

    /**
     * Создаёт объект ImageHandler и все необходимые для него компоненты
     * Устанавливает объект изображения, объект ImageOptimization и возвращает сконфигурированный объект ImageHandler
     *
     * @param string $filePath
     * @return ImageHandler
     * @throws ImagickException
     */
    protected function getImageHandler(string $filePath): ImageHandler
    {
        $imagick = new Imagick(realpath($filePath));

        return new ImageHandler($imagick);
    }

    /**
     * Поворот изображения на $degrees градусов
     *
     * @param string $filePath
     * @param int $degrees
     * @return bool
     * @throws ImagickException
     */
    public function rotate(string $filePath, int $degrees): bool
    {
        $imagick = $this->getImageHandler($filePath);

        return $imagick->rotate($degrees) && $this->replacePreview($filePath);
    }

    /**
     * Заменяет превью изображения на новое
     *
     * @param string $filePath
     * @return bool
     * @throws ImagickException
     */
    protected function replacePreview(string $filePath): bool
    {
        $imagick = $this->getImageHandler($filePath);

        return $imagick->replacePreview();
    }

    /**
     * Создать превью изображения
     *
     * @param string $filePath - По какому изображению создать превью
     * @param string $previewPath - Имя файла с путём для превьюшки
     * @return bool
     * @throws ImagickException
     */
    public function createPreview(string $filePath, string $previewPath): bool
    {
        $imagick = $this->getImageHandler($filePath);

        return $imagick->createPreview($previewPath);
    }

    /**
     * Возвращает true, если изображение уже было оптимизировано
     *
     * @param string $path
     * @return bool
     */
    public function isImageWasAlreadyOptimized(string $path): bool
    {
        return null !== $this->imageOptimizationRepository->findOptimizedImageByPath($path);
    }

    /**
     * Нужно ли пережимать файл
     * Если размер файла после оптимизации больше максимально допустимого - вернёт true
     *
     * @param string $path
     * @return bool
     */
    public function isNeedToBeResized(string $path): bool
    {
        return $this->maxSizeImage < $this->imageOptimizationRepository->findOptimizedImageByPath($path)->getSizeAfter();
    }

    /**
     * @param string $firstPhoto
     * @param string $secondPhoto
     * @return string
     */
    public function gluePhotos(string $firstPhoto, string $secondPhoto): string
    {
        $firstImageSize = getimagesizefromstring($firstPhoto);
        $secondImageSize = getimagesizefromstring($secondPhoto);

        $destWidth = $firstImageSize[0] + $secondImageSize[0];
        $destHeight = max($firstImageSize[1], $secondImageSize[1]);

        $dest = imagecreatetruecolor($destWidth, $destHeight);
        $fileName = (new DateTimeImmutable())->format(DateFmt::D_DB) . '.png';

        $firstPhotoImage = imagecreatefromstring($firstPhoto);
        $secondPhotoImage = imagecreatefromstring($secondPhoto);

        imagecopy($dest, $firstPhotoImage , 0, 0, 0, 0, $firstImageSize[0], $firstImageSize[1]);
        imagecopy($dest, $secondPhotoImage, $firstImageSize[0], 0, 0, 0, $secondImageSize[0], $secondImageSize[1]);

        imagedestroy($firstPhotoImage);
        imagedestroy($secondPhotoImage);
        ob_start();
        imagepng($dest);
        return ob_get_clean();
    }
}
