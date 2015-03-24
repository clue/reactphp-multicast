<?php

namespace Clue\React\Multicast;

use React\EventLoop\LoopInterface;
use React\Datagram\Socket as DatagramSocket;
use BadMethodCallException;
use RuntimeException;

class Factory
{
    private $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function createSender()
    {
        $stream = @stream_socket_server('udp://0.0.0.0:0', $errno, $errstr, STREAM_SERVER_BIND);
        if ($stream === false) {
            throw new RuntimeException('Unable to create sending socket: ' . $errstr, $errno);
        }

        return new DatagramSocket($this->loop, $stream);
    }

    public function createReceiver($address)
    {
        if (!defined('MCAST_JOIN_GROUP')) {
            throw new BadMethodCallException('MCAST_JOIN_GROUP not defined (requires PHP 5.4+)');
        }
        if (!function_exists('socket_import_stream')) {
            throw new BadMethodCallException('Function socket_import_stream missing (requires ext-sockets and PHP 5.4+)');
        }

        $parts = parse_url('udp://' . $address);

        $stream = @stream_socket_server('udp://0.0.0.0:' . $parts['port'], $errno, $errstr, STREAM_SERVER_BIND);
        if ($stream === false) {
            throw new RuntimeException('Unable to create receiving socket: ' . $errstr, $errno);
        }

        $socket = socket_import_stream($stream);
        if ($stream === false) {
            throw new RuntimeException('Unable to access underlying socket resource');
        }

        // allow multiple processes to bind to the same address
        $ret = socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        if ($ret === false) {
            throw new RuntimeException('Unable to enable SO_REUSEADDR');
        }

        // join multicast group and bind to port
        $ret = socket_set_option(
            $socket,
            IPPROTO_IP,
            MCAST_JOIN_GROUP,
            array('group' => $parts['host'], 'interface' => 0)
        );
        if ($ret === false) {
            throw new RuntimeException('Unable to join multicast group');
        }

        return new DatagramSocket($this->loop, $stream);
    }
}
