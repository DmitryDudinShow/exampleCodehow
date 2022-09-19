<?php

namespace Glavfinans\Core\ComputerVision;

use Glavfinans\Core\Fssp\Captcha\FsspCaptcha;
use Glavfinans\Core\Fssp\Captcha\FsspCaptchaCollection;
use Imagick;
use ImagickException;

/**
 * Класс-обработчик изображений для извлечения текста
 */
class CaptchaReader
{
    /**
     * @param Imagick $imagick
     * @param FsspCaptchaModelDirector $director
     */
    public function __construct(protected Imagick $imagick, protected FsspCaptchaModelDirector $director) { }

    /**
     * Получить разгаданные капчи по коллекции
     *
     * @param FsspCaptchaCollection $captchaCollection
     *
     * @return FsspCaptchaCollection
     * @throws ImagickException
     */
    public function getSolvedByCollection(FsspCaptchaCollection $captchaCollection): FsspCaptchaCollection
    {
        /** @var FsspCaptcha $captcha */
        foreach ($captchaCollection as $captcha) {

            [$code, $cleanImage] = $this->director->optimalModel(hash: $captcha->getHash());

            $captcha->setCleanImage(cleanImage: base64_encode(string: $cleanImage));
            $captcha->setMachineCode(machineCode: $code);
            $captcha->setIsSuccess(isSuccess: false);

            if ($captcha->checkSuccess()) {
                $captcha->setIsSuccess(isSuccess: true);
                $captchaCollection->incrementResolvedCounter();
            }
        }

        return $captchaCollection;
    }
}
