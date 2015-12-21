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
* PHPDoc parser for use in Doqumentor
*
* Simple example usage: 
* $a = new Parser($string); 
* $a->parse();
* 
* @author Murray Picton
* @copyright 2010 Murray Picton
*/
class Parser {
	
	/**
	* The string that we want to parse
	*/
	private $string;
	/**
	* Storge for the short description
	*/
	private $shortDesc;
	/**
	* Storge for the long description
	*/
	private $longDesc;
	/**
	* Storge for all the PHPDoc parameters
	*/
	private $params;
	
	/**
	* Parse each line
	*
	* Takes an array containing all the lines in the string and stores
	* the parsed information in the object properties
	* 
	* @param array $lines An array of strings to be parsed
	*/
	private function parseLines($lines) {
		foreach($lines as $line) {
			$parsedLine = $this->parseLine($line); //Parse the line
			
			if($parsedLine === false && empty($this->shortDesc)) {
				$this->shortDesc = implode(PHP_EOL, $desc); //Store the first line in the short description
				$desc = array();
			} elseif($parsedLine !== false) {
				$desc[] = $parsedLine; //Store the line in the long description
			}
		}
		$this->longDesc = implode(PHP_EOL, $desc);
	}
	/**
	* Parse the line
	*
	* Takes a string and parses it as a PHPDoc comment
	* 
	* @param string $line The line to be parsed
	* @return mixed False if the line contains no parameters or paramaters
	* that aren't valid otherwise, the line that was passed in.
	*/
	private function parseLine($line) {
		
		//Trim the whitespace from the line
		$line = trim($line);
		
		if(empty($line)) return false; //Empty line
		
		if(strpos($line, '@') === 0) {
			$param = substr($line, 1, strpos($line, ' ') - 1); //Get the parameter name
			$value = substr($line, strlen($param) + 2); //Get the value
			if($this->setParam($param, $value)) return false; //Parse the line and return false if the parameter is valid
		}
		
		return $line;
	}
	/**
	* Setup the valid parameters
	* 
	* @param string $type NOT USED
	*/
	private function setupParams($type = "") {
		$params = array(
			"access"	=>	'',
			"author"	=>	'',
			"copyright"	=>	'',
			"deprecated"=>	'',
			"example"	=>	'',
			"ignore"	=>	'',
			"internal"	=>	'',
			"link"		=>	'',
			"param"		=>	'',
			"return"	=> 	'',
			"see"		=>	'',
			"since"		=>	'',
			"tutorial"	=>	'',
			"version"	=>	''
		);
		
		$this->params = $params;
	}
	
	/**
	* Parse a parameter or string to display in simple typecast display
	*
	* @param string $string The string to parse
	* @return string Formatted string wiht typecast
	*/
	private function formatParamOrReturn($string) {
		
		$pos = strpos($string, ' ');
		
		$type = substr($string, 0, $pos);
		return '(' . $type . ')' . substr($string, $pos+1);
	}
	
	/**
	* Set a parameter
	* 
	* @param string $param The parameter name to store
	* @param string $value The value to set
	* @return bool True = the parameter has been set, false = the parameter was invalid
	*/
	private function setParam($param, $value) {
		if(!array_key_exists($param, $this->params)) return false;
		
		if($param == 'param' || $param == 'return') $value = $this->formatParamOrReturn($value);
		
		if(empty($this->params[$param])) {
			$this->params[$param] = $value;
		} else {
			$arr = array($this->params[$param], $value);
			$this->params[$param] = $arr;
		}
		return true;
	}
	/**
	* Setup the initial object
	* 
	* @param string $string The string we want to parse
	*/
	public function __construct($string) {
		$this->string = $string;
		$this->setupParams();
	}
	/**
	* Parse the string
	*/
	public function parse() {
		//Get the comment
		if(preg_match('#^/\*\*(.*)\*/#s', $this->string, $comment) === false)
			die("Error");
			
		$comment = trim($comment[1]);
		
		//Get all the lines and strip the * from the first character
		if(preg_match_all('#^\s*\*(.*)#m', $comment, $lines) === false)
			die('Error');
		
		$this->parseLines($lines[1]);
	}
	/**
	* Get the short description
	*
	* @return string The short description
	*/
	public function getShortDesc() {
		return $this->shortDesc;
	}
	/**
	* Get the long description
	*
	* @return string The long description
	*/
	public function getDesc() {
		return $this->longDesc;
	}
	/**
	* Get the parameters
	*
	* @return array The parameters
	*/
	public function getParams() {
		return $this->params;
	}
}
?>