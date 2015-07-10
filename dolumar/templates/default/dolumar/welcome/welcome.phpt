<?php $this->setTextSection ('welcome', 'welcome'); ?>

<p>
	<?=$this->putIntoText ($this->getText('welcome'), array ('nickname' => $nickname)); ?><br />
	<?=$this->putIntoText 
	(
		$this->getText ('unreadmessages'), 
		array 
		(
			'inbox' => '<a href="javascript:void(0);" onclick="openWindow(\'messages\');">' . $this->getText ('inbox') . '</a>',
			'amount' => $inbox
		)
	);?>
</p>

<?php if (isset ($headline)) { ?>
	<div class="fancybox">
		<?=$headline?>
	</div>
<?php } ?>

<h3><?=$this->getText ('clanlogs')?></h3>
<?php if (isset ($list_logs)) { ?>
	<div class="fancybox">
		<table>
			<?php foreach ($list_logs as $v) { ?>
				<tr>
					<!--<td class="date"><?=$v['date']?></td>-->
					<td><?=$v['text']?></td>
				</tr>
			<?php } ?>
		</table>
	</div>
<?php } elseif (!$hasclan) { ?>
	<p class="false">
		<?=
			$this->putIntoText
			(
				$this->getText ('noclan'),
				array
				(
					'join' => '<a href="javascript:void(0);" onclick="openWindow(\'clan\');">'.$this->getText ('joinclan').'</a>'
				)
			); 
		?>
	</p>
<?php } ?>

<?php if (isset ($list_gamenews)) { ?>
	<h3><?=$this->getText ('gamenews')?></h3>
	<div class="fancybox">
		<table>
			<?php foreach ($list_gamenews as $v) { ?>
				<tr>
					<td class="date"><?=$v['date']?></td>
					<td><a href="<?=$v['url']?>" target="_BLANK"><?=$v['title']?></a></td>
				</tr>
			<?php } ?>
		</table>
	</div>
<?php } ?>

<h3><?=$this->getText ('premiumtitle')?></h3>

<?php if (!$isPremium) { ?>
	<p class="premium">
		<span class="premium-icon" />
		<?=$this->putIntoText
		(
			$this->getText ('nopremium'),
			array
			(
				'extend' => '<br /><a href="javascript:void(0);" onclick="openWindow(\'premium\')">'.$this->getText ('become').'</a>'
			)
		); ?>
	</p>
<?php } else { ?>
	<p class="premium">
		<span class="premium-icon">
		<?=$this->putIntoText
		(
			$this->getText ($isFreePremium ? 'freepremium' : 'premium'),
			array
			(
				'date' => $date,
				'extend' => '<br /><a href="javascript:void(0);" onclick="openWindow(\'premium\')">'.$this->getText ('extend').'</a>'
			)
		); ?>
		</span>
	</p>
<?php } ?>

<?php if (isset ($welcome)) { ?>
	<div class="information">
		<?=$welcome?>
	</div>
<?php } ?>

<!--<div class="rightbutton">
	<button type="submit" onclick="closeThisWindow(this);"><span><?=$this->getText ('okay'); ?></span></button>
</div>-->
