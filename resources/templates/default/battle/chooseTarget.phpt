<?php
	/* Battle window: choose a target for an attack or support. Rows come from Dolumar_Battle_TargetList (names
	   already escaped). Tabs: suggested (attacks only, when there is something to suggest), nearby, recent, search.
	   Built from the ui-* kit (.ai/style-guide.md). */
	$go = function ($params) use ($mode) {
		$params = array_merge (array ('action' => $mode, 'command' => $mode), $params);
		return 'windowAction(this, ' . htmlspecialchars (json_encode ($params), ENT_QUOTES) . ');';
	};

	$card = function ($r) use ($go, $mode, $premium) {
		$lines = array ();

		// Who and how big.
		$who = $r['owner'];
		if ($r['race'] !== '') {
			$who .= ' · ' . $r['race'];
		}
		$who .= ' · ' . $this->putIntoText ($this->getText ('networth'), array ('networth' => $r['networth']));
		$lines[] = '<span class="bt-line ui-muted">' . $who . '</span>';

		// How far, and what it costs.
		$far = array ();
		if ($r['leagues'] !== null) {
			$far[] = $this->putIntoText ($this->getText ('leagues'), array ('distance' => $r['leagues']));
		}
		if ($r['honour'] !== null) {
			$far[] = $r['honour'] > 0
				? '<span class="ui-bad">' . $this->putIntoText ($this->getText ('honourLoss'), array ('honour' => $r['honour'])) . '</span>'
				: '<span class="ui-good">' . $this->getText ('noHonourLoss') . '</span>';
		}
		if (count ($far) > 0) {
			$lines[] = '<span class="bt-line">' . implode (' · ', $far) . '</span>';
		}

		if ($r['note']) {
			$lines[] = '<span class="bt-line bt-note">' . $r['note'] . '</span>';
		}

		if (!$r['canPick']) {
			$lines[] = '<span class="bt-line bt-why">' . $this->getText ('status_' . $r['status']) . '</span>';
		}

		$img = '<img src="' . IMAGE_URL . $r['image'] . '" alt="" />';
		$body = '<span class="bt-rt"><span class="ui-card-title">' . $r['name'] . '</span>' . implode ('', $lines) . '</span>';

		if ($r['canPick']) {
			return '<a class="ui-card bt-target" href="javascript:void(0);" onclick="' . $go (array ('target' => $r['id'])) . '">'
				. $img . $body . '<span class="ui-card-go">›</span></a>';
		}
		return '<div class="ui-card bt-target off">' . $img . $body . '</div>';
	};

	$upgrade = function ($key) {
		return '<div class="premium ui-msg bt-premium"><p>' . $this->getText ($key) . '</p>'
			. '<p class="bt-premium-go"><a class="ui-btn primary" href="javascript:void(0);" onclick="openWindow(\'Premium\');">'
			. $this->getText ('upgrade') . '</a></p></div>';
	};
?>
<div class="battle-report bt-pick">

	<p class="br-back"><a href="javascript:void(0);" onclick="windowAction(this, {'overview':'true'});">‹ <?=$this->getText ('allBattles', 'report2')?></a></p>

	<h2><?=$this->getText ('title_' . $mode)?></h2>

	<div class="ui-tabs">
		<?php foreach ($tabs as $t) { ?>
			<a href="javascript:void(0);"<?php if ($t === $tab) { ?> class="active"<?php } else { ?> onclick="<?=$go (array ('tab' => $t))?>"<?php } ?>><?=$this->getText ('tab_' . $t)?></a>
		<?php } ?>
	</div>

	<div class="ui-tab-panel">

		<?php if ($tab === 'suggested') { ?>
			<p class="ui-muted bt-intro"><?=$this->getText ('suggestedIntro')?></p>
		<?php } ?>

		<?php if ($tab === 'search') { ?>
			<form onsubmit="return submitForm(this);" class="bt-search">
				<input type="hidden" class="hidden" name="action" value="<?=$mode?>" />
				<input type="hidden" class="hidden" name="command" value="<?=$mode?>" />
				<input type="hidden" class="hidden" name="tab" value="search" />
				<div class="ui-field">
					<label><?=$this->getText ('village')?></label>
					<input type="text" name="sVillageName" value="<?=$query?>" />
				</div>
				<button type="submit" class="ui-btn"><?=$this->getText ('search')?></button>
			</form>
		<?php } ?>

		<?php foreach ($sections as $s) { ?>

			<?php if ($s['title']) { ?>
				<h3><?=$this->getText ('section_' . $s['title'])?></h3>
			<?php } ?>

			<?php if (!empty ($s['nearby']) && $premium) { ?>
				<p class="br-filters">
					<?php $first = true; foreach (array ('all', 'players', 'tribes') as $f) { ?>
						<?php if (!$first) { ?> · <?php } $first = false; ?>
						<?php if ($f === $filter) { ?>
							<strong><?=$this->getText ('filter_' . $f)?></strong>
						<?php } else { ?>
							<a href="javascript:void(0);" onclick="<?=$go (array ('tab' => 'nearby', 'filter' => $f))?>"><?=$this->getText ('filter_' . $f)?></a>
						<?php } ?>
					<?php } ?>
					<span class="br-count ui-muted"><?=$this->putIntoText ($this->getText ('closest'), array ('amount' => $nearbyCount))?></span>
				</p>
			<?php } ?>

			<?php if (!empty ($s['nearby']) && !$premium) { ?>
				<?=$upgrade ('premiumNearby')?>
			<?php } elseif (count ($s['rows']) > 0) { ?>
				<?php foreach ($s['rows'] as $r) { echo $card ($r); } ?>
			<?php } elseif ($tab !== 'search' || !empty ($s['search'])) { ?>
				<p class="ui-muted"><?=$this->getText ('empty_' . ($s['title'] ? $s['title'] : $tab))?></p>
			<?php } ?>

		<?php } ?>

		<?php if (!$premium && $mode === 'attack' && $tab !== 'nearby') { ?>
			<p class="bt-honour-hint ui-muted"><?=$this->getText ('premiumHonour')?> <a href="javascript:void(0);" onclick="openWindow('Premium');"><?=$this->getText ('upgradeShort')?></a></p>
		<?php } ?>

	</div>
</div>
