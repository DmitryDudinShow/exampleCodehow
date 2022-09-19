<?php


namespace Glavfinans\Core;

/**
 * Class Collection класс базовой коллекции
 * @package Glavfinans\Core
 */
class Collection implements \Iterator, \Countable
{
    /**
     * @var int указатель на текущий элемент для реализации интерфейса Iterator
     */
    private $position = 0;

    /**
     * @var array используется для хранения объектов коллекции
     */
    protected $objects = [];

    /**
     * @param object $object добавляет объект в массив
     */
    protected function addObject(object $object): void
    {
        $this->objects[] = $object;
    }


    //############## Iterator START ##################################

    /**
     * Сброс указателя на текущий элемент на начало (интерефейс Iterator)
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Возвращает текущий элемент коллекции (интерефейс Iterator)
     * @return object
     */
    public function current(): object
    {
        return $this->objects[$this->position];
    }

    /**
     * Возвращает текущий указатель на элемент коллекции (интерефейс Iterator)
     * @return mixed
     */
    public function key(): mixed
    {
        return $this->position;
    }

    /**
     * Переводит указатель на следующий элемент (интерефейс Iterator)
     */
    public function next(): void
    {
        $this->position++;
    }

    /**
     * Проверяет существование элемента по текущему указателю (интерефейс Iterator)
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->objects[$this->position]);
    }

    //############## Iterator END ##################################


    //############## Countable START ##################################

    /**
     * Возвращает число элементов в коллекции (интерфейс Countable)
     * @return int
     */
    public function count(): int
    {
        return count($this->objects);
    }

    //############## Countable END ##################################

    /**
     * вспомогательная функция для реализации сортировок
     * @param string $field имя поля для сортировки, доступ осуществляется через get-метод
     * @param bool $direction - направление сортировки
     */
    protected function sortObjectsByField(string $field, bool $direction)
    {
        usort($this->objects, function (object $r1, object $r2) use ($field, $direction) {
            $result = $r1->{"get$field"}() <=> $r2->{"get$field"}();

            if ($direction) {
                return $result;
            }

            return -$result;
        });
    }


    /**
     * Возвращает список всех объектов в коллекции
     * @return array
     */
    public function getAll(): array
    {
        return $this->objects;
    }

    /**
     * Проверка на пустоту коллекции
     */
    public function isEmpty()
    {
        return empty($this->getAll());
    }
}
