<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use App\UserValidator;
use App\UserRepository;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);

$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {
    $messages = $this->get('flash')->getMessages();

    $params = [
        'flash' => $messages ?? [],
    ];

    return $response->withRedirect($router->urlFor('index.users'), 302);
})->setName('home');

$repo = new \App\UserRepository(__DIR__ . '/UsersRepository.json');

$app->post('/users', function ($request, $response) use ($repo, $router) {
    $validator = new UserValidator();
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        $repo->save($user);
        $this->get('flash')->addMessage('success', 'User was added successfully');
        return $response->withRedirect($router->urlFor('index.users'),302);
    }

    $params = [
        'user' => $user,
        'errors' => $errors,
        'router' => $router
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
    $messages = $this->get('flash')->getMessages();
    $term = $request->getQueryParam('term','');
    $users = $repo->getAllUsers();
    $filteredUsers = array_filter($users, function ($user) use ($term) {
        return empty($term) || str_contains($user['nickname'], $term);
    });

    $params = [
        'users' => $filteredUsers,
        'term' => $term,
        'flash' => $messages,
        'router' => $router
    ];

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('index.users');

$app->get('/users/{id}', function ($request, $response, $args) use ($repo, $router) {
    $id = $args['id'];
    $user = $repo->find($id);

    if (!$user) {
        return $response->write('User not found')->withStatus(404);
    }

    $params = [
        'user' => $user,
        'router' => $router
    ];

    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('show.user');

$app->get('/users/{id}/edit', function ($request, $response, $args) use ($repo, $router) {
    $messages = $this->get('flash')->getMessages();
    $id = $args['id'];
    $user = $repo->find($id);

    if (!$user) {
        return $response->write('User not found')->withStatus(404);
    }

    $params = [
        'user' => $user,
        'errors' => [],
        'router' => $router,
        'flash' => $messages
    ];

    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('user.edit');

$app->patch('/users/{id}', function ($request, $response, $args) use ($repo, $router) {
    $id = $args['id'];
    $user = $repo->find($id);

    if (!$user) {
        return $response->write('User not found')->withStatus(404);
    }

    $userData = $request->getParsedBodyParam('user');
    $validator = new UserValidator();
    $errors = $validator->validate($userData);

    if (count($errors) === 0) {
        $user['nickname'] = $userData['nickname'];
        $user['email'] = $userData['email'];

        $repo->save($user);

        $this->get('flash')->addMessage('success', "User was updated successfully");
        $url = $router->urlFor('user.edit', ['id' => $user['id']]);
        return $response->withRedirect($url);
    }

    $params = [
        'user' => $userData,
        'errors' => $errors
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('user.update');

$app->run();