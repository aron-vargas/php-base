<?php

use Freedom\Components\FreedomSession;
use Freedom\Components\FreedomRoutes;
use Freedom\Components\FreedomHtmlErrorRenderer;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;


# Setup auto load
require __DIR__ . '../vendor/autoload.php';
//require __DIR__ . '/src/components/FreedomRoutes.php';

# Start the Session
session_start();
$session = new FreedomSession();
$session->validate();

// Create Container
$builder = new ContainerBuilder();
//$builder->useAutowiring(true);
$builder->addDefinitions('src/config.php');
$container = $builder->build();
$container->set('session', $session);
AppFactory::setContainer($container);
$GLOBALS['base_url'] = $container->get('base_url');

// Create the app
$app = AppFactory::create();
//$callableResolver = $app->getCallableResolver();

// Add Routing
FreedomRoutes::AddRoutes($app, $container->get('Controllers'));

// Add Routing Middleware
$app->addRoutingMiddleware();

// TODO: Evaluate if this is realy needed
// Using $_GET and $_POST seem to be the traditional way
//
// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Add Error Middleware
// Replace the default HtmlErrorRenderer with FreedomHtmlErrorRenderer
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->registerErrorRenderer('text/html', FreedomHtmlErrorRenderer::class);

$app->run();
