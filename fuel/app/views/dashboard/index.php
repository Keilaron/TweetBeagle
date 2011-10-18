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
<div class="l-row">
	<div class="l-column"><?php echo $collections ?></div>
	<div class="l-column"><?php echo $search ?>
		<div class="pane dash-right-pane">
	  	<h3>Public Collections <input type="text" class="pub-col-search" value="Type to Filter"></input></h3>
		  	<div class="dash-public-collections">
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
	</div>
</div>
<div class="clear"></div>
