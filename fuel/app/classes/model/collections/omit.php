<?php
/** Also known as hidden tags */
class Model_Collections_Omit extends Orm\Model {
	
	/**
	 * Shows a comma-delim list of omitted tags.
	 */
	public static function show_list(array $tags, $singular_type, $include_link = FALSE)
	{
		$activeClass = 'dyn_hides';
		$type = Inflector::pluralize($singular_type);
		if (empty($tags))
			return '<div class="'.$activeClass.'">No hidden '.$type.'.</div>';
		else
		{
			$prefix = '';
			if ($singular_type == 'hashtag') $prefix = '#';
			if ($singular_type == 'mention') $prefix = '@';
			$str = '<div class="'.$activeClass.'">Hidden '.$type.': ';
			foreach ($tags as $which => $tag)
			{
				switch ($singular_type)
				{
				case 'link':
					// Shorten the URLs
					$tag['content'] = Str::truncate_mid($tag['content']);
					break;
				case 'hashtag':
				case 'mention':
					$tag['content'] = $prefix.$tag['content'];
					break;
				case 'term':
				default:
					break;
				}
				if ($include_link) $str .= '<a class="'.$singular_type.'_unhide" rel="'.$tag['id'].'">';
				                   $str .= $tag['content'];
				if ($include_link) $str .= '</a>';
				                   $str .= ', ';
			}
			return substr($str, 0, -2).'.</div>';
		}
	}
}

/* End of file collections_omit.php */