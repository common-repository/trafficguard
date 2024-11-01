<?php
/**
 *
 * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 * https://stackoverflow.com/questions/17806301/best-way-to-autoload-classes-in-php
 * https://stackoverflow.com/questions/24720724/php-spl-autoload-register-cant-load-class-with-require-scope-when-namespace-use
 *
 */
class TrafficGuardAutoLoader{
	public function __construct() {
		spl_autoload_register(array($this, 'loader'));
	}
	
	static function loader($className) {

		$className = ltrim($className, '\\');
		
		$fileName  = '';
		$namespace = '';
		
		if ($lastNsPos = strrpos($className, '\\')) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = substr($className, $lastNsPos + 1);
			$fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
		
		// Filtering only TrafficGuard classes!
		if (strpos($fileName, "TrafficGuard") !== FALSE) {
			require $fileName;					
		}
		
	}
}