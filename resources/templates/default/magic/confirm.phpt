<h2><?=$this->getText('title')?></h2>

<?php if (isset ($target)) { ?>
	<p><?php echo Neuron_Core_Tools::putIntoText ($this->getText ('confirm'), array ('spell' => $spell, 'village' => $target)); ?></p>
<?php } else { ?>
	<p><?php echo Neuron_Core_Tools::putIntoText ($this->getText ('confirm_notarget'), array ('spell' => $spell)); ?></p>
<?php } ?>

<div class="fancybox">
	<p>
		<span class="title"><?=$this->getText('cost')?>:</span><br />
		<?=$cost?>
	</p>
</div>

<h3><?=$spell?></h3>
<div class="fancybox">
	<table>
		<tr>
			<td width="50%"><?=$this->getText ('stype')?>:</td>
			<td><?=$duration?></td>
		</tr>
	
		<tr>
			<td><?=$this->getText ('difficulty')?>:</td>
			<td><?=$difficulty?></td>
		</tr>
	
		<tr>
			<td><?=$this->getText ('probability')?>:</td>
			<td><?=$probability?>%</td>
		</tr>
	</table>

	<p><?=$about?></p>
</div>

<?php $action = json_encode (isset ($inputData) ? array_merge ($inputData, array ('confirm' => 'yes') ) : array ('confirm' => 'yes')); ?>

<p>
	<a href="javascript:void(0);" onclick='return windowAction (this, <?=$action?>);'><?=$toCast?></a>
</p>
