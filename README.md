Aerospike Cache and Session for Yii 2
===============================================

This extension provides the [aerospike](http://http://www.aerospike.com) key-value store support for the Yii2 framework.
It includes a `Cache` and `Session` storage handler.

Requirements
------------

At least Aerospike version 3.5.3 Server and [Aerospike PHP Extension](http://www.aerospike.com/docs/client/php/install/)is required for all components to work properly.

Installation
------------


Using the Cache component
-------------------------

To use the `Cache` component, in addition to configuring the connection as described above,
you also have to configure the `cache` component to be `yii\aerospike\Cache`:

```php
return [
    //....
    'components' => [
        // ...
        'cache' => [
            'class' => 'yii\aerospike\Cache',
            'aerospike' =>
                [
                    'config' => [ "hosts" => [ [ "addr" => "127.0.0.1", "port" => 3000 ] ]],
                    'namespace' => 'happy',
                    'set' => 'cache',
                    'persistent_connection' => true
                ],
        ],
    ]
];
```

Using the Session component
---------------------------

To use the `Session` component, in addition to configuring the connection as described above,
you also have to configure the `session` component to be `yii\aerospike\Session`:

```php
return [
    //....
    'components' => [
        // ...
        'session' => [
            'class' => 'yii\aerospike\Session',
            'aerospike' =>
                [
                    'config' => [ "hosts" => [ [ "addr" => "127.0.0.1", "port" => 3000 ] ]],
                    'namespace' => 'session',
                    'set' => 'session',
                    'persistent_connection' => true
                ],
        ],
    ]
];
```