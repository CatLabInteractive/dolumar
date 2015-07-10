<?php $this->setTextSection ('imprint', 'help'); ?>

<?php if (isset ($moderators) && $hasmods) { ?>
	<h2><?=$this->getText ('moderators'); ?></h2>
	<p><?=$this->getText ('aboutmods'); ?></p>

	<?php foreach ($moderators as $k => $v) { ?>
		<?php if (count ($v) > 0) { ?>
			<h3><?=$this->getText ($k, 'imprint', 'help', $k); ?></h3>
			<?php foreach ($v as $vv) { ?>
				<ul>
					<li><?=$vv?></li>
				</ul>
			<?php } ?>
		<?php } ?>
	<?php } ?>
<?php } ?>
