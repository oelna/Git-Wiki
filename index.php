<?php

	/*
	Ideas:
	- git config core.preloadindex true (https://stackoverflow.com/a/2873039/3625228)
	- GIT_INDEX_FILE=$GIT_INDEX_FILE.new
	- git ls-files --stage
	- 

	// todo: maybe add admin page with repo cleanup options, eg commit every change, stash, etc.
	// shell_exec('git stash save --keep-index --include-untracked');
	shell_exec('git add .');
	shell_exec('git commit -m "cleaned up working dir"');

cp .git/index ../tmp/abc
GIT_INDEX_FILE=../tmp/abc git add home-1590572225.md
GIT_INDEX_FILE=../tmp/abc git status
GIT_INDEX_FILE=../tmp/abc git commit -m "test commit from index abc"
rm ../tmp/abc
	*/

	require_once(__DIR__.DIRECTORY_SEPARATOR.'config.php');

	if(!$git) die('Could not init git engine!');

	if(!$git->available()) {
		die('Git is required on the server running this application!');
	}

	if(!file_exists(TMPDIR) || !is_dir(TMPDIR)) {
		mkdir(TMPDIR);
	}

	if(!file_exists(GITDIR) || !is_dir(GITDIR)) {
		// chmod(ROOT, 0755);
		mkdir(GITDIR);
		shell_exec('cd '.GITDIR.' && '.GITBINARY.' init 2>&1');

		$git->set_user($config['gituser']['name'], $config['gituser']['email']);

		// todo: create the first file (homepage) and commit!
		$homepage_file = WIKIROOT.DS.'home.md';
		file_put_contents($homepage_file, "# Welcome\n\nThis is your homepage.");
		$result = $git->commit(array($homepage_file), 'Initial commit.', $config['user']);
	}
	chdir(GITDIR);

	// check repo health
	// todo: test this!
	if(!$git->is_repository(GITDIR)) {
		$git->init_repository(GITDIR);

		$git->set_user($config['gituser']['name'], $config['gituser']['email']);

		// todo: create the first file (homepage) and commit!
		$homepage_file = WIKIROOT.DS.'home.md';
		file_put_contents($homepage_file, "# Welcome\n\nThis is your homepage.");
		$result = $git->commit(array($homepage_file), 'Initial commit.', $config['user']);
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

				$commit_content = rtrim($_POST['page_content'], NL).NL;
				file_put_contents(GITDIR.DS.$filename, $commit_content, LOCK_EX);
				
				/*
				shell_exec(GITBINARY.' add '.$filename);
				$command = GITBINARY.' commit --allow-empty-message';
				$command .= ' --author="'.$config['user']['name'].' <'.$config['user']['email'].'>"';
				$command .= ' -m "'.$commit_message.'"';
				$command .= ' 2>&1';
				exec($command, $new_commit);
				*/
				$git->commit(array(GITDIR.DS.$filename), $commit_message, $config['user']);

				// stats
				if($hash[0]) {
					echo nl2br(shell_exec(GITBINARY.' diff --shortstat '.$hash[0].' HEAD '.$filename).BR);
				}
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
	
