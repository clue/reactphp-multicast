# clue/reactphp-multicast

[![CI status](https://github.com/clue/reactphp-multicast/workflows/CI/badge.svg)](https://github.com/clue/reactphp-multicast/actions)

Simple, event-driven multicast UDP message client and server for [ReactPHP](https://reactphp.org/).

Multicast UDP messages are needed for quite a few (low-level) networking protocols.
Among others, multicast networking is the basis for mDNS (Multicast DNS),
HTTPMU (Multicast UDP HTTP Messages), UPnP/SSDP (Universal Plug and Play /
Simple Service Discovery Protocol) and others.
This library exposes a simple subset of commonly needed functionality for
multicast networking through an easy to use API.

**Table of contents**

* [Support us](#support-us)
* [Quickstart example](#quickstart-example)
* [Usage](#usage)
    * [Factory](#factory)
        * [createSender()](#createsender)
        * [createReceiver()](#createreceiver)
    * [SocketInterface](#socketinterface)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Support us

We invest a lot of time developing, maintaining and updating our awesome
open-source projects. You can help us sustain this high-quality of our work by
[becoming a sponsor on GitHub](https://github.com/sponsors/clue). Sponsors get
numerous benefits in return, see our [sponsoring page](https://github.com/sponsors/clue)
for details.

Let's take these projects to the next level together! 🚀

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

The `Factory` is responsible for creating your [`SocketInterface`](#socketinterface) instances.
It also registers everything with the main [`EventLoop`](https://github.com/reactphp/event-loop#usage).

```php
$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);
```

#### createSender()

The `createSender(): SocketInterface` method can be used to
create a socket capable of sending outgoing multicast datagrams and receiving
incoming unicast responses. It returns a [`SocketInterface`](#socketinterface) instance.

```php
$socket = $factory->createSender();

// send a multicast message to everybody listening on the given address
$socket->send('hello?', '224.10.20.30:4050');

// report incoming unicast replies
$socket->on('message', function ($data, $address) {
    echo 'received ' . strlen($data) . ' bytes from ' . $address . PHP_EOL;
});
```

This method works on PHP versions as old as PHP 5.3 (and up), as its socket API has always been
[level 1 multicast conformant](https://www.tldp.org/HOWTO/Multicast-HOWTO-2.html#ss2.2).

#### createReceiver()

The `createReceiver(string $address): SocketInterface` method can be used to
create a socket capable of receiving incoming multicast datagrams and sending
outgoing unicast or multicast datagrams. It returns a [`SocketInterface`](#socketinterface) instance.

```php
$socket = $factory->createReceiver('224.10.20.30:4050');

// report incoming multicast messages 
$socket->on('message', function ($data, $remote) use ($socket) {
    echo 'Sending back ' . strlen($data) . ' bytes to ' . $remote . PHP_EOL;
    
    // send a unicast reply to the remote
    $socket->send($data, $remote);
});
```

This method requires PHP 5.4 (or up) and `ext-sockets`.
Otherwise, it will throw a `BadMethodCallException`.
This is a requirement because receiving multicast datagrams requires a
[level 2 multicast conformant](https://www.tldp.org/HOWTO/Multicast-HOWTO-2.html#ss2.2)
socket API.
The required multicast socket options and constants have been added with
[PHP 5.4](http://php.net/manual/en/migration54.global-constants.php) (and up).
These options are only available to the low level socket API (ext-sockets), not
to the newer stream based networking API.

Internally, this library uses a workaround to create stream based sockets
and then sets the required socket options on its underlying low level socket
resource.
This is done because ReactPHP is built around the general purpose stream based API
and has only somewhat limited support for the low level socket API.

### SocketInterface

The [`Factory`](#factory) creates instances of the `React\Datagram\SocketInterface`
from the [react/datagram](https://github.com/reactphp/datagram) package.
This means that you can use all its normal methods like so:

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

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project follows [SemVer](https://semver.org/).
This will install the latest supported version:

```bash
$ composer require clue/multicast-react:^1.1
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on legacy PHP 5.3 through current PHP 8+ and
HHVM.
It's *highly recommended to use PHP 7+* for this project.

The [`createSender()`](#createsender) method works on all supported platforms
without any additional requirements. However, the [`createReceiver()`](#createreceiver)
method requires PHP 5.4 (or up) and `ext-sockets`. See above for more details.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```

## License

This project is released under the permissive [MIT license](LICENSE).

> Did you know that I offer custom development services and issuing invoices for
  sponsorships of releases and for contributions? Contact me (@clue) for details.
