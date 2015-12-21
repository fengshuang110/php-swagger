<?php 

require __DIR__.'/../../Library/bootstrap.php';
$config = require_once '/../config.php';
$className = empty($_GET['class']) ? false : ucfirst($_GET['class']) ;
$files = scandir($config['projectPath']);

$operation = [
	'httpMethod'=> "GET",
	"nickname"=>"region",
	"responseClass"=>"Array",
	"summary"=>"一点O2O&nbsp; <i class=\"icon-unlock-alt icon-large\"></i>",
	"errorResponses"=>[]
	];
unset($config['projectPath']);
$objs = array();
header('Content-type:application/json');
foreach ($files as $file){
	$fileinfo = pathinfo($file);
	if(strtolower($fileinfo['extension']) == "php"){
		$class = "Application\Controller\\".$fileinfo['filename'];
		$objs[$fileinfo['filename']] = $class;
	}
}

if(empty($className)){
	foreach ($objs as $className){
		$class = new \ReflectionClass($className);
		$json['path'] = strtolower("/resources/".$class->getShortName().".{format}");
		$json['description'] = "";
		$config['apis'][] = $json;
	}
	echo json_encode($config,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT |JSON_UNESCAPED_SLASHES);
}else{
	if(array_key_exists($className, $objs)){
		$class = new \ReflectionClass($objs[$className]);
		$methods = $class->getMethods();
		$DocParser = new DocParser();
		foreach ($methods as $method){
			//公共方法才生成文档
			if($method->isPublic()){
				$info = $DocParser->parse($method->getDocComment());
				$json = array();
				$json['path'] = strtolower('/'.$class->getShortName().'/'.$method->getName().".{format}"); 
				$json['description'] = $info['description'];
				$operation['nickname'] = $method->getName();
				$operation['summary'] =  $info['description'];
				foreach ($method->getParameters() as $key => $parameter){
					$temp = explode('$'.$parameter->getName(), $info['param'][$key]);
					$dataType = str_replace('(','', $temp[0]);
					$dataType = str_replace(')','', $dataType);
					$params= [
							"name"=>$parameter->getName(),
							"description"=>$info['param'][$key],
							"paramType"=>"query",
							"required"=>true,
							"defaultValue"=>0,
							"allowMultiple"=>false,
							"dataType"=>$dataType
						];
					try{
						if($parameter->isOptional()){
							$params['required'] = false;
						}
					}catch (\Exception $e){
						;
					}
					if($parameter->isDefaultValueAvailable()){
						$params['required'] = false;
						$params["defaultValue"] = $parameter->getDefaultValue();
					}
					$operation['parameters'][] = $params;
				}
			
				$json['operations'][] = $operation;
				$config['apis'][] = $json;
			}
		}
		$config['resourcePath'] = strtolower("/".$class->getShortName());
		$config['models'] = '';
		echo json_encode($config,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT |JSON_UNESCAPED_SLASHES );
		die;
	}else{
		echo '404';
	}
	
}

/**
 * Parses the PHPDoc comments for metadata. Inspired by Documentor code base
* @category   Framework
* @package    restler
* @subpackage helper
* @author     Murray Picton <info@murraypicton.com>
* @author     R.Arul Kumaran <arul@luracast.com>
* @copyright  2010 Luracast
* @license    http://www.gnu.org/licenses/ GNU General Public License
* @link       https://github.com/murraypicton/Doqumentor
*/
class DocParser {
	private $params = array ();
	function parse($doc = '') {
		if ($doc == '') {
			return $this->params;
		}
		// Get the comment
		if (preg_match ( '#^/\*\*(.*)\*/#s', $doc, $comment ) === false)
			return $this->params;
		$comment = trim ( $comment [1] );
		// Get all the lines and strip the * from the first character
		if (preg_match_all ( '#^\s*\*(.*)#m', $comment, $lines ) === false)
			return $this->params;
		$this->parseLines ( $lines [1] );
		return $this->params;
	}
	private function parseLines($lines) {
		foreach ( $lines as $line ) {
			$parsedLine = $this->parseLine ( $line ); // Parse the line

			if ($parsedLine === false && ! isset ( $this->params ['description'] )) {
				if (isset ( $desc )) {
					// Store the first line in the short description
					$this->params ['description'] = implode ( PHP_EOL, $desc );
				}
				$desc = array ();
			} elseif ($parsedLine !== false) {
				$desc [] = $parsedLine; // Store the line in the long description
			}
		}
		$desc = implode ( ' ', $desc );
		if (! empty ( $desc ))
			$this->params ['long_description'] = $desc;
	}
	private function parseLine($line) {
		// trim the whitespace from the line
		$line = trim ( $line );

		if (empty ( $line ))
			return false; // Empty line

			if (strpos ( $line, '@' ) === 0) {
				if (strpos ( $line, ' ' ) > 0) {
					// Get the parameter name
					$param = substr ( $line, 1, strpos ( $line, ' ' ) - 1 );
					$value = substr ( $line, strlen ( $param ) + 2 ); // Get the value
				} else {
					$param = substr ( $line, 1 );
					$value = '';
				}
				// Parse the line and return false if the parameter is valid
				if ($this->setParam ( $param, $value ))
					return false;
			}

			return $line;
	}
	private function setParam($param, $value) {
		if ($param == 'param' || $param == 'return')
			$value = $this->formatParamOrReturn ( $value );
		if ($param == 'class')
			list ( $param, $value ) = $this->formatClass ( $value );

		if (empty ( $this->params [$param] )) {
			$this->params [$param][] = $value;
		} else {
			$this->params [$param][] = $value;
		}
		return true;
	}
	private function formatClass($value) {
		$r = preg_split ( "[|]", $value );
		if (is_array ( $r )) {
			$param = $r [0];
			parse_str ( $r [1], $value );
			foreach ( $value as $key => $val ) {
				$val = explode ( ',', $val );
				if (count ( $val ) > 1)
					$value [$key] = $val;
			}
		} else {
			$param = 'Unknown';
		}
		return array (
				$param,
				$value
		);
	}
	private function formatParamOrReturn($string) {
		$pos = strpos ( $string, ' ' );

		$type = substr ( $string, 0, $pos );
		return '(' . $type . ')' . substr ( $string, $pos + 1 );
	}
}

?>