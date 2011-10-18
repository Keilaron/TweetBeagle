<?php

namespace Fuel\Migrations;

class Create_accounts {

	public function up()
	{
		\DBUtil::create_table('accounts', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'oauth_key' => array('constraint' => 255, 'type' => 'varchar'),
			'oauth_secret' => array('constraint' => 255, 'type' => 'varchar'),
			'created_at' => array('type' => 'timestamp'),
			'updated_at' => array('type' => 'timestamp'),

		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('accounts');
	}
}