<?php

namespace Glavfinans\Core\EntityGenerator;

use Glavfinans\Core\Exception\BaseException;

/**
 * Генерация новой сущности
 */
class EntityGenerator
{
    /**
     * Менеджер для работы с Entity
     *
     * @var \Glavfinans\Core\EntityGenerator\EntityManager $entityManager
     */
    protected EntityManager $entityManager;

    /**
     * Принимаем имя файла
     *
     * @param \Glavfinans\Core\EntityGenerator\EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        /** Нельзя генерировать файлы на продакшене */
        if (PRODUCTION_SERVER) {
            throw new BaseException("Нельзя генерировать файлы на боевом сервере");
        }

        $this->entityManager = $entityManager;
    }

    /**
     * Создать файл Entity
     *
     */
    protected function getNewEntity()
    {
        $entityManager = $this->entityManager;
        $file = __DIR__ . '/templateEntity.php';
        ob_start();
        ob_implicit_flush(false);
        require($file);
        return ob_get_clean();
    }

    /**
     * Создать файл репозитория
     *
     */
    protected function getNewRepository()
    {
        $entityManager = $this->entityManager;
        $file = __DIR__ . '/templateRepository.php';
        ob_start();
        ob_implicit_flush(false);
        require($file);
        return ob_get_clean();
    }

    /**
     * Создать файл интерфейс репозитория
     *
     */
    protected function getNewRepositoryInterface()
    {
        $entityManager = $this->entityManager;
        $file = __DIR__ . '/templateRepositoryInterface.php';
        ob_start();
        ob_implicit_flush(false);
        require($file);
        return ob_get_clean();
    }

    /**
     * Сгенерировать Entity, Repository и RepositoryInterface
     *
     * @throws \Glavfinans\Core\Exception\NotFoundException
     */
    public function createFiles(): bool
    {
        $entityDirectory = $this->entityManager->getEntityDirectory();
        $entityName = $this->entityManager->getEntityName();
        $fileDirectory = $entityDirectory . $entityName;

        if (!is_dir($fileDirectory) && !mkdir($fileDirectory, 0777, true) && !is_dir($fileDirectory)) {
            throw new \RuntimeException('Не удалось создать папку ' . $fileDirectory);
        }

        if (!file_put_contents("{$fileDirectory}/{$entityName}.php", $this->getNewEntity())
            || !file_put_contents("{$fileDirectory}/{$entityName}Repository.php", $this->getNewRepository())
            || !file_put_contents("{$fileDirectory}/I{$entityName}Repository.php", $this->getNewRepositoryInterface())
        ) {
            throw new \RuntimeException('Не удалось создать файл ' . $fileDirectory);
        }

        return true;
    }
}
