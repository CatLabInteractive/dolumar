<?php $this->setTextSection ('clanportal', 'buildings'); ?>

<h3><?=$this->getText ('mission'); ?></h3>
<p>Mission: <?=$mission?></p>

<p><?=$openmission?></p>

<h3><?=$this->getText ('dispatch'); ?></h3>
<p><?=$this->getText ('sendRegiments'); ?></p>
<p><?=$this->getText ('armybundle'); ?></p>

<form method="post" onsubmit="return submitForm(this);">

	<input type="hidden" class="hidden" name="action" value="mission" />
	<input type="hidden" class="hidden" name="id" value="<?=$id?>" />

	<fieldset>
	
		<legend><?=$this->getText ('sendsquads'); ?></legend>

		<?php if (count ($units) === 0) { ?>
			<p><?=$this->getText ('nosquads'); ?></p>
		<?php } else { ?>
			
			<ol>


				<?php foreach ($units as $v) { ?>
					<li>
						<input type="checkbox" name="unit_<?=$v['id']?>" value="1" class="checkbox" id="unit_<?=$v['id']?>" />
						<label for="unit_<?=$v['id']?>" class="checkbox"><?=$v['name']?></label>
					</li>
				<?php } ?>

				<li>
					<label for="spawnpoint"><?=$this->getText ('spawnpoint');?></label>
					<select name="spawnpoint" id="spawnpoint">
						<?php foreach ($spawnpoints as $v) { ?>
							<option value="<?=$v->getId ()?>"><?=Neuron_Core_Tools::output_varchar ($v->getName ());?></option>
						<?php } ?>
					</select>
				</li>
				
				<li>
					<button type="submit"><span><?=$this->getText ('submitDispatch'); ?></span></button>
				</li>
				
			</ol>
		<?php } ?>
	
	</fieldset>
</form>

<ul class="actions">

	<li>
		<?=$openmission?>
	</li>

	<li>
		<?=$return?>
	</li>
</ul>