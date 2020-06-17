<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Web\Dev\Validator;

use function Stringy\create as str;

session_start();

$container = new Container();

// Обработчик 'renderer' -> slim-php-view
$container->set('renderer', function () {
    // Параметром передается базовая директория в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

// Обработчик 'flash' -> slim-flash
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

// ------ GLOBALS ------ //

$path = __DIR__ . '/../src/data/users-info.txt';

if (file_exists($path)) {
    $data = file_get_contents($path);
    $preparedUsersData = explode(PHP_EOL, $data);
    $users = collect($preparedUsersData)->filter()->all();
} else {
    $users = [];
}

// ------ INDEX ------ //

$app->get('/', function ($request, $response) use ($router) {
    return $this->get('renderer')->render($response, 'index.phtml', ['router' => $router]);
});

/*
 * $app->get('/', function ($request, $response) use ($router) {
 *     // в функцию передаётся имя маршрута, а она возвращает url
 *     return $response->write($router->urlFor('users'));
 * });
*/

// ------ USERS ------ //

$app->get('/users', function ($request, $response) use ($users, $router) {
    $value = $request->getQueryParam('user');
    $messages = $this->get('flash')->getMessages();

    $params = [
        'users' => $users,
        'router' => $router,
        'messages' => $messages
    ];

    if ($value !== null) {
        $search = collect($users)->filter(function ($user) use ($value) {
            $decodedUser = json_decode($user, true);
            return str($decodedUser['name'])->contains($value, false);
        })->all();
    
        $params['search'] = $search;
        $params['showRequest'] = $value;
    }
    
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');


$app->get('/users/new', function ($request, $response) use ($router) {

    $params = [
        'user' => ['name' => '', 'email' => '', 'password' => '', 'passwordConfirmation' => ''],
        'errors' => [],
        'router' => $router
    ];

return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('new');


$app->post('/users', function ($request, $response) use ($users, $router) {
    $validator = new Validator();
    $user = $request->getParsedBodyParam('user');
    $user['id'] = uniqid();
    $errors = $validator->validate($user);

    $file = __DIR__ . '/../src/data/users-info.txt';
    if (count($errors) === 0) {
        $preparedUser = json_encode($user) . PHP_EOL;
        $result = file_put_contents($file, $preparedUser, FILE_APPEND | LOCK_EX);
        if ($result) {
            $this->get('flash')->addMessage('success', 'The user was succeeded created');
            return $response->withRedirect($router->urlFor('users'), 302);
        } else {
            $this->get('flash')->addMessage('error', 'The user wasn\'t created');
            return $response;
        }
    }

    $params = [
        'user' => $user,
        'errors' => $errors,
        'router' => $router
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'users/new.phtml', $params);
})->setName('users');


$app->get('/users/{id}', function ($request, $response, $args) use ($router, $users) {
    $id = $args['id'];

    $usersById = collect($users)->map(function ($user) {
        return json_decode($user, true);
    })->keyBy('id')->all();
    
    if (!array_key_exists($id, $usersById)) {
        return $response->withStatus(404)->write("Oops, user with id: {$id} not found");
    }

    $params = [
        'id' => $id,
        'nickname' => "user-{$usersById[$id]['name']}",
        'router' => $router
    ];

    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');


// ------ COMPANIES ------ //

$app->get('/companies', function ($request, $response) {
    return $response->write('GET /companies');
})->setName('companies');

$app->post('/companies', function ($request, $response) {
    return $response->write('POST /companies');
})->setName('companies');

// ------ COURSES ------ //

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
})->setName('course');

$app->get('/foo', function ($req, $res) {
    // Добавление флеш-сообщения. Оно станет доступным на следующий HTTP-запрос.
    // 'success' — тип флеш-сообщения. Используется при выводе для форматирования.
    // Например можно ввести тип success и отражать его зелёным цветом (на Хекслете такого много)
    $this->get('flash')->addMessage('success', 'This is a message');

    return $res->withRedirect('/bar');
});

$app->get('/bar', function ($req, $res, $args) {
    // Извлечение flash сообщений установленных на предыдущем запросе
    $messages = $this->get('flash')->getMessages();
    return $this->get('renderer')->render($res, 'foobar.phtml', ['messages' => $messages]);
})->setName('bar');

$app->run();
