<?php

namespace Clue\Tests\React\Multicast;

use Clue\React\Multicast\Factory;

class FunctionalTest extends TestCase
{
    private $loop;
    private $factory;

    private $address = '224.224.244.244:2244';

    /**
     * @before
     */
    public function setUpMocks()
    {
        $this->loop = \React\EventLoop\Factory::create();
        $this->factory = new Factory($this->loop);
    }

    /** @doesNotPerformAssertions */
    public function testSenderWithNoReceiver()
    {
        $sender = $this->factory->createSender();

        // send a single message and do not receive anything
        $sender->pause();
        $sender->send('hello?', $this->address);

        $this->loop->run();
    }

    public function testMultipleReceivers()
    {
        try {
            $receiver1 = $this->factory->createReceiver($this->address);
        } catch (\BadMethodCallException $e) {
            $this->markTestSkipped('No multicast support');
        }

        $receiver2 = $this->factory->createReceiver($this->address);
        $sender = $this->factory->createSender();

        // expect both receivers receive a single message
        $receiver1->on('message', $this->expectCallableOnce());
        $receiver2->on('message', $this->expectCallableOnce());

        // stop waiting for further messages once the first message arrived
        $receiver1->on('message', array($receiver1, 'pause'));
        $receiver2->on('message', array($receiver2, 'pause'));

        // send a single message and do not receive anything
        $sender->pause();
        $sender->send('message', $this->address);

        $this->loop->run();
    }

    public function testCreateSenderThrowsWhenFileDescriptorsAreExceeded()
    {
        $ulimit = exec('ulimit -n 2>&1', $_, $exit);
        if ($exit !== 0 || $ulimit < 1) {
            $this->markTestSkipped('Unable to determine limit of open files (ulimit not available?)');
        }

        $memory = ini_get('memory_limit');
        if ($memory === '-1') {
            $memory = PHP_INT_MAX;
        } elseif (preg_match('/^\d+G$/i', $memory)) {
            $memory = ((int) $memory) * 1024 * 1024 * 1024;
        } elseif (preg_match('/^\d+M$/i', $memory)) {
            $memory = ((int) $memory) * 1024 * 1024;
        } elseif (preg_match('/^\d+K$/i', $memory)) {
            $memory = ((int) $memory) * 1024;
        }

        // each file descriptor takes ~600 bytes of memory, so skip test if this would exceed memory_limit
        if ($ulimit * 600 > $memory || $ulimit > 100000) {
            $this->markTestSkipped('Test requires ~' . round($ulimit * 600 / 1024 / 1024) . '/' . round($memory / 1024 / 1024) . ' MiB memory with ' . $ulimit . ' file descriptors');
        }

        // keep creating dummy file handles until all file descriptors are exhausted
        $fds = array();
        for ($i = 0; $i < $ulimit; ++$i) {
            $fd = @fopen('/dev/null', 'r');
            if ($fd === false) {
                break;
            }
            $fds[] = $fd;
        }

        $this->setExpectedException('RuntimeException', 'Unable to create sending socket: ');
        $this->factory->createSender();
    }

    public function testCreateReceiverThrowsForPortAlreadyInUse()
    {
        if (!defined('MCAST_JOIN_GROUP') || !function_exists('socket_import_stream')) {
            $this->markTestSkipped('No multicast support');
        }

        // bind to random port without SO_REUSEADDR, so receiver can not bind to same port
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_bind($socket, '0.0.0.0', 0);
        socket_getsockname($socket, $host, $port);

        $this->setExpectedException('RuntimeException', 'Unable to create receiving socket: ' . socket_strerror(SOCKET_EADDRINUSE));
        $this->factory->createReceiver('224.224.244.244:' . $port);
    }

    public function testCreateReceiverThrowsForNonMulticastAddressWithoutCallingCustomErrorHandler()
    {
        if (!defined('MCAST_JOIN_GROUP') || !function_exists('socket_import_stream')) {
            $this->markTestSkipped('No multicast support');
        }

        // find random free port, as createReceiver() can not parse port 0 on legacy PHP
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_bind($socket, '0.0.0.0', 0);
        socket_getsockname($socket, $host, $port);
        socket_close($socket);

        $error = null;
        set_error_handler(function ($_, $errstr) use (&$error) {
            $error = $errstr;
        });

        $this->setExpectedException('RuntimeException', 'Unable to join multicast group: ' . socket_strerror(SOCKET_EINVAL), SOCKET_EINVAL);

        try {
            $this->factory->createReceiver('127.0.0.1:' . $port);

            restore_error_handler();
        } catch (\Exception $e) {
            restore_error_handler();
            $this->assertNull($error);

            throw $e;
        }
    }

    public function testConstructWithoutLoopAssignsLoopAutomatically()
    {
        $factory = new Factory();

        $ref = new \ReflectionProperty($factory, 'loop');
        if (PHP_VERSION_ID < 80100) {
            $ref->setAccessible(true);
        }
        $loop = $ref->getValue($factory);

        $this->assertInstanceOf('React\EventLoop\LoopInterface', $loop);
    }

    public function testCtorThrowsForInvalidLoop()
    {
        $this->setExpectedException('InvalidArgumentException', 'Argument #1 ($loop) expected null|React\EventLoop\LoopInterface');
        new Factory('loop');
    }
}
