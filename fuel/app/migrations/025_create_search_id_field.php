<?php

namespace Fuel\Migrations;

class Create_search_id_field {

	public function up()
	{
		\DB::query('ALTER TABLE `tweeters` ADD COLUMN `search_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id`')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `tweeters` DROP COLUMN `search_id`')->execute();
	}
}
