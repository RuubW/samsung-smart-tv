<?php

namespace App\Tests;

use App\Library\RemoteClient;
use PHPUnit\Framework\MockObject\MockObject ;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

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
     * @inheritDoc
     */
    protected function setUp()
    {
        // mocks of cache, logger, connector, WebSocket
        $this->cache = $this->createMock(AdapterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->remoteClient = new RemoteClient(
            $this->cache,
            $this->logger,
            '192.168.0.0',
            'wws',
            8002,
            'RemoteClient Test'
        );

        // secure, insecure
    }

    /**
     * @inheritDoc
     */
    protected function tearDown()
    {
        $this->remoteClient = null;
        $this->cache = null;
        $this->logger = null;
    }

    public function testHostIsCorrectlySet()
    {
        $host = $this->remoteClient->getHost();

        $this->assertEquals('192.168.0.0', $host);
    }

    public function testKeypressMessageisCorrectlyGenerated()
    {
        // function is private so test through queue sending

        $this->assertFalse(false);
    }

    public function testAddKeytoQueue()
    {
        $this->assertFalse(false);
    }

    public function testSendKey()
    {
        $this->assertFalse(false);
    }

    public function testSendKeys()
    {
        $this->assertFalse(false);
    }

    public function testSendQueue()
    {
        $this->assertFalse(false);
    }
}
