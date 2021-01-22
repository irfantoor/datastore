<?php

use IrfanTOOR\Database;
use IrfanTOOR\Datastore;
use IrfanTOOR\Datastore\Model;
use IrfanTOOR\Test;

class DatastoreTest extends Test
{
    protected $ds;

    protected $samples = [
        # $id = md5($string)
        # $id[0] . $id[1] . '/' . $id[2] . $id[3] . '/' . $id[4] . $id[5] . '/' . {$id}
        'hello' => '5d/41/40/5d41402abc4b2a76b9719d911017c592',
        'hello-world' => '20/95/31/2095312189753de6ad47dfe20cbe97ec'
    ];

    function __construct()
    {
        $path = $this->getPath();

        if (is_file($path . "/ds.idx"))
            unlink($path . "/ds.idx");

        if (!is_dir($path))
            mkdir ($path);
    }

    function getPath()
    {
        return __DIR__ . '/ds';
    }

    function getDatastore($path = null)
    {
        if (!$path)
            $path = $this->getPath();

        return new Datastore($path);
    }

    function testDatastoreInstance()
    {
        $ds = $this->getDatastore();
        $this->assertInstanceOf(IrfanTOOR\Datastore::class, $ds);
    }

    /**
     * throws: Exception::class
     * message: path must be a directory
     */
    function testDatastorePathException()
    {
        $this->getDatastore(__DIR__ . 'abc/def/ghi');
    }

    function testGetVersion()
    {
        $ds = $this->getDatastore();
        $version = Datastore::VERSION;
        $this->assertString($version);
        $this->assertFalse(strpos($version, 'VERSION'));
        $this->assertEquals($ds::VERSION, $version);
    }

    function testGetPath()
    {
        $ds = $this->getDatastore();

        foreach ($this->samples as $k => $v) {
            $this->assertEquals($v, $ds->getPath($k));
        }
    }

    function testHas()
    {
        $ds = $this->getDatastore();
        $this->assertFalse($ds->has('hello'));
        $this->assertFalse($ds->has('hello/world'));

        $ds->set('hello', 'Some Contents');
        $this->assertTrue($ds->has('hello'));
        $this->assertFalse($ds->has('hello/world'));
    }

    function testGet()
    {
        $ds = $this->getDatastore();

        $this->assertTrue($ds->has('hello'));
        $this->assertEquals('Some Contents', $ds->get('hello'));
        $this->assertFalse($ds->has('world'));
        $this->assertEquals(null, $ds->get('world'));
    }

    /**
     * throws: TypeError::class
     */
    function testSetException()
    {
        $ds = $this->getDatastore();
        $ds->set('hello', []);
    }

    function testSet() {
        $ds = $this->getDatastore();
        $ds->set('hello', 'Hello World!');
        $this->assertEquals('Hello World!', $ds->get('hello'));

        $this->assertTrue($ds->set('hello', 'Something else'));
        $this->assertEquals('Something else', $ds->get('hello'));

        $info = $ds->info('hello');
        $this->assertArray($info);
        $this->assertEquals('5d41402abc4b2a76b9719d911017c592', $info['hash']);
        $this->assertEquals(14, $info['size']);
    }

    function testInfo()
    {
        $ds = $this->getDatastore();

        $info = $ds->info('hello-world');
        $this->assertNull($info);

        $ds->set('hello-world', 'Information');
        $info = $ds->info('hello-world');
        $this->assertArray($info);
        $this->assertEquals(md5('hello-world'), $info['hash']);
        $this->assertEquals($this->samples['hello-world'], $ds->getPath('hello-world'));
        $this->assertEquals(11, $info['size']);
        $this->assertNotNull($info['created_on']);
        $this->assertNotNull($info['updated_on']);
    }

    function testRemove()
    {
        $ds = $this->getDatastore();

        $this->assertTrue($ds->has('hello'));
        $this->assertTrue($ds->remove('hello'));
        $this->assertFalse($ds->has('hello'));
        $this->assertNull($ds->get('hello'));
        $this->assertFalse($ds->remove('hello'));

        $file = __DIR__ . '/ds' . '/' . 'ds.idx';
        $db = new Database(['type' => 'sqlite', 'file' => $file, 'table' => 'ds']);
        $r = $db->getFirst('ds');
        $this->assertArray($r);
        $this->assertEquals(md5('hello-world'), $r['hash']);

        $ds->remove('hello-world');
        $r = $db->getFirst('ds');
        $this->assertNull($r);
    }

    public function testAddFile()
    {

        $ds = $this->getDatastore();

        $key = 'datastore-test-file';
        $file = __FILE__;
        $meta = [
            'keywords' => 'datastore, test, php',
        ];
        $contents = file_get_contents($file);

        $ds->addFile($key, $file, $meta);
        $info = $ds->info($key);

        $this->assertEquals($key, $info['key']);
        $this->assertEquals(time(), $info['created_on']);
        $this->assertEquals(filectime($file), strtotime($info['meta']['created_on']));
        $this->assertEquals(filemtime($file), $info['meta']['updated_on']);

        $this->assertEquals($file, $info['meta']['file']);
        $this->assertEquals('DatastoreTest.php', $info['meta']['filename']);
        $this->assertEquals('text/x-php', $info['meta']['mime']);
        $this->assertEquals($meta['keywords'], $info['meta']['keywords']);

        $this->assertEquals($contents, $ds->get($key));
    }
}
