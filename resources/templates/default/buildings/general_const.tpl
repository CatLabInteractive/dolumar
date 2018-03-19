<p class="false">
	<?=$txt?>
</p>

<p class="maybe">
	<?=$tl?>: <span class="counter"><?=$timeLeft?></span>
</p>

<?php if (isset ($cancel) && isset ($cancel_url) && $cancel && $confirmTxt) { ?>
<p class="maybe">
	<?=$cancel[0]?> <a href="javascript:void(0)" onclick="confirmAction(this,<?=$cancel_url?>, '<?=$confirmTxt?>');"><?=$cancel[1]?></a> <?=$cancel[2]?>
</p>
<?php } ?>

<ul class="actions">
	<?php echo '<li><a href="javascript:void(0);" onclick="windowAction(this,{\'page\':\'general\'});">'.$general.'</a></li>'; ?>
</ul>
