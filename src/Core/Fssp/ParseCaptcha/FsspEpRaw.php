<?php

namespace Glavfinans\Core\Fssp\ParseCaptcha;

use DateFmt;
use DateTimeImmutable;
use Exception;
use LogicException;

/**
 * Сырые данные по исполнительному производству, полученные с сайта ФССП
 * Свойства называются так, чтоб соответствовать структуре таблицы fssp_ep
 */
class FsspEpRaw
{
    /**
     * Создавать объект можно только через фабрику
     */
    protected function __construct() { }

    /** @var string $name - Имя, дата рождения, регион */
    private string $name;

    /** @var string $exeProduction - Номер и дата производства */
    private string $exeProduction;

    /** @var string $details - Реквизиты исполнительного документа */
    private string $details;

    /** @var string $subject - Предмет исполнения */
    private string $subject;

    /** @var string $department - Отдел судебных приставов */
    private string $department;

    /** @var string $bailiff - Судебный пристав-исполнитель */
    private string $bailiff;

    /** @var string|null $epEnd - Данные об окончании ИП */
    private ?string $epEnd;

    /**
     * Создать объект сырого ответа ФССП из перебираемых данных
     *
     * @param iterable $data
     *
     * @return static
     */
    public static function makeFromIterable(iterable $data): self
    {
        /** Проверка, что есть необходимые для сборки элементы, и у них есть свойств nodeValue */
        foreach (range(start: 0, end: 7) as $index) {
            if (!isset($data[$index]) && !isset($data[$index]->nodeValue)) {
                throw new LogicException(
                    message: 'Ошибка конструирования FsspEpRaw. Ожидаются ключи 0-7, пришло элементов: ' . count($data),
                    code:    422,
                );
            }
        }

        $fsspEpRaw = new self();
        $fsspEpRaw->name = $data[0]->nodeValue;
        $fsspEpRaw->exeProduction = $data[1]->nodeValue;
        $fsspEpRaw->details = $data[2]->nodeValue;
        $fsspEpRaw->epEnd = $data[3]->nodeValue;

        if (empty($data[3]->nodeValue)) {
            $fsspEpRaw->epEnd = null;
        }

        $fsspEpRaw->subject = $data[5]->nodeValue;
        $fsspEpRaw->department = $data[6]->nodeValue;
        $fsspEpRaw->bailiff = $data[7]->nodeValue;

        return $fsspEpRaw;
    }

    /**
     * Получить имя
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getExeProduction(): string
    {
        return $this->exeProduction;
    }

    /**
     * @return string
     */
    public function getDetails(): string
    {
        return $this->details;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function getEpEnd(): ?string
    {
        return $this->clearEpEnd();
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getDepartment(): string
    {
        return $this->department;
    }

    /**
     * @return string
     */
    public function getBailiff(): string
    {
        return $this->bailiff;
    }


    /**
     * Чистка даты окончания производства.
     * Дело в том, что по API мы получали вот такой формат: '2021-12-10, 46, 1, 3'
     * А при парсинге получили: 27.10.2021 ст. 46 ч. 1 п. 3
     * Тут мы переводим строку из одного формата в другой
     *
     * @return string|null
     * @throws Exception
     */
    private function clearEpEnd(): ?string
    {
        if (null === $this->epEnd) {
            return null;
        }

        $epEndArray = explode(separator: ' ', string: $this->epEnd);

        $dateNowString = (new DateTimeImmutable())->format(format: DateFmt::D_DB);

        /**
         * Костыль для статьи 33. Она может быть без даты, без части, без пункта.
         * Если пришла такая статья, остальные поля ставим прочерком, а дату - дату парсинга.
         * Сама статья подразумевает что ИП передано в другой суд, и скоро появится новое, но с другим номером ИП.
         * Поэтому оставлять неактуальную дату допустимо
         */
        if (count(value: $epEndArray) === 3) {
            return "$dateNowString, {$epEndArray[2]}, -, -";
        }

        /**
         * По идее должна приходить строка вида: '27.10.2021 ст. 46 ч. 1 п. 3'
         * Если разобрать её по пробелам, должен получить массив вида:
         * array(7) {
         *      [0]=>
         *      string(10) "27.10.2021"
         *      [1]=>
         *      string(5) "ст."
         *      [2]=>
         *      string(2) "46"
         *      [3]=>
         *      string(3) "ч."
         *      [4]=>
         *      string(1) "1"
         *      [5]=>
         *      string(3) "п."
         *      [6]=>
         *      string(1) "3"
         * }
         *
         * Поэтому пробуем проверить корректность строки, в другом случае записываем как есть
         * Костыльно, но по-другому не представляется возможным
         */

        if (
            new DateTimeImmutable(datetime: $epEndArray[0]) /** Проверка, что первый элемент массива корректная дата */
            && $epEndArray[1] === 'ст.'
            && $epEndArray[3] === 'ч.'
            && $epEndArray[5] === 'п.'
        ) {
            /** Удаляем лишние символы */
            unset($epEndArray[1], $epEndArray[3], $epEndArray[5]);

            /** Меняем формат даты */
            $epEndArray[0] = (new DateTimeImmutable(datetime: $epEndArray[0]))->format(format: DateFmt::D_DB);

            /** Возвращаем строку в формате: '2021-12-10, 46, 1, 3' */
            return implode(', ', $epEndArray);
        }

        /** Иначе возвращаем строку как есть */
        return $this->epEnd;
    }

    /**
     * Создать тестовы обхект для быстрого дебага.
     *
     * todo - Убрать после тестирования
     * @return array
     * @deprecated - Использовать нельзя
     */
    public static function makeTest(): array
    {
        $eps = [
            [
                "name" => "КРАСКИН ВАДИМ ВИКТОРОВИЧ 10.11.1975 ГОРОД МОСКВА",
                "exeProduction" => "126168/21/77025-ИП от 02.11.2021",
                "details" => "Исполнительный лист от 23.09.2020 № ФС027684765Постановление о взыскании исполнительского сбораСОЛНЦЕВСКИЙ РАЙОННЫЙ СУД7725039953",
                "epEnd" => null,
                "subject" => "Иные взыскания имущественного характера в пользу физических и юридических лиц: 137721.48 руб.Исполнительский сбор: 12276.54 руб.",
                "department" => "Солнцевское ОСП119285, Россия, г. Москва, , , , пер. 2-й Мосфильмовский, д. 8а, ,",
                "bailiff" => "МИРОНОВ А. С.+74956657284",
            ],

            [
                "name" => "КРАСКИН ВАДИМ ВИКТОРОВИЧ 10.11.1975 Г. МОСКВА",
                "exeProduction" => "164890/21/77025-ИП от 10.01.2022",
                "details" => "Исполнительный лист от 12.10.2021 № ВС09023459Постановление о взыскании исполнительского сбораСУДЕБНЫЙ УЧАСТОК № 142 РАЙОНА НОВО-ПЕРЕДЕЛКИНО2310161900",
                "subject" => "Иные взыскания имущественного характера в пользу физических и юридических лиц: 7400.00 руб.Исполнительский сбор: 1000.00 руб.",
                "department" => "Солнцевское ОСП119285, Россия, г. Москва, , , , пер. 2-й Мосфильмовский, д. 8а, ,",
                "bailiff" => "МИРОНОВ А. С.+74956657284",
                "epEnd" => null,
            ],

            [
                "name" => "КРАСКИН ВАДИМ ВИКТОРОВИЧ 10.11.1975 Г. МОСКВА",
                "exeProduction" => "12537/22/77025-ИП от 01.02.2022",
                "details" => "Исполнительный лист от 27.07.2021 № ВС090189940Постановление о взыскании исполнительского сбораСУДЕБНЫЙ УЧАСТОК № 142 РАЙОНА НОВО-ПЕРЕДЕЛКИНО4205271785",
                "subject" => "Задолженность по кредитным платежам (кроме ипотеки): 5796.00 руб.Исполнительский сбор: 1000.00 руб.",
                "department" => "Солнцевское ОСП119285, Россия, г. Москва, , , , пер. 2-й Мосфильмовский, д. 8а, ,",
                "bailiff" => "МИРОНОВ А. С.+74956657284",
                "epEnd" => null,
            ],
        ];

        $eps = [
            [
                "name" => "КОВАЛЬЧУК ВЛАДИМИР НИКОЛАЕВИЧ 28.10.1979",
                "exeProduction" => "40366/22/39001-ИП от 06.04.2022",
                "details" => "Судебный приказ от 18.08.2021 № 2-3934/212-Й СУДЕБНЫЙ УЧАСТОК ЛЕНИНГРАДСКОГО СУДЕБНОГО РАЙОНА ГОРОДА КАЛИНИНГРАДА2310161900",
                "subject" => "Задолженность по кредитным платежам (кроме ипотеки)Общая сумма задолженности: 221727.00 руб.",
                "department" => "ОСП Ленинградского района236040, Россия, , , г. Калининград, , ул. Сергеева, 2, , ",
                "bailiff" => "БОНДАРЕВА Л. Н.+74012924900",
                "epEnd" => "10.06.2022 ст. 46 ч. 1 п. 3",
            ]
        ];

        $result = [];

        foreach ($eps as $ep) {
            $fsspEpRaw = new self();
            $fsspEpRaw->name = $ep['name'];
            $fsspEpRaw->exeProduction = $ep['exeProduction'];
            $fsspEpRaw->details = $ep['details'];
            $fsspEpRaw->epEnd = $ep['epEnd'];

            if (empty($ep['epEnd'])) {
                $fsspEpRaw->epEnd = null;
            }

            $fsspEpRaw->subject = $ep['subject'];
            $fsspEpRaw->department = $ep['department'];
            $fsspEpRaw->bailiff = $ep['bailiff'];

            $result[] = $fsspEpRaw;
        }

        return $result;
    }
}
