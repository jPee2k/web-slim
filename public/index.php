<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use Web\Dev\Validator;

use function Web\Dev\Users\usersData;
use function Web\Dev\Users\findUserById;
use function Web\Dev\Users\putContents;

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
$app->add(MethodOverrideMiddleware::class);

$router = $app->getRouteCollector()->getRouteParser();

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

// SHOW USERS
$app->get('/users', function ($request, $response) use ($router) {
    $users = usersData();
    $value = $request->getQueryParam('user');
    $messages = $this->get('flash')->getMessages();

    $params = [
        'users' => json_encode($users),
        'router' => $router,
        'messages' => $messages,
        'search' => [],
        'queryParam' => ''
    ];

    if ($value !== null) {
        $search = collect($users)->filter(function ($user) use ($value) {
            return str($user['name'])->contains($value, false);
        })->all();
    
        $params['search'] = json_encode($search);
        $params['queryParam'] = $value;
    }
    
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

// REDIRECT to FORM CREATING
$app->get('/users/new', function ($request, $response) use ($router) {

    $params = [
        'user' => ['name' => '', 'email' => '', 'password' => '', 'passwordConfirmation' => ''],
        'errors' => [],
        'router' => $router
    ];

return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('new');

// CREATING a new USER
$app->post('/users', function ($request, $response) use ($router) {
    $user = $request->getParsedBodyParam('user');
    if (!isset($user['id'])) {
        $user['id'] = uniqid();
    }
    
    $validator = new Validator();
    $errors = $validator->validate($user);

    $user['password'] = password_hash($user['password'], PASSWORD_DEFAULT);
    unset($user['passwordConfirmation']);

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

// READING USER'S DATA
$app->get('/users/{id}', function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $users = usersData();

    if (!array_key_exists($id, $users)) {
        return $response->withStatus(404)->write("Oops, user with id: {$id} not found");
    }

    $params = [
        'id' => $id,
        'nickname' => $users[$id]['name'],
        'router' => $router
    ];

    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

// REDIRECTING to FORM UPDATING USER'S DATA
$app->get('/users/{id}/edit', function ($request, $response, $args) {
    $id = $args['id'];
    $flash = $this->get('flash')->getMessages();

    //$users = usersData();
    $user = findUserById($id);

    $params = [
        'user' => $user,
        'flash' => $flash,
        'errors' => []
    ];

    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('editUser');

// UPDATING USER'S DATA
$app->patch('/users/{id}', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $user = findUserById($id);

    $data = $request->getParsedBodyParam('user');

    $validator = new Validator();
    $errors = $validator->validate($data);

    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    unset($data['passwordConfirmation']);

    if (count($errors) === 0) {
        $user['name'] = $data['name'];
        $user['email'] = $data['email'];
        $user['password'] = $data['password'];

        // TODO REWRITE Exist user!!!!!
        if (putContents($user)) {
            $this->get('flash')->addMessage('succes', 'Your data has been updated');
            return $response->withRedirect($router->urlFor('editUser', ['id' => $user['id']]));
        } 
    } else {
        $this->get('flash')->addMessage('error', 'Ooh, crap! Your data wasn\'t updated');
    }

    $params = [
        'user' => $user,
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'users/edit.phtml', $params);
});

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
