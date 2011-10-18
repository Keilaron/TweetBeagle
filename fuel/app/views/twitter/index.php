<?php

foreach ($output as $lbl => $out)
{
	echo 'Getting ',$lbl,': <br />';
	Debug::dump($out);
}
