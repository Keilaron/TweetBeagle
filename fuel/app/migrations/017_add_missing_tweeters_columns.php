<?php

namespace Fuel\Migrations;

class Add_missing_tweeters_columns {
	
	public function up()
	{
		\DB::query('ALTER TABLE `tweeters`
			ADD COLUMN description     VARCHAR(160) NULL,
			ADD COLUMN url             VARCHAR(255) NULL,
			ADD COLUMN followers_count MEDIUMINT UNSIGNED NULL,
			ADD COLUMN friends_count   MEDIUMINT UNSIGNED NULL,
			ADD COLUMN verified        TINYINT NULL
			')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `tweeters`
			DROP COLUMN description,
			DROP COLUMN url,
			DROP COLUMN followers_count,
			DROP COLUMN friends_count,
			DROP COLUMN verified
			')->execute();
	}
}
