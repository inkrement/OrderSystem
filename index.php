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


    /**
     * service layer
     */

    function removeProduct($id){
        $product = ProductQuery::create()->findPk($id);
        if($product != null){

        }
    }


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
        $order->getUser()->getFirstname();
        //$order->getOrderPositions();
        //$order->getOrderPositions()->
        //$order->getDatetime()->format("Y-m-d H:i:s");
        $order->save();

        /*
        $product = new Product();
        $product->setName("asd");
        $product->setUnitPrice(12.2);
        $product->save();
        */

        $order_position = new OrderPosition();
        $order_position->setProductId(ProductQuery::create()->findPk(1));
        $order_position->setQuantity(2);
        $order_position->setOrder($order);
        //$order_position->getQuantity();
        $order_position->save();


        //$q = new UserQuery();
        //$firstUser = $q->findPK(1);

        //$app->render('test.twig', ['name' => $firstUser->getFirstName()]);
    });

    $app->get('/seed', function(){
        $user = new User();
        $user->setRole('admin');
        $user->setFirstname('chris');
        $user->setLastname('hotz');
        $user->setEmail('a');
        $user->setPassword(password_hash('a', PASSWORD_DEFAULT));
        $user->save();
    });

    /* show products (index page) */
    $app->get('/', $authenticateForRole('member'), function () use ($app) {
        $app->render('frontend/product/list.twig', ['products'=> ProductQuery::create()->find()]);
    });

    $app->group('/orders', $authenticateForRole('member'), function() use($app){

        $app->get('/', function () use ($app) {
            $userid = $app->getCookie('userid');
            $app->render('frontend/order/list.twig', ['orders'=> OrderQuery::create()->findByUserId($userid)]);
        });

        $app->get('/:orderId', function ($orderId) use ($app) {
            //TODO: check permissions

            $app->render('frontend/order/show.twig', ['order'=> OrderQuery::create()->findPk($orderId)]);
        });

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

            $app->setCookie('role', $user->getRole());
            $app->setCookie('userid', $user->getId());
            $app->redirect('/');
            return;
        }
        $app->log->info('wrong credentials');

        $app->flash('error', 'wrong credentials');
        $app->redirect('/login');
    });

    $app->get('/logout', function () use ($app) {
        $app->deleteCookie('role');
        $app->deleteCookie('userid');
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


    /* backend */

    $app->group('/backend', $authenticateForRole('admin'), function () use ($app) {

        /* list */
        $app->get('/orders', function () use ($app) {
            $app->render('/backend/order/list.twig', ['orders'=> OrderQuery::create()->find()]);
        });

        $app->get('/products', function () use ($app) {
            $app->render('backend/product/list.twig', ['products'=> ProductQuery::create()->find()]);
        });

        $app->get('/users', function () use ($app) {
            $app->render('backend/user/list.twig', ['users'=> UserQuery::create()->find()]);
        });

    });



    $app->run();
?>