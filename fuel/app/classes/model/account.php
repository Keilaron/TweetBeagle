<?php

class Model_Account extends Orm\Model {
	
	protected static $_has_many = array('collections');
	
	public static function find_current()
	{
		$access_token = Session::get('access_token');
		return self::find($access_token['user_id']);
	}
}

/* End of file account.php */