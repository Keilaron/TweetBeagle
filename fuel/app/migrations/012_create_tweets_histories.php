<?php

namespace Fuel\Migrations;

class Create_tweets_histories {

	public function up()
	{
		\DBUtil::create_table('tweets_histories', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'tweet' => array('constraint' => 11, 'type' => 'int'),
			'whole_tweet' => array('type' => 'text'),

		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('tweets_histories');
	}
}