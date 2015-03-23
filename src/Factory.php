<?php

namespace Clue\React\Multicast;

use React\EventLoop\LoopInterface;
use Socket\React\Datagram\Factory as DatagramFactory;
use Socket\Raw\Factory as RawFactory;

class Factory
{
    private $loop;
    private $rawFactory;
    private $datagramFactory;

    public function __construct(LoopInterface $loop, RawFactory $rawFactory = null, DatagramFactory $datagramFactory = null)
    {
        if ($rawFactory === null) {
            $rawFactory = new RawFactory();
        }

        if ($datagramFactory === null) {
            $datagramFactory = new DatagramFactory($loop);
        }

        $this->rawFactory = $rawFactory;
        $this->datagramFactory = $datagramFactory;
    }

    public function createSender()
    {
        $socket = $this->rawFactory->createUdp4();
        return $this->datagramFactory->createFromRaw($socket);
    }

    public function createReceiver($address)
    {
        if (!defined('MCAST_JOIN_GROUP')) {
            throw new BadMethodCallException('MCAST_JOIN_GROUP not defined');
        }

        $parts = parse_url('udp://' . $address);

        $socket = $this->rawFactory->createUdp4();

        // allow multiple processes to bind to the same address
        $socket->setOption(SOL_SOCKET, SO_REUSEADDR, 1);

        // join multicast group and bind to port
        $socket->setOption(
            IPPROTO_IP,
            MCAST_JOIN_GROUP,
            array('group' => $parts['host'], 'interface' => 0)
        );
        $socket->bind('0.0.0.0:' . $parts['port']);

        return $this->datagramFactory->createFromRaw($socket);
    }
}
