<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<!--[if IE]>
	<link rel="shortcut icon" href="<?php echo Uri::create('assets/img/favicon.ico'); ?>" />
	<![endif]-->
	<link rel="icon" type="image/png" href="<?php echo Uri::create('assets/img/favicon.png'); ?>" />
	<!-- iOS devices (first adds shine, second doesn't):
	<link rel="apple-touch-icon" href="somepath/image.png" />
	<link rel="apple-touch-icon-precomposed" href="somepath/image.png" />
	-->
	<title><?php echo $title; ?> - <?php echo $app['name']; ?></title>
	<?php echo Asset::css('main.css'); ?>
	<link href="<?php echo Uri::create('assets/skins/blue/skin.css') ?>" media="screen" rel="stylesheet" type="text/css" />
	<script type="text/javascript">
	var DEBUG_MODE = <?php echo ((Fuel::$env == Fuel::PRODUCTION) ? 'false' : 'true'); ?>;
	var BASE_URL   = '<?php echo URI::create(''); ?>';
	</script>
	<script type="text/javascript" src="<?php echo Uri::create('assets/js/krumo.js') ?>"></script>
	<script type="text/javascript" src="<?php echo Uri::create('assets/js/jquery-1.6.min.js') ?>"></script>
	<script type="text/javascript" src="<?php echo Uri::create('assets/js/debug.js') ?>"></script>
	<script type="text/javascript">
	$(document).ready( function() {
		$('#colSelect').bind("change",function() {
  			window.location = $(this).val();
		});
	});
	</script>
	<?php echo Asset::render('assets'); ?>
	<script type="text/javascript">

	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', 'UA-23091403-1']);
	  _gaq.push(['_setDomainName', '.tweetbeagle.com']);
	  _gaq.push(['_setAllowHash', 'false']);
	  _gaq.push(['_trackPageview']);

	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();

	</script>
</head>
<body>
<div class="wrapper" id="header">
	<div id="logo">
		<h1><a href="<?php echo Uri::create(''); ?>"><?php echo Asset::img('tweetbeagle_logo_sm.png', array('alt' => 'TweetBeagle')); ?></a></h1>
	</div>

	<?php
	if (TwitterAccount::isLoggedIn()) { 
		$user_id = Arr::element(Session::get('access_token'), 'user_id');
		$collections = Model_Collection::find()->where('account_id', '=', $user_id)->get();
		
		$current_navigation = Uri::segment(1) ? Uri::segment(1) : 'dashboard';
		
		$navigation = array(
			'dashboard' => 'My Dashboard',
			'search'    => 'Tweet Search',
			'about'     => 'About'
		);
		
	?>
	<div id="welcome_msg">
	Welcome <?php echo Session::get('screen_name') ?>!
	(Not you? <a href="<?php echo Uri::create('account/signout') ?>">Sign Out</a>.)
	</div>
	<ul id="navigation">
		<?php foreach ($navigation as $nav_url => $nav_label) : ?>
			<li<?php echo ($current_navigation == $nav_url) ? ' class="selected"' : ''; ?>><a href="<?php echo Uri::create($nav_url) ?>"><?php echo $nav_label; ?></a></li>
		<?php endforeach; ?>
		<li class="collection-select">
			<select id="colSelect">
				<option value='#'>View a collection</option>
				<?php foreach ($collections as $thiscollection): ?>
					<option value="<?php echo Uri::create('collections/view/').$thiscollection->id; ?>"><?php echo $thiscollection->name; ?></option>
				<?php endforeach; ?>
			</select>
		</li>
	</ul>
	<?php } ?>

	<div class="clear"></div>
</div>
<div class="centre">
    	<div class="wrapper">
	
	<div id="contentContainer">
		<div id="content">
			<?php if (Session::get_flash('notice')): ?>
				<div class="notice"><?php echo Session::get_flash('notice'); ?></div>
			<?php endif; ?>
			<?php echo $content; ?>
		</div>
	</div>
</div>
</div>
<div id="footer">
	<div id="footerContainer" class="wrapper">
		<div id="copyright_holder">
			<a href="http://www.silenceit.ca/" target="_blank">&copy; TweetBeagle 2011, web application by silenceIT</a>
		</div>
		<div id="sit_logo_holder">
			<a href="http://www.silenceit.ca/" target="_blank" title="Web design and development by silenceIT"><?php echo Asset::img('silenceit_logo.png', array('width'=>"57", 'height'=>"44", 'alt'=>"Web design and development by silenceIT")); ?></a>
		</div>
		<div id="twitter-logo">
			<a href="http://twitter.com"><?php echo Asset::img('twitter-logo.png', array('width' => '100', 'height' => '19', 'alt' => 'Built on the Twitter platform.')); ?></a>
		</div>
		<div style="clear: both;"></div>
	</div>
</div>
</body>
</html>
