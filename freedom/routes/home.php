<?php


$app->get('/', [\CDController::class, 'index'])->setName('index');
$app->get('/home', [\HomeController::class, 'home'])->setName('home');
$app->get('/contact', [\HomeController::class, 'contact'])->setName('contact');
$app->get('/about', [\HomeController::class, 'about'])->setName('about');
$app->get('/register', [\HomeController::class, 'register'])->setName('register');
$app->post('/register', [\HomeController::class, 'create']);
$app->get('/login', [\HomeController::class, 'login'])->setName('login');
$app->post('/login', [\HomeController::class, 'authenticate']);
$app->get('/forgot-password', [\HomeController::class, 'forgot_password'])->setName('password.forgot');
$app->post('/forgot-password', [\HomeController::class, 'new_password']);
$app->get('/reset-password/{token}', [\HomeController::class, 'reset_create'])->setName('password.reset');
$app->post('/reset-password', [\HomeController::class, 'reset_store'])->setName('password.store');
