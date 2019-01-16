<?php

require_once 'vendor/autoload.php';

use Gui\Application;
use Gui\Components\Button;
use Gui\Components\Window;
use Kingga\Gui\Routing\Request;
use Kingga\Gui\Routing\RouteGroup;
use Kingga\Gui\Routing\Router;

class MainController
{
    public function showMainWindow(Request $request)
    {
        $btn = (new Button())
            ->setLeft(10)
            ->setTop(10)
            ->setWidth(200)
            ->setValue('Click me!');

        $btn->on('click', function () use ($btn) {
            $btn->setValue('You clicked me!');
            $request->getRouter()->handle('show_close_modal');
        });
    }

    public function killApp(Request $request)
    {
        $request->getApp()->terminate();
    }
}

$app = new Application([
    'title' => 'PHP GUI',
    'width' => 1280,
    'height' => 720,
]);

$router = new Router($app);
$router->create(function (RouteGroup $group) {
    $group->route('main', 'MainController@showMainWindow');
    $group->route('kill', [MainController::class, 'killApp']);

    $group->route('show_close_modal', function (Request $request) {
        $wnd = new Window([
            'title' => 'Popup Window',
            'width' => 800,
            'height' => 600,
        ]);

        $btnClose = new Button([], $wnd);
        $btnClose->setTop(10)
            ->setLeft(10)
            ->setWidth(200)
            ->setValue('Close Application');
        $btnClose->on('click', function () use ($request) {
            $request->getRouter()->handle('kill');
        });
    });
});

$app->on('start', function () use ($app, $router) {
    $router->handle('main');
});

$app->run();
