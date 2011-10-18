<?php

namespace Fuel\Migrations;

class Add_collection_name {
	
	public function up()
	{
		\DB::query('ALTER TABLE `collections` ADD COLUMN name VARCHAR(50)  NULL')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `collections` DROP COLUMN name')->execute();
	}
}
