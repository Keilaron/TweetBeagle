<?php

namespace Fuel\Migrations;

class Fix_account_times {
	
	public function up()
	{
		\DB::query('ALTER TABLE `accounts` CHANGE COLUMN created_at created_at TIMESTAMP DEFAULT "0000-00-00 00:00:00" NOT NULL')->execute();
		\DB::query('ALTER TABLE `accounts` CHANGE COLUMN updated_at updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `accounts` CHANGE COLUMN updated_at updated_at TIMESTAMP DEFAULT "0000-00-00 00:00:00" NOT NULL')->execute();
		\DB::query('ALTER TABLE `accounts` CHANGE COLUMN created_at created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL')->execute();
	}
}
