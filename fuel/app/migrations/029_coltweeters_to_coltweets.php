<?php

namespace Fuel\Migrations;

class Coltweeters_to_coltweets {

	public function up()
	{
		\DBUtil::create_table('collections_tweets', array(
			'collection_id' => array('constraint' => 10, 'type' => 'int', 'unsigned' => true, 'null' => 'false'),
			'tweet_id' => array('constraint' => 10, 'type' => 'bigint', 'unsigned' => true, 'null' => 'false'),
		));
		\DB::query('INSERT INTO collections_tweets (SELECT ctwtr.collection_id,tw.id FROM collections_tweeters ctwtr JOIN tweets tw ON ctwtr.tweeter_id = tw.tweeter_id ORDER BY collection_id)')->execute();
		\DBUtil::drop_table('collections_tweeters');
	}

	public function down()
	{
		\DBUtil::create_table('collections_tweeters', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'collection_id' => array('constraint' => 10, 'type' => 'int', 'unsigned' => true, 'null' => 'false'),
			'tweeter_id' => array('constraint' => 10, 'type' => 'int', 'unsigned' => true, 'null' => 'false'),
		), array('id'));
		\DB::query('INSERT INTO collections_tweeters (collection_id, tweeter_id) (SELECT DISTINCT ctw.collection_id,tw.tweeter_id FROM collections_tweets ctw JOIN tweets tw ON tw.id=ctw.tweet_id order by tweeter_id)')->execute();
		\DBUtil::drop_table('collections_tweets');
	}
}
