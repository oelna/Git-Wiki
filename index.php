<?php

	require_once(__DIR__.DIRECTORY_SEPARATOR.'config.php');

	if(!$git) die('Could not init git engine!');

	if(!$git->available()) {
		die('Git is required on the server running this application!');
	}

	if(!file_exists(GITDIR) || !is_dir(GITDIR)) {
		// chmod(ROOT, 0755);
		mkdir(GITDIR);
		shell_exec('cd '.GITDIR.' && '.GITBINARY.' init 2>&1');
	}
	chdir(GITDIR);

	// check repo health
	// todo: test this!
	if(!$git->is_repository(GITDIR)) {
		$git->init_repository(GITDIR);
	}

	// simple url parsing, via https://stackoverflow.com/a/15365504/3625228
	$params = (isset($_GET['params'])) ? strtolower(trim($_GET['params'], '/')) : '';
	list($wikiword, $commit, $param3, $param4, $param5) = array_pad(explode('/', $params), 5, null);
	if(empty($wikiword)) $wikiword = 'home'; // todo: prevent creation of index filename!
	if(empty($commit)) {
		// $commit = trim(shell_exec(GITBINARY.' rev-parse HEAD'));
		$commit = 'HEAD';
	} else {
		$commit = trim(shell_exec(GITBINARY.' rev-parse '.$commit));
	}

	// detect theme
	if(!file_exists(THEMES.DS.$config['theme'])) {
		die('Missing theme directory!');
	}
	require(THEMES.DS.$config['theme'].DS.'header.php');

	if(in_array(mb_strtolower($wikiword), $config['reserved_words'])) {
		// echo('reserved word!');

		require(SUBPAGES.DS.$wikiword.'.php');
	} else {
		$filename = trim($wikiword).'.md';

		if(file_exists(GITDIR) && is_dir(GITDIR)) {

			exec(GITBINARY.' rev-parse --verify HEAD 2> /dev/null', $hash);

			if(isset($_POST['page_content'])) {
				$commit_message = str_replace('"', '\"', $_POST['commit_message']);

				$commit_content = rtrim($_POST['page_content'].NL).NL;
				file_put_contents(GITDIR.DS.$filename, $commit_content, LOCK_EX);
				shell_exec(GITBINARY.' add '.$filename);
				$command = GITBINARY.' commit --allow-empty-message';
				$command .= ' --author="'.$config['user']['name'].' <'.$config['user']['email'].'>"';
				$command .= ' -m "'.$commit_message.'"';
				$command .= ' 2>&1';
				exec($command, $new_commit);

				// stats
				echo nl2br(shell_exec(GITBINARY.' diff --shortstat '.$hash[0].' HEAD '.$filename).BR);
			}

			if($commit && $commit == 'diff') {
				require(SUBPAGES.DS.'diff.php');
			} else {
				require(THEMES.DS.$config['theme'].DS.'index.php');
			}
			
			
		} else {
			echo('Not a valid git repo!');
			require(THEMES.DS.$config['theme'].DS.'error.php');
		}
	}

	require(THEMES.DS.$config['theme'].DS.'footer.php');
	
