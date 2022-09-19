<?php
declare(strict_types=1);

namespace Glavfinans\Core\Proxy;

use InvalidArgumentException;

/**
 * Обёртка надо прокси-адресом
 */
class Proxy
{
    /**
     * @param string $ip
     * @param string $port
     * @param string|null $login
     * @param string|null $password
     * @param string $host
     */
    public function __construct(
        private string  $ip,
        private string  $port,
        private ?string $login,
        private ?string $password,
        private string  $host = 'http',
    ) {
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getStringForGuzzleClient();
    }

    /**
     * Получить прокси строкой в формате для Guzzle-клиент
     * http://6tfo6b:0PSHah@45.144.169.111:8000 - Для прокси с паролем
     * http://45.144.169.111:8000 - Для незалогиненных прокси
     *
     * @return string
     */
    public function getStringForGuzzleClient(): string
    {
        $address = $this->getClearAddress();

        if (null === $this->login || null === $this->password) {
            return "$this->host://$address";
        }

        return "$this->host://$this->login:$this->password@$address";
    }

    /**
     * Получить адрес строкой
     *
     * @return string
     */
    public function getClearAddress(): string
    {
        return "$this->ip:$this->port";
    }

    /**
     * Создать объект из строки вида:
     * http://6tfo6b:0PSHah@45.144.169.111:8000
     *
     * @param string $proxyString
     *
     * @return static
     */
    public static function makeAuthorizationProxyFromString(string $proxyString): self
    {
        $proxyPart = [];

        /** Простая регулярочка на растаскивание прокси на части */
        preg_match(pattern: '/([a-z]+):\/\/(.+):(.+)@(.+):(.+)/', subject: $proxyString, matches: $proxyPart);

        /** Должно получиться 6 элементов */
        if (6 !== count((array)$proxyPart)) {
            throw new InvalidArgumentException(message: "Попытка сконструировать невалидный прокси: $proxyString", code: 422);
        }

        return new self(
            ip:       $proxyPart[4],
            port:     $proxyPart[5],
            login:    $proxyPart[2],
            password: $proxyPart[3],
            host:     $proxyPart[1],
        );
    }
}
