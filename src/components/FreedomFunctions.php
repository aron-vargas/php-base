<?php

use Freedom\Controllers\CDController;
use Freedom\Controllers\HomeController;
use Freedom\Controllers\API1;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

function AddRoutes($app)
{
    $app->options('/{routes:.*}', function (Req $request, Res $response)
    {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', [CDController::class, 'index'])->setName('index');
    $app->get('/home', [HomeController::class, 'home'])->setName('home');

    // Named Routes
    $app->get('/contact', [HomeController::class, 'contact'])->setName('contact');
    $app->get('/about', [HomeController::class, 'about'])->setName('about');
    $app->get('/register', [HomeController::class, 'register'])->setName('register');
    $app->post('/register', [HomeController::class, 'create']);
    $app->get('/login', [HomeController::class, 'login'])->setName('login');
    $app->post('/login', [HomeController::class, 'authenticate']);
    $app->get('/logout', [HomeController::class, 'logout'])->setName('logout');
    $app->get('/forgot-password', [HomeController::class, 'forgot_password'])->setName('password.forgot');
    $app->post('/forgot-password', [HomeController::class, 'new_password']);
    $app->get('/reset-password/{token}', [HomeController::class, 'reset_create'])->setName('password.reset');
    $app->post('/reset-password', [HomeController::class, 'reset_store'])->setName('password.store');

    // Static Pages
    $app->get('/static/{page:.*}', [CDController::class, 'static']);

    // API Calls
    $app->get('/api/v1/{act}/{model}', [API1::class, 'get_act']);
    $app->post('/api/v1/{act}/{model}', [API1::class, 'post_act']);
    $app->put('/api/v1/{act}/{model}', [API1::class, 'put_act']);
    $app->delete('/api/v1/{act}/{model}', [API1::class, 'delete_act']);

    // General Get Page
    $app->get('/{section}/{page}[/{act}[/{pkey:[0-9]+}]]', [CDController::class, 'get_act']);

    // General Post Page
    $app->post('/{section}/{page}[/{act}]', [CDController::class, 'post_act']);
}