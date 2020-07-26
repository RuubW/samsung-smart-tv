<?php

namespace App\Tests;

use App\Library\RemoteClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;

/**
 * Class RemoteClientTest.
 *
 * @package App\Tests
 */
class RemoteClientTest extends TestCase
{
    /**
     * @var MockObject|RemoteClient
     */
    private $remoteClient;

    /**
     * @var MockObject|AdapterInterface
     */
    private $cache;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->cache = $this->createMock(AdapterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->remoteClient = new RemoteClient(
            $this->cache,
            $this->logger,
            '192.168.0.0',
            'wws',
            8002,
            'RemoteClient Test',
            'test'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->remoteClient = null;
        $this->cache = null;
        $this->logger = null;
    }

    /**
     * Test whether the host is correctly set by the RemoteClient constructor.
     */
    public function testHostIsCorrectlySet()
    {
        // Retrieve the host.
        $host = $this->remoteClient->getHost();

        // Test whether the host was set as expected.
        $this->assertEquals('192.168.0.0', $host);
    }

    /**
     * Test whether the sending of the queue is halted when it is empty.
     */
    public function testSendQueueExecutionIsHaltedWhenEmpty()
    {
        // Empty queue warning logging.
        $this->logger
            ->expects($this->once())
            ->method('warning');

        // Connection debug logging.
        $this->logger
            ->expects($this->never())
            ->method('debug');

        // Cache item retrieval.
        $this->cache
            ->expects($this->never())
            ->method('getItem');

        // Send the (empty) queue.
        $this->remoteClient->sendQueue();
    }

    /**
     * Test whether the sending of the queue is executed as expected.
     */
    public function testSendQueueExecution()
    {
        // Empty queue warning logging.
        $this->logger
            ->expects($this->never())
            ->method('warning');

        // Connection debug logging.
        $this->logger
            ->expects($this->once())
            ->method('debug');

        // Cache item retrieval.
        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->willReturn(new CacheItem());

        // Add a key to the queue.
        $this->remoteClient->queueKey('home');
        // Send the queue.
        $this->remoteClient->sendQueue();
    }

    /**
     * Test whether the sending of a single key is executed as expected.
     */
    public function testSendKey()
    {
        // Empty queue warning logging.
        $this->logger
            ->expects($this->never())
            ->method('warning');

        // Connection debug logging.
        $this->logger
            ->expects($this->once())
            ->method('debug');

        // Cache item retrieval.
        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->willReturn(new CacheItem());

        // Send a key.
        $this->remoteClient->sendKey('home');
    }

    /**
     * Test whether the sending of multiple keys is executed as expected.
     */
    public function testSendKeys()
    {
        // Empty queue warning logging.
        $this->logger
            ->expects($this->never())
            ->method('warning');

        // Connection debug logging.
        $this->logger
            ->expects($this->once())
            ->method('debug');

        // Cache item retrieval.
        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->willReturn(new CacheItem());

        // Send a list of keys.
        $this->remoteClient->sendKeys(['home', 'enter']);
    }
}
