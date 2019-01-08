<?php

namespace IrfanTOOR;

use Exception;
use IrfanTOOR\Datastore\DatastoreIndex;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

/**
 * Datastore -- A filesystem based datastore
 */
class Datastore
{	
	protected $db;
	protected $cache;
	protected $fs;

	/**
	 * constructs the datastore
	 *
	 * @param string $path
	 */
	function __construct($path, $adapter = null)
	{
		# memory based cache of hashes
		$this->cache = [];
		
		# adapter 
		if (!$adapter)
        	$adapter  = new Local($path, LOCK_EX, Local::SKIP_LINKS);

        # filesystem
        $this->fs = new Filesystem($adapter);

		$sqlite = '.datastore.sqlite';
		if ($this->fs->has($sqlite)) {
			$this->db = new DatastoreIndex(['file' => $path . $sqlite]);
		} else {
			$this->fs->put($sqlite, '');
			$this->db = new DatastoreIndex(['file' => $path . $sqlite]);
			$this->db->create();
		}
	}
	
	/**
     * getPath - returns the relative path of stored entity
     *
     * @param string $id
     */
	public function getPath($id)
	{
		# repetitive cache
		if (isset($this->cache[$id]))
			return $this->cache[$id];

		$r = $this->db->getFirst(
			['where' => 'id = :id'],
			['id' => $id]
		);
		
		if ($r) {
			$path = $r['path'];
		} else {
			$h = md5($id);
			$path = 
				$h[0] . $h[1] . '/' .
				$h[2] . $h[3] . '/' .
				$h[4] . $h[5] . '/' .
				$h;
		}
		
		$this->cache[$id] = $path;
		
		if (count($cache) > 10) {
			shift($cache);
		}
		
		return $path;
	}
	
	/**
	 * has - verifies the datastore has an entity with the given id
	 *
	 * @param string #id
	 *
	 * @return boolean true | false
	 */
	function has($id)
	{
		return $this->getInfo($id) ? true : false;
	}

	/**
	 * getInfo - returns the information associated to an entity
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	function getInfo($id)
	{
		$r = $this->db->getFirst(
			['where' => 'id=:id'],
			['id' => $id]
		);

		return $r ?: null;
	}	
	
	/**
	 * getContents - returns the contents associated to an id
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	function getContents($id)
	{
		$r = $this->getInfo($id);
		if ($r) {
			return $this->fs->read($r['path']);
		} else {
			return '';
		}
	}
	
	/**
	 * setContents - sets the contents associated to an id, and returns the associated info
	 *
	 * @param string $id
	 * @param string $contents
	 *
	 * @return mixed array | false
	 */
	function setContents($id, $contents = '')
	{
		if (!is_string($contents)) {
			throw new Exception('contents must be a string');
		}

		$r = $this->getInfo($id);
		$path =  $r ? $r['path'] : $this->getPath($id);

		if ($this->fs->put($path, $contents)) {
			$u['id'] = $id;
			$u['path']   = $path;
			$u['size'] = strlen($contents);
			$u['updated_on'] = time();

			$this->db->insertOrUpdate($u);
			return $this->getInfo($id);
		} else {
			return false;
		}
	}

	/**
	 * delete - deletes the contents associated to an id and the entity
	 *
	 * @param string $id
	 *
	 * @return bool true | false
	 */
	function delete($id)
	{
		$r = $this->getInfo($id);
		if (isset($r['path'])) {
			$this->db->remove(
				['where' => 'path=:path'],
				['path' => $r['path']]
			);
			
			return $this->fs->delete($r['path']);
		} else {
			return false;
		}
	}
}
