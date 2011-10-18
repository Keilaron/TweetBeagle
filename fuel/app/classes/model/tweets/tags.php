<?php

class Model_Tweets_Tags extends Orm\Model {
	
	protected static $_belongs_to = array(
		'tweets', 'tags'
	);
}

/* End of file tweets_tags.php */