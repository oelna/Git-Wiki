<?php

	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	date_default_timezone_set('Europe/Berlin');

	DEFINE('BR', '<br />');
	DEFINE('NL', PHP_EOL);
	DEFINE('DS', DIRECTORY_SEPARATOR);
	DEFINE('ROOT', __DIR__);
	DEFINE('HOME', '/~oelna/designwiki4');
	DEFINE('GITBINARY', '/usr/bin/git');
	DEFINE('GITDIR', ROOT.DS.'pages');
	DEFINE('WIKIROOT', GITDIR);

	require_once(ROOT.DS.'Parsedown.php');

	$config = [
		'user' => [
			'name' => 'Arno Richter 220577',
			'email' => '220577@stud.hs-mannheim.de'
		],
		'short_hash_length' => 7,
		'markdown_parser' => new Parsedown()
	];

	// check git availability
	if(strpos(shell_exec('git --version 2>&1'), 'git version') === false) {
		die('Git is required on the server running this application!');
	}

	if(!file_exists(GITDIR) || !is_dir(GITDIR)) {
		// chmod(ROOT, 0755);
		mkdir(GITDIR);
		shell_exec('cd '.GITDIR.' && git init 2>&1');
	}
	chdir(GITDIR);

	// check repo health
	if(strpos(shell_exec('git status 2>&1'), 'not a git repository') !== false) {
		shell_exec('git init 2>&1');
	}

	// simple url parsing, via https://stackoverflow.com/a/15365504/3625228
	$params = (isset($_GET['params'])) ? strtolower(trim($_GET['params'], '/')) : '';
	list($wikiword, $commit) = array_pad(explode('/', $params), 5, null);
	if(empty($wikiword)) $wikiword = 'home'; // todo: prevent creation of index filename!
	if(empty($commit)) {
		// $commit = trim(shell_exec(GITBINARY.' rev-parse HEAD'));
		$commit = 'HEAD';
	} else {
		$commit = trim(shell_exec(GITBINARY.' rev-parse '.$commit));
	}

	$filename = trim($wikiword).'.md';

	function git_log(string $page, int $count):array {
		if(!isset($count) || !is_numeric($count)) $count = 0;

		$command = GITBINARY.' log';
		//$command .= ' --pretty=format:\'{  "commit": "%H",  "abbreviated_commit": "%h",  "tree": "%T",  "abbreviated_tree": "%t",  "parent": "%P",  "abbreviated_parent": "%p",  "date": "%aD",  "subject": "%s",  "author": { "name": "%aN", "email": "%aE"}}\'';

		// https://gist.github.com/varemenos/e95c2e098e657c7688fd
		$command .= ' --pretty=format:\'{  *$*commit*$*: *$*%H*$*,  *$*abbreviated_commit*$*: *$*%h*$*,  *$*tree*$*: *$*%T*$*,  *$*abbreviated_tree*$*: *$*%t*$*,  *$*parent*$*: *$*%P*$*,  *$*abbreviated_parent*$*: *$*%p*$*,  *$*date*$*: *$*%aD*$*,  *$*subject*$*: *$*%s*$*,  *$*author*$*: { *$*name*$*: *$*%aN*$*, *$*email*$*: *$*%aE*$*}}\'';

		if($count > 0) $command .= ' -'.$count;
		$command .= ' -- '.$page;

		exec($command.' 2> /dev/null', $log);

		// var_dump($log);
		$log = str_replace('"', '\"', $log);
		$log = str_replace('*$*', '"', $log);

		$return = array();
		foreach($log as $key => $value) {
			$return[] = json_decode($value, true);
		}
		
		return $return;
	}

	/*
	function parse_log_array2(array $array):array {
		$return_array = [];

		$log_item = [];

		foreach ($array as $key => $value) {
			
			// if(empty($value)) continue;

			$check = substr($value, 0, 6);

			if($check === 'commit') {

				// save the previous entry
				if(sizeof($log_item) > 0) {
					$return_array[] = $log_item;
					$log_item = [];
				}

				$value = str_replace('commit ', '', $value);
				$log_item['commit'] = trim($value);
				continue;
			}

			if($check === 'Author') {
				$pos = strpos($value, 'Author: ');
				if ($pos !== false) {
					$value = substr_replace($value, '', $pos, 8);
				}

				$author = rtrim($value, '>');
				$author = explode(' <', $author);
				$log_item['author'] = [
					'name' => $author[0],
					'email' => $author[1]
				];

				continue;
			}

			if($check === 'Date: ') {
				$pos = strpos($value, 'Date: ');
				if ($pos !== false) {
					$value = substr_replace($value, '', $pos, 6);
				}
				$log_item['date'] = strtotime($value);
				continue;
			}

			$log_item['message'] = trim($value);
			var_dump($log_item);
		}

		// save the last item
		$return_array[] = $log_item;

		return $return_array;
	}
	*/

	if(file_exists(GITDIR) && is_dir(GITDIR)) {

		/*
		echo('Site root: '.ROOT).BR;
		echo('Wiki pages dir: '.GITDIR).BR;
		echo 'Working dir: '.shell_exec('pwd').BR; // returns string
		echo 'Current user: '.shell_exec('whoami').BR;

		echo 'git version: '.shell_exec('git --version').BR;

		echo 'current user name: '.$config['user']['name'].BR;
		echo 'current user email: '.$config['user']['email'].BR;
		*/

		/*
		exec('git status 2>&1', $status);
		echo('<pre>');var_dump($status);echo('</pre>');
		*/

		exec(GITBINARY.' rev-parse --verify HEAD 2> /dev/null', $hash);

		// echo('<pre><h3>Current HEAD</h3>');var_dump($hash);echo('</pre>');

		// git log -1 --format=%h --abbrev=8 // 8 char hash
		// git log -1 --format=%H // full hash

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
		
		// echo('<pre>');var_dump($new_commit);echo('</pre>');

		// stats
		// echo nl2br(shell_exec('git diff --stat '.$hash[0].' HEAD '.$filename).BR);
		// echo nl2br(shell_exec('git diff --shortstat '.$hash[0].' HEAD '.$filename).BR);
		// echo nl2br(shell_exec('git diff --numstat '.$hash[0].' HEAD '.$filename).BR);


		// exec('git log 2> /dev/null', $log);
		// $parsed_log = git_log($filename, 4);
		// echo('Log Size: '.sizeof($parsed_log).'<pre><h3>Log</h3>');var_dump($parsed_log);echo('</pre>');

		// echo('<pre>');echo(file_get_contents(GITDIR.DS.$filename));echo('</pre>');
	} else {
		echo('Not a valid git repo!');
	}

	// git log --numstat --pretty="%H" --author="Your Name" commit1..commit2 | awk 'NF==3 {plus+=$1; minus+=$2} END {printf("+%d, -%d\n", plus, minus)}'
	// git log --numstat --pretty="%H" d0f3c67dda1d42d945842cfc500f08ba2b7c62d5..24acdf118017442c6f496254690e644507d8b429 | awk 'NF==3 {plus+=$1; minus+=$2} END {printf("+%d, -%d\n", plus, minus)}'

	// d0f3c67dda1d42d945842cfc500f08ba2b7c62d5 24acdf118017442c6f496254690e644507d8b429
	// git diff HEAD~1 HEAD page.md
	// git log --oneline --shortstat page.md
	// git log --stat
	// git log d0f3c67dda1d42d945842cfc500f08ba2b7c62d5^..24acdf118017442c6f496254690e644507d8b429 --oneline --shortstat --author="Mike Surname"
	// git log --name-only --oneline

?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>Git Wiki v0.2</title>

	<link href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAYAAABPYyMiAAAABmJLR0T///////8JWPfcAAAACXBIWXMAAABIAAAASABGyWs+AAAAF0lEQVRIx2NgGAWjYBSMglEwCkbBSAcACBAAAeaR9cIAAAAASUVORK5CYII=" rel="icon" type="image/x-icon" />

	<style>
		* { box-sizing: border-box; }

		html {
			font: 100%/1.4 system-ui, Helvetica, Arial, sans-serif;
		}

		#container {
			display: flex;
		}

		#container > * {
			flex: 1;
			margin-right: 2em;
			width: CALC((100% / 3) - 2em);
		}

		form textarea,
		form input[name="commit_message"] {
			width: 100%;
			font-size: 1rem;
			padding: 0.4em;
		}

		#log table th,
		#log table td {
			vertical-align: top;
			text-align: left;
		}

		#log table tr + tr td {
			border-top: 1px solid #000;
		}

		#log .author { width: 40ch; }
		#log .date { width: 20ch; }
		#log .commit { width: 9ch; }
		#log .message { width: auto; }
	</style>
</head>
<body>

	<h1><?= $wikiword ?></h1>
	<h2>Showing <span title="<?= $commit ?>"><?= substr($commit, 0, $config['short_hash_length']) ?></span></h2>
	<p class="message">x<?php
		$message = shell_exec(GITBINARY.' show -s --format=%s '.$commit);
		echo($message);
	?></p>
	
	<div id="container">
		<form action="<?= HOME.'/'.$wikiword ?>/" method="post">
			<?php
				$content = '';
				if(file_exists(GITDIR.DS.$filename)) { // todo: different error handling!
					// $commit_hash = 'e0f719095445c2d99a37ac1ac7a18b8bfbc3983f';
					$content = shell_exec(GITBINARY.' show '.$commit.':'.$filename);
					
					// $content = file_get_contents(GITDIR.DS.$filename);
				}
			?>
			<input type="hidden" name="previous_commit" value="<?= $hash[0] ?>" />
			<input type="hidden" name="page_name" value="<?= $filename ?>" />
			<textarea name="page_content" rows="20"><?= $content ?></textarea><br />
			<input type="text" name="commit_message" maxlength="255" /><br />
			<input type="submit" name="Save" />
		</form>

		<div id="preview">
			<!-- todo: https://github.com/cure53/DOMPurify -->
			<?php echo $config['markdown_parser']->setBreaksEnabled(true)->text($content); ?>
		</div>

		<div id="log">
			<h2>Die letzten Änderungen</h2>
			<table>
				<tr>
					<th class="author">Author</th>
					<th class="date">Datum</th>
					<th class="commit">Commit</th>
					<th class="message">Message</th>
				</tr>
				<?php
					$parsed_log = git_log($filename, 6);
					foreach($parsed_log as $c):
						$timestamp = strtotime($c['date']);
				?>
				<tr>
					<td><?= $c['author']['name'] ?></td>
					<td><time datetime="<?= date('Y-m-d H:i:s', $timestamp) ?>"><?= date('d.m. H:i', $timestamp) ?></time></td>
					<td><a href="<?= HOME ?>/<?= $wikiword ?>/<?= $c['abbreviated_commit'] ?>/"><?= $c['abbreviated_commit'] ?></a></td>
					<td><?= trim($c['subject']) ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>
	</div>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/showdown/1.9.1/showdown.min.js"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function (event) {
			const parser = new showdown.Converter();

			const output = document.querySelector('#preview');
			const textarea = document.querySelector('textarea[name="page_content"]');
			textarea.addEventListener('keyup', function (event) {
				// todo: add delay?
				// todo: only parse on change
				const html = parser.makeHtml(event.target.value);
				output.innerHTML = html;
			});
		});
	</script>
</body>
</html>