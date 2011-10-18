<?php

class Html extends Fuel\Core\Html
{
	public static function help($str, $help_array, $which)
	{
		if (!isset($help_array[$which]))
		{
			Log::debug('No '.$which.' in help array.');
			return $str;
		}
		elseif (empty($help_array[$which]))
			return $str;
		else
			return '<span class="has_help" title="'.$help_array[$which].'">'.$str.'</span>';
	}
}
