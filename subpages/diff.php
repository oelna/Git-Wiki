<?php
	$template = THEMES.DS.$config['theme'].DS.'diff.tpl.php';

	if(empty($param3) || empty($param4)) {
		echo('no versions to compare!');
	}

	if(file_exists($template)) {
		require($template);
	}