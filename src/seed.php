<?php

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

$order = new Order();
$order->setUser($user);
$order->setDatetime('2015-01-07 14:53:12');
$order->save();

$product = new Product();
$product->setName("Kartoffel");
$product->setDescription("Irgendwelche Veggy Pflanzen");
$product->setImg("placeholder.gif");
$product->setUnitPrice(12.2);
$product->save();

$product2 = new Product();
$product2->setName("Rindfleisch");
$product2->setImg("placeholder.gif");
$product2->setDescription("Steak");
$product2->setUnitPrice(43.2);
$product2->save();

$product3 = new Product();
$product3->setName("Wasser");
$product3->setImg("placeholder.gif");
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