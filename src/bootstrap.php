<?php
/**
 * BOOTSTRAP
 *
 * @author Christian Hotz-Behofsits <chris.hotz.behofits@gmail.com>
 */

use Slim\Slim;
use Slim\Views;
use Service\AuthService;
use Service\OrderService;

/*
 * SLIM Config
 */
$app = new Slim([
    'debug' => true,
    'templates.path' => './templates',
    'log.level' => \Slim\Log::DEBUG,
    //'log.writer' => (new \Slim\LogWriter(fopen('../log/shop.log', 'a'))),
    //'cookies.encrypt' => true,
    //do not allow client to view cookies
    //'cookies.httponly' => true,
    //some secret for cookies
    'cookies.secret_key' => '043028b2c88efd91a024b7aace0f25f8'
]);

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
    return AuthService::isAllowed(AuthService::getUser()->getRole(), $role);
}));

$twig->addFunction(new Twig_SimpleFunction('getUser', function () use($app){
    return AuthService::getUser();
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
        $current_role = AuthService::getUser()->getRole();

        if(!AuthService::isAllowed($current_role, $role)){
            $app->log->info("not allowed rights: '$current_role' and not '$role'" );
            $app->flash('error', 'Login required');
            $app->redirect('/auth/login');
        }
    };
};


