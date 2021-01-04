# Fuel PHP ORM's Unit of Work Layer
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/stwarog/uow-fuel?style=for-the-badge)
![GitHub tag (latest by date)](https://img.shields.io/github/v/tag/stwarog/uow-fuel?color=%237bfc03&style=for-the-badge&label=version)

### Foreword
This package is an extension for [UOW Core](https://github.com/stwarog/uow) for Fuel PHP Framework.
At this moment it supports ORM models (with relations).

**Note it's still under tests, don't use on production yet!**

#### Zero configuration installation

```bash 
composer require stwarog/uow-fuel
```

Just find some place to initialize this package (wrap it to the singleton or use dependency injection container), 
then call:
```php 
FuelEntityManager::forge($db, $config = []);
``` 

DB must be a reference to your instance of this class (it is globally available in Fuel). [UOW Core](https://github.com/stwarog/uow) will 
use existing (and configured) object to build queries based on resolved criteria.

Take a look at [configuration guide](https://github.com/stwarog/uow#config) to change default behaviour of package.

### The problem & Goal
Fuel's ORM is convenient in use. We can use OOP syntax to handle relations, batch update etc.
Assuming we have `Todo` model with many `Lines`, let's compare result of test below:

##### Fuel's way

```php 
DB::start_transation();
try {
    $todo = Model_Todo::query()->related('lines')->where('id', 1)->get_one();
    $todo->status = 2;
    
    foreach ($todo->lines as $line) {
        $rand = rand(0, 5);
        $line->content = 'changed' . $rand;
    }
    
    $todo->save();
} catch (Exception $e) {
    DB::rollback_transation();
    throw $e;
}
DB::commit_transation();
```
but the *problem* is that the Fuel will produce for example (writing part):

```mysql 
# transaction start

UPDATE `todo_table` SET `status` = 2 WHERE `id` = 1;
UPDATE `lines_table` SET `content` = changed1 WHERE `id` = 1;
UPDATE `lines_table` SET `content` = changed2 WHERE `id` = 2;
UPDATE `lines_table` SET `content` = changed1 WHERE `id` = 3;
UPDATE `lines_table` SET `content` = changed4 WHERE `id` = 4;
...
UPDATE `lines_table` SET `content` = changed1 WHERE `id` = 150;

# transaction commit
```

In case we have much more related lines it might be a huge performance hit.

##### "Unit of Work" approach
Let's refactor code above to this plugin syntax:

```php 
$entityManager = FuelEntityManager::forge($db, $config = []);

$todo = Model_Todo::query()->related('lines')->where('id', 1)->get_one();
$todo->status = 2;

foreach ($todo->lines as $line) {
    $rand = rand(0, 5);
    $line->content = 'changed' . $rand;
}

$entityManager->save($todo);
$entityManager->flush();

# or inline $entityManager->save($todo, $flush = true)
```

Script will group changed table columns & fields and built query in more elegant way! All will be wrapped in transaction out of the box.

```mysql 
# transaction start

UPDATE `todo_table` SET `status` = 2 WHERE `id` IN (1);
UPDATE `lines_table` SET `content` = changed1 WHERE `id` IN (1,2,3,4,5,6,7,8,9...10);
UPDATE `lines_table` SET `content` = changed2 WHERE `id` IN (11,12,13,14,15,16,17,18,19...100);

# transaction commit
```

Of course these examples are trivial. In real life each model may have a lots of nested relations. Entity Manager will take care how final
query should looks like.


#### Contribution
Please take a look at core [contribution guide](https://github.com/stwarog/uow#contribution).

#### Changelog
Nothing yet.

#### License
MIT

### Change Log

##### 1.1.3 (2021-01-04)
* *398d1247* used the most recent version of uow core

##### 1.1.2 (2021-01-03)
* *a0bf93b7* used array_diff_assoc instead of array_diff due no columns were checked
