<?php

use Slim\Slim;
use Slim\Views;
use Service\AuthService;
use Service\OrderService;

/**
 * BOOTSTRAP
 *
 * @author Christian Hotz-Behofsits <chris.hotz.behofits@gmail.com>
 */

/*
 * SLIM Config
 */
$app = new Slim([
    'debug' => true,
    'templates.path' => './templates',
    'log.level' => \Slim\Log::DEBUG,
    'log.writer' => (new \Slim\LogWriter(fopen('../log/shop.log', 'a'))),
    //'cookies.encrypt' => true,
    //do not allow client to view cookies
    //'cookies.httponly' => true,
    //some secret for cookies
    'cookies.secret_key' => '043028b2c88efd91a024b7aace0f25f8'
]);
$app->add(new \Slim\Middleware\SessionCookie(array(
    'expires' => '20 minutes',
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'httponly' => false,
    'name' => 'slim_session',
    'secret' => 'SomeSuperSecretValue',
    'cipher' => MCRYPT_RIJNDAEL_256,
    'cipher_mode' => MCRYPT_MODE_CBC
)));

/*
 * TWIG CONFIG
 */
$view = $app->view(new Views\Twig());
$view->parserOptions = array(
    'debug' => true,
    'charset' => 'utf-8',
    'cache' => realpath('./templates/cache'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);
$app->view->parserExtensions = array(new Views\TwigExtension());
$view->parserDirectory = 'Twig';

$twig = $app->view->getEnvironment();
$function = new Twig_SimpleFunction('order_sum', function ($orderId) use($app){
    return OrderService::getTotal($orderId);
});

$twig->addFunction($function);
$twig->addFunction(new Twig_SimpleFunction('isAllowed', function ($role) use($app){
    return isAllowed($app->getCookie('role', $role));
}));



/**
 * auth filter. so called middleware
 *
 * @param string $role
 * @return callable
 */
$authenticateForRole = function ( $role = 'member' ) {
    return function () use ( $role ) {
        $app = Slim::getInstance();

        $current_role = AuthService::getUser()->getRole;

        if(!AuthService::isAllowed($current_role, $role)){
            $app->flash('error', 'Login required');
            $app->redirect('/auth/login');
        }
    };
};


