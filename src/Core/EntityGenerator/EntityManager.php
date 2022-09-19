<?php

namespace Glavfinans\Core\EntityGenerator;

use Glavfinans\Core\Exception\NotFoundException;

/**
 * Класс для получения информации о сущностях и их репозиториях
 */
class EntityManager
{
    /** @var string|null $tableName - Имя таблицы в БД */
    private ?string $tableName;

    /** @var array $schemeTable - Схема таблицы */
    private array $schemeTable;

    /** @var array $indexesTable - Индексы таблицы */
    private array $indexesTable;

    /** @var array $errorType - Ошибка, если встречается неизвестный тип */
    private array $errorType;

    /**
     * Заполняем имя таблицы в БД, и дальше с ним работаем
     *
     * @param string|null $tableName
     */
    public function __construct(?string $tableName = null)
    {
        $this->tableName = $tableName;
    }

    /**
     * Устанавливает имя таблицы
     *
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * Получаем имя таблицы. Все действия производятся через этот метод
     *
     * @return string
     * @throws \Glavfinans\Core\Exception\NotFoundException
     */
    public function getTableName(): string
    {
        if (null === $this->tableName) {
            throw new NotFoundException("Не заполнено свойство (Имя таблицы)");
        }

        return $this->tableName;
    }

    /**
     * Вернёт true, если Entity существует по переданному имени таблицы
     *
     * @throws \Glavfinans\Core\Exception\NotFoundException
     */
    public function getExistingEntities(): bool
    {
        $entityName = $this->getEntityName();
        return is_file($this->getEntityDirectory() . $entityName . '/' . $entityName . '.php');
    }

    /**
     * Вернёт true, если Repository существует по переданному имени таблицы
     *
     * @return bool
     * @throws \Glavfinans\Core\Exception\NotFoundException
     */
    public function getExistingRepository(): bool
    {
        $entityName = $this->getEntityName();
        return is_file($this->getEntityDirectory() . $entityName . '/' . $entityName . 'Repository.php');
    }

    /**
     * Вернёт true, если IRepository существует по переданному имени таблицы
     *
     * @return bool
     * @throws \Glavfinans\Core\Exception\NotFoundException
     */
    public function getExistingInterfaceRepository(): bool
    {
        $entityName = $this->getEntityName();
        return is_file($this->getEntityDirectory() . $entityName . '/I' . $entityName . 'Repository.php');
    }

    /**
     * Получить директорию хранения Entity
     *
     * @return string
     */
    public function getEntityDirectory(): string
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/src/Core/Entity/';
    }

    /**
     * Преобразует snake_case в PascalCase
     *
     * @return string
     * @throws \Glavfinans\Core\Exception\NotFoundException
     */
    public function getEntityName(): string
    {
        return str_replace('_', '', ucwords($this->getTableName(), '_'));
    }

    /**
     * Возвращает текст по умолчанию, если файл существует
     *
     * @return string
     */
    public function getDefaultTestIfExist(): string
    {
        return 'Существует';
    }

    /**
     * Установить схему таблицы
     *
     * @param array $schemeTable
     */
    public function setSchemeTable(array $schemeTable): void
    {
        $clearScheme = [];
        foreach ($schemeTable as $id => $scheme) {
            $field = $scheme['Field'];
            if ($field === 'created_at' || $field === 'updated_at') {
                continue;
            }
            $clearScheme[$id] = $scheme;
        }
        $this->schemeTable = $clearScheme;
    }

    /**
     * Установить индексы таблицы
     *
     * @param array $indexesTable
     */
    public function setIndexesTable(array $indexesTable): void
    {
        $this->indexesTable = $indexesTable;
    }

    /**
     * Получить схему таблицы
     *
     * @return array
     */
    public function getSchemeTable(): array
    {
        return $this->schemeTable;
    }

    /**
     * Получить комментарий из таблицы в БД
     *
     * @return string
     */
    public function getComment(int $recordId): string
    {
        $comment = $this->schemeTable[$recordId]['Comment'];
        if (empty($comment)) {
            return 'todo - Заполнить комментарий';
        }

        return $comment;
    }

    /**
     * Получить комментарий для метода
     *
     * @param int $recordId
     * @return string
     */
    public function getCommentForMethods(int $recordId, bool $isSetter = false): string
    {
        $comment = $this->schemeTable[$recordId]['Comment'];
        if (empty($comment)) {
            return 'todo - Заполнить комментарий';
        }

        return $isSetter ? "Установить $comment" : "Получить $comment";
    }

    /**
     * Получить строку в стиле camelCase
     *
     * @param string $string
     * @return string
     */
    public function getCamelCase(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    /**
     * Получить строку в PascaleCase
     *
     * @param string $string
     * @return string
     */
    public function getPascalCase(string $string): string
    {
        return ucfirst($this->getCamelCase($string));
    }

    /**
     * Возвращает true - если поле имеет значение Nullable
     *
     * @param int $propertyId
     * @return bool
     * @throws \Glavfinans\Core\Exception\NotFoundException
     */
    public function isNullProperty(int $propertyId): bool
    {
        $null = $this->schemeTable[$propertyId]['Null'];
        if ('NO' === $null) {
            return false;
        }
        if ('YES' === $null) {
            return true;
        }
        throw new NotFoundException("Неизвестное значение NULL в базе");
    }

    /**
     * Возвращает тип данных по полю в таблице
     *
     * @param int $propertyId
     * @return string
     */
    public function getTypeProperty(int $propertyId): string
    {
        $clearType = $this->getClearTypeProperty($propertyId);
        switch ($clearType) {
            case 'int':
            case 'tinyint':
                return 'int';
            case 'date':
            case 'datetime':
            case 'timestamp':
                return "\DateTimeInterface";
            case 'varchar':
            case 'char':
            case 'tinytext':
            case 'text':
                return 'string';
            default:
                $this->errorType[$propertyId] = $this->schemeTable[$propertyId]['Type'];
                return 'string';
        }
    }

    /**
     * Получить свойство без скобок.
     * Было: int(11) => Стало: int
     *
     * @param int $propertyId
     * @return string|null
     */
    public function getClearTypeProperty(int $propertyId): ?string
    {
        $typeInDB = $this->schemeTable[$propertyId]['Type'];
        preg_match('/^[a-z]+/i', $typeInDB, $clear);

        if (!isset($clear[0])) {
            return null;
        }

        return $clear[0];
    }

    /**
     * Получить сообщение об отсутствующем типе
     *
     * @param int $propertyId
     * @return string|null
     */
    public function getMessageErrorType(int $propertyId): ?string
    {
        $this->getTypeProperty($propertyId);
        if (isset($this->errorType[$propertyId])) {
            return $this->getTextMessageErrorType($this->errorType[$propertyId]);
        }

        return null;
    }

    /**
     * Получить текст сообщения о неверном типе
     *
     * @param string $type
     * @return string
     */
    protected function getTextMessageErrorType(string $type): string
    {
        return "todo - Проверить тип данных, в таблице {$type}";
    }

    /**
     * Получить аннотацию Cycle для свойства
     *
     * @param int $propertyId
     * @return string
     */
    public function getCycleAnnotationProperty(int $propertyId): string
    {
        if ($this->isPrimaryProperty($propertyId)) {
            return '@Cycle\Column(type = "primary")';
        }

        $typeDirty = $this->schemeTable[$propertyId]['Type'];
        $typeClearString = $this->getClearTypeProperty($propertyId);
        $typeCycle = '';
        $lengthProperty = $this->getValueInBrackets($propertyId);

        switch ($typeClearString) {
            case 'datetime':
                $typeCycle = 'datetime';
                break;
            case 'date':
                $typeCycle = 'date';
                break;
            case 'time':
                $typeCycle = 'time';
                break;
            case 'timestamp':
                $typeCycle = 'timestamp';
                break;
            case 'int':
                $typeCycle = 'integer';
                break;
            case 'tinyint':
                $typeCycle = "tinyInteger{$lengthProperty}";
                break;
            case 'char':
            case 'varchar':
                $typeCycle = "string{$lengthProperty}";
                break;
            case 'tinytext':
                $typeCycle = "tinyText";
                break;
            case 'text':
                $typeCycle = "text";
                break;
            case 'enum':
                $typeCycle = "enum{$lengthProperty}";
                break;
            default:
                return "todo - Неизвестный тип {$typeDirty} - Заполните вручную";
        }

        $nullable = $this->isNullProperty($propertyId) ? 'true' : 'false';

        return "@Cycle\Column(type = \"{$typeCycle}\", nullable = $nullable)";
    }

    /**
     * Является ли свойство первичным ключом
     *
     * @param int $propertyId
     * @return bool
     */
    protected function isPrimaryProperty(int $propertyId): bool
    {
        foreach ($this->indexesTable as $index) {
            if ($index['Column_name'] === $this->schemeTable[$propertyId]['Field']
                && $index['Key_name'] === 'PRIMARY'
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Получить значение в скобочках
     *
     * @param int $propertyId
     * @return string|null
     */
    protected function getValueInBrackets(int $propertyId): ?string
    {
        preg_match('/\(.+\)/i', $this->schemeTable[$propertyId]['Type'], $clear);

        if (!isset($clear[0])) {
            return null;
        }

        /** Убираем кавычки для типа enum */
        return str_replace("'", '', $clear[0]);
    }
}
