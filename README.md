# Datastore 

Storage of an key, value pair to a filesystem

# Quick Start

Incstallation or inclusion in your project:
```sh
$ composer require irfantoor/datastore
```

Creating the Datastore:
```php
$ds = new IrfanTOOR\Datastore('/yourpath/to/datatore/');
```

## hasKey($id)
Verify if the store has an entity with the requested id. returns true or false

```php
$ds = new IrfanTOOR\Datastore('/yourpath/to/datatore/');

if ($ds->has('hello')) {
	echo $ds->getContents('hello');
}
```

## setContents($id, $contents)
sets the contents of an id:

```php
$ds->setContents('hello', 'Hello');
$ds->setContents('hello-world', 'Hello World!');
# ...
```

## getInfo($id)
You can use the function `getInfo` to retrive the information of an entity:

```php
$info = $ds->getInfo('hello-world');
print_r($info);
```

This function returns the information array of the entity as well.

## getContents($id)
returns the value associated to an id:

```php
$contents = $ds->setContents('hello', 'Hello');
echo $contents;
echo $ds->getContents('hello-world');
```

## setComposite($r)
sets the contents and other information related to an entity passed as array. Note a special  field
meta is added to add the meta tags related to stored entry.

```php
$r = [
    'key' => 'hello',
    'contents' => 'Hello World!',
    'meta' => [
        'keywords' => 'hello, world',
    ],
    'created_on' => '2019-03-18 16:13:00',
];
$ds->setComponents($r);
# ...
```

## getComposite($id)
returns the information related to a key and its contents as array:

```php
$r = $ds->getComposite('hello');
echo $r['contents'];
```

## removeContents($id)
removes an entity assosiated to the provided id:

```php
$ds->remove('hello');
```


