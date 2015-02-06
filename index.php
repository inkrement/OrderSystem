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

    $app->get('/seed', function() use ($app){
        include 'seed.php';

        $app->redirect('/');
    });

    /* show products (index page) */
    $app->get('/', $authenticateForRole('customer'), function () use ($app) {
        $cart = json_decode($app->getCookie('orderpositions'), true);
        if($cart == null) $cart = [];

        $app->render('frontend/onepage.twig',
            ['products'=> ProductQuery::create()->findByDeleteflag(false),
                'cart' => $cart,
                'orders' => OrderQuery::create()->filterByUserId($app->getCookie('userid'))->filterByDatetime(array('min' => time() - 24 * 60 * 60))]
        );
    });

    $app->delete('/orders/:id', $authenticateForRole('customer'), function ($id) use ($app) {
        OrderQuery::create()->findById($id)->delete();
        $app->redirect('/');
    });

    $app->post('/order', $authenticateForRole('customer'), function() use ($app) {
        $post = $app->request()->post();
        $c = $app->getCookie('orderpositions');

        $orders = ($c == null)?[]:json_decode($c, true);

        array_push($orders, ['id' => $post['productId'], 'quantity' => $post['quantity'],
            'name' => $post['productName']]);

        $app->setCookie('orderpositions', json_encode($orders));
        $app->redirect('/');
    });

    $app->delete('/order/:id', $authenticateForRole('customer'), function($id) use ($app) {
        $post = $app->request()->post();
        $c = $app->getCookie('orderpositions');
        $orders = ($c == null)?[]:json_decode($c, true);

        //var_dump($orders);
        //echo "------";
        unset($orders[$id]);
        //var_dump($orders);

        $app->setCookie('orderpositions', json_encode($orders));
        $app->redirect('/');
    });

    $app->get('/order', $authenticateForRole('customer'), function() use ($app) {
        $c = $app->getCookie('orderpositions');
        $orders = ($c == null)?[]:json_decode($c, true);

        //TODO: this could be better.
        $user = UserQuery::create()->findById($app->getCookie('userid'))->getFirst();

        if(count($orders) > 0){
            $order = new Order();
            $order->setUser($user);
            $order->save();

            foreach($orders as $pos){
                $orderpos = new OrderPosition();
                $orderpos->setQuantity($pos['quantity']);
                $orderpos->setProduct(ProductQuery::create()->findById($pos['id'])->getFirst());
                $orderpos->setOrder($order);
                $orderpos->save();
            }

        }

        $app->setCookie('orderpositions', json_encode([]));
        $app->redirect('/');
    });



    /* login */
    $app->get('/login', function () use ($app) {
        $app->render('login.twig', []);
    });

    $app->post('/login', function () use ($app) {
        global $auth_user;

        $credentials = $app->request()->post();

        $email=$credentials['username'];
        $password=$credentials['password'];

        $app->log->info("new login attempt. username: '$email' password: '$password'");

        $auth_user = UserQuery::create()->findOneByEmail($email);

        if($auth_user != null && password_verify($password, $auth_user->getPassword())){
            $app->log->info('sucessfully logged in');

            $app->setCookie('role', $auth_user->getRole());
            $app->setCookie('userid', $auth_user->getId());

            switch($auth_user->getRole()){
                case 'admin':
                case 'employee':
                    $app->redirect('/backend/orders');
                    break;
                case 'member':
                case 'customer':
                default:
                    $app->redirect('/');
            }
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
            OrderQuery::create()->findById($id)->delete();
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

        //show
        $app->get('/orders/:id', function ($id) use ($app) {
            $app->render('/backend/order/show.twig', ['order'=> OrderQuery::create()->findById($id)->getFirst()]);
        });

        //add
        $app->get('/products/new', function () use ($app) {
            $app->render('backend/product/add.twig', []);
        });

        $app->post('/products/new', function () use ($app){
            $post = $app->request()->post();

            $name = uniqid('img-'.date('Ymd').'-');

            $image = new \Eventviva\ImageResize($_FILES['inputPicture']['tmp_name']);
            $image->resizeToWidth(300);
            $image->save('templates/img/' . $name);

            $product = new Product();
            $product->setImg($name);
            $product->setName($post['inputName']);
            $product->setUnitPrice($post['inputUnitPrice']);
            $product->setDescription($post['inputDescription']);
            $product->save();

            $app->redirect('/backend/products');
        });

        $app->get('/users/new', function () use ($app) {
            $app->render('/backend/user/add.twig', []);
        });

        $app->post('/users/new', function () use ($app){
            $post = $app->request()->post();

            $user = new User();
            $user->setFirstname($post['inputFirstname']);
            $user->setLastname($post['inputLastname']);
            $user->setEmail($post['inputEmail']);
            $user->setPassword($post['inputPassword']);
            $user->setRole($post['inputRole']);
            $user->setPhone($post['inputPhone']);
            $user->setPlz($post['inputPlz']);
            $user->setCity($post['inputCity']);
            $user->save();

            $app->redirect('/backend/users');
        });

    });

    $app->run();