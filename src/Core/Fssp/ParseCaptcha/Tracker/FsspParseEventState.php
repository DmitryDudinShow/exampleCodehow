<?php

namespace Glavfinans\Core\Fssp\ParseCaptcha\Tracker;

use DateFmt;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use JsonSerializable;
use LogicException;

/**
 * Объект состояния парсинга ФССП
 */
class FsspParseEventState implements JsonSerializable
{
    /**
     * @param DateTimeInterface $startDate
     * @param int $countSuccess
     * @param int $processId
     * @param DateTimeInterface|null $lastSuccessDate
     * @param DateTimeInterface|null $lastErrorDate
     * @param string|null $lastError
     */
    private function __construct(
        private DateTimeInterface  $startDate,
        private int                $countSuccess,
        private int                $processId,
        private ?DateTimeInterface $lastSuccessDate,
        private ?DateTimeInterface $lastErrorDate,
        private ?string            $lastError,
    ) {}

    /**
     * Получить дату начала парсинга
     *
     * @return DateTimeInterface
     */
    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    /**
     * Получить количество успешно полученных запросов
     *
     * @return int
     */
    public function getCountSuccess(): int
    {
        return $this->countSuccess;
    }


    /**
     * Получить дату последнего успешного запроса
     *
     * @return DateTimeInterface|null
     */
    public function getLastSuccessDate(): ?DateTimeInterface
    {
        return $this->lastSuccessDate;
    }

    /**
     * Получить дату последнего неудачного запроса
     *
     * @return DateTimeInterface|null
     */
    public function getLastErrorDate(): ?DateTimeInterface
    {
        return $this->lastErrorDate;
    }

    /**
     * Получить ID процесса
     *
     * @return int
     */
    public function getProcessId(): int
    {
        return $this->processId;
    }

    /**
     * Получить текст последнего неудачного запроса
     *
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Получить среднюю скорость парсинга в час
     *
     * @return float
     */
    public function getAverageSpeedParse(): float
    {
        $interval = $this->getStartDate()->diff(targetObject: new DateTimeImmutable());

        $seconds = $interval->days * 86400 +
            $interval->h * 3600 +
            $interval->i * 60 +
            $interval->s;

        return round(num: ($this->getCountSuccess() / ($seconds / 3600)), precision: 2);
    }

    /**
     * Создать объект нового процесса
     *
     * @return static
     */
    public static function makeStartProcess(): self
    {
        return new self(
            startDate:       new DateTimeImmutable(),
            countSuccess:    0,
            processId:       (int)getmypid(),
            lastSuccessDate: null,
            lastErrorDate:   null,
            lastError:       null,
        );
    }

    /**
     * Создать объект состояния из json
     *
     * @param string $json
     *
     * @return static
     * @throws Exception
     */
    public static function makeFromJson(string $json): self
    {
        $arrayData = json_decode(json: $json, associative: true);

        if (false === $arrayData) {
            throw new LogicException(
                message: 'Не удалось раскодировать json для отслеживания события парсинга ФССП.' .
                         print_r(value: $json, return: true)
            );
        }

        $selfAttributes = get_class_vars(class: self::class);

        /** Проверка, что структура файла соответствует объекту трекера */
        if (!empty(array_diff_key($selfAttributes, $arrayData))) {
            throw new LogicException(
                message: "Некорректный формат файла для отслеживания события парсинга ФССП." .
                         print_r(value: $arrayData, return: true)
            );
        }

        $lastSuccessDate = null;
        if (null !== $arrayData['lastSuccessDate']) {
            $lastSuccessDate = new DateTimeImmutable(datetime: $arrayData['lastSuccessDate']);
        }

        $lastErrorDate = null;
        if (null !== $arrayData['lastErrorDate']) {
            $lastErrorDate = new DateTimeImmutable(datetime: $arrayData['lastErrorDate']);
        }

        return new self(
            startDate:       new DateTimeImmutable(datetime: $arrayData['startDate']),
            countSuccess:    $arrayData['countSuccess'],
            processId:       $arrayData['processId'],
            lastSuccessDate: $lastSuccessDate,
            lastErrorDate:   $lastErrorDate,
            lastError:       $arrayData['lastError'],
        );
    }

    /**
     * Увеличить счётчик успешного парсинга и обновление даты
     *
     * @return $this
     */
    public function incrementSuccess(): self
    {
        $this->countSuccess++;
        $this->lastSuccessDate = new DateTimeImmutable();

        return $this;
    }

    /**
     * Записать ошибку
     *
     * @param Exception $error
     *
     * @return $this
     */
    public function setError(Exception $error): self
    {
        $this->lastError = $error->getMessage();
        $this->lastErrorDate = new DateTimeImmutable();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'startDate' => $this->startDate->format(format: DateFmt::DT_DB),
            'lastSuccessDate' => $this->lastSuccessDate?->format(format: DateFmt::DT_DB),
            'processId' => $this->processId,
            'countSuccess' => $this->countSuccess,
            'lastErrorDate' => $this->lastErrorDate?->format(format: DateFmt::DT_DB),
            'lastError' => $this->lastError,
        ];
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $json = json_encode(value: $this->jsonSerialize());

        if (false === $json) {
            throw new LogicException(message: 'Не получилось закодировать json из массива');
        }

        return $json;
    }
}
