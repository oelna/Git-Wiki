<?php

class git {

	public $gitbinary = '/usr/bin/git';

	public function __construct($gitbinary) {
		if(isset($gitbinary)) $this->gitbinary = $gitbinary;
	}

	// check git availability
	public function available() {
		if(strpos(shell_exec($this->gitbinary.' --version 2>&1'), 'git version') === false) {
			return false;
		}

		return true;
	}

	public function is_repository($dir='.') {
		if(strpos(shell_exec($this->gitbinary.' status '.$dir.' 2>&1'), 'not a git repository') !== false) {
			return false;
		}

		return true;
	}

	public function init_repository($dir='.') {
		// todo: add checks
		shell_exec($this->gitbinary.' init '.$dir.' 2>&1');
	}

	public function set_user($name, $email) {
		shell_exec($this->gitbinary.' config user.name "'.addslashes($name).'" 2>&1');
		shell_exec($this->gitbinary.' config user.email "'.addslashes($email).'" 2>&1');
		return true;
	}

	public function get_file_content($filename, $commit='HEAD') {
		return shell_exec($this->gitbinary.' show '.$commit.':'.$filename);
	}

	public function commit($files, $message='', $user=null, $tmp_dir=null) {
		$temp_index = tempnam(($tmp_dir) ? $tmp_dir : sys_get_temp_dir(), 'gitwiki_index_');
		$repo_dir = trim(shell_exec($this->gitbinary.' rev-parse --show-toplevel'));

		// make a custom index for this commit
		$indexprefix = '';
		$indexprefix = 'GIT_INDEX_FILE='.$temp_index;
		shell_exec('\cp '.$repo_dir.'/.git/index '.$temp_index);

		// add the files
		foreach ($files as $file) {
			shell_exec($indexprefix.' '.$this->gitbinary.' add '.$file);
		}

		// build the commit command
		$command = $indexprefix.' '.$this->gitbinary.' commit';
		$command .= ' --allow-empty-message';
		if(!empty($user) && isset($user['name']) && isset($user['email'])) $command .= ' --author="'.$user['name'].' <'.$user['email'].'>"';
		$command .= ' -m "'.addslashes($message).'"';
		$result = shell_exec($command);

		// clean up the main index
		$hash = trim(shell_exec($indexprefix.' '.$this->gitbinary.' rev-parse --verify HEAD'));
		foreach ($files as $file) {
			shell_exec($this->gitbinary.' checkout '.$hash.' -- '.$file);
		}
		
		// remove the temp index file
		shell_exec('rm '.$temp_index);

		return $result;
	}

	public function file_exists($filename, $commit='HEAD') {
		$command = $this->gitbinary.' cat-file -e '.$commit.':'.$filename;
		$check = shell_exec($command);
		// https://stackoverflow.com/a/18462219/3625228
		return (empty($check)) ? true : false;
	}

	public function head() {
		$data = $this->show('HEAD');
		return $data;
	}

	public function show($commit) {
		if(!$commit) return false;
		$command = $this->gitbinary.' show '.$commit;

		$command .= ' --pretty=format:\'{^^^^commit^^^^: ^^^^%H^^^^,^^^^abbreviated_commit^^^^: ^^^^%h^^^^,^^^^date^^^^: ^^^^%aD^^^^,^^^^subject^^^^: ^^^^%s^^^^,^^^^author^^^^: { ^^^^name^^^^: ^^^^%aN^^^^, ^^^^email^^^^: ^^^^%aE^^^^}}\'';
		$command .= " | sed 's/\"/\\\\\"/g' | sed 's/\^^^^/\"/g'";
		$data = shell_exec($command);

		// get rid of the diff content somehow
		$data = explode('}}', $data);
		$data = trim($data[0]).'}}';

		return json_decode($data, true);
	}

	public function get_commit_date($commit):int {
		if(!$commit) return 0;
		$command = $this->gitbinary.' show -s --format=%ci '.$commit;
		
		$data = shell_exec($command);
		return strtotime($data);
	}

	public function diff_file($filename, $commit_1, $commit_2) {
		if(!$filename || !$commit_1 || !$commit_2) return '';
		$command = $this->gitbinary.' diff ';
		$command .= '--no-prefix -U2000 '; // magic number! https://stackoverflow.com/a/24932000/3625228
		$command .= $commit_1.'..'.$commit_2.' -- '.$filename;

		$data = shell_exec($command);
		return $data;
	}

	public function log($filename='', $limit=0) {
		if(!$filename) return false;
		$command = $this->gitbinary.' log';

		// https://gist.github.com/varemenos/e95c2e098e657c7688fd
		$command .= ' --pretty=format:\'{^^^^commit^^^^: ^^^^%H^^^^,^^^^abbreviated_commit^^^^: ^^^^%h^^^^,^^^^tree^^^^: ^^^^%T^^^^,^^^^abbreviated_tree^^^^: ^^^^%t^^^^,^^^^parent^^^^: ^^^^%P^^^^,^^^^abbreviated_parent^^^^: ^^^^%p^^^^,^^^^date^^^^: ^^^^%aD^^^^,^^^^subject^^^^: ^^^^%s^^^^,^^^^author^^^^: { ^^^^name^^^^: ^^^^%aN^^^^, ^^^^email^^^^: ^^^^%aE^^^^}}\'';

		if($limit > 0) $command .= ' -'.$limit;
		$command .= ' -- '.$filename;
		$command .= " | sed 's/\"/\\\\\"/g' | sed 's/\^^^^/\"/g'";

		exec($command.' 2> /dev/null', $log);

		$return = array();
		foreach($log as $key => $value) {
			$return[] = json_decode($value, true);
		}
		
		return $return;
	}
}
