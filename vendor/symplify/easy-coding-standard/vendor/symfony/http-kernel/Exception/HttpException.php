<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix202306\Symfony\Component\HttpKernel\Exception;

/**
 * HttpException.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class HttpException extends \RuntimeException implements HttpExceptionInterface
{
    /**
     * @var int
     */
    private $statusCode;
    /**
     * @var mixed[]
     */
    private $headers;
    public function __construct(int $statusCode, string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        parent::__construct($message, $code, $previous);
    }
    public function getStatusCode() : int
    {
        return $this->statusCode;
    }
    public function getHeaders() : array
    {
        return $this->headers;
    }
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }
}
