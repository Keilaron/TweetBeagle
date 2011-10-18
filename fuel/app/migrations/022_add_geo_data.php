<?php

namespace Fuel\Migrations;

class Add_geo_data {
	
	public function up()
	{
		\DB::query('ALTER TABLE `tweets` ADD COLUMN coord_type  VARCHAR(25)  NULL')->execute();
		\DB::query('ALTER TABLE `tweets` ADD COLUMN coord_coord VARCHAR(100) NULL')->execute();
		\DB::query('ALTER TABLE `tweets` ADD COLUMN geo_type    VARCHAR(25)  NULL')->execute();
		\DB::query('ALTER TABLE `tweets` ADD COLUMN geo_coord   VARCHAR(100) NULL')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `tweets` DROP COLUMN coord_type')->execute();
		\DB::query('ALTER TABLE `tweets` DROP COLUMN coord_coord')->execute();
		\DB::query('ALTER TABLE `tweets` DROP COLUMN geo_type')->execute();
		\DB::query('ALTER TABLE `tweets` DROP COLUMN geo_coord')->execute();
	}
}
