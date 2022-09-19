<?php

namespace Glavfinans\Core\Fssp\ParseCaptcha;

use DomainException;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use LogicException;

/**
 * Обработка HTML документа, и получение данных по исполнительным производствам при парсинге сайта ФССП
 */
class FsspParserHtml
{
    /**
     * Распарсить DOM-документ
     *
     * @param DOMDocument $dom
     *
     * @return FsspEpRawCollection
     */
    public function parse(DOMDocument $dom): FsspEpRawCollection
    {
        /** Получаем элемент таблицы */
        $tbody = ($dom->getElementsByTagName(qualifiedName: 'table'));

        /** Таблица должна быть одна */
        if (1 !== $tbody->count()) {
            throw new LogicException(
                message: 'Ошибка разбора ответа от ФССП при парсинге сайта. тег table->count() !== 1',
                code:    422,
            );
        }

        $epCollection = new FsspEpRawCollection();

        /** @var DOMNode $tr - Перебираем все строки */
        foreach ($tbody->item(index: 0)->getElementsByTagName(qualifiedName: 'tr') as $tr) {
            /** @var DOMNodeList $tdElements */
            $tdElements = $tr->getElementsByTagName(qualifiedName: 'td');

            /** Смотрим что внутри есть td и их 8 */
            if (8 !== $tdElements->count()) {
                continue;
            }

            $epCollection->add(ep: FsspEpRaw::makeFromIterable(data: $tdElements));
        }

        return $epCollection;
    }
}
