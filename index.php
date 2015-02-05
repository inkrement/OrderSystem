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
        $user->setCity('Wien');
        $user->setPlz(1040);
        $user->setPhone('004369911602033');
        $user->setPassword(password_hash('a', PASSWORD_DEFAULT));
        $user->save();

        $user = new User();
        $user->setRole('employee');
        $user->setFirstname('andrea');
        $user->setLastname('musterfrau');
        $user->setEmail('peter@musterfrau.at');
        $user->setCity('Wien');
        $user->setPlz(1140);
        $user->setPhone('004369911602033');
        $user->setPassword(password_hash('1234', PASSWORD_DEFAULT));
        $user->save();

        $user = new User();
        $user->setRole('customer');
        $user->setFirstname('peter');
        $user->setLastname('mustermann');
        $user->setEmail('peter@mustermann.at');
        $user->setCity('Wien');
        $user->setPlz(1090);
        $user->setPhone('004369911602033');
        $user->setPassword(password_hash('1234', PASSWORD_DEFAULT));
        $user->save();

        $order = new Order();
        $order->setUser($user);
        $order->save();

        $product = new Product();
        $product->setName("Kartoffel");
        $product->setDescription("Irgendwelche Veggy Pflanzen");
        $product->setImg("sadasd.png");
        $product->setUnitPrice(12.2);
        $product->save();

        $product2 = new Product();
        $product2->setName("Rindfleisch");
        $product2->setImg("saasdasd.png");
        $product2->setDescription("Steak");
        $product2->setUnitPrice(43.2);
        $product2->save();

        $product3 = new Product();
        $product3->setName("Wasser");
        $product3->setImg("wasser.png");
        $product3->setDescription("mineralwasser in glasflasche");
        $product3->setUnitPrice(1.2);
        $product3->save();

        $order_position = new OrderPosition();
        $order_position->setProduct($product);
        $order_position->setQuantity(2);
        $order_position->setOrder($order);
        $order_position->save();

        $order_position = new OrderPosition();
        $order_position->setProduct($product2);
        $order_position->setQuantity(4);
        $order_position->setOrder($order);
        $order_position->save();

        $order_position = new OrderPosition();
        $order_position->setProduct($product3);
        $order_position->setQuantity(10);
        $order_position->setOrder($order);
        $order_position->save();

    });

    /* show products (index page) */
    $app->get('/', $authenticateForRole('customer'), function () use ($app) {
        $app->render('frontend/onepage.twig',
            ['products'=> ProductQuery::create()->findByDeleteflag(false),
                'orders' => OrderQuery::create()->filterByUserId($app->getCookie('userid'))->filterByDatetime(array('min' => time() - 24 * 60 * 60))]
        );
    });

    $app->delete('/orders/:id', $authenticateForRole('customer'), function ($id) use ($app) {
        OrderQuery::create()->findById($id)->delete();
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

    /*
    $app->group('/api', $authenticateForRole('customer'), function() use ($app){
        $app->get('/orders');
        $app->delete('/orders/:id');
        $app->post('/orders');
    });
    */

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