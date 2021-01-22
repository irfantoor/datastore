<?php

/**
 * IrfanTOOR\Database
 * php version 7.3
 *
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2021 Irfan TOOR
 */

namespace IrfanTOOR;

use Exception;
use IrfanTOOR\Datastore\Index;
use IrfanTOOR\Filesystem;

class Datastore
{
    const NAME           = "Irfan's Datastore";
    const DESCRIPTION    = "Data Store of key, value pairs";
    const VERSION        = "0.4.1";

    /** @var Model */
    protected $index;

    /** @var Filesystem */
    protected $fs;

    /**
     * Constructs the Datastore
     *
     * @param string $path Datastore path
     */
    public function __construct(string $path)
    {
        if (!is_dir($path))
            throw new Exception("path must be a directory", 1);

        $this->fs = new Filesystem($path);

        $file = rtrim($path, '/') . '/ds.idx';
        $this->index = new Index(
            [
                'file'  => $file,
                'table' => 'ds'
            ]
        );
    }

    /**
     * Calculates the hash of a key
     *
     * @param string $key
     * @return string
     */
    protected function hash(string $key): string
    {
        return md5($key);
    }

    /**
     * Caclulates the path from a hash
     *
     * @param string $hash
     * @return string
     */
    protected function hashToPath(string $h): string
    {
        if (!isset($h[5]))
            throw new Exception("invalid hash");

        return
            $h[0] . $h[1] . '/' .
            $h[2] . $h[3] . '/' .
            $h[4] . $h[5] . '/' .
            $h;
    }

    /**
     * Caclulates and returns the path assciated with a key
     *
     * @param string $key
     */
    public function getPath(string $key): string
    {
        $hash = $this->hash($key);
        return $this->hashToPath($hash);
    }

    /**
     * Verifies if a hash is present
     */
    protected function hasHash(string $hash): bool
    {
        return $this->index->has(['hash' => $hash]);
    }

    /**
     * Returns the version of Datastore
     *
     * @return string
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Verifies if a key is present in the store
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->index->has(
            [
                'where' => 'key=:key',
                'bind' => [
                    'key' => $key
                ]
            ]
        );
    }

    /**
     * Sets the value of the key
     *
     * @param string $key
     * @param string $value
     * @param array  $meta  Meta-tags or keywords related to the value
     *
     * @return bool
     */
    function set(string $key, string $value, array $meta = []): bool
    {
        $hash = $this->hash($key);
        $path = $this->hashToPath($hash);
        $dir = dirname($path);

        $updating = $this->fs->has($path);

        if (!$updating)
            $this->fs->createDir($dir, true);

        if ($this->fs->write($path, $value, true)) {
            $info = [
                'key' => $key,
                'hash' => $hash,
                'meta' => json_encode($meta),
                'size'  => strlen($value),
                'updated_on' => time(),
            ];

            if (!$updating)
                $info['created_on'] = $info['updated_on'];

            return $this->index->insertOrUpdate($info);
        }

        return false;
    }

    /**
     * Retrieves information regarding an entry
     *
     * @param string $key
     *
     * @return null|array
     */
    function info(string $key)
    {
        $info = $this->index->getFirst(
            [
                'where' => 'key=:key',
                'bind' => [
                    'key' => $key
                ]
            ]
        );

        if ($info)
            $info['meta'] = json_decode($info['meta'], true);

        return $info ?? null;
    }

    /**
     * Retrieves the value of key
     *
     * @param string $key
     *
     * @return null|string
     */
    function get(string $key)
    {
        $info = $this->info($key);
        return $info ? $this->fs->read($this->hashToPath($info['hash'])) : null;
    }

    /**
     * Removes the value associated to the key and its information
     *
     * @param string $key
     */
    public function remove(string $key)
    {
        $info = $this->info($key);

        if ($info) {
            $path = $this->hashToPath($info['hash']);

            $result = $this->index->remove(
                [
                    'where' => 'key = :key',
                    'bind'  => [
                        'key' => $key,
                    ]
                ],
            );

            if ($result) {
                return $this->fs->remove($path);
            }
        }

        return false;
    }

    function addFile($key, $file, $meta = [])
    {
        if (!is_file($file))
            throw new Exception("file: $file, does not exist", 1);

        $path = dirname($file);
        $filename = str_replace($path, '', $file);
        $ds = $filename[0];
        $path = $path . $ds;
        $filename = str_replace($ds, '', $filename);

        $meta = array_merge(
            [
                'file'       => $file,
                'filename'   => $filename,
                'mime'       => mime_content_type($file),
                'created_on' => date('Y-m-d H:i:s', filectime($file)),
                'updated_on' => filemtime($file),
            ],
            $meta
        );

        return $this->set($key, file_get_contents($file), $meta);
    }
}
