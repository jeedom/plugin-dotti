<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

if (!isConnect('admin')) {
	throw new Exception('401 Unauthorized');
}
echo '<div class="row" style="height:100%; width: 100%">';
echo '<div class="col-lg-12">';
$file = dirname(__FILE__) . '/../../data/collection.json';
$dataMemory = array();
if (file_exists($file)) {
	$dataMemory = json_decode(file_get_contents($file), true);
}
foreach ($dataMemory as $name=>$data){
	
echo '<div class="form-group pull-left">';
echo '<span class="label label-info" style="font-size:1em;cursor:default">' . ucfirst($name) . '</span></br>';
$i = 1;
while ($i < 65) {
	$j = 1;
	while ($j < 9) {
		$marginTop = '0px';
		if ($i >= 9){
			$marginTop = '-15px';
		}
		echo '<label class="fa fa-stop" style="color : ' . $data[$i] . ';font-size:2.1em; margin-top:' . $marginTop . ';margin-left:-1px;cursor:default;border-radius:0"></label>';
		$j++;
		$i++;
		
	}
	if ($i != 65){
		echo '<br/>';
	}
}

echo '</div>';
}
echo '</div>';
echo '</div>';
?>