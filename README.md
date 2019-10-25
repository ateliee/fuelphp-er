# Fuelphp generate ER diagram

このパッケージはFuelphpのモデルから自動でER図を生成するパッケージです。

全モジュールも含め、PlantUMLにて出力されるので設計書を作る作業がなくなります。

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
    'fuelphp-er'
 ),
```

## usage
```
# plan text
php oil refine diagram:generate > er.puml
# pattern file type
php oil refine diagram:generate --png > er.png
php oil refine diagram:generate --svg > er.svg
```