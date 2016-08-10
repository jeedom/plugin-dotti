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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class dotti extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	public static function dependancy_info() {
		$return = array();
		$return['log'] = 'dotti_update';
		$return['progress_file'] = '/tmp/dependancy_dotti_in_progress';
		if (exec('which hcitool | wc -l') != 0) {
			$return['state'] = 'ok';
		} else {
			$return['state'] = 'nok';
		}
		return $return;
	}

	public static function dependancy_install() {
		log::remove('dotti_update');
		$cmd = 'sudo /bin/bash ' . dirname(__FILE__) . '/../../ressources/install.sh';
		$cmd .= ' >> ' . log::getPathToLog('dotti_update') . ' 2>&1 &';
		exec($cmd);
	}

	public static function text2array($_text, $_color = 0xFFFFFF, $_displaySize = array(8, 8)) {
		$image = imagecreatetruecolor($_displaySize[0] + 1, $_displaySize[1] + 1);
		imagefill($image, 0, 0, 0x000000);
		imagestring($image, 1, 0, 0, $_text, $_color);
		$return = array();
		for ($x = 0; $x < imagesy($image); $x++) {
			for ($y = 0; $y < imagesx($image); $y++) {
				$rgb = imagecolorat($image, $y, $x);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				$return[$x][$y] = array($r, $g, $b);
			}
		}
		$column_black = true;
		foreach ($return as $x => $line) {
			if ($line[0][0] != 0 || $line[0][0] != 0 || $line[0][0] != 0) {
				$column_black = false;
				break;
			}
		}
		foreach ($return as $x => &$line) {
			if ($column_black) {
				array_shift($line);
			} else {
				array_pop($line);
			}
		}
		array_pop($return);
		return $return;
	}

	public static function array2table($_array) {
		$return = '<table>';
		foreach ($_array as $x => $line) {
			$return .= '<tr>';
			foreach ($line as $y => $color) {
				$return .= '<td style="Background-Color:RGB(' . $color[0] . ',' . $color[1] . ',' . $color[2] . ');height:40px;width:40px;"></td>';
			}
			$return .= '</tr>';
		}
		$return .= '<table>';
		return $return;
	}

	/*     * *********************Méthodes d'instance************************* */

	public function postSave() {
		$cmd = $this->getCmd(null, 'sendtext');
		if (!is_object($cmd)) {
			$cmd = new dottiCmd();
			$cmd->setLogicalId('sendtext');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Afficher text', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('message');
		$cmd->setEqLogic_id($this->getId());
		$cmd->setDisplay('title_placeholder', __('Options', __FILE__));
		$cmd->save();

		$cmd = $this->getCmd(null, 'sendraw');
		if (!is_object($cmd)) {
			$cmd = new dottiCmd();
			$cmd->setLogicalId('sendraw');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Afficher', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('message');
		$cmd->setEqLogic_id($this->getId());
		$cmd->setDisplay('title_placeholder', __('Options', __FILE__));
		$cmd->setDisplay('message_placeholder', __('Données brute en json', __FILE__));
		$cmd->save();
	}

	public static function generateJson($_data, $_options = array()) {
		$_options['data'] = $_data;
		if (file_exists('/tmp/dotti.json')) {
			shell_exec('sudo rm /tmp/dotti.json');
		}
		file_put_contents('/tmp/dotti.json', $_options);
	}

	/*     * **********************Getteur Setteur*************************** */
}

class dottiCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function execute($_options = array()) {

	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
