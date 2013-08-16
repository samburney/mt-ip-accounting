<?php
use Illuminate\Database\Capsule\Manager as DB;

$db = new DB;

$db->addConnection(array(
    'driver'    => $db_driver,
    'host'      => $db_host,
    'database'  => $db_db,
    'username'  => $db_user,
    'password'  => $db_pass,
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => $db_prefix,
));

// Set the event dispatcher used by Eloquent models... (optional)
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
$db->setEventDispatcher(new Dispatcher(new Container));

// Set the cache manager instance used by connections... (optional)
//$capsule->setCacheManager(...);

// Make this Capsule instance available globally via static methods... (optional)
$db->setAsGlobal();

// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$db->bootEloquent();