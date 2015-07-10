<?php
class Dolumar_Windows_ClanForum extends Dolumar_Windows_IngameForum
{
	protected function getForum ()
	{
		$requestData = $this->getRequestData ();
		
		if (isset ($requestData['clan']))
		{
			$clan = new Dolumar_Players_Clan ($requestData['clan']);
			if ($clan)
			{
				$login = Neuron_Core_Login::__getInstance ();

				if ($login->isLogin ())
				{
					$me = Neuron_GameServer::getPlayer ();
					
					$isMember = $clan->isMember ($me);
					$isModerator = $clan->isModerator ($me);
					
					//__construct ($iForumType, $iForumId, $objUser = false, $bCanSeeAll = false, $bIsModerator = false)
					$forum = new Neuron_Forum_Forum (1, $clan->getId (), $me, $isMember, $isModerator);
				}
				else
				{
					$forum = new Neuron_Forum_Forum (1, $clan->getId (), false, false, false);
				}
				
				// Fetch thze title
				$text = Neuron_Core_Text::__getInstance ();
				
				$forum->setTitle 
				(
					Neuron_Core_Tools::putIntoText 
					(
						$text->get ('title', 'forum', 'clan'),
						array
						(
							'clan' => Neuron_Core_Tools::output_varchar ($clan->getName ())
						)
					)
				);
		
				return $forum;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
}
?>
