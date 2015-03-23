<?php
/**
 * Simple sending socket example that exits after sending a single message
 *
 * Accepts a single argument socket address (defaults to 224.10.20.30:12345)
 */

use Clue\React\Multicast\Factory;

require __DIR__ . '/../vendor/autoload.php';

$address = isset($argv[1]) ? $argv[1] : '224.10.20.30:12345';

$loop = React\EventLoop\Factory::create();
$factory = new Factory($loop);
$sender = $factory->createSender();

// do not wait for incoming messages
$sender->pause();

// send a simple message
$message = 'ping 123';
$sender->send($message, $address);

$loop->run();
