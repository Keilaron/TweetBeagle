<?php

namespace Fuel\Migrations;

class Fix_integers_and_creation_time {
	
	public function up()
	{
		\DB::query('ALTER TABLE `collections` CHANGE COLUMN id id INT UNSIGNED NOT NULL AUTO_INCREMENT')->execute();
		\DB::query('ALTER TABLE `collections` CHANGE COLUMN since_id since_id BIGINT UNSIGNED NULL')->execute();
		\DB::query('ALTER TABLE `collections` CHANGE COLUMN account_id account_id INT UNSIGNED NOT NULL')->execute();
		\DB::query('ALTER TABLE `tweets` CHANGE COLUMN created_at created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL')->execute();
		\DB::query('ALTER TABLE `collections_tweeters` CHANGE COLUMN collection_id collection_id INT UNSIGNED NOT NULL')->execute();
		\DB::query('ALTER TABLE `collections_tweeters` CHANGE COLUMN tweeter_id tweeter_id INT UNSIGNED NOT NULL')->execute();
	}

	public function down()
	{
		\DB::query('ALTER TABLE `collections` CHANGE COLUMN id id INT NOT NULL AUTO_INCREMENT')->execute();
		\DB::query('ALTER TABLE `collections` CHANGE COLUMN since_id since_id INT UNSIGNED NULL')->execute();
		\DB::query('ALTER TABLE `collections` CHANGE COLUMN account_id account_id INT NULL')->execute();
		\DB::query('ALTER TABLE `tweets` CHANGE COLUMN created_at created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL')->execute();
		\DB::query('ALTER TABLE `collections_tweeters` CHANGE COLUMN collection_id collection_id INT NULL')->execute();
		\DB::query('ALTER TABLE `collections_tweeters` CHANGE COLUMN tweeter_id tweeter_id INT NULL')->execute();
	}
}
