<?php $this->setTextSection ('clanportal', 'buildings'); ?>

<h3><?=$this->getText ('missions'); ?></h3>
<p><?=$this->getText ('aboutmissions'); ?></p>

<?php if (isset ($missions) && count ($missions) > 0) { ?>

	<ul class="missions">
		<?php foreach ($missions as $v) { ?>
			<li>
				<?=$v?>
			</li>
		<?php } ?>
	</ul>

<?php } else { ?>
	<p><?=$this->getText ('nomissions'); ?></p>
<?php } ?>