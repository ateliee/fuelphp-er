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

namespace Er;

/**
 * Class Modelmap
 * @package Er
 */
class Modelmap
{
	/**
	 * @var string 基準のパス
	 */
	protected $base_path;

	/**
	 * @var string Namespace
	 */
	protected $namespace;

	/**
	 * @var
	 */
	protected $models;

	/**
	 * Modelmap constructor.
	 * @param string $base_path
	 * @param string|null $namespace
	 */
	public function __construct($base_path, $namespace = null)
	{
		$this->base_path = $base_path;
		$this->namespace = $namespace;

		$this->models = $this->scan_models($base_path.'classes', 'model');
	}

	/**
	 * @param string $class
	 * @return string
	 */
	public function add_namespace($class)
	{
		return ($this->namespace ? $this->namespace.'\\' : '').$class;
	}

	/**
	 * @return string
	 */
	public function get_namespace()
	{
		return $this->namespace;
	}

	/**
	 * get model classname
	 *
	 * @return string[]
	 */
	public function get_models(){
		$model = array();
		foreach($this->models as $name => $path){
			$model[] = $name;
		}
		return $model;
	}

	/**
	 * scan class to directory
	 *
	 * @param string $base_path
	 * @param string $dir
	 * @param array $models
	 * @return array
	 */
	protected function scan_models($base_path, $dir, $models = array())
	{
		$cdir = scandir($base_path.DS.$dir);
		foreach ($cdir as $key => $value)
		{
			if (in_array($value, array(".", ".."))) {
				continue;
			}
			$p = $dir.DS.$value;
			if (is_dir($base_path.DS.$p))
			{
				$models = $this->scan_models($base_path, $p, $models);
				continue;
			}
			if(!preg_match('/^.+\.php/', $value)){
				continue;
			}
			$models[static::path_to_class($p)] = $p;
		}
		return $models;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected static function path_to_class($path){
		$class = preg_replace('/^(.+)\.php/', '$1', $path);
		return implode('_', array_map('ucfirst', explode(DS, $class)));
	}
}