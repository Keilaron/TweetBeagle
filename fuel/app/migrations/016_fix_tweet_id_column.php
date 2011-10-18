<?php

namespace Fuel\Migrations;

class Fix_tweet_id_column {
	
	public function up()
	{
		\DB::query('ALTER TABLE `tweets` CHANGE COLUMN `id` `id` BIGINT UNSIGNED NOT NULL')->execute();
		\DB::query('ALTER TABLE `tweets_entities`  CHANGE COLUMN `tweet_id` `tweet_id` BIGINT UNSIGNED NOT NULL')->execute();
		\DB::query('ALTER TABLE `tweets_histories` CHANGE COLUMN `tweet_id` `tweet_id` BIGINT UNSIGNED NOT NULL')->execute();
		\DB::query('ALTER TABLE `tweets_tags`      CHANGE COLUMN `tweet_id` `tweet_id` BIGINT UNSIGNED NOT NULL')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `tweets`           CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT')->execute();
		\DB::query('ALTER TABLE `tweets_entities`  CHANGE COLUMN `tweet_id` `tweet_id` INT(11) NULL')->execute();
		\DB::query('ALTER TABLE `tweets_histories` CHANGE COLUMN `tweet_id` `tweet_id` INT(11) NULL')->execute();
		\DB::query('ALTER TABLE `tweets_tags`      CHANGE COLUMN `tweet_id` `tweet_id` INT(11) NULL')->execute();
	}
}
