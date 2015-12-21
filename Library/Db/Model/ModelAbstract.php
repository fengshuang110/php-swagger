<?php 
namespace Library\Db\Model;

use Library\Db\Dao;
abstract class  ModelAbstract extends Dao{
	 public function fields(){
	 	;
	}
	public function arrayLevel($arr){
		$level = 1;
		foreach ($arr as $value){
			if(is_array($value)){
				 $level++;
				 break;
			}
		}
		return $level;
	}
}
?>