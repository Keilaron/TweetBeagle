<?php

namespace Fuel\Migrations;

class Add_tweet_columns {
	
	public function up()
	{
		\DB::query('ALTER TABLE `tweets` 
			ADD COLUMN source                  VARCHAR(255) NULL,
			ADD COLUMN in_reply_to_status_id   INT UNSIGNED NULL,
			ADD COLUMN in_reply_to_user_id     INT UNSIGNED NULL,
			ADD COLUMN favorited               TINYINT UNSIGNED NULL,
			ADD COLUMN in_reply_to_screen_name VARCHAR(15) NULL,
			ADD COLUMN place_name              VARCHAR(255) NULL,
			ADD COLUMN place_country_code      CHAR(2) NULL,
			ADD COLUMN user_location           VARCHAR(255) NULL,
			ADD COLUMN user_followers_count    INT UNSIGNED DEFAULT 0,
			ADD COLUMN user_friends_count      INT UNSIGNED DEFAULT 0,
			ADD COLUMN user_verified           TINYINT UNSIGNED DEFAULT 0
			')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `tweets` 
			DROP COLUMN source
			DROP COLUMN in_reply_to_status_id
			DROP COLUMN in_reply_to_user_id
			DROP COLUMN favorited
			DROP COLUMN in_reply_to_screen_name
			DROP COLUMN place_name
			DROP COLUMN place_country_code
			DROP COLUMN user_location
			DROP COLUMN user_followers_count
			DROP COLUMN user_friends_count
			DROP COLUMN user_verified
			')->execute();
	}
}
