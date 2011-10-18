<?php

namespace Fuel\Migrations;

class Rename_user_id_to_account_id {

	public function up()
	{
		\DB::query('ALTER TABLE `collections` CHANGE `user_id` `account_id` INT;')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `collections` CHANGE `account_id` `user_id` INT;')->execute();
	}
}