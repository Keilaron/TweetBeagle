<?php

namespace Fuel\Migrations;

class Create_collections_tweeters {

	public function up()
	{
		\DBUtil::create_table('collections_tweeters', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'collection_id' => array('constraint' => 11, 'type' => 'int'),
			'tweeter_id' => array('constraint' => 11, 'type' => 'int'),

		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('collections_tweeters');
	}
}