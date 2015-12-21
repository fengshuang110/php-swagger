<?php
$config =  [
		"apiVersion" => "1",
		"swaggerVersion" => "1.1",
		"basePath" => "http://www.swagger.com",
		'projectPath'=>__DIR__.'/Controller',
		"produces" => [
				"application/json"
			],
		"consumes" => [
			"application/json"
		],
];
return $config;

