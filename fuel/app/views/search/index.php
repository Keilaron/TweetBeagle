<center>
<div class="search-title">
<h2>Search Twitter Realtime</h2>
</div>

<div class='text-search-div'>
<div class='search-input'>
<?php
echo Form::open(array('action' => 'search/query/1', 'method' => 'get')); ?>
<center>
<?php
echo Form::input('query', '', array('size' => '75', 'style' => '', 'class' => 'search'));
?>
</center>
 <div class="clear"></div>
</div>
</div>

 <div class="clear"></div>

<?php
echo Form::submit('submit', 'Search', array('class' => 'search'));
echo Form::close();

echo "<div class='search-tips'><a href='http://search.twitter.com/operators' target='_blank'>Search Tips</a></div>";
?>
