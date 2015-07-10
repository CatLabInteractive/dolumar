<?php if (isset ($list_technologies)) { ?>
	<ul>
		<?php foreach ($list_technologies as $v) { ?>
			<li>&raquo; <?=$v[0]?></li>
		<?php } ?>
	</ul>
<?php } else { ?>
	<p class="false"><?=$this->getText ('noResearch', 'techview', 'village')?></p>
<?php } ?>
