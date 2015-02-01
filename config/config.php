<?php
$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->checkVersion('2.0.0-dev');
$serviceContainer->setAdapterClass('shop', 'sqlite');
$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();
$manager->setConfiguration(array (
  'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
  'dsn' => 'sqlite:shop.db',
  'user' => 'chris',
  'password' => 'somepassword',
  'settings' =>
  array (
    'charset' => 'utf8',
    'queries' =>
    array (
    ),
  ),
));
$manager->setName('shop');
$serviceContainer->setConnectionManager('shop', $manager);
$serviceContainer->setDefaultDatasource('shop');
$serviceContainer->setLoggerConfiguration('defaultLogger', array (
  'type' => 'stream',
  'path' => 'log/propel.log',
  'level' => 300,
));
$serviceContainer->setLoggerConfiguration('shop', array (
  'type' => 'stream',
  'path' => 'log/propel_shop.log',
));