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
	const MAX_UPLOAD_BYTES = 2097152;
	const MAX_DIMENSION = 4096;

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
				
				if (!is_dir (SIGNS_PATH))
					@mkdir (SIGNS_PATH, 0755, true);
				
				$filename = intval ($userid).'_'.date ('dmYHis').'_'.bin2hex (random_bytes (4)).'.png';
				$saved = is_dir (SIGNS_PATH) && @imagepng ($new, SIGNS_PATH.$filename);
				if ($saved)
				{
					chmod (SIGNS_PATH.$filename, 0644);
					
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
		}
		
		return $page->parse ('dolumar/pages/admin/bonusbuilding/bonusbuilding.phpt');
	}
	
	private function getImageFromInput ($file)
	{
		// The client-supplied name and MIME type are ignored: the type is detected from the content.
		if (!is_array ($file) || !isset ($file['tmp_name'], $file['error']) || $file['error'] !== UPLOAD_ERR_OK)
			return false;

		if (!is_uploaded_file ($file['tmp_name']) || filesize ($file['tmp_name']) > self::MAX_UPLOAD_BYTES)
			return false;

		$size = @getimagesize ($file['tmp_name']);
		if (!$size || $size[0] < 1 || $size[1] < 1 || $size[0] > self::MAX_DIMENSION || $size[1] > self::MAX_DIMENSION)
			return false;

		switch ($size[2])
		{
			case IMAGETYPE_JPEG:
				return @imagecreatefromjpeg ($file['tmp_name']);

			case IMAGETYPE_PNG:
				return @imagecreatefrompng ($file['tmp_name']);

			case IMAGETYPE_GIF:
				$im = @imagecreatefromgif ($file['tmp_name']);
				if (!$im)
					return false;
				$nx = imagecreatetruecolor ($size[0], $size[1]);
				imagecopy ($nx, $im, 0, 0, 0, 0, $size[0], $size[1]);
				return $nx;
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
