# Fuelphp generate ER diagram

Fuelphp package auto generation ER diagram from model setting.

## usage
* [PlantUML](http://plantuml.com/ja/)


## install
```
composer require ateliee/fuelphp-er
```

require config.php insert here

```
 'packages' => array(
    'orm',
    'er'
 ),
```

## usage
```
# plan text
php oil refine diagram:generate > er.puml
```