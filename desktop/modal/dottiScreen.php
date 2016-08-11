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
?>
<center>
	<?php
	$i=1;
	while ($i < 65){
		$j=1;
		while ($j < 9){
			echo '<i class="fa fa-square fa-lg pixel" data-pixel="' . $i .'" style="color : #000000;font-size:4em; margin-top:10px; cursor: pointer"></i>  ';
			$j++;
			
		$i++;
		}
		echo '<br/>';
	}
     ?>
</center>
<script>
$('.pixel').on('click', function () {
	console.log($(this).attr('data-pixel'));
});
</script>