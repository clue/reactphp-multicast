<?php

namespace Clue\React\Multicast;

use React\EventLoop\LoopInterface;
use React\Datagram\Socket as DatagramSocket;
use BadMethodCallException;

class Factory
{
    private $loop;
    private $rawFactory;
    private $datagramFactory;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function createSender()
    {
        $stream = stream_socket_server('udp://0.0.0.0:0', $errno, $errstr, STREAM_SERVER_BIND);

        return new DatagramSocket($this->loop, $stream);
    }

    public function createReceiver($address)
    {
        if (!defined('MCAST_JOIN_GROUP')) {
            throw new BadMethodCallException('MCAST_JOIN_GROUP not defined');
        }

        $parts = parse_url('udp://' . $address);

        $stream = stream_socket_server('udp://0.0.0.0:' . $parts['port'], $errno, $errstr, STREAM_SERVER_BIND);

        $socket = socket_import_stream($stream);

        // allow multiple processes to bind to the same address
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

        // join multicast group and bind to port
        socket_set_option(
            $socket,
            IPPROTO_IP,
            MCAST_JOIN_GROUP,
            array('group' => $parts['host'], 'interface' => 0)
        );

        return new DatagramSocket($this->loop, $stream);
    }
}
