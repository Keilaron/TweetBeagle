<?php if (TwitterAccount::isLoggedIn()): ?>
  <div>
    Hi <?php echo $screenName ?>, it looks like you are already signed-in. Go to 
    <a href="<?php echo Uri::create('dashboard/index') ?>">the Dashboard &raquo;</a>
  </div>
<?php else: ?>
	<script>
	
	jQuery.expr[':'].Contains = function(a,i,m){
    	return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase())>=0;
	};
	
	jQuery(function ($) {
		$(".pub-col-search").change( function () {
		    var filter = $(this).val();
		    if(filter) {
		      $(list).find("a:not(:Contains(" + filter + "))").parent().slideUp();
		      $(list).find("a:Contains(" + filter + ")").parent().slideDown();
		    } else {
		      $(list).find("li").slideDown();
		    }
		    return false;
		  })
		.keyup( function () {
		    $(this).change();
		});
		$(".pub-col-search").focusin( function () {
			$(this).val('');
		});
		$(".pub-col-search").focusout( function () {
			$(this).val('Type to Filter');
		});
	});
	</script>
  <div class="pane home-signin fancybox">
	<h3>Fetch Data, Retrieve Trends</h3>
	<div class="home-content">
	<p>Twitter has created an avalanche of communication and the trend is rising. Listening to a world crowded with over 77 million voices is no longer manageable. To truly understand, trend and predict, you need to sort and qualify your source.</p>

<p>Tweatbeagle allows you to collet tweets based on search terms or a group of tweeters and automatically identifies growing trends, influencers and hot topics within a specific industry or group.</p>

<p>Take it for a test drive by surfing through any of our public collection on the right or sign up for free with your twitter account today.</p>
		<p>Please click on the button below to sign in using your Twitter account.</p>
		<center>
		<a href="<?php echo Uri::create('account/signin') ?>">
		  <?php echo Asset::img('twitter_signin_button.png', array('alt' => 'Sign in with Twitter')) ?>
		</a>
		</center>
    </div>
  </div>
  <div class="pane home-collections fancybox">
  	<h3>Public Collections <input type="text" class="pub-col-search" value="Type to Filter"></input></h3>
  	<div class="home-content home-public-collections">
  		<ul id="list">
  		<?php

  			foreach ($public_collections as $index => $collection)
  			{
  				echo "<li><a href='".Uri::create('collections/view/'.$index)."'>".$collection."</a>";
  			}
  		?>
  		</ul>
  	</div>
  </div>
<?php endif; ?>
