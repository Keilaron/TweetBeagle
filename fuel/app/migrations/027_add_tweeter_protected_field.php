<?php

namespace Fuel\Migrations;

class Add_tweeter_protected_field {

	public function up()
	{
		\DB::query('ALTER TABLE `tweeters` ADD COLUMN `protected` BOOLEAN DEFAULT NULL')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `tweeters` DROP COLUMN `protected`')->execute();
	}
}
