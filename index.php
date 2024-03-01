<?php
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

# Setup auto load
require __DIR__ . '../vendor/autoload.php';
spl_autoload_register('LoadClass');
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__);

# Start the Session
session_start();
$session = new FreedomSession();
$session->validate();

// Create Container
$builder = new ContainerBuilder();
$builder->useAutowiring(true);
$builder->addDefinitions('freedom/config.php');
$container = $builder->build();
$container->set('session', $session);
AppFactory::setContainer($container);

// Create the app
$app = AppFactory::create();

// Add Routing Middleware
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();

// Import routes from the route directory
RouteImport($app, __DIR__ . "/freedom/routes");

$app->run();

function LoadClass($class)
{
    if (file_exists(__DIR__ . "/freedom/{$class}.php"))
        include_once __DIR__ . "/freedom/{$class}.php";
    else if (file_exists(__DIR__ . "/freedom/components/{$class}.php"))
        include_once __DIR__ . "/freedom/components/{$class}.php";
    else if (file_exists(__DIR__ . "/freedom/models/{$class}.php"))
        include_once __DIR__ . "/freedom/models/{$class}.php";
    else if (file_exists(__DIR__ . "/freedom/views/{$class}.php"))
        include_once __DIR__ . "/freedom/views/{$class}.php";
    else if (file_exists(__DIR__ . "/freedom/controllers/{$class}.php"))
        include_once __DIR__ . "/freedom/controllers/{$class}.php";
    else if (file_exists(__DIR__ . "/freedom/templates/{$class}"))
        include_once __DIR__ . "/freedom/templates/{$class}";
}
function RouteImport($app, $path)
{
    if (is_dir($path))
    {
        $fileNames = glob($path . '/*.php');
        foreach ($fileNames as $fileName)
        {
            require $fileName;
        }
    }
}