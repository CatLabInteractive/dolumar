<p><?=$this->getText ('about')?></p>

<?php foreach ($units as $v) { ?>

	<?php $location = '<a href="javascript:void(0);" onclick="openWindow(\'villageProfile\',{\'village\':'.$v['location_id'].'});">'.$v['location'].'</a>'; ?>

	<div class="specialunit" >
		<h3><?=$v['name']?></h3>
		<?php if (isset ($v['moving'])) { ?>

			<!-- Unit is still moving to the new location -->
			<?=Neuron_Core_Tools::putIntoText ($this->getText ('travelling'), array ($location));?><br />
			<?=Neuron_Core_Tools::putIntoText ($this->getText ('timeLeft'), array ($v['moving']));?>
			
		<?php } else {?>

			<!-- Unit is ready for some new commands! -->
			<p>
				<?=Neuron_Core_Tools::putIntoText ($this->getText ('location'), array ($location));?><br />
				<a href="javascript:void(0);" onclick="windowAction (this,{'unit':<?=$v['id']?>,'action':'move'});"><?=$this->getText ('sendAway')?></a> | 
				<a href="javascript:void(0);" onclick="windowAction (this,{'unit':<?=$v['id']?>,'action':'cast'});"><?=$this->getText ('doAction')?></a>
			</p>
		<?php } ?>
	</div>
<?php } ?>
