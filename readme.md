# Fuel PHP ORM's Unit of Work Layer
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/stwarog/uow-fuel?style=for-the-badge)
![GitHub tag (latest by date)](https://img.shields.io/github/v/tag/stwarog/uow-fuel?color=%237bfc03&style=for-the-badge)
### Foreword
This package is an extension for [UOW Core](https://github.com/stwarog/uow). Allows to build
queries with limited amount of redudant queries (Fuel's ORM like to call DB too much).

#### Instalation

```bash 
composer require stwarog/uow-fuel
```

Just find some place to initialize this package (wrap it to the singleton or use dependency injection container), 
then call:
```php 
FuelEntityManager::forge($db, $config = []);
``` 

DB must be a reference to your instance of this class (it is globally available in Fuel). There is nothing more to
do. Take a look at core [config](https://github.com/uow#config) to change behaviour.

#### License
MIT
