<?php $this->setTextSection ('townCenter', 'buildings'); ?>

<h3><?=$this->getText ('searchRunes'); ?></h3>
<p><?=$this->getText ('scoutLands')?></p>

<?php $pagelist_loc = 'top'; include (TEMPLATE_DIR.'blocks/pagelist.tpl'); ?>

<?php if (isset ($scoutResults) && isset ($scoutResult_isGood)) { if ($scoutResult_isGood) { ?>
		<p class="true"><?=$scoutResults?></p>
	<?php } else {?>
		<p class="false"><?=$scoutResults?></p>
<?php }} else { ?>

	<?php foreach ($scoutoptions as $scoutoption) { ?>

		<div class="fancybox halfsize">
			<h3 style="float: right;"><span class="duration"><?=$scoutoption['scoutDuration']?></span></h3>
			<h3><?php echo $scoutoption['runes'] ?> runes</h3>
			<p class="resources"><?=$scoutoption['scoutCost']?></p>

			<ul class="actions">
				<li>
					<a href="javascript:void(0);" onclick="windowAction(this,{'do':'scout','runes':'<?php echo $scoutoption['runes']; ?>'});">Scout for <?php echo $scoutoption['runes']; ?> runes</a>
				</li>
			</ul><br />
		</div>
	<?php } ?>

<?php } ?>

<ul class="actions" style="clear: both;">
	<li>
		<a href="javascript:void(0);" onclick="windowAction(this,{'do':'none'});"><?php echo $this->getText ('tooverview'); ?></a>
	</li>
</ul>