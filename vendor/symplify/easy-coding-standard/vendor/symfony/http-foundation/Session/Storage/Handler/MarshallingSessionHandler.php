<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix202306\Symfony\Component\HttpFoundation\Session\Storage\Handler;

use ECSPrefix202306\Symfony\Component\Cache\Marshaller\MarshallerInterface;
/**
 * @author Ahmed TAILOULOUTE <ahmed.tailouloute@gmail.com>
 */
class MarshallingSessionHandler implements \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface
{
    /**
     * @var \Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler
     */
    private $handler;
    /**
     * @var \Symfony\Component\Cache\Marshaller\MarshallerInterface
     */
    private $marshaller;
    public function __construct(AbstractSessionHandler $handler, MarshallerInterface $marshaller)
    {
        $this->handler = $handler;
        $this->marshaller = $marshaller;
    }
    public function open(string $savePath, string $name) : bool
    {
        return $this->handler->open($savePath, $name);
    }
    public function close() : bool
    {
        return $this->handler->close();
    }
    public function destroy(string $sessionId) : bool
    {
        return $this->handler->destroy($sessionId);
    }
    /**
     * @return int|false
     */
    public function gc(int $maxlifetime)
    {
        return $this->handler->gc($maxlifetime);
    }
    public function read(string $sessionId) : string
    {
        return $this->marshaller->unmarshall($this->handler->read($sessionId));
    }
    public function write(string $sessionId, string $data) : bool
    {
        $failed = [];
        $marshalledData = $this->marshaller->marshall(['data' => $data], $failed);
        if (isset($failed['data'])) {
            return \false;
        }
        return $this->handler->write($sessionId, $marshalledData['data']);
    }
    public function validateId(string $sessionId) : bool
    {
        return $this->handler->validateId($sessionId);
    }
    public function updateTimestamp(string $sessionId, string $data) : bool
    {
        return $this->handler->updateTimestamp($sessionId, $data);
    }
}
