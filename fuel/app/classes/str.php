<?php

class Str extends Fuel\Core\Str
{
	/**
	 * Pretty-print me some JSON.
	 * From (with adjustments): http://www.php.net/manual/en/function.json-encode.php#80339
	 */
	function json_format($json, $encode_for_me = FALSE, $tab = "\t")
	{
		$new_json = "";
		$indent_level = 0;
		$in_string = FALSE;
		
		if (!$encode_for_me)
		{
			// Validate the JSON object
			$json_obj = json_decode($json);
			
			if ($json_obj === FALSE)
				return FALSE;
			$json = json_encode($json_obj);
			unset($json_obj);
		}
		else
			$json = json_encode($json);
		$len = strlen($json);
		
		for ($c = 0; $c < $len; $c++)
		{
			$char = $json[$c];
			switch ($char)
			{
			case '{':
			case '[':
				// Don't put newlines if it's empty.
				$matching_char = ($char == '{') ? '}' : ']';
				if (!$in_string && ($json[$c+1] != $matching_char))
				{
					$new_json .= $char."\n".str_repeat($tab, $indent_level+1);
					$indent_level++;
				}
				else
					$new_json .= $char;
				break;
			case '}':
			case ']':
				// Don't put newlines if it's empty.
				$matching_char = ($char == '}') ? '{' : '[';
				if (!$in_string && ($json[$c-1] != $matching_char))
				{
					$indent_level--;
					$new_json .= "\n".str_repeat($tab, $indent_level).$char;
				}
				else
					$new_json .= $char;
			break;
			case ',':
				if (!$in_string)
					$new_json .= ",\n" . str_repeat($tab, $indent_level);
				else
					$new_json .= $char;
				break;
			case ':':
				if (!$in_string)
					$new_json .= ": ";
				else
					$new_json .= $char;
				break;
			case '"':
				if ($c > 0 && $json[$c-1] != '\\')
					$in_string = !$in_string;
			default:
				$new_json .= $char;
				break;
			}
		}
		
		return $new_json;
	}
	
	/**
	 * Inserts an elipsis in the middle of a string if it is too long.
	 * Note that this is not "HTML safe" like the original truncate().
	 * @see Str::truncate
	 */
	public static function truncate_mid($str, $maxlen = 50, $mid = '&hellip;')
	{
		if (strlen($str) > $maxlen)
		{
			$chars = floor($maxlen / 2) - 1;
			return substr($str, 0, $chars).$mid.substr($str, -1 * $chars);
		}
		else
			return $str;
	}
}
