<?php
/**
 * UPNP simple service discovery protocol (SSDP)
 */

use Clue\React\Multicast\Factory;

require __DIR__ . '/../vendor/autoload.php';

$address = '239.255.255.250:1900';

$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);
$sender = $factory->createSender();

// dump all incoming messages
$sender->on('message', function ($data, $remote) use ($hex) {
    echo 'Received from ' . $remote . PHP_EOL;
    echo $data . PHP_EOL;
});

// stop waiting for incoming messages after 3.0s (MX is 2s)
$loop->addTimer(3.0, function () use ($sender) {
    $sender->pause();
});

// send a discovery message that all upnp/ssdp aware devices will respond to
$data  = "M-SEARCH * HTTP/1.1\r\n";
$data .= "HOST: " . $address . "\r\n";
$data .= "MAN: \"ssdp:discover\"\r\n";
$data .= "MX: 2\r\n";
$data .= "ST: ssdp:all\r\n";
$data .= "\r\n";
$sender->send($data, $address);

$loop->run();
