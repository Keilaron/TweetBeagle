<?php

namespace Fuel\Migrations;

class Add_indexes {

	public function up()
	{
		\DB::query('ALTER TABLE collections_tweets ADD INDEX tweet_id (tweet_id)')->execute();
		\DB::query('ALTER TABLE collections_tweets ADD INDEX collection_id (collection_id)')->execute();
		\DB::query('ALTER TABLE tags               ADD INDEX type (type)')->execute();
		\DB::query('ALTER TABLE tweets             ADD INDEX created_at(created_at)')->execute();
		\DB::query('ALTER TABLE tweets_tags        ADD INDEX tweet_id(tweet_id)')->execute();
		\DB::query('ALTER TABLE tweets_tags        ADD INDEX tag_id(tag_id)')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE collections_tweets DROP INDEX tweet_id')->execute();
		\DB::query('ALTER TABLE collections_tweets DROP INDEX collection_id')->execute();
		\DB::query('ALTER TABLE tags               DROP INDEX type')->execute();
		\DB::query('ALTER TABLE tweets             DROP INDEX created_at')->execute();
		\DB::query('ALTER TABLE tweets_tags        DROP INDEX tweet_id')->execute();
		\DB::query('ALTER TABLE tweets_tags        DROP INDEX tag_id')->execute();
	}
}
