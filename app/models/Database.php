<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database {
	function __construct() {
		$capsule = new Capsule;
		$capsule->addConnection([
		    "driver" => env('DBDRIVER'),
		    "host" => env('DBHOST'),
		    "database" => env('DBNAME'),
		    "username" => env('DBUSER'),
		    "password" => env('DBPASS'),
		    "charset" => "utf8",
		    "collation" => "utf8_unicode_ci",
		    "prefix" => "",
		]);

		$capsule->setAsGlobal();
		$capsule->bootEloquent();
	}
}