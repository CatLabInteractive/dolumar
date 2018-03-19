<?php ?>

<h2><?=$this->getText ('yoursquads')?></h2>
<!--<p class="actions">
	<?=$toAdd[0]?><a href="javascript:void(0);" onclick="windowAction (this, 'page=add');"><?=$toAdd[1]?></a><?=$toAdd[2]?><br />
	<?=$toAll[0]?><a href="javascript:void(0);" onclick="openWindow ('units', {'vid':'<?=$vid?>'});"><?=$toAll[1]?></a><?=$toAll[2]?>
</p>-->

<?php if (isset ($list_squads)) { ?>
	<div class="squads">
		<?php foreach ($list_squads as $v) { ?>
				
			<div class="squad <?=$v['status']?>">
			
				<?php if ($v['isMine']) { ?>
					<p class="sidenote">
						<a href="javascript:void(0);" onclick="windowAction (this, {'page':'squad', 'id' : '<?=$v['id']?>'});"><?=$edit?></a>,
						<a href="javascript:void(0);" onclick="confirmAction (this, {'page':'overview', 'remove':<?=$v['id']?>}, '<?=$confirm?>');"><?=$remove?></a>
					</p>
				<?php } ?>
	
				<h3><?=$v['name']?></h3>
				
				<div class="fancybox">
			
				<?php if (count ($v['units']) > 0) { ?>
					<?php foreach ($v['units'] as $unit) { ?>
						
						<?php
							$status = null;
						
							switch ($v['status'])
							{
								case 'away':
								case 'rent':
									$status = '<tr>';
									$status .= '<td colspan="2">';
									$status .= Neuron_Core_Tools::putIntoText
									(
										$this->getText ($v['status']),
										array
										(
											'village' => '<a href="javascript:void(0);" onclick="openWindow (\'villageProfile\', {\'village\':'.$v['village_id'].'});">'.$v['village'].'</a>',
											'location' => '<a href="javascript:void(0);" onclick="openWindow (\'villageProfile\', {\'village\':'.$v['location_id'].'});">'.$v['location'].'</a>'
										)
									);
									$status .= '</td>';
									$status .= '</tr>';
								break;
							}
						?>
						
						<table>
						
							<tr>
							
								<td rowspan="<?=isset($status) ? '4' : '3'?>" style="width: 54px; padding: 2px;">
									<?php if ($v['isMine']) { ?>
										<a href="javascript:void(0);" onclick="windowAction (this, {'page':'squad', 'id' : '<?=$v['id']?>'});">
									<?php } ?>
										<img
											src="<?php echo $unit->getImageUrl (); ?>"
											title="<?php echo $unit->getAvailableAmount () . ' ' . Neuron_Core_Tools::output_varchar ($unit->getName ()); ?>"
										/>
									<?php if ($v['isMine']) { ?>
										</a>
									<?php } ?>
								</td>
								<td style="width: 25%;"><?=$this->getText ('units')?>:</td>
								<td><?php echo $unit->getAvailableAmount (); ?></td>
							</tr>
						
							<tr>
								<td><?=$this->getText ('morale')?>:</td>
								<td><?=$unit->getMorale ();?></td>
							</tr>
						
							<!-- Conditional! -->
							<?=$status; ?>
						
							<tr>
								<td colspan="2">
									<?php if ($v['isMine']) { ?>
										<a href="javascript:void(0);" onclick="windowAction (this, {'page':'addUnits', 'id' : <?=$v['id']?>});"><?=$this->getText ('addUnits')?></a> - 
										<a href="javascript:void(0);" onclick="windowAction (this, {'page':'removeUnits', 'id':<?=$v['id']?>});"><?=$this->getText ('removeUnits')?></a><?php  
									}
								
									if ($v['status'] == 'away' || $v['status'] == 'rent') { ?><?php if ($v['isMine']) { ?>,<?php } ?> <a href="javascript:void(0);" onclick="confirmAction (this, {'recall':<?=$v['id']?>}, '<?=str_replace ("'", "\'", $this->getText ($v['status'].'_return_con'))?>');"><?=$this->getText ($v['status'].'_return')?></a>
									<?php } ?>
								</td>
							</tr>
						</table>
					<?php } ?>
				<?php } else { ?>
					<p class="false"><?=$noUnits?></p>
				<?php } ?>
				
				</div>
			</div>
		<?php } ?>
</div>

<?php } else { ?>
	<p><?=$noSquads?></p>
<?php } ?>

<h3><?=$this->getText ('actions'); ?></h3>
<ul class="actions">
	<li>
		<a href="javascript:void(0);" onclick="windowAction (this, 'page=add');"><?=$this->getText ('addsquad'); ?></a>
	</li>
	
	<li>
		<a href="javascript:void(0);" onclick="openWindow ('units', {'vid':'<?=$vid?>'});"><?=$this->getText ('showunits'); ?></a>
	</li>
</ul>
