<?php
/**
 * UPnP simple service discovery protocol (SSDP)
 */


require __DIR__ . '/../vendor/autoload.php';

$address = '239.255.255.250:1900';

$factory = new Clue\React\Multicast\Factory();
$sender = $factory->createSender();

// dump all incoming messages
$sender->on('message', function ($data, $remote) {
    echo 'Received from ' . $remote . PHP_EOL;
    echo $data . PHP_EOL;
});

// stop waiting for incoming messages after 3.0s (MX is 2s)
Loop::addTimer(3.0, function () use ($sender) {
    $sender->pause();
});

// send a discovery message that all UPnP/SSDP aware devices will respond to
$data  = "M-SEARCH * HTTP/1.1\r\n";
$data .= "HOST: " . $address . "\r\n";
$data .= "MAN: \"ssdp:discover\"\r\n";
$data .= "MX: 2\r\n";
$data .= "ST: ssdp:all\r\n";
$data .= "\r\n";
$sender->send($data, $address);
