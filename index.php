<?php

    /**
     * Application Entry Point
     *
     * this file includes the SLIM App and the routing
     * stuff (controllers etc).
     */

    require 'vendor/autoload.php';
    require_once './config/config.php';

    //HOTFIX: to remove warning
    date_default_timezone_set('Europe/Vienna');

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
        'charset' => 'utf-8',
        'cache' => realpath('./templates/cache'),
        'auto_reload' => true,
        'strict_variables' => false,
        'autoescape' => true
    );
    $app->view->parserExtensions = array(new \Slim\Views\TwigExtension());
    $view->parserDirectory = 'Twig';


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

            if($cookie_role != $role){
                $app->flash('error', 'Login required');
                $app->redirect('/login');
            }
        };
    };


    /**
     * Routes
     */
    $app->get('/test', function () use ($app) {

        $user = new User();
        $user->setFirstName('Chris');
        $user->setLastName('somename');
        $user->setEmail('example@test.com');
        $user->setPassword(password_hash("1234", PASSWORD_DEFAULT));


        $order = new Order();
        $order->setUser($user);
        $order->save();

        /*
        $product = new Product();
        $product->setName("asd");
        $product->setUnitPrice(12.2);
        $product->save();
        */
        /*
        $order_position = new OrderPosition();
        $order_position->setProductId(ProductQuery::create()->findPk(1));
        $order_position->setQuantity(2);
        $order_position->setOrder($order);
        $order_position->save();
        */

        //$q = new UserQuery();
        //$firstUser = $q->findPK(1);

        //$app->render('test.twig', ['name' => $firstUser->getFirstName()]);
    });

    /* show products (index page) */
    $app->get('/', $authenticateForRole('member'), function () use ($app) {
        $app->render('productlist.twig', ['products'=> ProductQuery::create()->find()]);
    });

    $app->get('/orders', $authenticateForRole('member'), function () use ($app) {
        $app->render('orders.twig', ['orders'=> OrderQuery::create()->find()]);
    });


    /* login */
    $app->get('/login', function () use ($app) {
        $app->render('login.twig', []);
    });

    $app->post('/login', function () use ($app) {
        $credentials = $app->request()->post();

        $email=$credentials['username'];
        $password=$credentials['password'];

        $app->log->info("new login attempt. username: '$email' password: '$password'");

        $user = UserQuery::create()->findOneByEmail($email);

        if($user != null && password_verify($password, $user->getPassword())){
            $app->log->info('sucessfully logged in');

            $app->setCookie('role', 'member');
            $app->redirect('/');
            return;
        }
        $app->log->info('wrong credentials');

        $app->flash('error', 'wrong credentials');
        $app->redirect('/login');
    });

    $app->get('/logout', function () use ($app) {
        $app->deleteCookie('role');
        $app->redirect('/login');
    });


    /* register user */
    $app->get('/register', function () use ($app) {
        $app->render('register.twig', []);
    });

    $app->post('/register', function () use ($app) {
        $post = $app->request()->post();

        $user = new User();
        $user->setFirstname($post['firstname']);
        $user->setLastname($post['lastname']);
        $user->setEmail($post['email']);
        $user->setPassword(password_hash($post['password'], PASSWORD_DEFAULT));
        $user->save();

        $app->redirect('/login');
    });



    $app->run();
?>