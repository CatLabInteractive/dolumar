<?=$custom?>

<?php $div_id = time () . '-' . rand (0, 1000); ?>

<?php if (isset ($list_technology) && is_array ($list_technology)) { ?>
	<h3><?php echo $technology; ?></h3>
	<ul class="actions">
		<?php foreach ($list_technology as $v) { ?>
			<li><a href="javascript:void(0);" onclick="windowAction(this,{'page':'technology','technology':'<?php echo $v[1]; ?>'});"><?php echo $v[0]; ?></a></li>
		<?php } ?>
	</ul>
<?php } ?>

<?php if ($showOptions) { ?>
	<h3><?=$overview?></h3>
	<ul class="actions">
		<?php
		if (isset ($upgrade))
		{
			echo '<li><a href="javascript:void(0);" onclick="windowAction(this,{\'page\':\'upgrade\'});">'.$upgrade.'</a></li>';
		}
		?>
		<?php if (isset ($destruct)) { ?>
			<?php echo '<li><a href="javascript:void(0);" onclick="confirmAction(this,'.$destruct_url.',\''.$confirmTxt.'\');">'.$destruct.'</a></li>'; ?>
		<?php } ?>
		<?php echo '<li><a href="javascript:void(0);" onclick="windowAction(this,{\'page\':\'general\'});">'.$general.'</a></li>'; ?>
	</ul>
<?php } ?>
