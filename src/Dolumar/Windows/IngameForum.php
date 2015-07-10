<?php
class Dolumar_Windows_IngameForum extends Neuron_GameServer_Windows_Window
{
	private $objForum;

	public function setSettings ()
	{		
		$this->objForum = $this->getForum ();
	
		// Window settings
		$this->setSize ('600px', '400px');
		$this->setPosition ('20px', '70px');
		$this->setTitle ($this->objForum->getTitle ());
		$this->setClass ('forumwin');
	}
	
	protected function getForum ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$login = Neuron_Core_Login::__getInstance ();

		if ($login->isLogin ())
		{
			$me = Neuron_GameServer::getPlayer ();
			$forum = new Neuron_Forum_Forum (0, 0, $me, $me->isChatModerator (), $me->isChatModerator ());
		}
		else
		{
			$forum = new Neuron_Forum_Forum (0, 0, false, false, false);
		}
		
		$forum->setTitle ($text->get ('ingameForum', 'menu', 'main'));
		
		return $forum;
	}

	public function getContent ()
	{
		if ($this->objForum)
		{
			return @$this->objForum->getHTML ($this->getInputData ());
		}
		else
		{
			return '<p class="false">Invalid Input: clan not found.</p>';
		}
	}

	public function processInput ()
	{
		$this->updateContent ();
	}
}
?>
