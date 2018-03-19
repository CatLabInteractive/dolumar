<?php $this->setTextSection ('bonusbuilding', 'premium'); ?>

<?php if (isset ($error)) { ?>
	<p class="false"><?=$error?></p>
<?php } ?>

<h3><?=$this->getText ('buildings'); ?></h3>

<?php if (isset ($list_buildings)) { ?>

	<ul class="bonusbuildings">
		<?php foreach ($list_buildings as $v) { ?>
			<li>
				<p class="credits right"><?=$this->putIntoText ($this->getText ('credits'), array ('credits' => $v['credits']))?></p>
			
				<h4><?=$v['name']?></h4>
				<div class="image">
					<a href="javascript:void(0);" onclick="<?=$v['action']?>">
						<img src="<?=$v['image_url']?>" />
					</a>
				</div>
				
				<div class="description">
					<?=$v['description']?>
					
					<a href="javascript:void(0);" onclick="<?=$v['action']?>">
						<?=$this->putIntoText
						(
							$this->getText ('build'),
							array
							(
								'building' => $v['name']
							)
						); ?>
					</a>
				</div>
			</li>
		<?php } ?>
	</ul>

<?php } else { ?>
	<p><?=$this->getText ('nobuildings'); ?></p>
<?php } ?>

<h3 style="clear: left;"><?=$this->getText ('signs'); ?></h3>
<?php
if (isset ($list_signs))
{
	foreach ($list_signs as $v)
	{
		echo '<a href="javascript:void(0);" '.
			'onclick="'.$v['action'].'">'.
			'<img src="'.$v['image_url'].'" /></a>';
	}
}
?>

<?php
echo '<p><a href="javascript:void(0);" onclick="popupWindow(\''.$upload_url.'\', 300, 200);">'.
	$this->getText ('customsign').
	'</a></p>';
?>
