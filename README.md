# Datastore 

Storage of an entity [id => contents] to a filesystem

# Quick Start

Incstallation or inclusion in your project:
```sh
$ composer require irfantoor/datastore
```

Creating the Datastore:
```php
$ds = new IrfanTOOR\Datastore('/yourpath/to/datatore/');
```

## has($id)
Verify if the store has an entity with the requested id. returns true or false

```php
$ds = new IrfanTOOR\Datastore('/yourpath/to/datatore/');
```

## setContents($id, $contents)
sets the contents of an id:

```php
$ds->setContents('hello', 'Hello');
$ds->setContents('hello-world', 'Hello World!');
# ...
```

This function returns the information array of the entity as well.

## getContents($id)
returns the value associated to an id:

```php
$contents = $ds->setContents('hello', 'Hello');
echo $contents;
echo $ds->getContents('hello-world');
```

## delete($id)
deletes an entity assosiated to the provided id:

```php
$ds->delete('hello');
```

## getInfo($id)
You can use the function `getInfo` to retrive the information of an entity:

```php
$info = $ds->getInfo('hello-world');
print_r($info);
```

