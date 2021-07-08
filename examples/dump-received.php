<?php
/**
 * Simple receiving example socket server that prints a hexdump of every message received
 *
 * Accepts a single argument socket address (defaults to 224.10.20.30:12345)
 */

require __DIR__ . '/../vendor/autoload.php';

$address = '224.10.20.30:12345'; // random test address
//$address = '239.255.255.250:1900'; // UPNP SSDP (simple service discovery protocol)

// use either above default address or the one given as first argument to this script
if (isset($argv[1])) {
    $address = $argv[1];
}

$factory = new Clue\React\Multicast\Factory();
$socket = $factory->createReceiver($address);
$hex = new Clue\Hexdump\Hexdump();

$socket->on('message', function ($data, $remote) use ($hex) {
    echo 'Received from ' . $remote . PHP_EOL;
    echo $hex->dump($data) . PHP_EOL;
});

