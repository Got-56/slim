<?php

require __DIR__ . '/../vendor/autoload.php'; // подключаем автозагрузчик Composer

use Slim\Factory\AppFactory; // создаем экземпляр приложения Slim

$app = AppFactory::create(); // инициализируем приложение
$app->addErrorMiddleware(true, true, true); // добавляем обработчик ошибок

$app->get('/users', function ($request, $response) { // регистрируем обработчик GET-запроса на /users
    return $response->write('Get /users'); // возвращаем ответ в виде текста
});

$app->run(); // запускаем приложение