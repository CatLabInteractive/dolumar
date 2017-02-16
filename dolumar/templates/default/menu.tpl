<div id="menu">
	<ul class="left">
		<li onmouseover="this.className+=' over';" onmouseout="this.className='';">
			<a onclick="openWindow('myAccount');" href="javascript:void(0);" title="<?=$myAccount;?>" >
				<span class="navigation account"><span><?=$myAccount;?></span></span>
			</a>
		</li>
		
		<li onmouseover="this.className+=' over';" onmouseout="this.className='';">
			<a href="javascript:void(0);" onClick="openWindow('logbook', {'village':<?=$vid?>});" title="<?=$this->getText ('logbook');?>" >
				<span class="navigation logbook"><span><?=$this->getText ('logbook');?></span></span>
			</a>
			
			<?php if (isset ($list_villages)) { ?>
			<div>
				<ul>
					<?php foreach ($list_villages as $v) { ?>
					<li <?php if ($v[1] == $vid) { ?>class="active"<?php } ?>><a href="javascript:void(0);" onclick="openWindow('logbook', {'village':<?=$v[1]?>});"><?=$v[0]?></a></li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>
		</li>
		
		<li onmouseover="this.className+=' over';" onmouseout="this.className='';">
			<a href="javascript:void(0);" onclick="openWindow('build', {'vid':'<?=$vid?>'});" title="<?=$build;?>" id="build">
				<span class="navigation build"><span><?=$build;?></span></span>
			</a>
			
			<?php if (isset ($list_villages)) { ?>
			<div>
				<ul>
					<?php foreach ($list_villages as $v) { ?>
					<li <?php if ($v[1] == $vid) { ?>class="active"<?php } ?>><a href="javascript:void(0);" onclick="openWindow('build', {'vid':'<?=$v[1]?>'});"><?=$v[0]?></a></li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>
			
		</li>
		
		<li onmouseover="this.className+=' over';" onmouseout="this.className='';">
			<a href="javascript:void(0);" onclick="openWindow('economy', {'vid':'<?=$vid?>'});" title="<?=$economy;?>" id="economics">
				<span class="navigation economy"><span><?=$economy;?></span></span>
			</a>
			
			<?php if (isset ($list_villages)) { ?>
			<div>
				<ul>
					<?php foreach ($list_villages as $v) { ?>
					<li <?php if ($v[1] == $vid) { ?>class="active"<?php } ?>><a href="javascript:void(0);" onclick="openWindow('economy', {'vid':'<?=$v[1]?>'});"><?=$v[0]?></a></li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>
		</li>
		<li onmouseover="this.className+=' over';" onmouseout="this.className='';">
			<a href="javascript:void(0);" onClick="openWindow('squads', {'vid':'<?=$vid?>'});" title="<?=$units;?>" >
				<span class="navigation units"><span><?=$units;?></span></span>
			</a>
			
			<?php if (isset ($list_villages)) { ?>
			<div>
				<ul>
					<?php foreach ($list_villages as $v) { ?>
					<li <?php if ($v[1] == $vid) { ?>class="active"<?php } ?>><a href="javascript:void(0);" onclick="openWindow('squads', {'vid':'<?=$v[1]?>'});"><?=$v[0]?></a></li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>
		</li>

		<?php if (isset ($equipment)) { ?>
			<li onmouseover="this.className+=' over';" onmouseout="this.className='';">
				<a href="javascript:void(0);" onClick="openWindow('equipment', {'vid':'<?=$vid?>'});" title="<?=$equipment;?>" >
					<span class="navigation equipment"><span><?=$equipment;?></span></span>
				</a>
				
				<?php if (isset ($list_villages)) { ?>
				<div>
					<ul>
						<?php foreach ($list_villages as $v) { ?>
						<li <?php if ($v[1] == $vid) { ?>class="active"<?php } ?>><a href="javascript:void(0);" onclick="openWindow('equipment', {'vid':'<?=$v[1]?>'});"><?=$v[0]?></a></li>
						<?php } ?>
					</ul>
				</div>
				<?php } ?>
			</li>
		<?php } ?>
		
		<li onmouseover="this.className+=' over';" onmouseout="this.className='';">
			<a href="javascript:void(0);" onClick="openWindow('battle', {'vid':'<?=$vid?>'});" title="<?=$battle;?>" >
				<span class="navigation battle"><span><?=$battle;?></span></span>
			</a>
			
			<?php if (isset ($list_villages)) { ?>
			<div>
				<ul>
					<?php foreach ($list_villages as $v) { ?>
					<li <?php if ($v[1] == $vid) { ?>class="active"<?php } ?>><a href="javascript:void(0);" onclick="openWindow('battle', {'vid':'<?=$v[1]?>'});"><?=$v[0]?></a></li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>
		</li>
		
	</ul>

	<ul class="right rightMenu">

		<li>
			<a href="javascript:void(0);" onClick="openWindow('language');" title="<?=$language;?>"><img src="<?=IMAGE_URL?>flags/<?=$flag?>.gif" style="margin-top: 7px;" /></a>
		</li>

		<!--
		<li onmouseover="this.className+=' over';" onmouseout="this.className='';">
			<a href="javascript:void(0);" onClick="openWindow('gifts');" title="<?=$this->getText ('gifts');?>" >
				<span class="navigation gifts"><span><?=$this->getText ('gifts');?></span></span>
			</a>
		</li>
		-->

		<?php if (isset ($premium) && $premium >= 0) { ?>
			<li class="credits">
				<a href="javascript:void(0);" onClick="openWindow('premiumshop');">
					<span>
						<?=$premium?> Credits
					</span>
				</a>
			</li>
		<?php } ?>
		
		<li>
			<a href="javascript:void(0);" onClick="openWindow('premiumshop');" title="<?=$this->getText ('premiumshop');?>">
				<span class="navigation premiumshop"><span><?=$this->getText ('premiumshop');?></span></span>
			</a>
		</li>

		<!--
		<li onmouseover="this.className+=' over';" onmouseout="this.className='';">
			<a href="javascript:void(0);" onclick="openWindow('Bonusbuildings', {'vid':'<?=$vid?>'});" title="<?=$bonusbuild;?>" id="bonusbuild">
				<span class="navigation bonusbuild"><span><?=$build;?></span></span>
			</a>
			
			<?php if (isset ($list_villages)) { ?>
			<div>
				<ul>
					<?php foreach ($list_villages as $v) { ?>
					<li <?php if ($v[1] == $vid) { ?>class="active"<?php } ?>><a href="javascript:void(0);" onclick="openWindow('Bonusbuild', {'vid':'<?=$v[1]?>'});"><?=$v[0]?></a></li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>
			
		</li>
		-->
		
		<li>
			<a href="javascript:void(0);" onClick="openWindow('search');" title="<?=$this->getText ('search');?>">
				<span class="navigation search"><span><?=$this->getText ('search');?></span></span>
			</a>
		</li>
		
		<li onmouseover="this.className+=' over';" onmouseout="this.className='';">
			<a href="javascript:void(0);" onClick="openWindow('clan');" title="<?=$this->getText ('clan');?>">
				<span class="navigation clan"><span><?=$this->getText ('clan');?></span></span>
			</a>
			
			<?php if (isset ($list_clans)) { ?>
				<div>
					<ul>
						<?php foreach ($list_clans as $v) { ?>
							<li class="title">
								<a href="javascript:void(0);" onclick="openWindow('Clan', {'clan':<?=$v['id']?>});">
									<?=$v['name']?>
								</a>
							</li>
							<li>
								<a href="javascript:void(0);" onclick="openWindow('Clanlogs', {'clan':<?=$v['id']?>});"><?=$this->getText ('clanlogs')?></a>
							</li>
				
							<li>
								<a href="javascript:void(0);" onclick="openWindow('clanForum', {'clan':<?=$v['id']?>});"><?=$this->getText ('clanforum')?></a>
							</li>

							<li>
								<a href="javascript:void(0);" onclick="openWindow('ClanChat', {'clan':<?=$v['id']?>});"><?=$this->getText ('clanchat')?></a>
							</li>
						<?php } ?>
					</ul>
				</div>
			<?php } ?>
		</li>

		<li onmouseover="this.className+=' over';" onmouseout="this.className='';">
			<a href="javascript:void(0);" onClick="openWindow('ranking', {'vid':'<?=$vid?>'});" title="<?=$ranking;?>" >
				<span class="navigation ranking"><span><?=$ranking;?></span></span>
			</a>
			
			<?php if (isset ($list_villages)) { ?>
			<div>
				<ul>
					<?php foreach ($list_villages as $v) { ?>
					<li <?php if ($v[1] == $vid) { ?>class="active"<?php } ?>><a href="javascript:void(0);" onclick="openWindow('ranking', {'vid':'<?=$v[1]?>'});"><?=$v[0]?></a></li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>
		</li>

		<li onmouseover="this.className+=' over';" onmouseout="this.className='';">
	
			<a href="javascript:void(0);" onClick="openWindow('help');" title="<?=$help;?>" >
				<span class="navigation help"><span><?=$help;?></span></span>
			</a>

			<div>
			<ul>
				<li>
					<a href="javascript:void(0);" onclick="openWindow('help');" title="<?=$help?>">
						<?=$help?>
					</a>
				</li>
				
				<?php if (isset ($messages)) { ?>
					<li>
						<a href="javascript:openWindow('messages');" title="<?=$messages?>"><?=$messages?></a>
					</li>
				<?php } ?>

				<?php if (isset ($forum_url)) { ?>
					<li><a href="<?php echo $forum_url; ?>" target="_BLANK" title="<?=$forum?>"><?=$forum?></a></li>
				<?php } ?>

				<!--
				<li>
					<a href="javascript:void(0);" onClick="openWindow('ingameForum');" title="<?=$ingameForum;?>"><?=$ingameForum;?></a>
				</li>
			-->

				<li>
					<a href="javascript:void(0);" onClick="openWindow('chat');" title="<?=$chat;?>" >
						<?=$chat;?>
					</a>
				</li>
				
				<li>
					<a href="javascript:void(0);" onClick="openWindow('ignorelist');" title="<?=$ignorelist;?>" >
						<?=$ignorelist;?>
					</a>
				</li>

				<!--
				<?php if (isset ($invitations)) { ?>
					<li>
						<a href="javascript:void(0);" onClick="openWindow('invitations');" title="<?=$invitations;?>" >
							<?=$invitations?>
						</a>
					</li>
				<?php } ?>
				-->
				
				<li>
					<a href="javascript:void(0);" onclick="openWindow('simulator');" title="<?=$simulator?>">
						<?=$simulator?>
					</a>
				</li>
				
				<!--
				<li class="important">
					<a href="javascript:void(0);" onClick="openWindow('invite');" title="<?=$invite;?>" >
						<?=$invite?>
					</a>
				</li>
				-->

				<li>
					<a href="<?php echo CONTACT_URL; ?>" target="_BLANK" title="<?=$contact?>" >
						<?=$contact?>
					</a>
				</li>
				
				<?php if (isset ($imprint)) { ?>
					<li>
						<a href="javascript:void(0);" onclick="openWindow('Imprint');" title="<?=$imprint?>">
							<?=$imprint?>
						</a>
					</li>
				<?php } ?>

				<li>
					<a href="javascript:void(0);" onclick="openWindow('serversettings');" title="Server settings">
						Server settings
					</a>
				</li>
			</ul>
			</div>
		</li>

	</ul>
	
	<span class="left"> </span>
	<span class="center"> </span>
	<span class="right"> </span>
</div>
