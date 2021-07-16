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
    
    public function testConstructWithoutLoopAssignsLoopAutomatically()
    {
        $factory = new Factory();
        
        $ref = new \ReflectionProperty($factory, 'loop');
        $ref->setAccessible(true);
        $loop = $ref->getValue($factory);
        
        $this->assertInstanceOf('React\EventLoop\LoopInterface', $loop);
    }
}
