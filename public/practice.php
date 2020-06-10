<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Tightenco\Collect;
use DI\Container;

// https://ru.hexlet.io/courses/php-mvc/lessons/handlers/theory_unit

$faker = \Faker\Factory::create();
$faker->seed(1234);

$domains = [];
for ($i = 0; $i < 10; $i++) {
    $domains[] = $faker->domainName;
}

$phones = [];
for ($i = 0; $i < 10; $i++) {
    $phones[] = $faker->phoneNumber;
}


$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

// BEGIN
$app->get('/phones', function ($request, $response) use ($phones) {
    return $response->write(json_encode($phones));
});

$app->get('/domains', function ($request, $response) use ($domains) {
    return $response->write(json_encode($domains));
});
// END


// https://ru.hexlet.io/courses/php-mvc/lessons/http-session/theory_unit
$companies = Web\Dev\Generator::generate(100);

// BEGIN
$app->get('/companies', function ($request, $response) use ($companies) {
    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 5);
    $offset = ($page - 1) * $per;

    $sliceOfCompanies = array_slice($companies, $offset, $per);
    return $response->write(json_encode($sliceOfCompanies));
});
// END


// https://ru.hexlet.io/courses/php-mvc/lessons/dynamic-routes/theory_unit
// BEGIN
$app->get('/companies/{id:[0-9]+}', function ($request, $response, $args) use ($companies) {
    $id = $args['id'];
    $company = collect($companies)->firstWhere('id', $id);
    if (!$company) {
        return $response->withStatus(404)->write('Page not found');
    }
    return $response->write(json_encode($company));
});
// END


// https://ru.hexlet.io/courses/php-mvc/lessons/template/theory_unit
$users = Web\Dev\UsersGenerator::generate(100);

// BEGIN
$app->get('/users', function ($request, $response) use ($users) {
    $params = [
        'users' => $users
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->get('/users/{id}', function ($request, $response, $args) use ($users) {
    $id = (int) $args['id'];
    $user = collect($users)->firstWhere('id', $id);
    $params = ['user' => $user];
    return $this->get('renderer')->render($response, 'users/show-practice.phtml', $params);
});
// END


$app->run();
