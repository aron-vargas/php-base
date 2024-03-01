<?php
use \Slim\Routing\RouteCollectorProxy;

$app->group('/crm', function (RouteCollectorProxy $group)
{
    // Users
    $group->get('/companies', [\CRMController::class, 'list_companyies'])->setName('companies');
    $group->get('/company/{id:[0-9]+}', [\CRMController::class, 'get_company'])->setName('get_company');
    $group->delete('/company/{id:[0-9]+}', [\CRMController::class, 'rm_company'])->setName('rm_company');
    $group->post('/company', [\CRMController::class, 'update_company'])->setName('update_company');

    //Profiles
    $group->get('/customers', [\CRMController::class, 'list_customers'])->setName('customers');
    $group->get('/customer/{id:[0-9]+}', [\CRMController::class, 'get_customer'])->setName('get_customer');
    $group->delete('/customer/{id:[0-9]+}', [\CRMController::class, 'rm_customer'])->setName('rm_customer');
    $group->post('/customer', [\CRMController::class, 'update_customer'])->setName('update_customer');
});