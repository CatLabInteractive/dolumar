<?php
/**
 *  Dolumar, browser based strategy game
 *  Copyright (C) 2009 Thijs Van der Schaeghe
 *  CatLab Interactive bvba, Gent, Belgium
 *  http://www.catlab.eu/
 *  http://www.dolumar.com/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

class Dolumar_Pages_Admin_Bonusbuilding extends Neuron_GameServer_Pages_Admin_Page
{
	public function getBody ()
	{		
		$myself = Neuron_GameServer::getPlayer ();
		if (!$myself->isAdmin ())
		{
			return '<p>You are not allowed to execute the commands. Only admins are.</p>';
		}
		
		$page = new Neuron_Core_Template ();
		
		$login = Neuron_Core_Login::getInstance ();
		
		if (!$login->isLogin ())
		{
			$userid = 0;
		}
		else
		{
			$userid = $login->getUserId ();
		}
		
		$text = Neuron_Core_Text::getInstance ();
		
		$content = array ();
		foreach ($text->getLanguages () as $v)
		{
			$page->addListValue ('languages', $v);
			
			$content[$v] = array
			(
				'title' => Neuron_Core_Tools::getInput ('_POST', 'title_'.$v, 'varchar'),
				'description' => Neuron_Core_Tools::getInput ('_POST', 'description_'.$v, 'varchar')
			);
		}
		
		if (isset ($_FILES['imagefile']))
		{			
			$im = $this->getImageFromInput ($_FILES['imagefile']);
			if ($im)
			{
				$new = $this->getGeneratedImage ($im);
				
				if (!is_dir (PUBLIC_PATH.'signs/'))
				{
					mkdir (PUBLIC_PATH.'signs/');
					chmod (PUBLIC_PATH.'signs/', 0755);
				}
				
				$filename = $userid.'_'.date ('dmYHis').'.png';
				imagepng ($new, PUBLIC_PATH.'signs/'.$filename);
				chmod (PUBLIC_PATH.'signs/'.$filename, 0755);
				
				$db = Neuron_DB_Database::getInstance ();
				
				$db->query
				("
					INSERT INTO
						players_tiles
					SET
						t_userid = ".intval ($userid).",
						t_imagename = 'signs/".$db->escape ($filename)."',
						t_isPublic = 1,
						t_description = '{$db->escape (json_encode ($content))}'
				");
			}
		}
		
		return $page->parse ('dolumar/pages/admin/bonusbuilding/bonusbuilding.phpt');
	}
	
	private function getImageFromInput ($file)
	{
		$ext = explode ('.', $file['name']);
		$ext = strtolower ($ext[count ($ext) - 1]);
		
		switch ($ext)
		{
			case 'jpg':
			case 'jpeg':
				return imagecreatefromjpeg ($file['tmp_name']);
			break;
			
			case 'png':
				return imagecreatefrompng ($file['tmp_name']);
			break;
			
			case 'gif':
				return imagecreatefromgif ($file['tmp_name']);
			break;
		}
		
		return false;
	}
	
	private function getGeneratedImage ($src)
	{
		$new = imagecreatefrompng (IMAGE_PATH.'tsprites/sign1.png');
		imagesavealpha ($src, true);
		return $src;
	}
}
?>
