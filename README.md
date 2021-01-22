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

## has($id)

Verify if the store has an entity with the requested id. returns true or false

```php
$ds = new IrfanTOOR\Datastore('/yourpath/to/datatore/');

if ($ds->has('hello')) {
	echo $ds->get('hello');
}
```

## set($id, $value, $meta = [])

Sets the value of an id:

```php
$ds->set('hello', 'Hello');
$ds->set('hello-world', 'Hello World!');
# ...
```

Any other information regarding can be stored using the 3rd argument $meta.

```php
$meta = [
    'meta' => [
        'keywords' => 'hello, world',
        'author'   => 'Jhon Doe',
        # ...
    ];
];

$ds->setComponents('hello', 'Hello World!', $meta);
```

## info($id)

You can use the function `info` to retrive the information of an entity:

```php
$info = $ds->info('hello-world');
print_r($info);

# Note: the information does not contain the value, which can be retrieved using
# the get function
```

## get($id)

Returns the value associated to an id:

```php
$contents = $ds->get('hello', 'Hello');
echo $contents;
echo $ds->get('hello-world');
```

## remove($id)

Removes an entity assosiated to the provided id:

```php
$ds->remove('hello');
```

## addFile($key, $file, $meta = [])

You can add a file to the datastore, using this function.

```php
$file = 'absolute\path\to\your\reference_file.txt';

$ds->addFile('reference', $file);

# or you can add some meta information
$ds->addFile('reference', $file, ['keywords' => 'reference', 'sites', 'index', '...']);
```
