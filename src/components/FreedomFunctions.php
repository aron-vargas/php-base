<?php

use Freedom\Controllers\CDController;
use Freedom\Controllers\HomeController;
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
    $app->get('/static/{page:.*}', [HomeController::class, 'static']);

    // General Get Page
    $app->get('/{act}/{model}[/{section:.*}]', [HomeController::class, 'get_act']);

    // General Post Page
    $app->post('/{act}/{model}[/{section:.*}]', [HomeController::class, 'post_act']);
}