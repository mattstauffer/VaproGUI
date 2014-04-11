<?php

require '../vendor/autoload.php';
require '../helpers/core.php';

// Prepare app
$app = new \Slim\Slim(array(
    'templates.path' => '../templates',
));

// Prepare view
$app->view(new \Slim\Views\Twig());
$app->view->parserOptions = array(
    'charset' => 'utf-8',
    'cache' => realpath('../templates/cache'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);
$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());

$app->get('/', function() use ($app) {
    $reader = new Vaprogui\Ruby\Reader('test-vagrantfile-simple');
    $reader->processFile();
    $processed = $reader->getProcessedFile();

    $presenter = new Vaprogui\UI\Form($processed);
    $form = $presenter->outputForm();

    $data = array('form' => $form);

    $app->render('form.html', $data);
});

$app->run();