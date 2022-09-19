<?php

declare(strict_types=1);

namespace Glavfinans\Core\Proxy;

use Glavfinans\Core\Collection;
use InvalidArgumentException;
use RuntimeException;

/**
 * Список прокси-адресов
 */
class ProxyList extends Collection
{
    /**
     * Добавить прокси в список
     *
     * @param Proxy $proxy
     *
     * @return void
     */
    public function add(Proxy $proxy): void
    {
        $this->addObject(object: $proxy);
    }

    /**
     * Получить текущий элемент и переместить указатель на следующий элемент в бесконечном режиме
     *
     * @return Proxy
     */
    public function getCurrentAndGoNext(): Proxy
    {
        /** @var Proxy $current */
        $current = $this->current();

        parent::next();
        if (!$this->valid()) {
            $this->rewind();
        }

        return $current;
    }

    /**
     * Создать список прокси-адресов из массива
     *
     * @param array $proxies
     *
     * @return static
     */
    public static function makeFromArray(array $proxies): self
    {
        $list = new self();

        foreach ($proxies as $proxy) {
            try {
                $list->add(proxy: Proxy::makeAuthorizationProxyFromString(proxyString: $proxy));
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        if ($list->isEmpty()) {
            throw new RuntimeException(
                message: sprintf('Не удалось собрать список прокси из %s', print_r($proxies, true))
            );
        }

        return $list;
    }

    /**
     * Создать объект списка прокси. И перемешиваются
     * todo - История временная, будет расширяться и абстрагироваться по мере нужды
     *
     * @return $this
     */
    public static function makeFromTxt(): self
    {
        $proxyList = self::makeFromArray(
            proxies: [
                         'http://6tfo6b:0PSHah@45.144.168.2:8000',
                         'http://6tfo6b:0PSHah@45.144.169.11:8000',
                         'http://dpgaYg:EqBQPT@87.251.69.50:8000',
                         'http://dpgaYg:EqBQPT@45.143.166.57:8000',
                     ],
        );

        shuffle(array: $proxyList->objects);

        return $proxyList;
    }
}
