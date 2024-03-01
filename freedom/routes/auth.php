<?php
use \Slim\Routing\RouteCollectorProxy;

$app->group('/auth', function (RouteCollectorProxy $group)
{
    // Users
    $group->get('/users', [\AdminController::class, 'list_users'])->setName('users');
    $group->get('/user/{id:[0-9]+}', [\AdminController::class, 'get_user'])->setName('get_user');
    $group->delete('/user/{id:[0-9]+}', [\AdminController::class, 'rm_user'])->setName('rm_user');
    $group->post('/user', [\AdminController::class, 'update_user'])->setName('update_user');

    //Profiles
    $group->get('/profiles', [\AdminController::class, 'list_profiles'])->setName('profiles');
    $group->get('/profile/{id:[0-9]+}', [\AdminController::class, 'get_profile'])->setName('get_profile');
    $group->delete('/profile/{id:[0-9]+}', [\AdminController::class, 'rm_profile'])->setName('rm_profile');
    $group->post('/profile', [\AdminController::class, 'update_profile'])->setName('update_profile');
});