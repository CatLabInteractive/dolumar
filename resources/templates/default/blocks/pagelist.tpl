<?php
	// Every page number gets a rank: 0 for the current page, then 1, 2, ... for the pages next to it, closest first.
	// The stylesheets hide the highest ranks when the list is too narrow, so the page list always stays on one line
	// and always shows the pages closest to the current one.
	$pagelist_ranks = array ();
	foreach ($pagelist_shortcuts as $v)
	{
		$pagelist_ranks[] = $v['page'];
	}
	usort ($pagelist_ranks, function ($a, $b) use ($pagelist_curpage) {
		$da = abs ($a - $pagelist_curpage);
		$db = abs ($b - $pagelist_curpage);
		return $da == $db ? $b - $a : $da - $db;
	});
	$pagelist_ranks = array_flip ($pagelist_ranks);

	// Wider numbers need more room per button, so the stylesheets use different breakpoints per digit count.
	$pagelist_digits = min (5, max (1, strlen ((string) $pagelist_end)));
?>
<?php if (isset ($pagelist_loc)) { ?>
	<div class="pagelist <?=$pagelist_loc?> digits-<?=$pagelist_digits?>">
<?php } else { ?>
	<div class="pagelist top digits-<?=$pagelist_digits?>">
<?php } ?>

	<?php if ($pagelist_curpage > 1) { ?>
		<span class="previous first"><?=$pagelist_firstpage_url?></span>
		<span class="previous"><?=$pagelist_previous_url?></span>
	<?php } else { ?>
		<span class="previous placeholder">|</span>
	<?php } ?>

	<?php foreach ($pagelist_shortcuts as $v) { ?>
		<span class="page rank-<?=$pagelist_ranks[$v['page']]?><?php if ($pagelist_curpage == $v['page']) { echo ' active current'; } ?>"><?=$v['url']?></span>
	<?php } ?>

	<?php if ($pagelist_curpage < $pagelist_end) { ?>
		<span class="next"><?=$pagelist_nextpage_url?></span>
		<span class="next last"><?=$pagelist_lastpage_url?></span>
	<?php } else { ?>
		<span class="next placeholder">|</span>
	<?php } ?>

</div>
