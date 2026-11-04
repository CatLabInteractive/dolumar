<?php
	/* Battle window, list of reports. Rows come from Dolumar_Battle_ReportView::getListRow (names already escaped). */
	$open = function ($id, $view = null) {
		$params = array ('report' => $id);
		if ($view) { $params['view'] = $view; }
		return 'windowAction(this, ' . htmlspecialchars (json_encode ($params), ENT_QUOTES) . ');';
	};
?>
<div class="battle-report br-list">

	<div class="br-actions3">
		<a class="action-button primary" href="javascript:void(0);" onclick="windowAction(this, {'action':'attack','command':'attack'});">⚔ <?=$this->getText ('attack')?></a>
		<a class="action-button" href="javascript:void(0);" onclick="windowAction(this, {'action':'support','command':'support'});">🛡 <?=$this->getText ('support')?></a>
		<a class="action-button" href="javascript:void(0);" onclick="openWindow('Formation', {'vid':<?=intval ($vid)?>});">⚑ <?=$this->getText ('formation')?></a>
	</div>

	<?php if (count ($live) > 0) { ?>
		<h3 class="br-h"><span class="br-live-dot"></span> <?=$this->getText ('fightingNow')?></h3>
		<?php foreach ($live as $r) { ?>
			<a class="br-rep live" href="javascript:void(0);" onclick="<?=$open ($r['id'], 'replay')?>">
				<span class="br-badge live"><?=$r['icon']?></span>
				<span class="br-rt"><span class="br-t"><?=$r['title']?></span><span class="br-l"><?=$r['line']?></span></span>
				<span class="action-button br-watch"><?=$this->getText ('watch')?></span>
			</a>
		<?php } ?>
	<?php } ?>

	<div class="br-filters">
		<?php foreach (array ('all', 'attacks', 'defences') as $f) { ?>
			<a class="br-chip <?= $f === $filter ? 'on' : '' ?>" href="javascript:void(0);" onclick="windowAction(this, {'overview':'true','filter':'<?=$f?>'});"><?=$this->getText ('filter_' . $f)?></a>
		<?php } ?>
		<span class="br-count"><?=$this->putIntoText ($this->getText ('count'), array ($total))?></span>
	</div>

	<?php if (count ($rows) === 0 && count ($live) === 0) { ?>
		<p class="br-muted"><?=$this->getText ('noReports')?></p>
	<?php } ?>

	<?php foreach ($rows as $r) { ?>
		<a class="br-rep" href="javascript:void(0);" onclick="<?=$open ($r['id'])?>">
			<span class="br-badge <?=$r['badge']?>"><?=$r['icon']?></span>
			<span class="br-rt"><span class="br-t"><?=$r['title']?></span><span class="br-l"><?=$r['line']?></span></span>
			<span class="br-when"><?=$r['when']?><span class="br-chev">›</span></span>
		</a>
	<?php } ?>

	<?php $pagelist_loc = 'bottom'; include ('blocks/pagelist.tpl'); ?>
</div>
