<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\UserValidator;
use App\UserRepository;

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$app = AppFactory::createFromContainer($container);

$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {

    return $response->withRedirect($router->urlFor('index.users'), 302);
});

$repo = new \App\UserRepository(__DIR__ . '/UsersRepository.json');

$app->post('/users', function ($request, $response) use ($repo, $router) {
    $validator = new UserValidator();
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
        $repo->save($user);
        return $response->withRedirect($router->urlFor('index.users'),302);
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('create.user');

$app->get('/users/new', function ($request, $response) use ($router){
    $params = [
        'user' => ['nickname' => '', 'email' => ''],
        'errors' => [],
        'router' => $router
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('new.user');

$app->get('/users', function ($request, $response) use ($repo, $router){
    $term = $request->getQueryParam('term','');
    $users = $repo->getAllUsers();
    $filteredUsers = array_filter($users, function ($user) use ($term) {
        return empty($term) || str_contains($user['nickname'], $term);
    });
    $params = ['users' => $filteredUsers, 'term' => $term, 'router' => $router];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('index.users');

$app->get('/users/{nickname}/{id}', function ($request, $response, $args) {
    $params = [
        'user' => ['nickname' => $args['nickname'], 'id' => $args['id']],
    ];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('show.user');

$app->run();