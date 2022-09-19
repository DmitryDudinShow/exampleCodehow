<?php

namespace Glavfinans\Core\Fssp\ParseCaptcha;

use DateFmt;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Glavfinans\Core\ComputerVision\FsspCaptchaModelDirector;
use Glavfinans\Core\Exception\HttpBadRequestException;
use Glavfinans\Core\Fssp\Captcha\FsspCaptchaGatherer;
use Glavfinans\Core\Logger\MonologFactory;
use Glavfinans\Core\Proxy\ProxyList;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use HttpException;
use ImagickException;
use LogicException;
use Throwable;

/**
 * Клиент для подключения к сайту ФССП
 */
class FsspSiteClient
{
    protected int $counter = 0;

    /** @var Client $client - Клиент для запросов на сайт ФССП */
    protected Client $client;

    /** @var string $baseUri - Базовый урл */
    protected string $baseUri = 'https://is.fssp.gov.ru/';

    /** @var array $cookie - Куки */
    protected array $cookie;

    /** @var int|null $timestamp */
    protected ?int $timestamp = null;

    /** @var string|null $scriptNumber */
    protected ?string $scriptNumber = null;

    /** @var string|null $callback */
    protected ?string $callback = null;

    /** @var ProxyList|null $proxyList - Список прокси, если необходимо парсить через них */
    private ?ProxyList $proxyList = null;

    /**
     * @param FsspCaptchaModelDirector $director
     * @param FsspParserHtml $parser
     * @param FsspCaptchaGatherer $captchaGatherer
     */
    public function __construct(
        protected FsspCaptchaModelDirector $director,
        protected FsspParserHtml           $parser,
        protected FsspCaptchaGatherer      $captchaGatherer,
    ) {
        /** Если нужно отключить прокси - просто убираем эту строку */
        $this->proxyList = ProxyList::makeFromTxt();
        $this->createClient();
    }

    /**
     * Создание нового клиента Guzzle со сбросом прокси, куки и колбэка
     *
     * @return void
     */
    private function createClient(): void
    {
        $config = [
            'base_uri' => $this->baseUri,
            RequestOptions::CONNECT_TIMEOUT => 60,
            RequestOptions::TIMEOUT => 60,
            RequestOptions::COOKIES => true,
        ];

        if (null !== $this->proxyList) {
            $config[RequestOptions::PROXY] = $this->proxyList->getCurrentAndGoNext()->getStringForGuzzleClient();
        }

        $this->client = new Client(config: $config);
        $this->cookie = [];

        $this->setCallback();
    }

    /**
     * Получить хэш капчи
     *
     * @return string
     * @throws GuzzleException
     */
    public function getCaptcha(): string
    {
        $response = $this->client->request(method: 'GET', uri: 'refresh_visual_captcha/');

        $json = json_decode(json: $response->getBody()->getContents(), associative: true);

        if (!isset($json['image'])) {
            throw new LogicException(message: 'Не получен параметр image', code: 422);
        }

        return $json['image'];
    }

    /**
     * Отправить запрос на сайт ФССП на получение ИП по O
     *
     * @param FsspRequestDtoInterface $dto
     * @param bool $isCaptchaResolve
     * @param string|null $code
     * @param int|null $page
     *
     * @return FsspEpRawCollection
     * @throws GuzzleException
     * @throws HttpBadRequestException
     * @throws HttpException
     * @throws ImagickException
     */
    public function sendRequestGetEp(
        FsspRequestDtoInterface $dto,
        bool                    $isCaptchaResolve = false,
        ?string                 $code = null,
        ?int                    $page = null,
    ): FsspEpRawCollection {
        /** Пока оставим, на случай быстрого дебага */
        $testData = $this->getTestData(dto: $dto);
        if (null !== $testData) {
            return $testData;
        }

        $monoLogger = MonologFactory::getFsspParse();

        /** Принудительная остановка при большом количестве неразгаданных каптч */
        if ($this->counter >= 10) {
            /** Сбрасываем счётчик */
            $this->counter = 0;
            $this->createClient();
            sleep(seconds: 180);
            throw new HttpBadRequestException(message: 'Не получили ответ с 10 раза');
        }

        /** Если капча не разгадана, создаём новый колбэк и данные */
        if (!$isCaptchaResolve) {
            $this->setCallback();
        }

        /** Получаем параметры запроса */
        $options = $this->getOptions(dto: $dto, code: $code);

        /** Если передана страница, на которую надо прыгнуть - добавляем в запрос */
        if (null !== $page) {
            $options['query']['page'] = $page;
        }

        $monoLogger->info(message: "1) Параметры перед запросом: ");
        $monoLogger->info(message: json_encode(value: $options));
        if (isset($options['cookies'])) {
            $monoLogger->info(message: 'Куки: ' . json_encode(value: $options['cookies']->toArray()));
        }

        if (null !== $this->proxyList) {
            $monoLogger->error(message: "Через прокси: {$this->proxyList->current()->getClearAddress()}");
        }

        /** Отправляем запрос на ajax_search и прибавляем счётчик */
        $this->counter++;
        try {
            $responseObject = $this->client->request(
                method:  'GET',
                uri:     'ajax_search',
                options: $options,
            );
        } catch (Throwable $e) {
            $monoLogger->error(message: "1.1) Запрос #{$this->counter}. Error: {$e->getMessage()}");
            $monoLogger->error(message: "1.2) Был прокси: {$this->proxyList->current()->getClearAddress()}");

            sleep(seconds: rand(min: 10, max: 15));
            $this->createClient();
            $monoLogger->error(message: "1.3) Новый прокси: {$this->proxyList->current()->getClearAddress()}");
            throw new HttpBadRequestException(message: "Ошибка сервера. Код: {$e->getCode()}. " . $e::class, code: 422);
        }

        $monoLogger->info(message: "2) Отправили запрос #{$this->counter} | Клиент #{$dto->getClientId()}");

        /** Вытаскиваем ответ */
        $response = $responseObject->getBody()->getContents();

        /** Получаем DOM-объект из ответа */
        $dom = $this->getDomDocumentFromResponse(response: $response, query: $options['query']);

        $monoLogger->info(message: "3) Получили ответ DOM");

        /**
         * @var bool $hasCaptcha - Возвращает true - если в ответе просят каптчу
         * @var bool $isResolved - Возвращает true - если каптча потенциально разгадана
         * @var null|string $code - Возвращает разгаданный код капчи, или null если не разгадана
         */
        [$hasCaptcha, $isResolved, $code] = $this->handleCaptcha($dom);

        $monoLogger->info(message: "4) Обработали капчу");

        /** Если просят каптчу - отправляем повторный запрос с указанием кода разгаданной капчи */
        if ($hasCaptcha) {
            $monoLogger->info(message: '4.1) Ушли на повторный запрос');
            sleep(seconds: rand(min: 3, max: 4));

            return $this->sendRequestGetEp(
                dto:              $dto,
                isCaptchaResolve: $isResolved,
                code:             $code,
                page:             $page,
            );
        }

        /** Обработка варианта отсутствия ИП, и ошибок сервера */
        $emptyCollection = $this->handleEmptyEp(dom: $dom);

        $monoLogger->info(message: "5) Обработали вариант проверка ошибок сервера");

        /** Если ИП не найдены - Устанавливаем clientId, и возвращаем пустую коллекцию */
        if (null !== $emptyCollection) {
            $emptyCollection->setClientId(clientId: $dto->getClientId());
            $emptyCollection->setRequestId(requestId: $dto->getRequestId());
            $this->counter = 0;

            return $emptyCollection;
        }

        /**
         *
         * Если дошли сюда - скорее всего пришёл нормальный ответ с исполнительными производствами
         * Поэтому собираем коллекцию ответов, сбрасываем счётчик, и возвращаем её, если не пагинация
         */
        $collection = $this->parser->parse(dom: $dom);

        $monoLogger->info(message: "6) Спарсили нормальный ответ");

        $collection->setClientId(clientId: $dto->getClientId());
        $collection->setRequestId(requestId: $dto->getRequestId());

        /** @var null|int $nextPage - Если нашли пагинацию - записываем страницу, на которую нужно скакать дальше */
        $nextPage = $this->jumpPagination(dom: $dom);

        /** Нужно ли переходить по страницам */
        if (null !== $nextPage) {
            $monoLogger->info(message: "7) Увидели пагинацию");
            $alsoCollection = $this->sendRequestGetEp(
                dto:              $dto,
                isCaptchaResolve: $isResolved,
                code:             $code,
                page:             $nextPage,
            );

            /** Добавляем в основную коллекцию все ИП, полученные при хождении по страницам пагинации */
            $collection->massAdd(...$alsoCollection->getAll());
        }

        $monoLogger->info(message: "8) Всё нормально. Клиент #{$dto->getClientId()} получен");
        $this->counter = 0;

        return $collection;
    }

    /**
     * Данные для тестов. Пока не стоит убирать
     * todo - Убрать после тестирования
     *
     * @param FsspRequestDto $dto
     *
     * @return FsspEpRawCollection|null
     */
    private function getTestData(FsspRequestDto $dto): ?FsspEpRawCollection
    {
        return null;

        $eps = FsspEpRaw::makeTest();

        $collection = new FsspEpRawCollection();
        $collection->massAdd(...$eps);
        $collection->setClientId(clientId: $dto->getClientId());
        $collection->setRequestId(requestId: $dto->getRequestId());

        return $collection;
    }

    /**
     * Прыгаем по страницам пагинации чтоб получить все результаты
     *
     * @param DOMDocument $dom
     *
     * @return int|null
     */
    private function jumpPagination(DOMDocument $dom): ?int
    {
        /** Ищем класс pagination-is. Только так можно получить селектор по классу */
        $domXPath = (new DOMXPath(document: $dom))->query(expression: "//*[contains(@class, 'pagination-is')]");

        /** Если не найден <div class pagination-is> - то перебирать нечего */
        if ($domXPath->count() === 0) {
            return null;
        }

        /** @var DOMElement $divPagination - Пробегаем по полученному блоку */
        foreach ($domXPath->getIterator() as $divPagination) {
            /** Получаем ссылки */
            $tagsA = $divPagination->getElementsByTagName(qualifiedName: 'a');

            /** На всякий случай сообщим ошибку если в структуре что-то поменяется */
            if ($tagsA->count() === 0) {
                throw new LogicException(message: 'Не найдены ссылки перехода пагинации при парсинге сайта ФССП');
            }

            /** @var DOMElement $tagA - Дальше работаем с ссылками как с объектом DOMElement */
            foreach ($tagsA->getIterator() as $tagA) {
                /** Если нашли элемент с текстом "Следующая" - то идём туда */
                if (str_contains(haystack: $tagA->textContent, needle: 'Следующая')) {
                    /** Забираем ссылку для перехода */
                    $rawUrl = $tagA->getAttribute(qualifiedName: 'href');

                    /** Парсим урл, чтоб достать номер страницы для перехода */
                    parse_str(string: parse_url(url: $rawUrl, component: PHP_URL_QUERY), result: $query);

                    /** Проверка что page существует */
                    if (!isset($query['page'])) {
                        throw new LogicException(
                            message: 'Ошибка парсинга ФССП. Отсутствует параметр page при переходе на пагинации',
                        );
                    }

                    /** Если дошли сюда - Значит пора переходить на следующую страницу */
                    return $query['page'];
                }
            }

            return null;
        }

        throw new LogicException(message: 'Ошибка парсинга сайта ФССП при хождению по пагинации');
    }

    /**
     * Устанавливаем колбэк, и необходимые данные для запроса
     *
     * @return void
     */
    private function setCallback(): void
    {
        $this->timestamp = (new DateTimeImmutable())->getTimestamp();
        $this->scriptNumber = (rand(min: 3000575945, max: 9000575945)) . (rand(min: 3000575945, max: 9000575945));
        $this->callback = "jQuery{$this->scriptNumber}_{$this->timestamp}";
    }

    /**
     * Получить параметры запроса
     *
     * @param FsspRequestDtoInterface $dto
     * @param string|null $code
     *
     * @return array
     */
    private function getOptions(FsspRequestDtoInterface $dto, ?string $code): array
    {
        $query = [
            /** Поиск ИП */
            'system' => 'ip',

            /** Без кэша */
            'nocache' => 1,

            /** Данные по исполнительному производству */
            'is' => [
                'extended' => 1,
                'variant' => 1,
                'last_name' => $dto->getLastName(),
                'first_name' => $dto->getFirstName(),
                'patronymic' => $dto->getMiddleName() ?? '',
                'date' => $dto->getBirthDate()->format(format: DateFmt::D_APP_NEW),
                'region_id' => [$dto->getRegionInFsspFormat()],
            ],
            '_' => $this->timestamp,
            'callback' => $this->callback,
        ];

        /** Если пришёл разгадочный код, добавляем его */
        if (null !== $code) {
            $query['code'] = $code;
        }

        /** Установка кук из прошлого запроса */
        if (!empty($this->cookie)) {
            $options['cookies'] = CookieJar::fromArray(cookies: $this->cookie, domain: 'is.fssp.gov.ru');
        }

        $options['query'] = $query;

        return $options;
    }

    /**
     * Собираем DOM-объект из ответа, предварительно его провалидировав
     *
     * @param string $response
     * @param array $query
     *
     * @return DOMDocument
     */
    private function getDomDocumentFromResponse(string $response, array $query): DOMDocument
    {
        /** Вытаскиваем JSON из ответа */
        $json = mb_substr(
            string: $response,
            start:  mb_stripos(haystack: $response, needle: '{'), /** Первая найденная кавычка */
            /** С длинной от первой позиции до последней */
            length: (mb_strripos(haystack: $response, needle: '}')) - (mb_stripos(haystack: $response, needle: '{')) + 1,
        );

        $responseArray = json_decode(json: $json, associative: true);

        /** Проверка на валидный JSON */
        if (
            json_last_error() !== JSON_ERROR_NONE
            || null === $responseArray
            || !isset($responseArray['data'])
        ) {
            throw new LogicException(
                message: 'Некорректный ответ от ФССП при парсинге сайта. Запрос: ' . print_r(value: $query, return: true),
            );
        }

        /** Из строки ответа делаем DOM-дерево, чтоб разобрать его на элементы */
        $dom = new DOMDocument();

        /** Конвертируем ответ в UTF-8, иначе кракозябры */
        $rawHtml = mb_convert_encoding(string: $responseArray['data'], to_encoding: 'HTML-ENTITIES', from_encoding: 'UTF-8');

        /** Меняем переносы на проблемы, иначе склеивает */
        $rawHtml = str_replace(search: ['<br>', '<br />'], replace: ' ', subject: $rawHtml);

        /** Парсим HTML из строки */
        $dom->loadHTML(source: $rawHtml);

        return $dom;
    }

    /**
     * Первичная обработка ответа.
     * Проверяем, есть ли каптча. Если нет - говорим что всё ок
     * Если нашли элемент каптчи, пытаемся разгадать, и возвращаем код, и всё что нужно
     *
     * @param DOMDocument $dom
     *
     * @return array [
     *              bool $hasCaptcha - Возвращает true - если в ответе просят каптчу
     *              bool $isResolved - Возвращает true - если каптча потенциально разгадана
     *              null|string $code - Возвращает разгаданный код капчи, или null если не разгадана
     *              ]
     * @throws HttpBadRequestException
     */
    private function handleCaptcha(DOMDocument $dom): array
    {
        /** Получаем элемент капчи */
        $captchaElem = $dom->getElementById(elementId: 'capchaVisual');

        /** Возвращаем что всё нормально, если не увидели каптчу в ответе */
        if (null === $captchaElem) {
            if (!$this->captchaGatherer->isClearObject()) {
                /** Сохраняем каптчу с результатом - Разгадано */
                $this->captchaGatherer->saveCaptchaWithResult(isResolved: true);
            }

            return [
                false, /** Отвечаем что каптчу не запрашивают */
                true, /** Каптча разгадана */
                null, /** Код не нужен, ответ получен */
            ];
        }

        /** Записываем куки в свойство объекта */
        if (!empty($this->client->getConfig(option: 'cookies')->toArray())) {
            $cookie = $this->client->getConfig(option: 'cookies')->toArray()[0];
            $this->cookie = [
                $cookie['Name'] => $cookie['Value'],
            ];
        }

        /** Вытаскиваем хэш капчи */
        $base64captcha = $captchaElem->getAttribute(qualifiedName: 'src');

        /** Отдаём на разгадывание, записываем в $code */
        try {
            [$code] = $this->director->optimalModel(hash: $base64captcha);
        } catch (Throwable $e) {
            sleep(seconds: 30);
            throw $e;
        }

        /** Если объект не новый - сохраняем что не разгадали */
        if (!$this->captchaGatherer->isClearObject()) {
            $this->captchaGatherer->saveCaptchaWithResult(isResolved: false);
        }

        /** Создаём новую каптчу во временный объект */
        $this->captchaGatherer->set(captcha: $base64captcha, code: $code);

        /** Если длина ответа не 5 символов - капча точно разгадана неверно */
        $isCaptchaResolve = mb_strlen(string: $code) === 5;
        $code = $isCaptchaResolve ? $code : null;

        /** Спячка на время, зависящее от количества запросов */
        sleep(seconds: $this->getSleep());

        return [
            true, /** Отвечаем что каптчу запрашивают */
            $isCaptchaResolve, /** Каптча разгадана */
            $code, /** Код не нужен, ответ получен */
        ];
    }

    /**
     * Вторичная обработка ответа
     * Проверяет на наличие класса empty (В нём может быть ошибка сервера, либо отсутствие производств)
     * Если ошибка сервера, выбрасываем исключение, чтоб заного к нему проситься
     * Если ИП не найдены - Возвращаем пустую коллекцию
     *
     * @param DOMDocument $dom
     *
     * @return FsspEpRawCollection|null
     */
    private function handleEmptyEp(DOMDocument $dom): ?FsspEpRawCollection
    {
        /** Ищем класс empty. Только так можно получить селектор по классу */
        $domXPath = (new DOMXPath(document: $dom))->query(expression: "//*[contains(@class, 'empty')]");

        /** Если исполнительных производств не найдено */
        if (1 !== $domXPath->length) {
            return null;
        }

        /** Сбрасываем счётчик */
        $this->counter = 0;

        /** Если в ответе написано что запрос обрабатывается уже ...мин, ...сек - Вылетаем, и заного долбимся */
        if (str_contains(haystack: $dom->textContent, needle: 'Ваш запрос')) {
            sleep(seconds: 180);
            throw new HttpBadRequestException(message: 'Ошибка сервиса ФССП');
        }

        /** Возвращаем пустую коллекцию */
        return new FsspEpRawCollection();
    }

    /**
     * Получить время спячки после множества неразгаданных каптч
     * При первых трёх запросах в секунду
     * от 4 до 6 - 2 секунды, и так по нарастающей
     * Выявлененно методом научного тыканья
     *
     * @return int
     */
    protected function getSleep(): int
    {
        return intval(value: $this->counter / 3.1) + 1;
    }
}
