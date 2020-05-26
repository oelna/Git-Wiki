<?php

?><div id="container">

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

		<div class="content diff">
			<div class="head">
				<h1><a class="button" href="<?= HOME ?>/<?= $wikiword ?>/">View current version</a></h1>
				<h2>Showing versions <span><?= $param3 ?></span> and <span><?= $param4 ?></span></h2>
			</div>

			<textarea id="diff-input"><?= $git->diff_file(basename($filename), $param3, $param4) ?></textarea>

			<div id="diff-stats">
				<div><?php $timestamp = $git->get_commit_date($param3); ?>
					<h3><a href="<?= HOME ?>/<?= $wikiword ?>/<?= $param3 ?>/">&#8594;&thinsp;<?= $param3 ?></a></h3>
					<time datetime="<?= date('Y-m-d H:i:s', $timestamp) ?>"><?= date($config['longdate'], $timestamp) ?></time>
				</div>
				<div><?php $timestamp = $git->get_commit_date($param4); ?>
					<h3><a href="<?= HOME ?>/<?= $wikiword ?>/<?= $param4 ?>/">&#8594;&thinsp;<?= $param4 ?></a></h3>
					<time datetime="<?= date('Y-m-d H:i:s', $timestamp) ?>"><?= date($config['longdate'], $timestamp) ?></time>
				</div>
			</div>
			<div id="diff-output"></div>

			<p><a class="button secondary" href="<?= HOME ?>/<?= $wikiword ?>/">Back</a></p>
		</div>
	</main>

	<footer></footer>
</div>