<?php
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/menu.class.php';

$img = imageCreateFromGif(dirname(__FILE__) . '/gfx/menu/menu_orig.gif');
if (isset($_GET['color']) && strlen($_GET['color']) === 6) {
    $color = Tools::getValue('color');
    $light = -150;
    if (isset($_GET['light']) && ((int)$_GET['light'] >= 0 && (int)$_GET['light'] <= 200)) {
        $light = (int)$_GET['light'] + $light;
    }
    else {
        $light += 100;
    }
    Menu::colorize($img, $color, $light);
}
if (isset($_GET['preview'])) {
    header('Content-type: image/gif');
    imageGif($img);
}
imagedestroy($img);