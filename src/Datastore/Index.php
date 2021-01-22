<?php

/**
 * IrfanTOOR\Database
 * php version 7.3
 *
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2021 Irfan TOOR
 */

namespace IrfanTOOR\Datastore;

use Exception;
use IrfanTOOR\Database\Model;

class Index extends Model
{
    /**
     * Constructs the Datastore
     *
     * @param array $connection
     */    
    function __construct($connection = [])
    {
        $this->schema = [
            'id INTEGER PRIMARY KEY',
            'key VARCHAR(200)',
            'hash VARCHAR(32)',
            'meta',
            'size INTEGER DEFAULT 0',
            'created_on DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_on INTEGER'
        ];

        $this->indices = [
            ['index' => 'key'],
            ['unique' => 'hash'],
            ['index' => 'created_on'],
        ];

        $connection['create'] = true;
        parent::__construct($connection);
    }
}
