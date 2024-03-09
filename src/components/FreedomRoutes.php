<?php

namespace Freedom\Components;

use Freedom\Controllers\CDController;
//use Freedom\Controllers\HomeController;
//use Freedom\Controllers\API1;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

class FreedomRoutes
{
    public function __construct()
    {
    }

    static public function AddRoutes($app, $controllers)
    {
        $app->options('/{routes:.*}', function (Req $request, Res $response)
        {
            // CORS Pre-Flight OPTIONS Request Handler
            return $response;
        });

        // Index
        $app->get('/', [CDController::class, 'index'])->setName('index');

        foreach ($controllers as $className)
        {
            $className::AddRoutes($app);
        }

        CDController::AddRoutes($app);
    }
}