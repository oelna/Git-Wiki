
<div id="container">

	<header>
		<h1>Git Wiki Fakultät D</h1>
	</header>

	<main>
		<nav>
			<ul><?php
				$pages = scandir(WIKIROOT);

				for ($i=0; $i < sizeof($pages); $i++):
					$pagename = basename($pages[$i], '.md');
					if($pagename[0] == '.') continue;
			?><li><a href="<?= HOME.'/'.$pagename ?>/"><?= $pagename ?></a></li>
			<?php endfor; ?>
			</ul>
		</nav>

		<div class="content">
			<div class="head">
				<h1><?= $wikiword ?></h1>
				<h2>Showing <span title="<?= $commit ?>"><?= substr($commit, 0, $config['short_hash_length']) ?></span></h2>
				<p class="message"><?php
					$message = shell_exec(GITBINARY.' show -s --format=%s '.$commit);
					// echo($message);
				?></p>
			</div>

			<div class="tab-container">
				<ul class="tab-nav">
					<li data-target="preview" class="active">View</li>
					<li data-target="editform">Edit</li>
					<li data-target="log">History</li>
				</ul>
				<div class="tabs">
					<form id="editform" action="<?= HOME.'/'.$wikiword ?>/" method="post">
						<?php
							$content = '';
							// echo('xxx'.$git->file_exists(GITDIR.DS.$filename, $commit));
							if($git->file_exists(GITDIR.DS.$filename, $commit)) {
								$content = $git->get_file_content($filename, $commit);
							}
						?>
						<input type="hidden" name="previous_commit" value="<?= $hash[0] ?>" />
						<input type="hidden" name="page_name" value="<?= $filename ?>" />
						<textarea name="page_content" rows="20"><?= $content ?></textarea><br />
						<input type="text" name="commit_message" maxlength="255" /><br />
						<input type="submit" name="Save" />
					</form>

					<div id="preview" class="active">
						<!-- todo: https://github.com/cure53/DOMPurify -->
						<?php echo $config['markdown_parser']->setBreaksEnabled(true)->text($content); ?>
					</div>

					<div id="log">
						<h2>Die letzten Änderungen</h2>
						<table>
							<tr>
								<th class="select"></th>
								<th class="author">Author</th>
								<th class="date">Datum</th>
								<th class="commit">Commit</th>
								<th class="message">Message</th>
							</tr>
							<?php
								// get HEAD
								$head = $git->head();
								if($head):
									$timestamp = strtotime($head['date']);
							?>
							<tr>
								<td><input type="radio" id="commit-<?= $head['abbreviated_commit'] ?>" class="select-commit" value="<?= $head['abbreviated_commit'] ?>" /></td>
								<td><?= $head['author']['name'] ?></td>
								<td><time datetime="<?= date('Y-m-d H:i:s', $timestamp) ?>"><?= date('d.m. H:i', $timestamp) ?></time></td>
								<td><a title="<?= $head['abbreviated_commit'] ?>" href="./<?= $wikiword ?>/head/">HEAD</a></td>
								<td><?= trim($head['subject']) ?></td>
							</tr>
							<?php
								endif;

								$parsed_log = $git->log($filename, 6);
								foreach($parsed_log as $c):
									$timestamp = strtotime($c['date']);
							?>
							<tr>
								<td><input type="radio" id="commit-<?= $c['abbreviated_commit'] ?>" class="select-commit" value="<?= $c['abbreviated_commit'] ?>" /></td>
								<td><?= $c['author']['name'] ?></td>
								<td><time datetime="<?= date('Y-m-d H:i:s', $timestamp) ?>"><?= date('d.m. H:i', $timestamp) ?></time></td>
								<td><a href="./<?= $wikiword ?>/<?= $c['abbreviated_commit'] ?>/"><?= $c['abbreviated_commit'] ?></a></td>
								<td><?= trim($c['subject']) ?></td>
							</tr>
							<?php endforeach; ?>
						</table>

						<button id="show-diff" class="" disabled>Show differences</button>
					</div>
				</div>
			</div>
		</div>
	</main>

	<footer></footer>
</div>