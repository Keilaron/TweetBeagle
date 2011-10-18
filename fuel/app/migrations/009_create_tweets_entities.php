<?php

namespace Fuel\Migrations;

class Create_tweets_entities {

	public function up()
	{
		\DBUtil::create_table('tweets_entities', array(
			'id' => array('constraint' => 10, 'type' => 'int', 'unsigned' => true, 'auto_increment' => true),
			'tweet' => array('constraint' => 11, 'type' => 'int'),
			'entity_body' => array('constraint' => 255, 'type' => 'varchar'),

		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('tweets_entities');
	}
}