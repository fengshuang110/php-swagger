<?php 
namespace Library\Config;
class Config{
	
	
	public static function getDbConfig(){
		return require   'config/db.php';
	} 
	
	public static function getCacheConfig(){
		return require   'config/cache.php';
	}
}


?>