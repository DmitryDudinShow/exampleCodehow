<?php

namespace Glavfinans\Core\Fssp\Captcha;

use ClickHouseDB\Client;

/**
 * Репозиторий для получения каптч ФССП
 */
class FsspCaptchaRepository
{
    /**
     * @param Client $client
     */
    public function __construct(protected Client $client) { }

    /**
     * Получить все записи
     *
     * @return array
     */
    public function findAll(): array
    {
        return $this->client->select(sql: "SELECT * FROM `fssp_captcha` LIMIT 100")->rows();
    }
}
