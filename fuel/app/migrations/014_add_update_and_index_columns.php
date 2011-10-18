<?php

namespace Fuel\Migrations;

class Add_update_and_index_columns {
	
	public function up()
	{
		\DB::query('ALTER TABLE `collections`
			ADD COLUMN updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER id,
			ADD COLUMN since_id INT UNSIGNED NULL AFTER id')->execute();
		\DB::query('ALTER TABLE `tweets` ADD COLUMN indexed TINYINT DEFAULT 0 AFTER id')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `collections` DROP COLUMN updated_at, DROP COLUMN since_id')->execute();
		\DB::query('ALTER TABLE `tweets` DROP COLUMN indexed')->execute();
	}
}
