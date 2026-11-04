<?php
	/* Battle report: header, view switch and one of the three views (summary, replay, chronicle).
	   Data comes from Dolumar_Battle_ReportView; names in the strings are already escaped. */
	$h = $header;
	$link = function ($params) use ($reportid) {
		$params['report'] = $reportid;
		return 'windowAction(this, ' . htmlspecialchars (json_encode ($params), ENT_QUOTES) . ');';
	};
	$verdictClass = $h['live'] ? 'live' : ($h['win'] ? 'win' : 'loss');
	if (!$h['live'] && $h['side'] === 'defence' && $h['win']) { $verdictClass = 'held'; }
?>
<div class="battle-report br-view-<?=$view?>">

	<p class="br-back"><a href="javascript:void(0);" onclick="windowAction(this,'overview=true');">‹ <?=$this->getText ('allBattles')?></a></p>

	<div class="br-verdict <?=$verdictClass?>">
		<span class="br-seal"><?= $h['side'] === 'attack' ? '⚔' : '🛡' ?></span>
		<div class="br-big"><?=$h['title']?></div>
		<div class="br-sub"><?=$h['subtitle']?></div>
		<div class="br-sub br-when"><?=$h['when']?></div>
		<?php if ($h['percent'] !== null) { ?>
			<div class="br-meter"><i style="width: <?=intval ($h['percent'])?>%;"></i></div>
		<?php } ?>
	</div>

	<?php if (count ($h['spoils']) > 0) { ?>
		<div class="br-spoils">
			<?php foreach ($h['spoils'] as $s) { ?>
				<span class="br-chip"><span class="<?=$s['class']?>" title="<?=$s['title']?>"></span><?=$s['amount']?></span>
			<?php } ?>
		</div>
	<?php } elseif (!$h['live']) { ?>
		<p class="br-muted"><?=$this->getText ('noLoot')?></p>
	<?php } ?>

	<ul class="br-switch">
		<?php foreach (array ('summary', 'replay', 'chronicle') as $v) { ?>
			<li class="<?= $v === $view ? 'active' : '' ?>"><a href="javascript:void(0);" onclick="<?=$link (array ('view' => $v))?>"><?=$this->getText ('view_' . $v)?></a></li>
		<?php } ?>
	</ul>

	<?php if ($view === 'summary' || $view === 'replay') { ?>
		<?php if ($view === 'replay') { ?>
			<div class="br-replay" data-replay="<?=htmlspecialchars (json_encode ($replay), ENT_QUOTES)?>">
				<div class="br-field">
					<?php if ($replay['live']) { ?><span class="br-livebadge">● <?=$this->getText ('live')?></span><?php } ?>
					<div class="br-slots"></div>
				</div>
				<div class="br-transport">
					<button type="button" class="br-b br-prev" title="<?=$this->getText ('previous')?>">⏮</button>
					<button type="button" class="br-b br-play" title="<?=$this->getText ('play')?>">▶</button>
					<button type="button" class="br-b br-next" title="<?=$this->getText ('next')?>">⏭</button>
					<div class="br-scrub"><div class="br-segs"></div><div class="br-names"></div></div>
					<button type="button" class="br-chip br-speed" title="<?=$this->getText ('speed')?>">1×</button>
				</div>
				<div class="br-caption"><span class="br-ph"></span><span class="br-tx"></span><span class="br-kills"></span></div>
				<p class="br-legend"><span class="br-dot us"></span> <?=$this->getText ('legend_yours')?> <span class="br-dot them"></span> <?=$this->getText ('legend_theirs')?></p>
				<?php if ($replay['live']) { ?><p class="br-note"><?=$this->getText ('liveNote')?></p><?php } ?>
			</div>
		<?php } ?>

		<h3 class="br-h"><?=$this->getText ('armies')?></h3>
		<div class="br-armies">
			<?php foreach ($armies as $side) { ?>
				<div class="br-side <?=$side['side']?>">
					<h4><span><?=$side['label']?></span><span class="br-loss"><?= $side['died'] > 0 ? '−' . Dolumar_Battle_ReportView::number ($side['died']) : '' ?></span></h4>
					<?php if (count ($side['rows']) === 0) { ?>
						<p class="br-muted"><?=$this->getText ('noTroops')?></p>
					<?php } ?>
					<?php foreach ($side['rows'] as $r) { ?>
						<div class="br-reg <?= $r['fate'] === 'wiped' ? 'dead' : '' ?>">
							<?php if ($r['img']) { ?><img src="<?=$r['img']?>" alt="" title="<?=$r['unitName']?>" /><?php } ?>
							<div class="br-n">
								<b><?=$r['name']?></b>
								<div class="br-bar"><i style="width: <?=$r['percent']?>%;"></i><u style="width: <?=100 - $r['percent']?>%;"></u></div>
								<?=$r['leftText']?> <span class="br-tag <?=$r['fate']?>"><?=$r['fateText']?></span>
								<?php if ($r['support']) { ?><br /><span class="br-muted"><?=$r['support']?></span><?php } ?>
							</div>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
	<?php } ?>

	<?php if ($view === 'summary') { ?>
		<h3 class="br-h"><?=$this->getText ('moments')?></h3>
		<?php if (count ($moments) === 0) { ?>
			<p class="br-muted"><?=$this->getText ('moment_none')?></p>
		<?php } ?>
		<?php foreach ($moments as $m) { ?>
			<a class="br-moment" href="javascript:void(0);" onclick="<?=$link (array ('view' => 'replay', 'log' => $m['event']))?>">
				<span class="br-ic <?=$m['icon']?>"></span>
				<span class="br-mtx"><span class="br-ph"><?=$m['label']?></span><?=$m['text']?></span>
				<span class="br-go">›</span>
			</a>
		<?php } ?>

		<?php if ($change) { ?>
			<div class="br-moment br-change">
				<span class="br-ic change"></span>
				<span class="br-mtx"><span class="br-ph"><?=$this->getText ('change')?></span><?=$change?></span>
			</div>
		<?php } ?>
	<?php } ?>

	<?php if ($view !== 'chronicle') { ?>
		<div class="br-actions">
			<?php if ($h['opponentId'] > 0) { ?>
				<a class="action-button primary" href="javascript:void(0);" onclick="windowAction(this, {'target': <?=intval ($h['opponentId'])?>});">⚔ <?=$this->getText ($isDefender ? 'strikeBack' : 'attackAgain')?></a>
			<?php } ?>
			<?php if ($isDefender) { ?>
				<a class="action-button" href="javascript:void(0);" onclick="openWindow('Formation', {'vid': <?=$vid?>});">⚑ <?=$this->getText ('formation')?></a>
			<?php } elseif ($view === 'summary') { ?>
				<a class="action-button" href="javascript:void(0);" onclick="<?=$link (array ('view' => 'replay'))?>">▶ <?=$this->getText ('replay')?></a>
			<?php } ?>
		</div>
	<?php } ?>

	<?php if ($view === 'summary') { ?>
		<details class="br-log">
			<summary><?=$this->getText ('fightlog')?> <span class="br-muted"><?=$this->putIntoText ($this->getText ('events'), array ($eventCount))?></span></summary>
			<?php foreach ($groups as $g) { ?>
				<div class="br-phase"><span><?=$g['label']?></span><span><?=$g['time']?></span></div>
				<?php foreach ($g['rows'] as $r) { ?>
					<div class="br-logrow">
						<?php if ($r['img']) { ?><img src="<?=$r['img']?>" alt="" /><?php } else { ?><span class="br-noimg"></span><?php } ?>
						<span class="br-lt"><?=$r['text']?></span>
						<?php if ($r['kills'] !== null) { ?><span class="br-dmg <?=$r['side']?>"><?=$r['kills']?></span><?php } ?>
					</div>
				<?php } ?>
			<?php } ?>
		</details>
	<?php } ?>

	<?php if ($view === 'chronicle') { ?>
		<?php $c = $chronicle; ?>
		<div class="br-chron">
			<h2><?=$c['title']?></h2>
			<div class="br-dateline"><?=$c['dateline']?></div>
			<div class="br-orn">❦ ❦ ❦</div>
			<?php $first = true; foreach ($c['chapters'] as $ch) { ?>
				<div class="br-chap"><?=$ch['title']?></div>
				<?php if ($ch['note']) { ?>
					<div class="br-margin"><?=$ch['note']['title']?><br />
						<?=$ch['note']['usLabel']?>: <b class="us"><?=Dolumar_Battle_ReportView::number ($ch['note']['us'])?></b><br />
						<?=$ch['note']['themLabel']?>: <b class="them"><?=Dolumar_Battle_ReportView::number ($ch['note']['them'])?></b>
					</div>
				<?php } ?>
				<p class="<?= $first ? 'first' : '' ?>"><?=$ch['text']?></p>
				<?php if (!empty ($ch['spoils']) && count ($h['spoils']) > 0) { ?>
					<div class="br-spoils center">
						<?php foreach ($h['spoils'] as $s) { ?>
							<span class="br-chip"><span class="<?=$s['class']?>" title="<?=$s['title']?>"></span><?=$s['amount']?></span>
						<?php } ?>
					</div>
				<?php } ?>
			<?php $first = false; } ?>

			<?php if ($c['verdict']) { ?>
				<div class="br-orn">❦</div>
				<div class="br-chap"><?=$this->getText ('chap_verdict')?></div>
				<p class="br-verdictline">“<?=$c['verdict']?>”</p>
				<div class="br-orn">❦</div>
			<?php } ?>
		</div>

		<?php if ($c['hero']) { ?>
			<div class="br-hero">
				<?php if ($c['hero']['img']) { ?><img src="<?=$c['hero']['img']?>" alt="" /><?php } ?>
				<div><div class="br-heroname"><?=$c['hero']['title']?></div><div class="br-muted"><?=$c['hero']['line']?></div></div>
			</div>
		<?php } ?>
	<?php } ?>

</div>
