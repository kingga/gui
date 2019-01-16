<?php

namespace Classes\Controllers;

use Gui\Components\Button;
use Kingga\Gui\Routing\Request;

class MainController
{
    public function showMainWindow(Request $request)
    {
        $btn = (new Button())
            ->setLeft(10)
            ->setTop(10)
            ->setWidth(200)
            ->setValue('Click me!');

        $btn->on('click', function () use ($btn, $request) {
            $btn->setValue('You clicked me!');
            $request->getRouter()->handle('show_close_modal');
        });
    }

    public function killApp(Request $request)
    {
        $request->getApp()->terminate();
    }
}
