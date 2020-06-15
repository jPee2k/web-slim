<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Web\Dev\Validator;

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

$data = file_get_contents(__DIR__ . '/../src/data/users-info.txt');
$preparedUsersData = explode(PHP_EOL, $data);
$users = collect($preparedUsersData)->filter()->all();

// ------ INDEX ------ //

$app->get('/', function ($request, $response) {
    return $response->write('GET /');
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});

// ------ USERS ------ //

$app->get('/users', function ($request, $response) use ($users) {
    $value = $request->getQueryParam('user');
    if ($value !== null) {
        $search = collect($users)->filter(function ($user) use ($value) {
            $decodedUser = json_decode($user, true);
            return str($decodedUser['name'])->contains($value, false);
        })->all();
    
        $params = ['users' => $users, 'search' => $search, 'showRequest' => $value];
    } else {
        $params = ['users' => $users];
    }
    
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->get('/users/new', function ($request, $response) {

    $params = [
        'user' => ['name' => '', 'email' => '', 'password' => '', 'passwordConfirmation' => '', 'city' => ''],
        'errors' => []
    ];

return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

$app->post('/users/new', function ($request, $response) use ($users) {
    $validator = new Validator();
    $id = count($users) + 1;
    $user = $request->getParsedBodyParam('user');
    $user['id'] = $id;
    $errors = $validator->validate($user);

    $file = __DIR__ . '/../src/data/users-info.txt';
    if (count($errors) === 0) {
        $preparedUser = json_encode($user) . PHP_EOL;
        if (file_exists($file)) {
            file_put_contents($file, $preparedUser, FILE_APPEND | LOCK_EX);
        } else {
            return $response->write('Оопс, что-то пошло не так');
        }
        return $response->withRedirect('/users', 302);
    }

    $params = [
        'user' => $user,
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'users/new.phtml', $params);
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
