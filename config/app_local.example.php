<?php
return [
    'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'Security' => [
        'salt' => env('SECURITY_SALT', '5ae39b4dcb1854ad50d3ba0e27697a2e514dfff591d1fa5950fe45c843dc0d49'),
    ],

   /* CONFIGURAÇÃO DO BANCO DE DADOS*/
    'Datasources' => [
        'default' => [
            'host' => '127.0.0.1',
            //'port' => 'non_standard_port_number',
            'username' => 'root',
            'password' => '',
            'database' => 'codechatmanagement',
            /*
             * Caso use DSN coloque aqui em baixo DESCOMENTE PRIMEIRO!
             */
          //  'url' => env('DATABASE_URL', null),
        ],
    ],

    /*
     * CONFIGURAÇÃO DO EMAIL!
     *
     * Host and credential configuration in case you are using SmtpTransport
     *
     */
    'EmailTransport' => [
        'default' => [
            'host' => 'localhost',
            'port' => 25,
            'username' => null,
            'password' => null,
            'client' => null,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],
];
