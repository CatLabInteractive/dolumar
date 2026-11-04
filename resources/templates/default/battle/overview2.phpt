<?php
	/* Battle window, list of reports. Rows come from Dolumar_Battle_ReportView::getListRow (names already escaped).
	   Built from the ui-* kit (.ai/style-guide.md). */
	$open = function ($id, $view = null) {
		$params = array ('report' => $id);
		if ($view) { $params['view'] = $view; }
		return 'windowAction(this, ' . htmlspecialchars (json_encode ($params), ENT_QUOTES) . ');';
	};
?>
<div class="battle-report br-list">

	<div class="ui-btn-row">
		<a class="ui-btn primary" href="javascript:void(0);" onclick="windowAction(this, {'action':'attack','command':'attack'});"><?=$this->getText ('attack')?></a>
		<a class="ui-btn" href="javascript:void(0);" onclick="windowAction(this, {'action':'support','command':'support'});"><?=$this->getText ('support')?></a>
		<a class="ui-btn" href="javascript:void(0);" onclick="openWindow('Formation', {'vid':<?=intval ($vid)?>});"><?=$this->getText ('formation')?></a>
	</div>

	<?php if (count ($live) > 0) { ?>
		<h3><?=$this->getText ('fightingNow')?></h3>
		<?php foreach ($live as $r) { ?>
			<a class="ui-card br-rep live" href="javascript:void(0);" onclick="<?=$open ($r['id'], 'replay')?>">
				<img src="<?=$r['icon']?>" alt="" class="br-icon" />
				<span class="br-rt"><span class="ui-card-title"><?=$r['title']?></span><span class="ui-muted"><?=$r['line']?></span></span>
				<span class="br-watch"><?=$this->getText ('watch')?> <span class="ui-card-go">›</span></span>
			</a>
		<?php } ?>
	<?php } ?>

	<p class="br-filters">
		<?php $first = true; foreach (array ('all', 'attacks', 'defences') as $f) { ?>
			<?php if (!$first) { ?> · <?php } $first = false; ?>
			<?php if ($f === $filter) { ?>
				<strong><?=$this->getText ('filter_' . $f)?></strong>
			<?php } else { ?>
				<a href="javascript:void(0);" onclick="windowAction(this, {'overview':'true','filter':'<?=$f?>'});"><?=$this->getText ('filter_' . $f)?></a>
			<?php } ?>
		<?php } ?>
		<span class="br-count ui-muted"><?=$this->putIntoText ($this->getText ('count'), array ($total))?></span>
	</p>

	<?php if (count ($rows) === 0 && count ($live) === 0) { ?>
		<p class="ui-muted"><?=$this->getText ('noReports')?></p>
	<?php } ?>

	<?php foreach ($rows as $r) { ?>
		<a class="ui-card br-rep" href="javascript:void(0);" onclick="<?=$open ($r['id'])?>">
			<img src="<?=$r['icon']?>" alt="" class="br-icon" />
			<span class="br-rt"><span class="ui-card-title"><?=$r['title']?></span><span class="ui-muted"><?=$r['line']?></span></span>
			<span class="br-when ui-muted"><?=$r['when']?></span>
			<span class="ui-card-go">›</span>
		</a>
	<?php } ?>

	<?php $pagelist_loc = 'bottom'; include ('blocks/pagelist.tpl'); ?>
</div>
