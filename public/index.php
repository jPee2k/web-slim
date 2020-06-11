<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

use function Stringy\create as str;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

// $app = AppFactory::create();
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

// ------ GLOBALS ------ //

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

// ------ INDEX ------ //

$app->get('/', function ($request, $response) {
    return $response->write('GET /');
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});

// ------ USERS ------ //

$app->get('/users', function ($request, $response) use ($users) {
    $value = $request->getQueryParam('user');
    $search = collect($users)->filter(function ($user) use ($value) {
        return str($user)->contains($value);
    })->all();
    
    $params = ['users' => $users, 'search' => $search, 'showRequest' => $value];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->post('/users', function ($request, $response) {
    return $response->withStatus(302);
});

// ------ COMPANIES ------ //

$app->get('/companies', function ($request, $response) {
    return $response->write('GET /companies');
});

$app->post('/companies', function ($request, $response) {
    return $response->write('POST /companies');
});

// ------ COURSES ------ //

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->run();
