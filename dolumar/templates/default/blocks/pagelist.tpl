<?php if (isset ($pagelist_loc)) { ?>
	<div class="pagelist <?=$pagelist_loc?>">
<?php } else { ?>
	<div class="pagelist top">
<?php } ?>

	<?php if ($pagelist_curpage > 1) { ?>
		<span class="previous"><?=$pagelist_firstpage_url?></span>
		<span class="previous"><?=$pagelist_previous_url?></span>
	<?php } else { ?>
		<span class="previous">|</span>
	<?php } ?>
	
	<?php foreach ($pagelist_shortcuts as $v) { ?>
		<span <?php if ($pagelist_curpage == $v['page']) { echo 'class="active current"'; } ?>><?=$v['url']?></span>
	<?php } ?>

	<?php if ($pagelist_curpage < $pagelist_end) { ?>
		<span class="next"><?=$pagelist_nextpage_url?></span>
		<span class="next"><?=$pagelist_lastpage_url?></span>
	<?php } else { ?>
		<span class="next">|</span>
	<?php } ?>

</div>
