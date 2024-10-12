<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use App\UserValidator;

session_start();

function getUsers($request) {
    return json_decode($request->getCookieParam('users') ?? '', true);
}

function filterUsersByName($users, $term) {
    return array_filter($users, fn($user) => str_contains($user['nickname'], $term) !== false);
}

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);

$router = $app->getRouteCollector()->getRouteParser();

$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$app->get('/', function ($request, $response) use ($router) {
    $messages = $this->get('flash')->getMessages();
    $params = [
        'email' => '',
        'flash' => $messages ?? []
    ];

    return $this->get('renderer')->render($response, 'home.phtml', $params);
})->setName('home');

$app->get('/users', function ($request, $response) use ($router){
    $term = $request->getQueryParam('term') ?? '';
    $users = getUsers($request) ?? [];
    $usersList = isset($term) ? filterUsersByName($users, $term) : $users;

    $messages = $this->get('flash')->getMessages();

    $params = [
        'users' => $usersList,
        'term' => $term,
        'flash' => $messages
    ];

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users.index');

$app->post('/users', function ($request, $response) use ($router) {
    $users = getUsers($request);
    $userData = $request->getParsedBodyParam('user');

    $validator = new UserValidator();
    $errors = $validator->validate($userData);

    if (count($errors) === 0) {
        $id = uniqid();
        $users[$id] = $userData;

        $encodedUsers = json_encode($users);

        $this->get('flash')->addMessage('success', 'User was added successfully');

        return $response->withHeader('Set-Cookie', "users={$encodedUsers};path=/")
            ->withRedirect($router->urlFor('users.index'));
    }

    $params = [
        'userData' => $userData,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response->withStatus(422), 'users/new.phtml', $params);
})->setName('users.store');

$app->get('/users/new', function ($request, $response) use ($router){

    $params = [
        'userData' => [],
        'errors' => []
    ];

    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('users.create');

$app->get('/users/{id}', function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $users = getUsers($request);

    if (!array_key_exists($id, $users)) {
        return $response->write('Page not found')->withStatus(404);
    }

    $messages = $this->get('flash')->getMessages();

    $params = [
        'id' => $id,
        'user' => $users[$id],
        'flash' => $messages
    ];

    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users.show');

$app->get('/users/{id}/edit', function ($request, $response, $args) use ($router) {
    $messages = $this->get('flash')->getMessages();
    $id = $args['id'];
    $users = getUsers($request);
    $userData = $users[$id];
    $params = [
        'id' => $id,
        'userData' => $userData,
        'errors' => [],
        'flash' => $messages
    ];

    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('users.edit');

$app->patch('/users/{id}', function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $users = getUsers($request);
    $user = $users[$id];
    $userData = $request->getParsedBodyParam('user');

    $validator = new UserValidator();
    $errors = $validator->validate($userData);

    if (count($errors) === 0) {
        $user['nickname'] = $userData['nickname'];
        $user['email'] = $userData['email'];
        $users[$id] = $user;

        $encodedUsers = json_encode($users);

        $this->get('flash')->addMessage('success', "User was updated successfully");

        return $response->withHeader('Set-Cookie', "users={$encodedUsers};path=/")
            ->withRedirect($router->urlFor('users.show', $args));
    }

    $params = [
        'id' => $id,
        'userData' => $userData,
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'users/edit.phtml', $params);
});

$app->delete('/users/{id}', function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $users = getUsers($request);
    unset($users[$id]);

    $encodedUsers = json_encode($users);

    $this->get('flash')->addMessage('success', "User was deleted successfully");

    return $response->withHeader('Set-Cookie', "users={$encodedUsers};path=/")
        ->withRedirect($router->urlFor('users.index'));
});

$app->run();