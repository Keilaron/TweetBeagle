<?php

namespace Fuel\Migrations;

class Add_public_to_collections {

	public function up()
	{
		\DB::query("ALTER TABLE collections ADD COLUMN public TINYINT(1) NOT NULL DEFAULT 0")->execute();
	}

	public function down()
	{
		\DB::query("ALTER TABLE collections DROP COLUMN")->execute();
	}
}