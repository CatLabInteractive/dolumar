<?php
	/* Battle report: header, view switch and one of the three views (summary, replay, chronicle).
	   Data comes from Dolumar_Battle_ReportView; names in the strings are already escaped.
	   Built from the ui-* kit (.ai/style-guide.md). */
	$h = $header;
	$link = function ($params) use ($reportid) {
		$params['report'] = $reportid;
		return 'windowAction(this, ' . htmlspecialchars (json_encode ($params), ENT_QUOTES) . ');';
	};
	$verdictClass = $h['live'] ? 'maybe' : ($h['win'] ? 'true' : 'false');
	$spoils = function () use ($h) {
		foreach ($h['spoils'] as $s) {
			echo '<span class="br-spoil"><span class="' . $s['class'] . '" title="' . $s['title'] . '"></span>' . $s['amount'] . '</span> ';
		}
	};
?>
<div class="battle-report br-view-<?=$view?>">

	<p class="br-back"><a href="javascript:void(0);" onclick="windowAction(this,'overview=true');">‹ <?=$this->getText ('allBattles')?></a></p>

	<div class="<?=$verdictClass?> ui-msg br-verdict">
		<strong class="ui-msg-title"><?=$h['title']?></strong>
		<?=$h['subtitle']?><br />
		<span class="ui-muted"><?=$h['when']?></span>
		<?php if (count ($h['spoils']) > 0) { ?>
			<span class="br-spoils"><?php $spoils (); ?></span>
		<?php } elseif (!$h['live']) { ?>
			<span class="br-spoils"><?=$this->getText ('noLoot')?></span>
		<?php } ?>
	</div>

	<div class="ui-tabs">
		<?php foreach (array ('summary', 'replay', 'chronicle') as $v) { ?>
			<a class="<?= $v === $view ? 'active' : '' ?>" href="javascript:void(0);" onclick="<?=$link (array ('view' => $v))?>"><?=$this->getText ('view_' . $v)?></a>
		<?php } ?>
	</div>
	<div class="ui-tab-panel">

	<?php if ($view === 'replay') { ?>
		<?php if ($replay['live']) { ?><p class="maybe ui-msg"><?=$this->getText ('liveNote')?></p><?php } ?>
		<div class="br-replay" data-replay="<?=htmlspecialchars (json_encode ($replay), ENT_QUOTES)?>">
			<div class="br-field"><div class="br-slots"></div></div>
			<div class="br-transport">
				<button type="button" class="ui-btn br-prev" title="<?=$this->getText ('previous')?>">‹</button>
				<button type="button" class="ui-btn primary br-play"><?=$this->getText ('play')?></button>
				<button type="button" class="ui-btn br-next" title="<?=$this->getText ('next')?>">›</button>
				<div class="br-scrub"><div class="br-segs"></div><div class="br-names"></div></div>
				<button type="button" class="ui-btn br-speed" title="<?=$this->getText ('speed')?>">1×</button>
			</div>
			<div class="ui-box br-caption"><strong class="br-ph"></strong> <span class="br-tx"></span> <span class="br-kills"></span></div>
			<p class="br-legend ui-muted"><span class="br-dot us"></span> <?=$this->getText ('legend_yours')?> &nbsp; <span class="br-dot them"></span> <?=$this->getText ('legend_theirs')?></p>
		</div>
	<?php } ?>

	<?php if ($view === 'summary' || $view === 'replay') { ?>
		<div class="ui-columns br-armies">
			<?php foreach ($armies as $side) { ?>
				<div class="ui-box br-side <?=$side['side']?>">
					<div class="br-sidehead">
						<strong><?=$side['label']?></strong>
						<?php if ($side['died'] > 0) { ?>
							<strong class="<?= $side['side'] === 'us' ? 'ui-bad' : 'ui-good' ?>">−<?=Dolumar_Battle_ReportView::number ($side['died'])?></strong>
						<?php } ?>
					</div>
					<?php if (count ($side['rows']) === 0) { ?>
						<p class="ui-muted"><?=$this->getText ('noTroops')?></p>
					<?php } ?>
					<?php foreach ($side['rows'] as $r) { ?>
						<div class="br-reg <?= $r['fate'] === 'wiped' ? 'dead' : '' ?>">
							<?php if ($r['img']) { ?><img src="<?=$r['img']?>" alt="" title="<?=$r['unitName']?>" /><?php } ?>
							<div class="br-n">
								<?=$r['name']?>
								<div class="ui-bar"><i style="width: <?=$r['percent']?>%;"></i></div>
								<?=$r['leftText']?>, <span class="<?= $r['fate'] === 'held' ? 'ui-good' : 'ui-bad' ?>"><?=$r['fateText']?></span>
								<?php if ($r['support']) { ?><br /><span class="ui-muted"><?=$r['support']?></span><?php } ?>
							</div>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
	<?php } ?>

	<?php if ($view === 'summary') { ?>
		<h3><?=$this->getText ('moments')?></h3>
		<?php if (count ($moments) === 0) { ?>
			<p class="ui-muted"><?=$this->getText ('moment_none')?></p>
		<?php } ?>
		<?php foreach ($moments as $m) { ?>
			<a class="ui-card br-moment" href="javascript:void(0);" onclick="<?=$link (array ('view' => 'replay', 'log' => $m['event']))?>">
				<span class="br-mtx"><strong><?=$m['label']?></strong><br /><?=$m['text']?></span>
				<span class="ui-card-go">›</span>
			</a>
		<?php } ?>

		<?php if ($change) { ?>
			<div class="information ui-msg"><strong><?=$this->getText ('change')?></strong><br /><?=$change?></div>
		<?php } ?>
	<?php } ?>

	<?php if ($view !== 'chronicle') { ?>
		<div class="br-actions">
			<?php if ($h['opponentId'] > 0) { ?>
				<a class="ui-btn primary" href="javascript:void(0);" onclick="windowAction(this, {'target': <?=intval ($h['opponentId'])?>});"><?=$this->getText ($isDefender ? 'strikeBack' : 'attackAgain')?></a>
			<?php } ?>
			<?php if ($isDefender) { ?>
				<a href="javascript:void(0);" onclick="openWindow('Formation', {'vid': <?=$vid?>});"><?=$this->getText ('formation')?></a>
			<?php } elseif ($view === 'summary') { ?>
				<a href="javascript:void(0);" onclick="<?=$link (array ('view' => 'replay'))?>"><?=$this->getText ('replay')?></a>
			<?php } ?>
		</div>
	<?php } ?>

	<?php if ($view === 'summary') { ?>
		<details class="br-log">
			<summary><strong><?=$this->getText ('fightlog')?></strong> <span class="ui-muted"><?=$this->putIntoText ($this->getText ('events'), array ($eventCount))?></span></summary>
			<?php foreach ($groups as $g) { ?>
				<div class="br-phase"><strong><?=$g['label']?></strong><span class="ui-muted"><?=$g['time']?></span></div>
				<?php foreach ($g['rows'] as $r) { ?>
					<div class="br-logrow">
						<?php if ($r['img']) { ?><img src="<?=$r['img']?>" alt="" /><?php } else { ?><span class="br-noimg"></span><?php } ?>
						<span class="br-lt"><?=$r['text']?></span>
						<?php if ($r['kills'] !== null) { ?><strong class="<?= $r['side'] === 'us' ? 'ui-bad' : 'ui-good' ?>"><?=$r['kills']?></strong><?php } ?>
					</div>
				<?php } ?>
			<?php } ?>
		</details>
	<?php } ?>

	<?php if ($view === 'chronicle') { ?>
		<?php $c = $chronicle; ?>
		<div class="br-chron">
			<h2><?=$c['title']?></h2>
			<p class="ui-muted"><?=$c['dateline']?></p>
			<?php foreach ($c['chapters'] as $ch) { ?>
				<h3><?=$ch['title']?></h3>
				<?php if ($ch['note']) { ?>
					<div class="ui-box br-margin"><strong><?=$ch['note']['title']?></strong><br />
						<?=$ch['note']['usLabel']?>: <span class="ui-bad"><?=Dolumar_Battle_ReportView::number ($ch['note']['us'])?></span><br />
						<?=$ch['note']['themLabel']?>: <span class="ui-good"><?=Dolumar_Battle_ReportView::number ($ch['note']['them'])?></span>
					</div>
				<?php } ?>
				<p><?=$ch['text']?></p>
				<?php if (!empty ($ch['spoils']) && count ($h['spoils']) > 0) { ?>
					<p class="br-spoils"><?php $spoils (); ?></p>
				<?php } ?>
			<?php } ?>

			<?php if ($c['verdict']) { ?>
				<h3><?=$this->getText ('chap_verdict')?></h3>
				<p>“<?=$c['verdict']?>”</p>
			<?php } ?>
		</div>

		<?php if ($c['hero']) { ?>
			<div class="ui-box br-hero">
				<?php if ($c['hero']['img']) { ?><img src="<?=$c['hero']['img']?>" alt="" /><?php } ?>
				<div><strong><?=$c['hero']['title']?></strong><br /><span class="ui-muted"><?=$c['hero']['line']?></span></div>
			</div>
		<?php } ?>
	<?php } ?>

	</div>
</div>
