<?php

namespace Fuel\Migrations;

class Create_tweets_tags {

	public function up()
	{
		\DBUtil::create_table('tweets_tags', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'tweet_id' => array('constraint' => 11, 'type' => 'int'),
			'tag_id' => array('constraint' => 11, 'type' => 'int'),
			'weight' => array('type' => 'float'),

		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('tweets_tags');
	}
}