<?php

namespace Fuel\Migrations;

class Clear_ruby_extractions {

	public function up()
	{
		// This is to clear all the work the ruby extractor has done.
		// The rules have changed and we're now using the python one, so the work so far is void.
		\DB::query('TRUNCATE `tags`')->execute();
		\DB::query('TRUNCATE `tweets_tags`')->execute();
		\DB::query('TRUNCATE `collections_omits`')->execute();
		\DB::query('UPDATE `tweets` SET indexed = NULL')->execute();
	}

	public function down()
	{
		// Nothing to do. It's too late! ;)
	}
}
