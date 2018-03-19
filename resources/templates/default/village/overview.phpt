<ul>
	<?php foreach ($groups as $groupid => $group) { ?>

		<li>
			<img src="<?=$group[0]->getSmallImage ()?>" />
			<span class="building-name">
				<?=count($group)?> x 
				<a href="javascript:void(0);" onclick="$('<?=$templateID.$groupid?>').toggle();"><?=$group[0]->getName ()?></a>
			</span>
		
			<div id="<?=$templateID.$groupid?>" style="display: none;">
				<table>
					<?php foreach ($group as $building) { ?>
						<tr>
							<td class="building-name">
								<a href="javascript:void(0);" onclick="openWindow('building',{'bid':<?=$building->getId()?>});"><?=$building->getName ()?></a>
							</td>
							<td class="building-level"><?=$building->getLevel ()?></td>
							<td class="building-cors">[<?=implode($building->getLocation (), ',')?>]</td>
						</tr>
					<?php } ?>
				</table>
			</div>
		</li>

	<?php } ?>
</ul>
