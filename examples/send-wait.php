<?php
/**
 * Simple sending socket example that sends a single message and then prints a hexdump of every response it receives
 *
 * Accepts a single argument socket address (defaults to 224.10.20.30:12345)
 */

require __DIR__ . '/../vendor/autoload.php';

$address = isset($argv[1]) ? $argv[1] : '224.10.20.30:12345';

$factory = new Clue\React\Multicast\Factory();
$sender = $factory->createSender();
$hex = new Clue\Hexdump\Hexdump();

// print a hexdump of every message received
$sender->on('message', function ($data, $remote) use ($hex) {
    echo 'Received from ' . $remote . PHP_EOL;
    echo $hex->dump($data) . PHP_EOL;
});

// send a simple message
$message = 'ping 123';
$sender->send($message, $address);
