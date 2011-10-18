<?php

namespace Fuel\Migrations;

class Fix_charset {

	public function up()
	{
		\DB::query('ALTER TABLE `collections`      MODIFY `name`          varchar(50) CHARACTER SET utf8 NOT NULL')->execute();
		\DB::query('ALTER TABLE `tags`             MODIFY `content`       varchar(255) CHARACTER SET utf8 NOT NULL')->execute();
		\DB::query('ALTER TABLE `tags`             MODIFY `type`          varchar(15) NOT NULL')->execute();
		\DB::query('ALTER TABLE `tweeters`         MODIFY `name`          varchar(255) CHARACTER SET utf8 DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tweeters`         MODIFY `location`      varchar(255) CHARACTER SET utf8 DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tweeters`         MODIFY `description`   varchar(160) CHARACTER SET utf8 DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tweets`           MODIFY `text`          text CHARACTER SET utf8')->execute();
		\DB::query('ALTER TABLE `tweets`           MODIFY `location`      varchar(255) CHARACTER SET utf8 DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tweets`           MODIFY `user_location` varchar(255) CHARACTER SET utf8 DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tweets_entities`  MODIFY `entity_body`   varchar(2048) CHARACTER SET utf8 NOT NULL')->execute();
		\DB::query('ALTER TABLE `tweets_histories` MODIFY `whole_tweet`   text CHARACTER SET utf8')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `collections`      MODIFY `name`          varchar(50) DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tags`             MODIFY `content`       varchar(255) DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tags`             MODIFY `type`          varchar(255) DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tweeters`         MODIFY `name`          varchar(255) DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tweeters`         MODIFY `location`      varchar(255) DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tweeters`         MODIFY `description`   varchar(160) DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tweets`           MODIFY `text`          text')->execute();
		\DB::query('ALTER TABLE `tweets`           MODIFY `location`      varchar(255) DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tweets`           MODIFY `user_location` varchar(255) DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tweets_entities`  MODIFY `entity_body`   varchar(1024) DEFAULT NULL')->execute();
		\DB::query('ALTER TABLE `tweets_histories` MODIFY `whole_tweet`   text')->execute();
	}
}
