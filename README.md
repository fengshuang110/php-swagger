# php-swagger
php 通过json 生成api文档
 前端使用swagger UI
 后端通过php扫描api controller 文件 对class使用发射实现

##说明
confi文件定义

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

##ngnix
	 server {
        listen     8999;
        server_name _;
        root  /alidata/www/php-swagger/application;
        index index.php index.html;
        location / {
            try_files $uri $uri/ /index.php?$args;
        }
        rewrite ^/resources/(.*)\.json?$ /resources/index.php?class=$1 last;

        location ~ .*\.(php|php5)?$
        {
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_index  index.php;
            fastcgi_param  SCRIPT_FILENAME  $document_root/$fastcgi_script_name;
            include        fastcgi_params;
        }
         access_log  /alidata/log/nginx/access/php-swagger.log;
    }

在线demo
[http://121.41.117.10:8999](http://121.41.117.10:8999 "在线demo")
