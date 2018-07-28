<?php

namespace Clue\React\Multicast;

use React\EventLoop\LoopInterface;
use React\Datagram\Socket as DatagramSocket;
use BadMethodCallException;
use RuntimeException;

class Factory
{
    private $loop;

    /**
     * The `Factory` is responsible for creating your [`SocketInterface`](#socketinterface) instances.
     * It also registers everything with the main [`EventLoop`](https://github.com/reactphp/event-loop#usage).
     *
     * ```php
     * $loop = React\EventLoop\Factory::create();
     * $factory = new Factory($loop);
     * ```
     *
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * Creates a socket capable of sending outgoing multicast datagrams and receiving
     * incoming unicast responses. It returns a [`SocketInterface`](#socketinterface) instance.
     *
     * ```php
     * $socket = $factory->createSender();
     *
     * // send a multicast message to everybody listening on the given address
     * $socket->send('hello?', '224.10.20.30:4050');
     *
     * // report incoming unicast replies
     * $socket->on('message', function ($data, $address) {
     *     echo 'received ' . strlen($data) . ' bytes from ' . $address . PHP_EOL;
     * });
     * ```
     *
     * This method works on PHP versions as old as PHP 5.3 (and up), as its socket API has always been
     * [level 1 multicast conformant](https://www.tldp.org/HOWTO/Multicast-HOWTO-2.html#ss2.2).
     *
     * @return \React\Datagram\SocketInterface
     * @throws RuntimeException
     */
    public function createSender()
    {
        $stream = @stream_socket_server('udp://0.0.0.0:0', $errno, $errstr, STREAM_SERVER_BIND);
        if ($stream === false) {
            throw new RuntimeException('Unable to create sending socket: ' . $errstr, $errno);
        }

        return new DatagramSocket($this->loop, $stream);
    }

    /**
     * Creates a socket capable of receiving incoming multicast datagrams and sending
     * outgoing unicast or multicast datagrams. It returns a [`SocketInterface`](#socketinterface) instance.
     *
     * ```php
     * $socket = $factory->createReceiver('224.10.20.30:4050');
     *
     * // report incoming multicast messages
     * $socket->on('message', function ($data, $remote) use ($socket) {
     *     echo 'Sending back ' . strlen($data) . ' bytes to ' . $remote . PHP_EOL;
     *
     *     // send a unicast reply to the remote
     *     $socket->send($data, $remote);
     * });
     * ```
     *
     * This method requires PHP 5.4 (or up) and `ext-sockets`.
     * Otherwise, it will throw a `BadMethodCallException`.
     * This is a requirement because receiving multicast datagrams requires a
     * [level 2 multicast conformant](https://www.tldp.org/HOWTO/Multicast-HOWTO-2.html#ss2.2)
     * socket API.
     * The required multicast socket options and constants have been added with
     * [PHP 5.4](http://php.net/manual/en/migration54.global-constants.php) (and up).
     * These options are only available to the low level socket API (ext-sockets), not
     * to the newer stream based networking API.
     *
     * Internally, this library uses a workaround to create stream based sockets
     * and then sets the required socket options on its underlying low level socket
     * resource.
     * This is done because ReactPHP is built around the general purpose stream based API
     * and has only somewhat limited support for the low level socket API.
     *
     * @param string $address
     * @return \React\Datagram\SocketInterface
     * @throws BadMethodCallException
     * @throws RuntimeException
     */
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
