<ul class="tabs">
	<li>
		<a href="javascript:void(0);" onclick="windowAction (this, {'page':'overview'});"><?=$this->getText ('overview', 'squad', 'squads'); ?></a>
	</li>

	<li <?php if ($current_tab == 'squad') { ?>class="active"<?php } ?>>
		<a href="javascript:void(0);" onclick="windowAction (this, {'page':'squad','id':<?=$squadId?>});"><?=$this->getText ('squad', 'squad', 'squads'); ?></a>
	</li>
	
	<li <?php if ($current_tab == 'addunits') { ?>class="active"<?php } ?>>
		<a href="javascript:void(0);" onclick="windowAction (this, {'page':'addUnits','id':<?=$squadId?>});"><?=$this->getText ('addUnits', 'squad', 'squads'); ?></a>
	</li>
	
	<li <?php if ($current_tab == 'removeunits') { ?>class="active"<?php } ?>>
		<a href="javascript:void(0);" onclick="windowAction (this, {'page':'removeUnits','id':<?=$squadId?>});"><?=$this->getText ('removeUnits', 'squad', 'squads'); ?></a>
	</li>
</ul>
