<?php

require '../vendor/autoload.php';
require '../helpers/core.php';

$app = new \Slim\Slim();

$app->get('/', function() use ($app) {
    $reader = new Vaprogui\Reader('test-vagrantfile-simple');
    $reader->processFile();
    $reader->printProcessedFile();
});

$app->run();