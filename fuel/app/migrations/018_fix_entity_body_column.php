<?php

namespace Fuel\Migrations;

class Fix_entity_body_column {
	
	public function up()
	{
		\DB::query('ALTER TABLE `tweets_entities` CHANGE COLUMN entity_body entity_body VARCHAR(1024)')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `tweets_entities` CHANGE COLUMN entity_body entity_body VARCHAR(255)')->execute();
	}
}
