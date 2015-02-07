<?php

    /**
     * Application Entry Point
     *
     * this file includes the SLIM App and the routing
     * stuff (controllers etc).
     */

    use Service\AuthService;
    use Service\CartService;
    use Service\ProductService;
    use Service\OrderService;
    use Service\UserService;

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

    $app->get('/test', function() use($app){
        $app->setCookie('bla', 'asd');
    });

    /* show products (index page) */
    $app->get('/', $authenticateForRole('customer'), function () use ($app) {
        $app->render('frontend/onepage.twig',
            ['products'=> ProductService::get(),
                'cart' => CartService::get(),
                'orders' => OrderService::getLast24Hours(AuthService::getUser())]
        );
    });

    $app->delete('/orders/:id', $authenticateForRole('customer'), function ($id) use ($app) {
        OrderService::remove($id);
        $app->redirect('/');
    });

    $app->post('/order', $authenticateForRole('customer'), function() use ($app) {
        $post = $app->request()->post();
        CartService::add($post['productId'], $post['quantity'], $post['productName']);
        $app->redirect('/');
    });

    $app->delete('/order/:id', $authenticateForRole('customer'), function($id) use ($app) {
        CartService::removePosition($id);
        $app->redirect('/');
    });

    $app->get('/order', $authenticateForRole('customer'), function() use ($app) {
        CartService::order(AuthService::getUser());
        CartService::clear();
        $app->redirect('/');
    });

    /* login */
    $app->get('/login', function () use ($app) {
        $app->render('login.twig', []);
    });

    $app->post('/login', function () use ($app) {
        $credentials = $app->request()->post();
        $email = $credentials['username'];
        $password = $credentials['password'];

        if(AuthService::check($email,$password)){
            $app->log->info('sucessfully logged in');

            switch(AuthService::getUser()->getRole()){
                case 'admin':
                case 'employee':
                    $app->redirect('/backend/orders');
                    break;
                case 'member':
                case 'customer':
                default:
                    $app->redirect('/');
            }
        }

        $app->log->info('wrong credentials');

        $app->flash('error', 'wrong credentials');
        $app->redirect('/login');
    });

    $app->get('/logout', function () use ($app) {
        AuthService::logout();
        $app->redirect('/login');
    });

    /* backend */
    $app->group('/backend', $authenticateForRole('admin'), function () use ($app) {
        /* list */
        $app->get('/orders', function () use ($app) {
            $app->render('/backend/order/list.twig', ['orders'=> OrderService::all()]);
        });

        $app->get('/products', function () use ($app) {
            $app->render('backend/product/list.twig', ['products'=> ProductService::all()]);
        });

        $app->get('/users', function () use ($app) {
            $app->render('backend/user/list.twig', ['users'=> UserService::all()]);
        });

        //delete routes
        $app->delete('/orders/:id', function ($id) use ($app) {
            OrderService::remove($id);
            $app->render('/backend/order/list.twig', ['orders'=> OrderService::all()]);
        });

        $app->delete('/products/:id', function ($id) use ($app) {
            ProductService::remove($id);
            $app->render('backend/product/list.twig', ['products'=> ProductService::all()]);
        });

        $app->delete('/users/:id', function ($id) use ($app) {
            UserService::remove($id);
            $app->render('backend/user/list.twig', ['users'=> UserService::all()]);
        });

        //show
        $app->get('/orders/:id', function ($id) use ($app) {
            $app->render('/backend/order/show.twig',
                ['order'=> OrderService::get($id)]);
        });

        //add
        $app->get('/products/new', function () use ($app) {
            $app->render('backend/product/add.twig', []);
        });

        $app->post('/products/new', function () use ($app){
            $post = $app->request()->post();

            ProductService::add($post['inputName'], $post['inputUnitPrice'],
                $post['inputDescription'], $_FILES['inputPicture']['tmp_name']);

            $app->redirect('/backend/products');
        });

        $app->get('/users/new', function () use ($app) {
            $app->render('/backend/user/add.twig', []);
        });

        $app->post('/users/new', function () use ($app){
            $post = $app->request()->post();

        UserService::add($post['inputFirstname'], $post['inputLastname'], $post['inputEmail'],
                $post['inputPassword'], $post['inputRole'], $post['inputPhone'], $post['inputPlz'],
                $post['inputCity']);

            $app->redirect('/backend/users');
        });

    });

    $app->run();