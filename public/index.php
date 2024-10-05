<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {

    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {
    $createUserUrl = $router->urlFor('user.create');
    $userListUrl = $router->urlFor('user.list');

    return $response->write("<a href='{$createUserUrl}'>Create User</a>");
})->setName('home');

$app->post('/users', function ($request, $response) {
    $userData = $request->getParsedBodyParam('user');
    $id = uniqid();
    $repo = __DIR__ . '/UserRepository.json'; // repository of users
    if (file_exists($repo)) { // read existing users
        $users = json_decode(file_get_contents($repo), true); // decode JSON
    } else {
        $users = [];
    }
    $users[] = [
        'nickname' => $userData['nickname'],
        'email' => $userData['email'],
        'id' => $id
    ];
    file_put_contents($repo, json_encode($users, JSON_PRETTY_PRINT));
    return $response->withStatus(302)->withHeader('Location', '/users');
})->setName('user.store');

$app->get('/users/new', function ($request, $response) use ($router) {
    $params = ['userData' => [], 'router' => $router];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('user.create');


$app->get('/users', function ($request, $response) use ($router) {
    $term = $request->getQueryParam('term');


    $repo = __DIR__ . '/UserRepository.json';
    if (file_exists($repo)) {
        $users = json_decode(file_get_contents($repo), true);
    } else {
        $users = [];
    }


    if ($term) {
        $filteredUsers = array_filter($users, function ($user) use ($term) {
            return str_contains($user['nickname'], $term);
        });
    } else {
        $filteredUsers = $users;
    }

    $params = [
        'users' => $filteredUsers,
        'term' => $term,
        'router' => $router
    ];

    return $this->get('renderer')->render($response, "users/index.phtml", $params);
})->setName('user.list');

$app->get('/users/{id:.+}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user.show');

$app->run();