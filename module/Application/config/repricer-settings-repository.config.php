<?php

namespace Application\Databases;

return [
    'table name' => 'REPRICER_SETTINGS',
    'primary key' => 'record_id',
    'unique keys' => [
        'setting_name' => 'setting_name'
    ],
    // In order to add columns to the table and the mapping, just update them here.
    'columns' => [
        'record_id' => [
            'definition' => 'int(11) NOT NULL AUTO_INCREMENT',
            'mapping' =>'',
        ],
        'setting_name' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' =>'',
        ],
        'setting_value' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' =>'',
        ],
        'date_created' => [
            'definition' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            'mapping' =>'',
        ],
        'last_updated' => [
            'definition' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'mapping' =>'',
        ],
    ],
];