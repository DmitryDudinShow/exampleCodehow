<?php

namespace Glavfinans\Core\Fssp\ParseCaptcha;

use Glavfinans\Core\Collection;

/**
 * Коллекция DTO для передачи данных в ФССП
 */
class FsspRequestDtoCollection extends Collection
{
    /**
     * Добавить DTO в коллекцию
     *
     * @param FsspRequestDtoInterface $dto
     *
     * @return void
     */
    public function add(FsspRequestDtoInterface $dto): void
    {
        $this->addObject(object: $dto);
    }
}
