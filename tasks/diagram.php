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

Runtime options:
  --png       # Output png file
  --svg       # Output svg file
  
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
		$output = 'plane';
		if(\Cli::option('png', false)) {
			$output = 'png';
		}else if(\Cli::option('svg', false)){
			$output = 'svg';
		}else if(\Cli::option('html', false)){
			$output = 'html';
		}

		$plantuml = static::generate_plantuml();

		if($output == 'plane'){
			echo $plantuml;
			exit;
		}

		// tmpフォルダに出力
		$tmpfile = static::output_tmpfile($plantuml);
		if(!$tmpfile){
			\Cli::write('create failed tmpfile.', 'red');
			exit();
		}

		$filename = pathinfo($tmpfile, PATHINFO_FILENAME).'.'.$output;
		$output_dir = sys_get_temp_dir();
		$path = VENDORPATH.'bin/plantuml';
		$command = $path.' '.$tmpfile.' -t'.$output.' -o'.$output_dir;

		shell_exec($command);

		$path = $output_dir.DS.$filename;
		if(!file_exists($path)){
			\Cli::write('create failed output tmpfile.', 'red');
			exit();
		}
		readfile($path);
		exit();
	}

	/**
	 * tmp fileに出力しパスを取得
	 *
	 * @param string $str
	 * @return string|null
	 */
	protected static function output_tmpfile($str){
		$tmpfname = tempnam(sys_get_temp_dir(), "fuelphp-er-").'.puml';
		if($temp = fopen($tmpfname, 'a')){
			fwrite($temp, $str);
			fseek($temp, 0);

			fclose($temp);
			return $tmpfname;
		}
		return null;
	}

	/**
	 * PlantUML出力
	 *
	 * @return string
	 */
	protected static function generate_plantuml(){
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
					$text = (isset($val['label']) && ($val['label'] != '') ? $val['label'].' : ' : '').$name;
					$entity->add(new PUMLStr('+ '.$text.' [PK]'));
				}
				$entity->add(new PUMLStr('--'));
				foreach($properties as $name => $val){
					if(in_array($name, $primary_keys)){
						continue;
					}
					$text = (isset($val['label']) && ($val['label'] != '') ? $val['label'].' : ' : '').$name;
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
		return $plant_uml->output($uml);
	}
}