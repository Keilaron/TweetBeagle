<?php

namespace Fuel\Migrations;

class Fix_id_columns {
	
	public function up()
	{
		\DB::query('ALTER TABLE `accounts` CHANGE COLUMN `id` `id` INT UNSIGNED NOT NULL')->execute();
		\DB::query('ALTER TABLE `tweeters` CHANGE COLUMN `id` `id` INT UNSIGNED NOT NULL')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `accounts` CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT')->execute();
		\DB::query('ALTER TABLE `tweeters` CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT')->execute();
	}
}
