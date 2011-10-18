<?php

namespace Fuel\Migrations;

class Create_collections {

	public function up()
	{
		\DBUtil::create_table('collections', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'user_id' => array('constraint' => 11, 'type' => 'int'),
			'type' => array('constraint' => 255, 'type' => 'varchar'),
			'reference' => array('constraint' => 255, 'type' => 'varchar'),

		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('collections');
	}
}