<?php
/**
 * Simple sending socket example that sends a single message and then prints a hexdump of every response it receives
 *
 * Accepts a single argument socket address (defaults to 224.10.20.30:12345)
 */

use Clue\React\Multicast\Factory;
use Clue\Hexdump\Hexdump;

require __DIR__ . '/../vendor/autoload.php';

$address = isset($argv[1]) ? $argv[1] : '224.10.20.30:12345';

$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);
$sender = $factory->createSender();
$hex = new Hexdump();

// print a hexdump of every message received
$sender->on('message', function ($data, $remote) use ($hex) {
    echo 'Received from ' . $remote . PHP_EOL;
    echo $hex->dump($data) . PHP_EOL;
});

// send a simple message
$message = 'ping 123';
$sender->send($message, $address);

$loop->run();
