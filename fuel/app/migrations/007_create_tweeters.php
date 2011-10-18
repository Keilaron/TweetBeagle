<?php

namespace Fuel\Migrations;

class Create_tweeters {

	public function up()
	{
		\DBUtil::create_table('tweeters', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'screen_name' => array('constraint' => 255, 'type' => 'varchar'),
			'name' => array('constraint' => 255, 'type' => 'varchar'),
			'location' => array('constraint' => 255, 'type' => 'varchar'),
			'profile_image_url' => array('constraint' => 255, 'type' => 'varchar'),
			'lang' => array('constraint' => 2, 'type' => 'varchar'),
			'created_at' => array('type' => 'timestamp'),

		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('tweeters');
	}
}