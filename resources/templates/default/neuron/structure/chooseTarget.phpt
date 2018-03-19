<?php $this->setTextSection ('chooseTarget', 'main'); ?>

<?php if (isset ($external_error)) { ?>
	<p class="false"><?=$this->getText ('error_' . $external_error); ?>sdf</p>
<?php } ?>

<h3><?=$this->getText('title');?></h3>

<?php if ($canTargetSelf) { ?>
	<?php $toSelf = $this->getClickTo ('toTargetSelf'); ?>
	<p><?=$toSelf[0]?><a href="javascript:void(0);" onclick='windowAction(this,<?=(json_encode (array_merge ($input, array ('target'=> $vid)))); ?>);'><?=$toSelf[1]?></a><?=$toSelf[2]?></p>
<?php } ?>

<form onsubmit="return submitForm(this);">
	<fieldset>
		<?php foreach ($input as $k => $v) { ?>
			<input type="hidden" class="hidden" name="<?=$k?>" value="<?=$v?>" />
		<?php } ?>
	
		<label><?=$this->getText('village');?>:</label>
		<input type="text" name="sVillageName" value="<?=$query?>" />
		<button type="submit"><?=$this->getText('submit')?></button>
	</fieldset>
</form>

<?php if (isset ($hasSearched)) { ?>

	<h3><?=$this->getText ('results');?></h3>
	<?php if (isset ($list_results)) { ?>
		<table>
			<tr>
				<th><?=$this->getText ('vname')?></th>
				<th style="text-align: center; width: 33%;"><?=$this->getText ('location')?></th>
			</tr>
			<?php foreach ($list_results as $v) { ?>
				<tr>
					<td>
						<a href="javascript:void(0);" onclick='windowAction(this,<?=(json_encode (array_merge ($input, array ('target'=> $v['id'],'sVillageName' => $query)))); ?>);'><?=$v['name']?></a>
					</td>
					<td style="text-align: center;"><?=$v['location']?></td>
				</tr>
			<?php } ?>
		</table>
	<?php } else { ?>
		<p class="false"><?=$this->getText('noresults');?></p>
	<?php } ?>
	
<?php } elseif (isset ($list_results)) { ?>
	<h3><?=$this->getText ('popular')?></h3>
	<table>
		<tr>
			<th><?=$this->getText ('vname')?></th>
			<th style="text-align: center; width: 33%;"><?=$this->getText ('location')?></th>
		</tr>
		<?php foreach ($list_results as $v) { ?>
			<tr>
				<td>
					<a href="javascript:void(0);" onclick='windowAction(this,<?=(json_encode (array_merge ($input, array ('target'=> $v['id'],'sVillageName' => $query)))); ?>);'><?=$v['name']?></a>
				</td>
				<td style="text-align: center;"><?=$v['location']?></td>
			</tr>
		<?php } ?>
	</table>
<?php } ?>

<?php if (isset ($returnUrl)) { ?>
	<p><a href="javascript:void(0);" onclick="windowAction(this,<?=$returnUrl?>);"><?=$returnText?></a></p>
<?php } ?>
