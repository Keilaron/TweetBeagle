<?php 
foreach ($recent_tweets as $tweet)
{
	// Regex the text for links, hashtags and mentions
	$text = preg_replace("/((http(s?):\/\/)|(www\.))([\w\.]+)([a-zA-Z0-9?&%.;:\/=+_-]+)/i", '<a href="http$3://$4$5$6" target="_blank">$2$4$5$6</a>', $tweet['text']);
	$text = preg_replace("/(?<=\A|[^A-Za-z0-9_])@([A-Za-z0-9_]+)(?=\Z|[^A-Za-z0-9_])/", '<a href="#" class="mention_filter" rel="$0">$0</a>', $text);
	$text = preg_replace("/(?<=\A|[^A-Za-z0-9_])#([A-Za-z0-9_]+)(?=\Z|[^A-Za-z0-9_])/", '<a href="#" class="hashtag_filter" rel="$0">$0</a>', $text);

	echo 				
	"<div style='padding-top: 0px;' class='stream'><div class='stream-item'><div class='stream-item-content'><div class='tweet-image'>".
			"<a href='http://twitter.com/".$tweet['screen_name']."' target='_blank'><img height='48' width='48' src='".$tweet['profile_image_url']."'></a>".
			"</div><div class='tweet-content'>";

	echo '<a href="http://twitter.com/'.$tweet['screen_name'].'" target="_blank"><h5>'.$tweet['screen_name'].'</h5></a>'.$text.
	'<br><small title="'.$tweet['created_at'].' UTC">'.Date::time_ago(strtotime($tweet['created_at'])).'</small><br><br></div></div></div></div>';
}
?>