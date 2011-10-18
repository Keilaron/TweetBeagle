<?php

namespace Fuel\Migrations;

class Create_collections_omits {

	public function up()
	{
		\DB::query('ALTER TABLE `tags`        CHANGE COLUMN     `id`     `id` INT UNSIGNED NOT NULL AUTO_INCREMENT')->execute();
		\DB::query('ALTER TABLE `tweets_tags` CHANGE COLUMN     `id`     `id` INT UNSIGNED NOT NULL AUTO_INCREMENT')->execute();
		\DB::query('ALTER TABLE `tweets_tags` CHANGE COLUMN `tag_id` `tag_id` INT UNSIGNED NOT NULL')->execute();
		\DBUtil::create_table('collections_omits', array(
			'id' => array('constraint' => 10, 'type' => 'int', 'unsigned' => true, 'auto_increment' => true),
			'collection_id' => array('constraint' => 10, 'type' => 'int', 'unsigned' => true, 'null' => 'false'),
			'tag_id' => array('constraint' => 10, 'type' => 'int', 'unsigned' => true, 'null' => 'false'),
		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('collections_omits');
		\DB::query('ALTER TABLE `tags`        CHANGE COLUMN     `id`     `id` INT(11) NOT NULL AUTO_INCREMENT')->execute();
		\DB::query('ALTER TABLE `tweets_tags` CHANGE COLUMN     `id`     `id` INT(11) NOT NULL AUTO_INCREMENT')->execute();
		\DB::query('ALTER TABLE `tweets_tags` CHANGE COLUMN `tag_id` `tag_id` INT(11)     NULL DEFAULT NULL')->execute();
	}
}