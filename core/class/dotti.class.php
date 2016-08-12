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

	public static function deamon_info() {
		$return = array();
		$return['log'] = 'dotti';
		$return['state'] = 'nok';
		$pid_file = '/tmp/dottid.pid';
		if (file_exists($pid_file)) {
			if (@posix_getsid(trim(file_get_contents($pid_file)))) {
				$return['state'] = 'ok';
			} else {
				shell_exec('sudo rm -rf ' . $pid_file . ' 2>&1 > /dev/null;rm -rf ' . $pid_file . ' 2>&1 > /dev/null;');
			}
		}
		$return['launchable'] = 'ok';
		return $return;
	}

	public static function deamon_start() {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$dotti_path = realpath(dirname(__FILE__) . '/../../resources/dottid');
		$cmd = '/usr/bin/python ' . $dotti_path . '/dottid.py';
		$cmd .= ' --loglevel=' . log::convertLogLevel(log::getLogLevel('dotti'));
		$cmd .= ' --socketport=' . config::byKey('socketport', 'dotti');

		$macs = '';
		foreach (self::byType('dotti') as $dotti) {
			if ($dotti->getConfiguration('mac') == '') {
				continue;
			}
			$macs .= $dotti->getConfiguration('mac') . ',';
		}
		if ($macs != '') {
			$cmd .= ' --macs=' . trim($macs, ',');
		}
		if (config::byKey('jeeNetwork::mode') == 'slave') {
			$cmd .= ' --sockethost=' . network::getNetworkAccess('internal', 'ip', '127.0.0.1');
			$cmd .= ' --callback=' . config::byKey('jeeNetwork::master::ip') . '/plugins/dotti/core/php/jeeDotti.php';
			$cmd .= ' --apikey=' . config::byKey('jeeNetwork::master::apikey');
		} else {
			$cmd .= ' --sockethost=127.0.0.1';
			$cmd .= ' --callback=' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/dotti/core/php/jeeDotti.php';
			$cmd .= ' --apikey=' . config::byKey('api');
		}
		log::add('dotti', 'info', 'Lancement démon dotti : ' . $cmd);
		$result = exec($cmd . ' >> ' . log::getPathToLog('dotti') . ' 2>&1 &');
		$i = 0;
		while ($i < 30) {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 30) {
			log::add('dotti', 'error', 'Impossible de lancer le démon dotti', 'unableStartDeamon');
			return false;
		}
		message::removeAll('dotti', 'unableStartDeamon');
		return true;
	}

	public static function deamon_stop() {
		$pid_file = '/tmp/dottid.pid';
		if (file_exists($pid_file)) {
			$pid = intval(trim(file_get_contents($pid_file)));
			system::kill($pid);
		}
		system::kill('dottid.py');
		system::fuserk(config::byKey('socketport', 'dotti'));
		sleep(1);
	}

	public static function text2array($_text, $_color = 'FFFFFF', $_displaySize = array(8, 8)) {
		$image = imagecreatetruecolor($_displaySize[0] + 1, $_displaySize[1] + 1);
		$rgbcolor = hex2rgb($_color);
		imagefill($image, 0, 0, 0x000000);
		imagestring($image, 1, 0, 0, $_text, 0xFFFFFF);
		$return = array();
		for ($x = 0; $x < imagesy($image); $x++) {
			for ($y = 0; $y < imagesx($image); $y++) {
				if (imagecolorat($image, $y, $x) != 0) {
					$return[$x][$y] = array($rgbcolor[0], $rgbcolor[1], $rgbcolor[2]);
				} else {
					$return[$x][$y] = array(0, 0, 0);
				}
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

	public static function number2line($_number, $_line = 1) {
		$return = array();
		$colors = array(
			1000 => '#FF0000',
			100 => '#FFFF00',
			10 => '#00FF00',
			1 => '#FFFFFF',
		);
		$start = ($_line - 1) * 8 + 1;
		$i = 0;
		for ($j = 0; $j < 8; $j++) {
			$return[$start + $j] = array(0, 0, 0);
		}
		foreach ($colors as $key => $color) {
			if (($_number / $key) >= 1) {
				for ($j = 1; $j <= ($_number / $key); $j++) {
					$return[$start + $i] = hex2rgb($color);
					if ($i == 7) {
						break (2);
					}
					$i++;
				}
				$_number = $_number - (floor($_number / $key) * $key);
			}
		}
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

	public static function sendDataRealTime($_data, $_id) {
		$dotti = dotti::byId($_id);
		$data = array();
		foreach ($_data as $pixel => $color) {
			$data[$pixel] = hex2rgb($color);
		}
		$dotti->sendData('display', $data);
	}

	public static function loadDotti($_memory, $_id) {
		$dotti = dotti::byId($_id);
		$dotti->sendData('loadid', $_memory);
		$file = dirname(__FILE__) . '/../../data/' . str_replace(':', '', $dotti->getConfiguration('mac')) . '.json';
		$dataColor = array();
		if (file_exists($file)) {
			$dataMemory = json_decode(file_get_contents($file), true);
			if (isset($dataMemory[$_memory])) {
				$dataColor = $dataMemory[$_memory]['data'];
			}
		}
		return $dataColor;
	}

	public static function listMemory($_id) {
		$dotti = dotti::byId($_id);
		$file = dirname(__FILE__) . '/../../data/' . str_replace(':', '', $dotti->getConfiguration('mac')) . '.json';
		$dataMemory = array();
		if (file_exists($file)) {
			$dataMemory = json_decode(file_get_contents($file), true);
		}
		$list = '';
		$i = 0;
		while ($i < 256) {
			$memoryName = $i . ': Mémoire vide';
			if (isset($dataMemory[$i])) {
				$memoryName = $i . ' : ' . $dataMemory[$i]['name'];
			}
			$list .= '<option value="' . $i . '">Mémoire ' . $memoryName . '</option>';
			$i++;
		}
		return $list;
	}

	public static function saveDotti($_memory, $_id, $_name, $_data) {
		$dotti = dotti::byId($_id);
		dotti::sendDataRealTime($_data, $_id);
		sleep(5);
		$dotti->sendData('saveid',$_memory);
		$directory= dirname(__FILE__) . '/../../data/';
		if (!is_dir($directory)) {
			mkdir($directory);
		}
		$file = dirname(__FILE__) . '/../../data/' . str_replace(':', '', $dotti->getConfiguration('mac')) . '.json';
		$dataMemory = array();
		if (file_exists($file)) {
			$dataMemory = json_decode(file_get_contents($file), true);
		}
		$dataMemory[$_memory]['name'] = $_name;
		$dataMemory[$_memory]['data'] = $_data;
		if (file_exists($file)) {
			shell_exec('sudo rm ' . $file);
		}
		file_put_contents($file, json_encode($dataMemory, JSON_FORCE_OBJECT));
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

		$cmd = $this->getCmd(null, 'blackscreen');
		if (!is_object($cmd)) {
			$cmd = new dottiCmd();
			$cmd->setLogicalId('blackscreen');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Ecran noir', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'rownumber');
		if (!is_object($cmd)) {
			$cmd = new dottiCmd();
			$cmd->setLogicalId('rownumber');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Nombre en ligne', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('message');
		$cmd->setDisplay('title_placeholder', __('Ligne', __FILE__));
		$cmd->setDisplay('message_placeholder', __('Nombre', __FILE__));
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'loadid');
		if (!is_object($cmd)) {
			$cmd = new dottiCmd();
			$cmd->setLogicalId('loadid');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Charger Image', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('message');
		$cmd->setDisplay('title_disable', 1);
		$cmd->setDisplay('message_placeholder', __('ID (0 à 255) ou nom', __FILE__));
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'saveid');
		if (!is_object($cmd)) {
			$cmd = new dottiCmd();
			$cmd->setLogicalId('saveid');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Sauver Image', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('message');
		$cmd->setDisplay('title_disable', 1);
		$cmd->setDisplay('message_placeholder', __('ID (0 à 255)', __FILE__));
		$cmd->setEqLogic_id($this->getId());
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
		$cmd->setDisplay('message_placeholder', __('Données brute', __FILE__));
		$cmd->save();
	}

	public function findIdWithName($_name) {
		$file = dirname(__FILE__) . '/../../data/' . str_replace(':', '', $this->getConfiguration('mac')) . '.json';
		$dataMemory = array();
		$id = 0;
		if (file_exists($file)) {
			$dataMemory = json_decode(file_get_contents($file), true);
		}
		foreach ($dataMemory as $key => $memory) {
			if ($memory['name'] == $_name) {
				$id = $key;
				break;
			}
		}
		return $id;
	}

	public function sendData($_type, $_data) {
		if ($_type == 'display') {
			if (isset($_data[0]) && is_array($_data[0])) {
				$data = array();
				$i = 1;
				foreach ($_data as $x => $line) {
					foreach ($line as $y => $color) {
						$data[$i] = $color;
						$i++;
					}
				}
				$_data = $data;
			}
		}
		$value = json_encode(array('apikey' => config::byKey('api'), 'type' => $_type, 'data' => $_data, 'mac' => $this->getConfiguration('mac')), JSON_FORCE_OBJECT);
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'dotti'));
		socket_write($socket, $value, strlen($value));
		socket_close($socket);
	}

	/*     * **********************Getteur Setteur*************************** */
}

class dottiCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function execute($_options = array()) {
		$eqLogic = $this->getEqLogic();
		if ($this->getLogicalId() == 'sendtext') {
			$options = arg2array($_options['title']);
			if (!isset($options['color'])) {
				$options['color'] = 'FFFFFF';
			}
			$eqLogic->sendData('display', dotti::text2array($_options['message'], $options['color']));
			return;
		}
		if ($this->getLogicalId() == 'blackscreen') {
			$data = array();
			for ($i = 1; $i < 65; $i++) {
				$data[$i] = array(0, 0, 0);
			}
			$eqLogic->sendData('display', $data);
			return;
		}
		if ($this->getLogicalId() == 'rownumber') {
			if (!is_numeric($_options['message'])) {
				throw new Exception(__('Le champs message doit être un numérique : ', __FILE__) . $_options['message']);
			}
			$line = 1;
			if (is_numeric($_options['title'])) {
				$line = $_options['title'];
			} else {
				$options = arg2array($_options['title']);
				if (!isset($options['line'])) {
					$line = $options['line'];
				}
			}
			$eqLogic->sendData('display', dotti::number2line($_options['message'], $line));
			return;
		}
		if ($this->getLogicalId() == 'sendraw') {
			$options = arg2array($_options['message']);
			$data = array();
			foreach ($options as $key => $value) {
				$data[$key] = hex2rgb($value);
			}
			$eqLogic->sendData('display', $data);
			return;
		}
		if (in_array($this->getLogicalId(), array('loadid', 'saveid'))) {
			if ($this->getLogicalId() == 'loadid') {
				if (!is_numeric($_options['message'])) {
					$_options['message'] = $eqLogic->findIdWithName($_options['message']);
				}
			}
			$eqLogic->sendData($this->getLogicalId(), $_options['message']);
			return;
		}
	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
