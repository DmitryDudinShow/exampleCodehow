<?php

namespace Glavfinans\Core\Fssp\ParseCaptcha;

use ClickHouseDB\Client as ClientCH;
use DateFmt;
use DateTimeImmutable;
use Exception;
use Generator;
use Glavfinans\Core\Fssp\ParseCaptcha\Tracker\FsspParseTracker;
use GuzzleHttp\Exception\ServerException;
use LogicException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Класс-обработчик для парсинга сайта ФССП
 */
class FsspSiteService
{
    /**
     * @param FsspSiteClient $client
     * @param ClientCH $clientCH
     * @param FsspParseTracker $tracker
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected FsspSiteClient   $client,
        protected ClientCH         $clientCH,
        protected FsspParseTracker $tracker,
        protected LoggerInterface  $logger,
    ) {}

    /**
     * Сохраняет пачку капчт
     *
     * @param int $limit
     *
     * @return void
     */
    public function saveCaptchaCollection(int $limit = 10): void
    {
        $i = 1;
        $startStream = new DateTimeImmutable();

        while ($i < $limit) {

            /** Каждые 5 минут надо подождать, так нужно */
            $dateDiff = $startStream->diff(targetObject: new DateTimeImmutable())->i;
            if ($dateDiff >= 4) {
                echo (new DateTimeImmutable())->format(format: DateFmt::DT_DB) . ' | dateDiff >=4 | ' . $i . PHP_EOL;
                sleep(seconds: 15);
                $startStream = new DateTimeImmutable();
            }

            try {
                $hash = $this->client->getCaptcha();
                echo (new DateTimeImmutable())->format(format: DateFmt::DT_DB) . ' | Получили хэш | ' . $i . PHP_EOL;
            } catch (ServerException) {
                echo (new DateTimeImmutable())->format(format: DateFmt::DT_DB) . ' | Ушли в sleep 1 | ' . $i . PHP_EOL;
                sleep(seconds: 3);
                continue;
            } catch (Throwable) {
                echo (new DateTimeImmutable())->format(format: DateFmt::DT_DB) . ' | Какая-то ошибка | ' . $i . PHP_EOL;
                continue;
            }

            $this->clientCH->insertAssocBulk(tableName: '`fssp_captcha`', values: ['hash' => $hash]);
            $i++;
            sleep(seconds: 1);
        }
    }

    /**
     * Получить исполнительные производства по коллекции DTO
     *
     * @param FsspRequestDtoCollection $collectionDto
     *
     * @return FsspEpRawCollection[]
     */
    public function getEp(FsspRequestDtoCollection $collectionDto): array
    {
        $result = [];
        foreach ($this->getEpByGenerator(collectionDto: $collectionDto) as $ep) {
            $result[] = $ep;
        }

        return $result;
    }

    /**
     * Получить ИП по коллекции DTO с информацией о клиентах в виде генератора
     *
     * @param FsspRequestDtoCollection $collectionDto
     *
     * @return Generator
     * @throws Exception
     */
    public function getEpByGenerator(FsspRequestDtoCollection $collectionDto): Generator
    {
        $start = microtime(as_float: true);
        $this->tracker->startProcess();

        /** @var FsspRequestDtoInterface $dto */
        foreach ($collectionDto as $dto) {
            $i = 0;

            /** while (true) для возможности получения целостных данных */
            while (true) {
                try {
                    if ($i++ > 10) {
                        echo 'i > 10 . Спим' . PHP_EOL;
                        sleep(seconds: rand(min: 5, max: 10));
                    }

                    if ($i > 50) {
                        echo 'i > 50 ' . PHP_EOL;
                        sleep(seconds: 300);
                    }

                    /** Возвращаем результат */
                    yield $this->client->sendRequestGetEp(dto: $dto);

                    /** Трэкнули успешность */
                    $this->tracker->commitStateParse();

                    /** Пока для отладки */
                    echo round(num: microtime(as_float: true) - $start, precision: 2) . ' | Получили с ' . $i . ' попытки | Дата: ' .
                        (new DateTimeImmutable())->format(format: DateFmt::DT_DB) . PHP_EOL;
                    $start = microtime(as_float: true);
                    sleep(seconds: 1);

                    /** Переход к запросу по следующему клиенту */
                    break;
                } catch (LogicException $e) { /** Отлавливаем то, что может нарушить структуру */
                    echo $e->getMessage() . PHP_EOL;
                    $this->tracker->endProcess();
                    $this->logger->error(message: "Парсинг ФССП вырубился: {$e->getMessage()}");
                    throw $e;
                } catch (Throwable $e) { /** Остальные ошибки не критичны, пробуем заново отправить запрос */
                    echo 'Ошибка ' . $e->getMessage() . PHP_EOL;
                    $this->tracker->commitStateParse(error: $e);
                    sleep(seconds: rand(min: 1, max: 4));
                    continue;
                }
            }
        }

        $this->tracker->endProcess();
    }
}
