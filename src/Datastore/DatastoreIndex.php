<?php

namespace IrfanTOOR\Datastore;

use IrfanTOOR\Database\Model;

class DatastoreIndex extends Model
{
	function __construct($connection)
	{
		$this->schema = [
			'id VARCHAR(200) PRIMARY KEY',
			'path VARCHAR(41)',
			'size INTEGER DEFAULT 0',
			'created_on DATETIME DEFAULT CURRENT_TIMESTAMP',
			'updated_on INTEGER'
		];

		$this->indecies = [
			['index'  => 'path'],
			['index' => 'created_on'],
		];

		parent::__construct($connection);
	}
}
