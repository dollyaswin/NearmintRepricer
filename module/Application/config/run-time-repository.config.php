<?php

namespace Application\Databases;

return [
    'table name' => 'SCRIPT_RUN_LOG',
    'primary key' => 'record_id',
    // In order to add columns to the table and the mapping, just update them here.
    'columns' => [
        'record_id' => [
            'definition' => 'int(11) NOT NULL AUTO_INCREMENT',
            'mapping' =>'',
        ],
        'script_name' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' =>'',
        ],
        'script_result' => [
            'definition' => 'varchar(255) DEFAULT NULL',
            'mapping' =>'',
        ],
        'script_error_message' => [
            'definition' => 'varchar(1600) DEFAULT NULL',
            'mapping' =>'',
        ],
        'start_time' => [
            'definition' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            'mapping' =>'',
        ],
        'completion_time' => [
            'definition' => 'timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'mapping' =>'',
        ],
    ],
];
