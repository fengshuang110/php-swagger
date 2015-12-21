<?php 
//namespace Doqumentor;
/**
	This file is part of Doqumentor.

    Doqumentor is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Doqumentor is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Doqumentor.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* Include parser.php to parse the phpDoc style comments into easy to 
* read HTML.
*/
require('parser.php');
/**
* Runtime documentor for PHP
* 
* Displays all functions, constants and classes available at the point
* of initialisation.  Options available allow the system to only show
* user defined functions/constants/classes or to display all available.
* Simple example usage: 
* Doqument::init()->display();
* 
* @author Murray Picton
* @copyright 2010 Murray Picton
*/
class Doqument {
	
	/**
	* Storge for object in singular pattern
	*/
	private static $instance;
	
	/**
	* Function storage array
	*/
	private $functions 	= array();
	
	/**
	* Class storage array
	*/
	private $classes 	= array();
	
	/**
	* Constant storage array
	*/
	private $constants 	= array();
	
	/**
	* Show all availabe or just user defined?
	*/
	private $showall	= false;
	
	/**
	* Use jQuery when outputting
	*/
	private $jquery		= false;
	
	/**
	* Hide the long filepath
	*/
	private $hidepath	= false;
	
	/**
	* Path to doqument.png
	*/
	private $imagePath	= "doqument.png";
	
	/**
	* Initialise all variables and get all defined assets
	*
	* Gets all declared functions, classes and constants available
	* when called.  Accepts single parameter of whether or not to
	* get only user defined assets or all assets
	* 
	* @param bool $showall true = show system & user assets, false = only user assets
	*/
	private function __construct($showall = false) {
		//Settings
		$this->showall		= $showall;
		
		//Get lists
		$functions	= get_defined_functions();
		$classes	= get_declared_classes();
		$constants	= get_defined_constants(true);
		
		/**
		* Parse functions
		*/
		$this->functions = $this->parseFunctions($functions['user']); //Get our user functions
		
		if($showall) {
			$this->functions = array_merge($this->functions, $this->parseFunctions($functions['internal'])); //Add all our system functions aswell
		}
		
		if(!$showall) {
			foreach($this->parseClasses($classes) as $class) {
				if($class->isUserDefined()) $this->classes[] = $class; //Only get user defined classes
			}
		} else {
			$this->classes = $this->parseClasses($classes); //Get all classes
		}
		if($showall) {
			foreach($constants as $constants) 
				$this->constants = array_merge($this->constants, $constants); //Flatten the mutli-dimension array of constants into a single dimension of all
		} else {
			$this->constants = $constants['user']; //Only get user defined constants
		}
		
		/**
		* Sort all my arrays into alphabetical order
		*/
		if(is_array($this->constants))
			asort($this->constants);
		usort($this->functions, array($this, 'sort'));
		usort($this->classes, array($this, 'sort'));
	}
	
	/**
	* Parse functions
	*
	* Take a list of function names and return a list of ReflectionFunction
	* objects; one for each function
	*
	* @param array $functions Array of functions to parse
	* @return array Array of ReflectionFunction objects
	*/
	public function parseFunctions($functions) {
		$functionList = array();
		foreach($functions as $func) {
			$functionList[] = new ReflectionFunction($func);
		}
		return $functionList;
	}
	
	/**
	* Parse classes
	*
	* Take a list of class names and return a list of ReflectionClass
	* objects; one for each class
	*
	* @param array $classes Array of classes to parse
	* @return array Array of ReflectionClass objects
	*/
	public function parseClasses($classes) {
		$classList = array();
		foreach($classes as $class) {
			$classList[] = new ReflectionClass($class);
		}
		return $classList;
	}
	
	/**
	* Custom sort function that sorts according to short name
	*
	* @param mixed $item1 Reflection object with method getName
	* @param mixed $item2 Reflection object with method getName
	* @return int
	*/
	public function sort($item1, $item2) {
		return strcmp($item1->getName(), $item2->getName());
	}
	
	/**
	* Format the paramaters for a function or method into an easy to
	* read HTML format
	*
	* @param array $params Array of paramaters to format
	* @return string HTML string of formatted parameters
	*/
	public function formatParameters($params) {
		$args = array();
		foreach($params as $param) {
			$arg = '';
			if($param->isPassedByReference()) {
				$arg .= '&';
			}
			if($param->isOptional()) { 
				$arg .= '[' . $param->getname();
				if($param->isDefaultValueAvailable()) {
					$arg .= ' = ';
					$default = $param->getDefaultValue();
					if(empty($default)) $arg .= '""';
					else $arg .= $default;
				}
				$arg .= ']';
			} else {
				$arg .= $param->getName();
			}
			$args[] = $arg;
		}
		return implode(', ', $args);
	}
	
	/**
	* Format a function or an array into an easy to read HTML format
	*
	* @param mixed $item ReflectionFunction or ReflectionClass object
	* @param string $type Type of item to be used as CSS class
	* @return string Formatted HTML
	*/
	public function formatItem($item, $type = 'unknown') {	
		$html  = '';
		
		$html .= "<div class=\"$type\" title=\"" . strtolower($item->getName()) . "\">" . PHP_EOL;
		$html .= "<h2>$type " . $item->getName();
		
		if(is_a($item, 'ReflectionFunction') || is_a($item, 'ReflectionMethod'))
			$html .= "(" . $this->formatParameters($item->getParameters()) . ")";
			
		$html .= "</h2>" . PHP_EOL;
		if($parser = $this->parseComment($item)) { //Get our parsed comment
			$shortDesc = $parser->getShortDesc();
			if(!empty($shortDesc)) {
				$html .= "<pre class=\"comment\">" . $shortDesc . "</pre>";
			}
			$desc = $parser->getDesc();
			if(!empty($desc)) {
				$html .= "<p class=\"comment\">Description:</p>";
				$html .= "<pre class=\"comment\">" . $desc . "</pre>";
			}
			$html .= $this->formatPHPDocParams($parser->getParams());
		}
		$filename = $this->formatFilePath($item->getFileName());
		if(!empty($filename)) {
			$html .= "<p class=\"info\"><span class=\"filename\">" . $filename. ": </span><span class=\"lines\">Lines " . $item->getStartLine() . " - " . $item->getEndLine() . "</span></p>" . PHP_EOL;
		}
		$html .= "</div>" . PHP_EOL;
		
		return $html;
	}
	
	/**
	* Get the jquery.js Javascript file and return for inline output
	*
	* @return string jquery.js formatted between <script> tags
	*/
	public function jquery() {
		//return "<script> " . file_get_contents('jquery.js', FILE_USE_INCLUDE_PATH) . "</script>";
	}
	
	/**
	* Parse the comment and return the parser object
	*
	* @param mixed $item ReflectionFunction or ReflectionClass object
	* @return Parser Parser object
	*/
	public function parseComment($item) {
		if(!$comment = $item->getDocComment()) return false;
		
		$parser = new Parser($comment);
		$parser->parse();
		return $parser;
	}
	
	/**
	* Format the PHPDoc paramaters into HTML
	* 
	* @param array $params The parameters to get
	* @return string HTML formatted parameters
	*/
	public function formatPHPDocParams($params) {
		$arr = array();
		foreach($params as $param=>$value) {
			if(empty($value)) continue;
			if($param == 'param')
				$arr = array_merge($arr, $this->formatParamArray($value));
			else
				$arr[] = "<li><em>$param:</em> " . htmlentities($value) . "</li>";
		}
		return '<ul>' . PHP_EOL . implode(PHP_EOL, $arr) . PHP_EOL . '</ul>';
	}
	
	/**
	* Format a PHPDoc paramaters
	*
	* Format a PHPDoc paramater or array of paramaters so it is easy
	* to read.
	*
	* @param mixed $params Either a string or array containing the parameter(s)
	* @return array Array of formatted parameters
	*/
	public function formatParamArray($params) {
		$arr = array();
		
		if(!is_array($params)) $params = array($params);
		
		foreach($params as $param) {
			$pos = strpos($param, ' ');
		
			$paramName = substr($param, 0, $pos);
			$arr[] = "<li><em>$paramName:</em> " . substr($param, $pos+1) . "</li>";
		}
		
		return $arr;
	}
	
	/**
	* Format a filepath according to settings
	*
	* @param string $filepath The filepath to format
	* @return string Formatted filepath
	*/
	public function formatFilePath($filepath) {
		if($this->hidefilepath)
			return '.../' . basename($filepath);
		else
			return $filepath;
	}
	
	/**
	* Initialise and return object
	*
	* Singular pattern.  Initialise object on first call and return
	* object on subsequent calls.
	*
	* @param bool $showall true = show system & user assets, false = only user assets
	*/
	public static function init($showall = false) {
		if(!isset(self::$instance))
			self::$instance = new Doqument($showall);
		
		return self::$instance;
	}
	
	/**
	* Use jQuery in output
	*
	* @return Doqument Doquement for stringing
	*/
	public function jquerify() {
		$this->jquery = true;
		return $this;
	}
	
	/**
	* Set image path for doqument.png
	*
	* @return Doqument Doquement for stringing
	*/
	public function setImagePath($path) {
		$this->imagePath = $path;
		return $this;
	}
	
	/**
	* Hide the filepath
	*
	* @return Doqument Doquement for stringing
	*/
	public function hidePath() {
		$this->hidefilepath = true;
		return $this;
	}
	
	/**
	* Display all functions in HTML format
	*
	* @return string HTML of all functions
	*/
	public function displayFunctions() {
		$html  = '';
		foreach($this->functions as $func) {
			$html .= $this->formatItem($func, 'function');
		}
		return $html;
	}
	
	/**
	* Display all classes in HTML format
	*
	* @return string HTML of all classes
	*/
	public function displayClasses() {
		$html = '';
		foreach($this->classes as $class) {
			$html .= "<div class=\"classWrapper\">" . $this->formatItem($class, 'class'); //Wrap the class in a div
			$methods = $class->getMethods();
			usort($methods, array($this, 'sort'));
			$html .= "<div class=\"methods\">" . PHP_EOL; //Wrap all the methods in a div to show/hide
			foreach($methods as $method) {
				$html .= $this->formatItem($method, 'method');
			}
			$html .= "</div></div>" . PHP_EOL;
		}
		return $html;
	}
	
	/**
	* Display all constants in HTML format
	*
	* @return string HTML of all constants
	*/
	public function displayConstants() {
		$html = '';
		if(!is_array($this->constants)) return false;
		foreach($this->constants as $const => $val) {
			$html .= "<div class=\"constant\" title=\"" . strtolower($const) . "\">" . PHP_EOL;
			$html .= "<h2>const " . $const . " = " . $val . "</h2>" . PHP_EOL;
			$html .= "</div>" . PHP_EOL;
		}
		return $html;
	}
	
	/**
	* Get and return the formatted HTML for the document
	*
	* @return string HTML of formatted document
	*/
	public function get() {
		$html  = '<div id="doqument">';
		if($this->jquery) {
			$html .= 'Search: <input type="text" class="search" onkeyup="search(this.value);">';
		}
		$html .= $this->displayFunctions();
		$html .= $this->displayClasses();
		if($consts = $this->displayConstants()) $html .= $consts;
		
		$html .= "</div>";
		return $html;
	}
	
	/**
	* Echo formatted HTML of the document
	*/
	public function display() {
		echo $this->get();
		if($this->jquery) {
			echo "<div style=\"position: fixed; bottom: 0px; right: 0px\"><a href=\"#\" onclick=\"$('#doqument').dialog('open'); return false;\"><img src=\"" . $this->imagePath . "\" border=\"0\" /></a></div>";
			echo $this->jquery();
		}
	}
}
?>