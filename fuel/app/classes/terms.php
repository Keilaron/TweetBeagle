<?php

class Terms {
	
	public static function extract($text)
	{
		//$script = APPPATH.'classes/lib/term-extract.rb';
		$script = APPPATH.'classes/lib/term-extract.py';
		$output = array();
		$return = 0;
		
		// Filter out common erroneous terms
		$regex = array(
		'/RT @:/i',		// Remove rt @:
		);
		
		//$parse = exec("ruby $script ".escapeshellarg($text), $output, $return);
		$parse = exec("python $script ".escapeshellarg(preg_replace($regex, '', $text)), $output, $return);
		$parse = json_decode($parse);
		
		$terms = array();
		
		/**
		foreach ($parse as $term => $count)
		{
			$terms[] = $term;
		}
		*/
		
		foreach ($parse as $term)
		{
			$terms[] = $term;
		}
		
		return $terms;
	}
}
