<?php

namespace IrfanTOOR;

use Exception;
use IrfanTOOR\Datastore\Constants;
use IrfanTOOR\Database\Model;
use IrfanTOOR\Filesystem;

class Datastore extends Model
{
    /**
     * Version
     *
     * @var const
     */
    public const VERSION = "0.3.5";

    /**
     * Filesystem Object
     *
     * @var Filesystem
     */
    protected $fs;

    /**
     * Constructs the Datastore
     *
     * @param array
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

        $this->indecies = [
            ['index' => 'key'],
            ['unique' => 'hash'],
            ['index' => 'created_on'],
        ];

        if (isset($connection['file'])) {
            if (!isset($connection['path'])) {
                $connection['path'] = filepath($connection['file']) . '/';
            }
        }

        if (isset($connection['path'])) {
            if (!is_dir($connection['path'])) {
                throw new Exception("path must be a directory", 1);
            }

            if (!isset($connection['file'])) {
                $connection['file'] = rtrim($connection['path'], '/') . '/.datastore.sqlite';
            }
        }

        $this->fs = new Filesystem($connection['path']);

        if (!isset($connection['table'])) {
            $connection['table'] = 'datastoreindex';
        }

        if (!is_file($connection['file'])) {
            file_put_contents($connection['file'], '');
            parent::__construct($connection);
            $this->create();
        } else {
            parent::__construct($connection);
        }
    }

    function hash($key)
    {
        return md5($key);
    }

    function hashToPath($h)
    {
        return
            $h[0] . $h[1] . '/' .
            $h[2] . $h[3] . '/' .
            $h[4] . $h[5] . '/' .
            $h;
    }

    function getPath($key)
    {
        $h = $this->hash($key);
        return $this->hashToPath($h);
    }

    function hasHash($hash)
    {
        return $this->db->has(
            ['where' => 'hash=:hash'],
            ['hash' => $hash]
        );
    }

    function getVersion()
    {
        return self::VERSION;
    }

    function hasKey($key)
    {
        return $this->has(
            ['where' => 'key=:key'],
            ['key' => $key]
        );
    }

    function setContents($key, $contents)
    {
        if (!is_string($contents)) {
            throw new Exception("value must be a string", 1);
        }

        return $this->setComposite([
            'key' => $key,
            'contents' => $contents
        ]);
    }

    function setComposite(Array $c) {
        if (!isset($c['key'])) {
            throw new Exception("key not provided", 1);
        }

        if (!isset($c['contents'])) {
            throw new Exception("contents not provided", 1);
        }

        $hash = $this->hash($c['key']);
        $path = $this->hashToPath($hash);
        $dir = dirname($path);

        if (!$this->fs->hasDir($dir)) {
            $this->fs->createDir($dir, true);
        }

        if ($this->fs->write($path, $c['contents'], true)) {
            $r = [
                'key' => $c['key'],
                'hash' => $hash,
                'meta' => isset($c['meta']) ? json_encode($c['meta']) : '',
                'size'  => strlen($c['contents']),
                'updated_on' => isset($c['updated_on']) ? $c['updated_on'] : time(),
            ];

            if (isset($c['created_on'])) {
                $r['created_on'] = $c['created_on'];
            }

            return $this->insertOrUpdate($r);
        }

        return false;
    }

    function getInfo($key)
    {
        $r = $this->getFirst(
            ['where' => 'key=:key'],
            ['key' => $key]
        );

        if (isset($r['meta'])) {
            $r['meta'] = json_decode($r['meta'], 1);
        }

        return $r ?: null;
    }

    function getContents($key)
    {
        $r = $this->getInfo($key);

        if ($r) {
            return $this->fs->read($this->hashToPath($r['hash']));
        }

        return null;
    }

    function getComposit($key)
    {
        $c = $this->getInfo($key);

        if ($c) {
            $c['contents'] = $this->fs->read($this->hashToPath($c['hash']));
        }

        return $c;
    }

    function removeContents($key)
    {
        $r = $this->getInfo($key);

        if ($r) {
            $path = $this->hashToPath($r['hash']);
            $removed = $this->remove(
                ['where' => 'key = :key'],
                ['key' => $key]
            );
            if ($removed) {
                $this->fs->remove($path);
                return true;
            }
        }

        return false;
    }

    function addFile($key, $file, $meta = [])
    {
        if (!is_file($file)) {
            throw new Exception("file: $file, does not exist", 1);
        }

        $path = dirname($file);
        $filename = str_replace($path, '', $file);
        $ds = $filename[0];
        $path = $path . $ds;
        $filename = str_replace($ds, '', $filename);
        $meta = array_merge(
            [
                'file' => $file,
                'filename' => $filename,
                'mime'     => mime_content_type($file),
            ],
            $meta
        );

        return $this->setComposite([
            'key' => $key,
            'meta' => $meta,
            'created_on' => date('Y-m-d H:i:s', filectime($file)),
            'updated_on' => filemtime($file),
            'contents' => file_get_contents($file),
        ]);
    }
}
