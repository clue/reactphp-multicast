<?php
/**
 * Simple receiving socket example server that sends back every message it received
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

$socket->on('message', function ($data, $remote) use ($socket) {
    echo 'Sending back ' . strlen($data) . ' bytes to ' . $remote . PHP_EOL;
    $socket->send($data, $remote);
});
