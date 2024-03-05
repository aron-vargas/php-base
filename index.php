<?php

use Freedom\Components\FreedomSession;
use Freedom\Components\FreedomHtmlErrorRenderer;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;


# Setup auto load
require __DIR__ . '../vendor/autoload.php';
require __DIR__ . '/src/components/FreedomFunctions.php';

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
AddRoutes($app);

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Add Error Middleware
// Replace the default HtmlErrorRenderer with FreedomHtmlErrorRenderer
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->registerErrorRenderer('text/html', FreedomHtmlErrorRenderer::class);

$app->run();
