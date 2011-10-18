<?php

namespace Fuel\Migrations;

class Create_tags {

	public function up()
	{
		\DBUtil::create_table('tags', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'content' => array('constraint' => 255, 'type' => 'varchar'),
			'type' => array('constraint' => 255, 'type' => 'varchar'),

		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('tags');
	}
}