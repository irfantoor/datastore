<?php

use IrfanTOOR\Datastore;
use IrfanTOOR\Test;

class DatastoreTest extends Test
{
    protected $samples = [
        # $id = md5($string)
        # $id[0] . $id[1] . '/' . $id[2] . $id[3] . '/' . $id[4] . $id[5] . '/' . {$id}
        'hello' => '5d/41/40/5d41402abc4b2a76b9719d911017c592',
        'hello-world' => '20/95/31/2095312189753de6ad47dfe20cbe97ec'
    ];

    public function testCreateATemporaryDatastore()
    {
        system('mkdir ' . __DIR__ . '/ds', $result);
        $this->assertEquals(0, $result);
    }

    public function testDatastoreInstance()
    {
        $ds = new Datastore(__DIR__ . '/ds/');        
        $this->assertInstanceOf(IrfanTOOR\Datastore::class, $ds);
    }

    public function testGetPath()
    {
        $ds = new Datastore(__DIR__ . '/ds/');

        foreach ($this->samples as $k=>$v) {
            $this->assertEquals($v, $ds->getPath($k));
        }
    }

    public function testHas()
    {
        $ds = new Datastore(__DIR__ . '/ds/');
        $this->assertFalse($ds->has('hello'));
        $this->assertFalse($ds->has('hello/world'));

        $ds->setContents('hello', 'Some Contents');
        $this->assertTrue($ds->has('hello'));
        $this->assertFalse($ds->has('hello/world'));
    }

    public function testGetContents()
    {
        $ds = new Datastore(__DIR__ . '/ds/');

        $this->assertTrue($ds->has('hello'));
        $this->assertEquals('Some Contents', $ds->getContents('hello'));

        $this->assertFalse($ds->has('world'));
        $this->assertEquals('', $ds->getContents('world'));
    }

    public function testSetContents()
    {
        $ds = new Datastore(__DIR__ . '/ds/');

        $e = $msg = null;
        try {
            $ds->setContents('hello', []);
        } catch(\Exception $e) {
            $msg = $e->getMessage();
        }

        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertEquals('contents must be a string', $msg);

        $ds->setContents('hello', 'Hello World!');
        $this->assertEquals('Hello World!', $ds->getContents('hello'));
        $return = $ds->setContents('hello', 'Something else');
        $this->assertTrue(is_array($return));
        $info = $ds->getInfo('hello');
        $this->assertEquals($return, $info);
        $this->assertEquals('Something else', $ds->getContents('hello'));
    }

    public function testDelete()
    {
        $ds = new Datastore(__DIR__ . '/ds/');

        $this->assertTrue($ds->has('hello'));

        $ds->delete('hello');
        $this->assertFalse($ds->has('hello'));
        $this->assertEquals('', $ds->getContents('hello'));
    }

    public function testGetInfo()
    {
        $ds = new Datastore(__DIR__ . '/ds/');

        $r = $ds->getInfo('hello-world');
        $this->assertNull($r);

        $ds->setContents('hello-world', 'Information');
        $r = $ds->getInfo('hello-world');
        $this->assertNotNull($r);
        $this->assertTrue(isset($r['id']));
        $this->assertTrue(isset($r['path']));
        $this->assertTrue(isset($r['size']));
        $this->assertTrue(isset($r['created_on']));
        $this->assertTrue(isset($r['updated_on']));
    }

    public function testRemoveTheTemporaryDatastore()
    {
        $dir  = __DIR__ . '/ds';
        $file = $dir . '/' . '.datastore.sqlite';

        if (file_exists($file)) {
            unlink($file);
        }

        system('rm -r ' . $dir, $result);

        $this->assertEquals(0, $result);
    }   
}
