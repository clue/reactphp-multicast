# clue/multicast-react [![Build Status](https://travis-ci.org/clue/php-multicast-react.svg?branch=master)](https://travis-ci.org/clue/php-multicast-react)

Multicast UDP messages, built on top of [React PHP](http://reactphp.org/).

Multicast UDP messages are needed for quite a few (low-level) networking protocols.
This library exposes a simple subset of commonly needed functionality for
multicast networking through an easy to use API.

Among others, multicast networking is the basis for:

* MDNS (Multicast DNS)
* HTTPU/HTTPMU (Multicast and Unicast UDP HTTP Messages)
* UPNP/SSDP (Univeral Plug and Play / Simple Service Discovery Protocol).

> Note: This project is in beta stage! Feel free to report any issues you encounter.

## Quickstart example

Once [installed](#install), you can use the following code to create a simple
echo server that listens for incoming multicast messages:

```php
$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);
$socket = $factory->createReceiver('224.10.20.30:4050');

$socket->on('message', function ($data, $remote) use ($socket) {
    echo 'Sending back ' . strlen($data) . ' bytes to ' . $remote . PHP_EOL;
    $socket->send($data, $remote);
});

$loop->run();
```

See also the [examples](examples).

## Usage

### Factory

The `Factory` is responsible for creating your [`Socket`](#socket) instances.
It also registers everything with the main [`EventLoop`](https://github.com/reactphp/event-loop#usage).

```php
$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);
```

#### createSender()

The `createSender()` method can be used to create a socket capable of sending outgoing multicast datagrams and receiving incoming unicast responses. It returns a [`Socket`](#socket) instance.

#### createReceiver()

The `createSender($address)` method can be used to create a socket capable of receiving incoming multicast datagrams and sending outgoing unicast or multicast datagrams. It returns a [`Socket`](#socket) instance.

### Socket

The [`Factory`](#factory) creates instances of the `React\Datagram\Socket` class from the [react/datagram](https://github.com/reactphp/datagram) package.

```php
$socket->send($message, $address);

$socket->on('message', function ($message, $address) { });
$socket->on('close', function() { });

$socket->pause();
$socket->resume();

$socket->end();
$socket->close();
```

Please refer to the [datagram documentation](https://github.com/reactphp/datagram#usage) for more details.

## Description

[PHP 5.4 added support](http://php.net/manual/en/migration54.global-constants.php)
for the required multicast socket options and constants.

These options are only available to the low level socket API (ext-sockets), not
to the newer stream based networking API.
For the most part, React PHP is built around the general purpose stream based API
and has only somewhat limited support for the low level socket API.
Because of this, this library uses a workaround to create stream based sockets
and then sets the required socket options on its underlying low level socket
resource.

This library also provides somewhat limited support for PHP 5.3.
While this version lacks the required socket options and constants for listening
on multicast addresses for incoming messages, its underlying socket API is still
[level 1 multicast conformant](http://www.tldp.org/HOWTO/Multicast-HOWTO-2.html#ss2.2).
This means that it can be used for sending outgoing packages to multicast addresses
and receiving incoming unicast responses in return.

## Install

The recommended way to install this library is [through composer](http://getcomposer.org). [New to composer?](http://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "clue/multicast-react": "~0.2.0"
    }
}
```

## License

MIT
