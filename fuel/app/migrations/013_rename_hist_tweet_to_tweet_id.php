<?php

namespace Fuel\Migrations;

class Rename_hist_tweet_to_tweet_id {
	
	public function up()
	{
		\DB::query('ALTER TABLE `tweets_histories` CHANGE COLUMN tweet tweet_id INT(11)')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `tweets_histories` CHANGE COLUMN tweet_id tweet INT(11)')->execute();
	}
}

