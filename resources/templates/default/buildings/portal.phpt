<?php $this->setTextSection ('portal', 'buildings'); ?>

<h3><?=$this->getText ('connections'); ?></h3>

<p>
	<?=$this->getText ('description'); ?>
</p>

<?php if (count ($targets) > 0) { ?>
	<div class="maybe">

		<?=
			$this->getText ('target')
		?>
		
		<?php asort ($targets); ?>
		
		<ul>
			<?php foreach ($targets as $v) { ?>
				<li>
					<?=$v?>
				</li>
			<?php } ?>
		</ul>
	</div>
<?php } else { ?>
	<p class="false"><?=$this->getText ('disconnected')?></p>
<?php } ?>

<?php if (isset ($timeleft)) { ?>
	<p class="false">
		<?=$this->putIntoText
		(
			$this->getText ('timeleft'),
			array
			(
				'timeleft' => $timeleft
			)
		);
		?>
	</p>
<?php } ?>