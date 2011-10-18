<div class="pane search-pane">
	<h3>Live Tweet Search</h3>
	<div class="top-controls">
		<span><?php echo Html::anchor('http://search.twitter.com/operators', 'Search Tips', array('target' => '_blank')); ?></span>
	</div>
	
	<div class="contents">
		<div class="search-input">
		<?php echo Form::open(array('action' => 'search/query/1', 'method' => 'get')) ?>
			<?php echo Form::input('query', '', array('size' => '31', 'style' => '', 'class' => 'search')) ?>
			<?php echo Form::submit('submit', 'Search', array('class' => 'search')); ?>
		<?php echo Form::close(); ?>
			<div class="clear"></div>
		</div>
	</div>
	
</div>

