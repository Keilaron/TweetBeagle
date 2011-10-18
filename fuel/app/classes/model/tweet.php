<?php

class Model_Tweet extends Orm\Model {
	
	protected static $_belongs_to = array('tweeter');
	
	protected static $_many_through = array(
		'tags' => array(
			'model_through'    => 'Model_Tweets_Tags', // both models plural without prefix in alphabetical order
		)
	);
}

/* End of file tweet.php */