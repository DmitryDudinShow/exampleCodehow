<?php

namespace Glavfinans\Core\Fssp\ParseCaptcha\Tracker;

use Exception;
use LogicException;

/**
 * Отслеживатель состояния парсинга ФССП построенное на файле
 */
class FsspParseTracker
{
    /** @var string $filePath - Путь к файлу */
    private string $filePath;

    /**
     * Задаем файл для хранения
     */
    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? $this->getBasePath();
    }

    /**
     * Запустить процесс
     *
     * @return void
     */
    public function startProcess(): void
    {
        $this->writeFileFromObject(eventState: FsspParseEventState::makeStartProcess());
    }

    /**
     * Возвращает true - если в текущий момент процесс запущен
     *
     * @param int $processId
     *
     * @return bool
     */
    private function isProcessInWork(int $processId): bool
    {
        exec(command: "ps -xa|grep 'php ./yiic fssp parseSite'", output: $output);
        foreach ($output as $item) {
            if (str_contains(haystack: $item, needle: $processId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Остановить процесс
     *
     * @return void
     * @throws Exception
     */
    public function endProcess(): void
    {
        if (!$this->isStateInProcess()) {
            return;
        }

        if (false === unlink(filename: $this->filePath)) {
            throw new LogicException(message: "Не удалось удалить файл {$this->filePath}");
        }
    }

    /**
     * Получить актуальное состояние парсинга, или null - если он остановлен
     *
     * @return FsspParseEventState|null
     * @throws Exception
     */
    public function getEventState(): ?FsspParseEventState
    {
        if (!$this->isStateInProcess()) {
            return null;
        }

        $jsonState = file_get_contents(filename: $this->filePath);
        if (false === $jsonState) {
            throw new LogicException(message: "Не удалось прочитать из файла {$this->filePath}");
        }

        return FsspParseEventState::makeFromJson(json: $jsonState);
    }

    /**
     * Зафиксировать в трекере событие парсинга
     *
     * @param Exception|null $error
     *
     * @return void
     * @throws Exception
     */
    public function commitStateParse(?Exception $error = null): void
    {
        $eventState = $this->getEventState();

        if (null === $eventState) {
            return;
        }

        /** Если есть ошибка - записываем её, иначе это успешный коммит */
        if (null !== $error) {
            $eventState->setError(error: $error);
        } else {
            $eventState->incrementSuccess();
        }

        $this->writeFileFromObject(eventState: $eventState);
    }

    /**
     * Записать в файл данные по объекту состояния
     *
     * @param FsspParseEventState $eventState
     *
     * @return void
     */
    private function writeFileFromObject(FsspParseEventState $eventState): void
    {
        $fileResource = fopen(filename: $this->filePath, mode: 'w+');
        if (false === $fileResource) {
            throw new LogicException(message: "Не удалось создать/открыть файл {$this->filePath}");
        }

        $isSuccessWrite = fwrite(stream: $fileResource, data: $eventState);

        if (false === $isSuccessWrite) {
            throw new LogicException(message: "Не удалось записать в файл {$this->filePath}");
        }
    }

    /**
     * Возвращает true - если парсинг запущен
     *
     * @return bool
     * @throws Exception
     */
    public function isStateInProcess(): bool
    {
        $isFileExist = file_exists(filename: $this->filePath);

        if (!$isFileExist) {
            return false;
        }

        $jsonState = file_get_contents(filename: $this->filePath);
        if (false === $jsonState) {
            throw new LogicException(message: "Не удалось прочитать из файла {$this->filePath}");
        }

        $event = FsspParseEventState::makeFromJson(json: $jsonState);

        $inProcess = $this->isProcessInWork(processId: $event->getProcessId());

        if (!$inProcess) {
            if (false === unlink(filename: $this->filePath)) {
                throw new LogicException(message: "Не удалось удалить файл {$this->filePath}");
            }
        }

        return $inProcess;
    }

    /**
     * Получить дефолтный путь до файла
     *
     * @return string
     */
    private function getBasePath(): string
    {
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            return $_SERVER['DOCUMENT_ROOT'] . '/common/monitoring/fsspParse.txt';
        }

        return __DIR__ . '/../../../../../common/monitoring/fsspParse.txt';
    }
}
