<?php
/**
 * Created by PhpStorm.
 * User: chris
 * Date: 04.02.15
 * Time: 18:25
 */

/**
 * app configuration
 */
$app = new \Slim\Slim([
    'debug' => true,
    'templates.path' => './templates',
    'log.level' => \Slim\Log::DEBUG
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

/**
 * setup template engine
 */
$view = $app->view(new \Slim\Views\Twig());
$view->parserOptions = array(
    'debug' => true,
    'charset' => 'utf-8',
    'cache' => realpath('./templates/cache'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);
$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());
$view->parserDirectory = 'Twig';

/**
 * register twig specific functions
 */
$twig = $app->view->getEnvironment();
$function = new Twig_SimpleFunction('order_sum', function ($orderId) use($app){
    $app->log->debug("sum order value for order '$orderId'");

    $orderpositions = OrderPositionQuery::create()->findByOrderId($orderId);

    $sum = 0.0;

    foreach($orderpositions as $position){
        $quantity = $position->getQuantity();
        $unitprice = $position->getProduct()->getUnitPrice();

        $app->log->debug("found new position quantity: '$quantity' unitprice: '$unitprice'");
        $sum += $quantity * $unitprice;
    }

    return $sum;
});

$twig->addFunction($function);
$twig->addFunction(new Twig_SimpleFunction('isAllowed', function ($role) use($app){
    return isAllowed($app->getCookie('role', $role));
}));


/**
 * converts right to number
 * @param $role
 * @return int
 */
function rights($role){
    $rights = 0;

    switch($role){
        case 'admin':
            $rights += 100;
        case 'employee':
            $rights += 100;
        case 'member':
            $rights += 100;
        case 'customer':
            $rights += 100;
    }

    return $rights;
}

/**
 * @param $role
 * @param string $min
 * @return bool
 */
function isAllowed($role, $min='member'){
    return (rights($role) < rights($min))? false: true;
}


/**
 * auth filter. so called middleware
 *
 * @param string $role
 * @return callable
 */
$authenticateForRole = function ( $role = 'member' ) {
    return function () use ( $role ) {
        $app = \Slim\Slim::getInstance();
        $cookie_role = $app->getCookie('role');

        $app->log->debug("auth filter for '$role' user is '$cookie_role'");

        if(!isAllowed($cookie_role, $role)){
            $app->flash('error', 'Login required');
            $app->redirect('/login');
        }
    };
};