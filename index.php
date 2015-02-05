<?php

    /**
     * Application Entry Point
     *
     * this file includes the SLIM App and the routing
     * stuff (controllers etc).
     */

    require 'vendor/autoload.php';
    require_once './config/config.php';
    require_once './config/php_settings.php';
    require_once 'bootstrap.php';


    /**
     * Routes
     */

    $app->get('/seed', function(){
        $user = new User();
        $user->setRole('admin');
        $user->setFirstname('chris');
        $user->setLastname('hotz');
        $user->setEmail('a');
        $user->setPassword(password_hash('a', PASSWORD_DEFAULT));
        $user->save();

        $order = new Order();
        $order->setUser($user);
        $order->save();

        $product = new Product();
        $product->setName("Kartoffel");
        $product->setDescription("Irgendwelche Veggy Pflanzen");
        $product->setUnitPrice(12.2);
        $product->save();

        $product2 = new Product();
        $product2->setName("Rindfleisch");
        $product2->setDescription("Steak");
        $product2->setUnitPrice(43.2);
        $product2->save();

        $order_position = new OrderPosition();
        $order_position->setProductId($product);
        $order_position->setQuantity(2);
        $order_position->setOrder($order);
        $order_position->save();

        $order_position = new OrderPosition();
        $order_position->setProductId($product2);
        $order_position->setQuantity(4);
        $order_position->setOrder($order);
        $order_position->save();

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
            $app->render('backend/product/list.twig', ['products'=> ProductQuery::create()->findByDeleteflag(false)]);
        });

        $app->get('/users', function () use ($app) {
            $app->render('backend/user/list.twig', ['users'=> UserQuery::create()->find()]);
        });

        //delete routes
        $app->delete('/orders/:id', function ($id) use ($app) {
            OrderQuery::create()->findPk($id)->delete();
            $app->render('/backend/order/list.twig', ['orders'=> OrderQuery::create()->find()]);
        });

        $app->delete('/products/:id', function ($id) use ($app) {
            $product = ProductQuery::create()->findPk($id);
            $product->setDeleteflag(true);
            $product->save();
            $app->render('backend/product/list.twig', ['products'=> ProductQuery::create()->findByDeleteflag(false)]);
        });

        $app->delete('/users/:id', function ($id) use ($app) {
            UserQuery::create()->findPk($id)->delete();
            $app->render('backend/user/list.twig', ['users'=> UserQuery::create()->find()]);
        });

    });

    $app->run();