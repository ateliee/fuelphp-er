<?php
/**
 * Fuelphp generate ER diagram from model.
 *
 * @package    Fuel
 * @version    1.8.2
 * @author     ateliee
 * @license    MIT License
 * @copyright  2019 ateliee.com
 * @link       https://ateliee.com
 */

namespace Fuel\Tasks;
use Ateliee\PlantUMLParser\PUMLElementList;
use Ateliee\PlantUMLParser\PUMLKeyValue;
use Ateliee\PlantUMLParser\PUMLParser;
use Ateliee\PlantUMLParser\PUMLStr;
use Ateliee\PlantUMLParser\Structure\PUMLEntity;
use Ateliee\PlantUMLParser\Structure\PUMLPackage;
use Ateliee\PlantUMLParser\Structure\PUMLRelation;
use Er\Modelmap;
use Fuel\Core\Module;
use Orm\HasMany;
use Orm\HasOne;
use Orm\ManyMany;
use Orm\Relation;

/**
 * Class Diagram
 *
 * @package Fuel\Tasks
 */
class Diagram
{
	/**
	 * Show help.
	 *
	 * Usage (from command line):
	 *
	 * php oil refine diagram
	 */
	public static function run()
	{
		static::help();
	}

	/**
	 * Show help.
	 *
	 * Usage (from command line):
	 *
	 * php oil refine diagram:help
	 */
	public static function help()
	{
		$output = <<<HELP

Description:
  Run scan model dir, generate plantUML diagram file(.puml) for model class.

Commands:
  php oil refine diagram:generate
  php oil refine diagram:help

HELP;
		\Cli::write($output);
	}

	/**
	 * generate diagram output file
	 *
	 * Usage (from command line):
	 *
	 * php oil refine diagram:generate
	 */
	public static function generate()
	{
		$output = 'file';
		if(\Cli::option('png', false)) {
			$output = 'png';
		}else if(\Cli::option('svg', false)){
			$output = 'svg';
		}

		/**
		 * @var Modelmap[] $modelmaps
		 */
		$modelmaps = array();
		$modelmaps['app'] = new Modelmap(APPPATH);
		foreach(Module::loaded() as $module => $path){
			$modelmaps[$module] = new Modelmap($path, ucfirst($module));
		}

		$uml = new PUMLElementList();
		$all_relations = array();
		foreach($modelmaps as $name => $map){
			$package = new PUMLPackage($name);

			/**
			 * @var Relation[] $all_relations
			 */
			$models = $map->get_models();
			foreach($models as $modelname){
				$entity = new PUMLEntity($modelname, $map->add_namespace($modelname));

				/**
				 * @var \Orm\Model $model
				 */
				$model = $map->add_namespace($modelname);

				$primary_keys = $model::primary_key();
				$properties = $model::properties();
				foreach($primary_keys as $name){
					$val = $properties[$name];
					$text = isset($val['label']) && ($val['label'] != '') ? $val['label'] : $name;
					$entity->add(new PUMLStr('+ '.$text.' [PK]'));
				}
				$entity->add(new PUMLStr('--'));
				foreach($properties as $name => $val){
					if(in_array($name, $primary_keys)){
						continue;
					}
					$text = isset($val['label']) && ($val['label'] != '') ? $val['label'] : $name;
					$entity->add(new PUMLStr($text));
				}

				$relations = $model::relations();
				foreach($relations as $name => $relation){
					$all_relations[] = $relation;
				}

				$package->add($entity);
			}
			$uml->add($package);
		}

		foreach($all_relations as $relation){
			$ref = null;
			if($relation instanceof HasMany) {
				$ref = new PUMLRelation($relation->model_from, '--o{', $relation->model_to);
			}else if($relation instanceof HasOne){
				$ref = new PUMLRelation($relation->model_from, '||--||', $relation->model_to);
			}else if($relation instanceof ManyMany){
				$ref = new PUMLRelation($relation->model_from, '}--{', $relation->model_to);
			}else{
				continue;
			}
			$uml->add($ref);
		}

		$plant_uml = new PUMLParser();
		echo $plant_uml->output($uml);
	}
}