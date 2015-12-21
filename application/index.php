<?php


require __DIR__.'/../Library/bootstrap.php';

$uri = $_SERVER['REQUEST_URI'];
$uriArr = explode('?',$uri);
$path = $uriArr[0];
$path = trim($path,'/');
$arr = explode('/',$path);
if($arr[0] == 'resources'){
	$className = (empty($arr[1])) ?  false : Ucfirst(strtolower($arr[1]));
	header('location: /resources?class='.$className);die;
}else{
	$className = (empty($arr[0])) ?  false : Ucfirst(strtolower($arr[0]));
	if(empty($className)){
		echo "默认路由不存在";die;
	}
	$func = str_replace('.json','',$arr[1]);
	$className = "Application\\Controller\\".$className;
	$class =  new $className;
// 	$method = Router::getRequestMethod();
	
	$result = call_user_func_array(array($class, $func),$_REQUEST);
	echo json_encode($result);
}

class Router{
	public static function getRequestMethod()
	{
		$method = $_SERVER['REQUEST_METHOD'];
		if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
			$method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
		} elseif (
				!empty(Defaults::$httpMethodOverrideProperty)
				&& isset($_REQUEST[Defaults::$httpMethodOverrideProperty])
		) {
			// support for exceptional clients who can't set the header
			$m = strtoupper($_REQUEST[Defaults::$httpMethodOverrideProperty]);
			if ($m == 'PUT' || $m == 'DELETE' ||
			$m == 'POST' || $m == 'PATCH'
					) {
				$method = $m;
			}
		}
		// support for HEAD request
		if ($method == 'HEAD') {
			$method = 'GET';
		}
		return $method;
	}
}
