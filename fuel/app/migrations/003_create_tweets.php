<?php

namespace Fuel\Migrations;

class Create_tweets {

	public function up()
	{
		\DBUtil::create_table('tweets', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'text' => array('type' => 'text'),
			'user_id' => array('constraint' => 11, 'type' => 'int'),
			'location' => array('constraint' => 255, 'type' => 'varchar'),
			'retweet' => array('type' => 'boolean'),
			'reply' => array('type' => 'boolean'),
			'created_at' => array('type' => 'timestamp'),

		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('tweets');
	}
}