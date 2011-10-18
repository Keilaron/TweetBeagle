<?php

namespace Fuel\Migrations;

class Rename_user_id_to_tweeter_id {

	public function up()
	{
		\DB::query('ALTER TABLE `tweets` CHANGE `user_id` `tweeter_id` INT;')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `tweets` CHANGE `tweeter_id` `user_id` INT;')->execute();
	}
}