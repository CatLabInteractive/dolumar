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

class Dolumar_Pages_Customsign extends Neuron_GameServer_Pages_Page
{
	const ZOOM = 3;
	const KORREL = 0;

	public function getBody ()
	{
		$action = $unit = $this->getParameter (2);
		
		switch ($action)
		{
			case 'list':
				return $this->getAllImages ();
			break;
		}
	
		$login = Neuron_Core_Login::getInstance ();
		
		if (!$login->isLogin ())
		{
			$userid = 0;
		}
		else
		{
			$userid = $login->getUserId ();
		}
	
		if (isset ($_FILES['uploadfile']))
		{			
			$im = $this->getImageFromInput ($_FILES['uploadfile']);
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
						t_imagename = 'signs/".$db->escape ($filename)."'
				");
				
				// Show confirm screen
				$page = new Neuron_Core_Template ();
				
				$page->set ('img_url', PUBLIC_URL.'signs/'.$filename);
				
				return $page->parse ('pages/customsign/image.phpt');
			}
		}
	
		$page = new Neuron_Core_Template ();
		
		$page->set ('action', ABSOLUTE_URL.'page/customsign/');
		
		return $page->parse ('pages/customsign/upload.phpt');
	}
	
	private function getAllImages ()
	{
		$page = new Neuron_Core_Template ();
		
		$db = Neuron_DB_Database::getInstance ();
		
		$data = $db->query
		("
			SELECT
				*
			FROM
				players_tiles
			ORDER BY
				t_id DESC
		");
		
		foreach ($data as $v)
		{
			$page->addListValue
			(
				'tiles',
				array
				(
					'src' => PUBLIC_URL.$v['t_imagename']
				)
			);
		}
		
		return $page->parse ('pages/customsign/list.phpt');
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
				$size = getimagesize ($file['tmp_name']);
				$im = imagecreatefromgif ($file['tmp_name']);
				$nx = imagecreatetruecolor ($size[0], $size[1]);
				imagecopyresampled ($nx, $im, 0, 0, 0, 0, $size[0], $size[1], $size[0], $size[0]);
				return $nx;
			break;
		}
		
		return false;
	}
	
	private function getColorAt ($src, $new, $cx, $cy, $size, $parts)
	{
		$rgb = imagecolorat ($src, $cx, $cy);
		
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;
		
		$i = 1;
		
		if (self::KORREL == 0)
		{
			return array ($r, $g, $b);
		}
		
		$margin = max ($parts) / (2 * self::KORREL);
		
		$startx = max (0, $cx - $margin);
		$endx = min ($cx + $margin, $size[0]);
		
		$starty = max (0, $cy - $margin);
		$endy = min ($cy + $margin, $size[1]);
		
		for ($x = $startx; $x < $endx; $x ++)
		{
			for ($y = $starty; $y < $endy; $y ++)
			{
				$rgb = imagecolorat ($src, $x, $y);
		
				$r += ($rgb >> 16) & 0xFF;
				$g += ($rgb >> 8) & 0xFF;
				$b += $rgb & 0xFF;
				
				$i ++;
			}
		}
		
		$r /= $i;
		$g /= $i;
		$b /= $i;
		
		return array ($r, $g, $b);
	}
	
	private function resize ($img, $ratio)
	{
		if ($ratio == 1)
			return $img;
	
		$new = imagecreatetruecolor (imagesx ($img) * $ratio, imagesy ($img) * $ratio);
		
		imagealphablending($new, false);
		
		//$trans = imagecolorallocate ($new, 200, 80, 150);
		//imagefill ($new, 0, 0, $trans);
		imagecolortransparent ($new);
		
		imagesavealpha ($new, true);
		imagecopyresampled ($new, $img, 0, 0, 0, 0, imagesx ($new), imagesy ($new), imagesx ($img), imagesy ($img));
		
		return $new;
	}
	
	private function getGeneratedImage ($src)
	{
		$new = imagecreatefrompng (IMAGE_PATH.'tsprites/sign1.png');
		
		$zoom = self::ZOOM;
		
		$new = $this->resize ($new, $zoom);
		
		$size = array (imagesx ($src), imagesy ($src));
		
		$startx = 26 * $zoom;
		$starty = 38 * $zoom;
		
		$width = 49 * $zoom;
		$height = 39 * $zoom;
		
		$part = array 
		(
			($size[0] / $width), 
			($size[1] / $height)
		);
		
		$smallestpart = min ($part);
		
		// Cut the image
		$part = array ($smallestpart, $smallestpart);
		
		$border = imagecolorallocate ($new, 0, 0, 0);
		
		$increment = 1;
		
		$border = 0;
		
		for ($i = 0; $i < ($width - 1); $i += $increment)
		{
			$y = $i * 0.53;
			
			$intensity_y = (($i - $border) < 0 || ($i + $border - 1) > $width) ? 0.5 : 1;
		
			for ($j = 0; $j < ($height - 1); $j += $increment)
			{
				$intensity_x = (($j - $border) < 0 || ($j + $border + 2) > $height) ? 0.5 : 1;
				
				$intensity = min ($intensity_y, $intensity_x);
			
				// We need to calculate the average colour here somehow
				$cx = floor ($part[0] * $i);
				$cy = floor ($part[1] * $j);
				
				$jj = 0 - $y + $j;
				
				$color = $this->getColorAt ($src, $new, $cx, $cy, $size, $part);
				$color = imagecolorallocate ($new, $color[0], $color[1], $color[2]);
				
				imagesetpixel ($new, $startx + $i, $starty + $jj, $color);
			}
		}
		
		imagesavealpha ($new, true);
		
		//return $new;
		return $this->resize ($new, 1 / $zoom);
	}
}
