<?=$this->setTextSection ('support', 'battle');?>
<h2><?=Neuron_Core_Tools::putIntoText ($this->getText ('title'), array ('target' => $target))?></h2>
<?php include ('actionSelect.phpt'); ?>

<?php if ($hasSend) { ?>
	<p class="true"><?=$this->getText ('hasSend')?></p>
<?php } ?>

<!-- Select bunch of troops to send -->
<form onsubmit="return submitForm(this);">

	<input type="hidden" class="hidden" name="command" value="support" />
	<input type="hidden" class="hidden" name="sendSquads" value="yup" />

	<fieldset>
		<legend><?=$this->getText ('selectSquads')?></legend>
	
		<?php if (isset ($list_squads)) { ?>
		<ol>
			<?php foreach ($list_squads as $v) { ?>
				<li>
					<input type="checkbox" id="<?=$v['id']?>" name="squad_<?=$v['id']?>" class="checkbox" value="yup" />
					<label class="checkbox" for="<?=$v['id']?>"><?=$v['sName']?></label>
				</li>
			<?php } ?>
		</ol>
		<?php } else { ?>
			<p class="false"><?=$this->getText ('noSquads'); ?></p>
		<?php } ?>
		
		<button type="submit"><?=$this->getText ('support')?></button>
	</fieldset>
</form>
