<?php

use IrfanTOOR\Database;
use IrfanTOOR\Datastore;
use IrfanTOOR\Datastore\Constants;
use IrfanTOOR\Datastore\DatastoreIndex;
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

    function getDatastore($path = null, $adapter = null)
    {
        if ($path) {
            return new Datastore(['path' => $path]);
        }

        if (!$this->ds) {
            $path = __DIR__ . '/ds';

            if (!is_dir($path)) {
                mkdir ($path);
            }

            $this->ds = new Datastore(['path' => $path]);
        }

        return $this->ds;
    }

    function testDatastoreInstance()
    {
        # if dir does not exists
        $this->assertException(function(){
            $this->getDatastore(__DIR__ . 'abc/def/ghi');
        });

        $ds = $this->getDatastore();
        $this->assertInstanceOf(IrfanTOOR\Datastore::class, $ds);
        $this->assertInstanceOf(IrfanTOOR\Database\Model::class, $ds);
    }

    function testGetVersion()
    {
        $ds = $this->getDatastore();
        $version = $ds->getVersion();

        $c = new \IrfanTOOR\Console();
        $c->write('(' . $version . ') ', 'dark');

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
        $this->assertFalse($ds->hasKey('hello'));
        $this->assertFalse($ds->hasKey('hello/world'));

        $ds->setContents('hello', 'Some Contents');
        $this->assertTrue($ds->hasKey('hello'));
        $this->assertFalse($ds->hasKey('hello/world'));
    }

    function testGet()
    {
        $ds = $this->getDatastore();

        $this->assertTrue($ds->hasKey('hello'));
        $this->assertEquals('Some Contents', $ds->getContents('hello'));

        $this->assertFalse($ds->hasKey('world'));
        $this->assertEquals(null, $ds->getContents('world'));
    }

    function testSet()
    {
        $ds = $this->getDatastore();

        $this->assertException(
            function() use($ds){
                $ds->setContents('hello', []);
            },
            Exception::class,
            'value must be a string'
        );

        $ds->setContents('hello', 'Hello World!');
        $this->assertEquals('Hello World!', $ds->getContents('hello'));

        $this->assertTrue($ds->setContents('hello', 'Something else'));
        $this->assertEquals('Something else', $ds->getContents('hello'));

        $info = $ds->getInfo('hello');
        $this->assertArray($info);
        $this->assertEquals('5d41402abc4b2a76b9719d911017c592', $info['hash']);
        $this->assertEquals(14, $info['size']);
    }

    function testGetInfo()
    {
        $ds = $this->getDatastore();

        $r = $ds->getInfo('hello-world');
        $this->assertNull($r);

        $ds->setContents('hello-world', 'Information');
        $r = $ds->getInfo('hello-world');
        $this->assertArray($r);
        $this->assertEquals($ds->hash('hello-world'), $r['hash']);
        $this->assertEquals($this->samples['hello-world'], $ds->hashToPath($r['hash']));
        $this->assertEquals(11, $r['size']);
        $this->assertNotNull($r['created_on']);
        $this->assertNotNull($r['updated_on']);
    }

    function testRemove()
    {
        $ds = $this->getDatastore();
        $this->assertTrue($ds->hasKey('hello'));
        $this->assertTrue($ds->removeContents('hello'));
        $this->assertFalse($ds->hasKey('hello'));
        $this->assertNull($ds->getContents('hello'));
        $this->assertFalse($ds->removeContents('hello'));

        $file = __DIR__ . '/ds' . '/' . '.datastore.sqlite';
        $db = new Database(['file' => $file]);
        $r = $db->getFirst(['table' => 'datastoreindex']);
        $this->assertArray($r);
        $this->assertEquals(md5('hello-world'), $r['hash']);

        $ds->removeContents('hello-world');
        $r = $db->getFirst(['table' => 'datastoreindex']);
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
        $info = $ds->getInfo($key);

        $this->assertEquals($key, $info['key']);
        $this->assertEquals(date('Y-m-d H:i:s', filectime($file)), $info['created_on']);
        $this->assertEquals(filemtime($file), $info['updated_on']);

        $this->assertEquals($file, $info['meta']['file']);
        $this->assertEquals('DatastoreTest.php', $info['meta']['filename']);
        $this->assertEquals('text/x-php', $info['meta']['mime']);
        $this->assertEquals($meta['keywords'], $info['meta']['keywords']);

        $this->assertEquals($contents, $ds->getContents($key));
    }

    public function testGetComposit()
    {
        $ds = $this->getDatastore();
        $file = __FILE__;
        $key = 'datastore-test-file';

        $contents = file_get_contents($file);
        $composit = $ds->getComposit($key);
        $info = $ds->getInfo($key);

        $this->assertEquals($contents, $composit['contents']);

        foreach ($info as $k=>$v) {
            if (is_int($k))
                continue;

            $this->assertEquals($composit[$k], $v);
        }
    }

    public function testSetComposit()
    {
        $ds = $this->getDatastore();
        $file = __FILE__;
        $contents = file_get_contents($file);

        $ds->removeContents($file);
        $c = [
            'key' => $file,
            'contents' => $contents,
            'meta' => [
                'hello' => 'world',
            ],
            'created_on' => date('Y-m-d H:i:s'),
            'updated_on' => 1,
        ];

        $ds->setComposite($c);

        $info = $ds->getInfo($file);
        $info['contents'] = $ds->getContents($info['key']);

        foreach ($c as $k=>$v) {
            if (is_int($k))
                continue;

            $this->assertEquals($c[$k], $info[$k]);
        }
    }

    function testSpeed()
    {
        $c = $this->console;
        $ds = $this->getDatastore();
        $t['start'] = microtime(1);

        for ($i = 0; $i < 100; $i++) {
            $ds->setComposite(
                [
                    'key' => "$i",
                    'meta' => [
                        'parity' => $i % 2,
                    ],
                    'contents' => "value:$i"
                ]
            );
        }

        $t['write'] = microtime(1);

        $v = [];
        for ($i = 0; $i < 100; $i++) {
            $v[$i] = $ds->getComposit("$i");
        }

        $t['read'] = microtime(1);

        for ($i = 0; $i < 100; $i++) {
            $this->assertEquals($v[$i]['contents'], "value:$i");
        }

        $c->write(
            sprintf(
                " (writing %2.4f sec, reading %2.4f sec)",
                ($t['write'] - $t['start']),
                ($t['read'] - $t['write'])
            ),
            "dark"
        );
    }

    function testRemoveTheTemporaryDatastore()
    {
        $dir  = __DIR__ . '/ds';
        $file = $dir . '/' . '.datastore.sqlite';

        if (file_exists($file)) {
            unlink($file);
        }

        if (is_dir($dir)) {
            system('rm -r ' . $dir, $result);
            $this->assertEquals(0, $result);
        }
    }
}
