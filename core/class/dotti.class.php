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
	public static $_widgetPossibility = array('custom' => true);

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
		$cmd = 'sudo /bin/bash ' . dirname(__FILE__) . '/../../resources/install.sh';
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
		$port = jeedom::getBluetoothMapping(config::byKey('port', 'dotti'));
		if ($port == '') {
			$return['launchable'] = 'nok';
			$return['launchable_message'] = __('Le port n\'est pas configuré', __FILE__);
		}
		return $return;
	}

	public static function deamon_start() {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$port = jeedom::getBluetoothMapping(config::byKey('port', 'dotti'));

		$dotti_path = realpath(dirname(__FILE__) . '/../../resources/dottid');
		$cmd = '/usr/bin/python ' . $dotti_path . '/dottid.py';
		$cmd .= ' --device=' . str_replace('hci', '', $port);
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

	public static function loadImage($_name) {
		$file = dirname(__FILE__) . '/../../data/collection.json';
		$dataColor = array();
		if (file_exists($file)) {
			$dataMemory = json_decode(file_get_contents($file), true);
			if (isset($dataMemory[$_name])) {
				$dataColor = $dataMemory[$_name];
			}
		}
		return $dataColor;
	}

	public static function renameImage($_oriname, $_newname) {
		$file = dirname(__FILE__) . '/../../data/collection.json';
		if (file_exists($file)) {
			$dataMemory = json_decode(file_get_contents($file), true);
			if (isset($dataMemory[$_oriname])) {
				$oldData = $dataMemory[$_oriname];
				unset($dataMemory[$_oriname]);
				$dataMemory[strtolower($_newname)] = $oldData;
			}
		}
		ksort($dataMemory);
		if (file_exists($file)) {
			shell_exec('sudo rm ' . $file);
		}
		file_put_contents($file, json_encode($dataMemory, JSON_FORCE_OBJECT));
		dotti::refreshTitles();
		return;
	}

	public static function getImageCode($_name) {
		$file = dirname(__FILE__) . '/../../data/collection.json';
		$dataColor = array();
		if (file_exists($file)) {
			$dataMemory = json_decode(file_get_contents($file), true);
			if (isset($dataMemory[$_name])) {
				$dataColor = $dataMemory[$_name];
			}
		}
		return json_encode($dataColor, JSON_FORCE_OBJECT);
	}

	public static function delImage($_name) {
		$file = dirname(__FILE__) . '/../../data/collection.json';
		if (file_exists($file)) {
			$dataMemory = json_decode(file_get_contents($file), true);
			if (isset($dataMemory[$_name])) {
				unset($dataMemory[$_name]);
			}
		}
		if (file_exists($file)) {
			shell_exec('sudo rm ' . $file);
		}
		file_put_contents($file, json_encode($dataMemory, JSON_FORCE_OBJECT));
		dotti::refreshTitles();
		return;
	}

	public static function listMemory() {
		$file = dirname(__FILE__) . '/../../data/collection.json';
		$dataMemory = array();
		if (file_exists($file)) {
			$dataMemory = json_decode(file_get_contents($file), true);
		}
		$list = '';
		foreach ($dataMemory as $name => $data) {
			$list .= '<option value="' . strtolower($name) . '">' . ucfirst($name) . '</option>';
		}
		return $list;
	}

	public static function getImageData($_name) {
		$file = dirname(__FILE__) . '/../../data/collection.json';
		$dataMemory = array();
		if (file_exists($file)) {
			$dataMemory = json_decode(file_get_contents($file), true);
		}
		$dataColor = '';
		foreach ($dataMemory as $name => $data) {
			if (strtolower($_name) == strtolower($name)) {
				foreach ($data as $pixel => $color) {
					$dataColor[$pixel] = hex2rgb($color);
				}
				break;
			}
		}
		return $dataColor;
	}

	public static function saveImage($_id, $_name, $_data, $_isjson = false) {
		$dotti = dotti::byId($_id);
		try {
			dotti::sendDataRealTime($_data, $_id);
		} catch (Exception $e) {
		}
		sleep(5);
		$directory = dirname(__FILE__) . '/../../data/';
		if (!is_dir($directory)) {
			mkdir($directory);
		}
		$file = dirname(__FILE__) . '/../../data/collection.json';
		$dataMemory = array();
		if (file_exists($file)) {
			$dataMemory = json_decode(file_get_contents($file), true);
		}
		if ($_isjson) {
			$_data = json_decode($_data, true);
		}
		$dataMemory[strtolower($_name)] = $_data;
		ksort($dataMemory);
		if (file_exists($file)) {
			shell_exec('sudo rm ' . $file);
		}
		file_put_contents($file, json_encode($dataMemory, JSON_FORCE_OBJECT));
		dotti::refreshTitles();
	}

	public static function refreshTitles() {
		$file = dirname(__FILE__) . '/../../data/collection.json';
		$dataMemory = array();
		if (file_exists($file)) {
			$dataMemory = json_decode(file_get_contents($file), true);
		}
		ksort($dataMemory);
		$array = array();
		foreach ($dataMemory as $name => $data) {
			$array[] = $name;
		}
		foreach (dotti::byType('dotti') as $dotti) {
			$cmd = $dotti->getCmd('action', 'loadimage');
			$cmd->setDisplay('title_possibility_list', json_encode($array));
			$cmd->save();
		}
	}

	public static function displayTimeout($_params) {
		$eqLogic = eqLogic::byId($_params['dotti_id']);
		$eqLogic->sendData('display', $eqLogic->getCache('previousDisplay'), -1);
	}
	/*     * *********************Méthodes d'instance************************* */
	public function preSave() {
		$this->setCategory('multimedia', 1);
	}
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

		$cmd = $this->getCmd(null, 'sendrandom');
		if (!is_object($cmd)) {
			$cmd = new dottiCmd();
			$cmd->setLogicalId('sendrandom');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Image aléatoire', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('message');
		$cmd->setDisplay('title_disable', 0);
		$cmd->setDisplay('title_placeholder', __('Options', __FILE__));
		$cmd->setDisplay('message_placeholder', __('Vide ou liste d\'icône sépraré par ;', __FILE__));
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'sendcolor');
		if (!is_object($cmd)) {
			$cmd = new dottiCmd();
			$cmd->setLogicalId('sendcolor');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Couleur', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('color');
		$cmd->setEqLogic_id($this->getId());
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

		$cmd = $this->getCmd(null, 'loadimage');
		if (!is_object($cmd)) {
			$cmd = new dottiCmd();
			$cmd->setLogicalId('loadimage');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Charger Image', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('message');
		$cmd->setDisplay('message_disable', 0);
		$cmd->setDisplay('title_disable', 0);
		$cmd->setDisplay('message_placeholder', __('Options', __FILE__));
		$cmd->setDisplay('title_placeholder', __('Nom', __FILE__));
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

		$cmd = $this->getCmd(null, 'resetpriority');
		if (!is_object($cmd)) {
			$cmd = new dottiCmd();
			$cmd->setLogicalId('resetpriority');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Raz priorité', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'lastimage');
		if (!is_object($cmd)) {
			$cmd = new dottiCmd();
			$cmd->setLogicalId('lastimage');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Image précédente', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		dotti::refreshTitles();
	}

	public function sendData($_type, $_data, $_priority = 100, $_timeout = null) {
		$cron = cron::byClassAndFunction('dotti', 'displayTimeout');
		if (is_object($cron)) {
			$cron->remove(false);
		}
		if ($_priority == -1) {
			$this->setCache('priority', 0);
			$_priority = 0;
		}
		if ($_type == 'display') {
			if ($this->getCache('priority', 0) > $_priority) {
				return;
			}
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
			$this->setCache('priority', $_priority);
			if ($_data != $this->getCache('display', array())) {
				if ($this->getCache('previousDisplay', array()) != $this->getCache('display', array())) {
					$this->setCache('previousDisplay', $this->getCache('display', array()));
				}
				$this->setCache('display', $_data);
			}
			if ($_timeout !== null) {
				if ($_timeout < 2) {
					$_timeout = 2;
				}
				$cron = new cron();
				$cron->setClass('dotti');
				$cron->setFunction('displayTimeout');
				$cron->setOption(array('dotti_id' => intval($this->getId())));
				$cron->setLastRun(date('Y-m-d H:i:s'));
				$cron->setOnce(1);
				$cron->setSchedule(cron::convertDateToCron(strtotime("now") + $_timeout * 60));
				$cron->save();
			}
		}
		$value = json_encode(array('apikey' => config::byKey('api'), 'type' => $_type, 'data' => $_data, 'mac' => $this->getConfiguration('mac')), JSON_FORCE_OBJECT);
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'dotti'));
		socket_write($socket, $value, strlen($value));
		socket_close($socket);
	}

	public function toHtml($_version = 'dashboard') {
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		$version = jeedom::versionAlias($_version);
		foreach ($this->getCmd('info') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			if ($cmd->getIsHistorized() == 1) {
				$replace['#' . $cmd->getLogicalId() . '_history#'] = 'history cursor';
			}
		}
		foreach ($this->getCmd('action') as $cmd) {
			$replace['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			if ($cmd->getLogicalId() == 'loadimage') {
				$replace['#loadimage#'] = str_replace(array("'", '+'), array("\'", '\+'), $cmd->getDisplay('title_possibility_list'));
			}
		}
		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'eqLogic', 'dotti')));
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
			if (!isset($options['priority'])) {
				$options['priority'] = 100;
			}
			if (!isset($options['timeout'])) {
				$options['timeout'] = null;
			}
			$eqLogic->sendData('display', dotti::text2array($_options['message'], $options['color']), $options['priority'], $options['timeout']);
			return;
		}
		if ($this->getLogicalId() == 'blackscreen') {
			$eqLogic->sendData('color', hex2rgb('#000000'));
			return;
		}
		if ($this->getLogicalId() == 'rownumber') {
			if (!is_numeric($_options['message'])) {
				throw new Exception(__('Le champs message doit être un numérique : ', __FILE__) . $_options['message']);
			}
			$options = array();
			$line = 1;
			if (is_numeric($_options['title'])) {
				$options['line'] = $_options['title'];
			} else {
				$options = arg2array($_options['title']);
			}
			if (!isset($options['line'])) {
				$options['line'] = 1;
			}
			if (!isset($options['priority'])) {
				$options['priority'] = 100;
			}
			if (!isset($options['timeout'])) {
				$options['timeout'] = null;
			}
			$eqLogic->sendData('display', dotti::number2line($_options['message'], $options['line']), $options['priority'], $options['timeout']);
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
		if ($this->getLogicalId() == 'loadimage') {
			$options = arg2array($_options['message']);
			if (!isset($options['priority'])) {
				$options['priority'] = 100;
			}
			if (!isset($options['timeout'])) {
				$options['timeout'] = null;
			}
			$eqLogic->sendData('display', dotti::getImageData($_options['title']), $options['priority'], $options['timeout']);
			return;
		}
		if ($this->getLogicalId() == 'sendcolor') {
			$eqLogic->sendData('color', hex2rgb($_options['color']));
			return;
		}
		if ($this->getLogicalId() == 'lastimage') {
			$eqLogic->sendData('display', $eqLogic->getCache('previousDisplay'), -1);
			return;
		}
		if ($this->getLogicalId() == 'sendrandom') {
			$arrayicon = array();
			$arraycheck = array();
			$file = dirname(__FILE__) . '/../../data/collection.json';
			$dataMemory = array();
			if (file_exists($file)) {
				$dataMemory = json_decode(file_get_contents($file), true);
			}
			foreach ($dataMemory as $name => $data) {
				$arraycheck[] = $name;
			}
			$arrayadd = array();
			$arraydel = array();
			if ($_options['message'] != '') {
				$arrayName = explode(';', $_options['message']);
				foreach ($arrayName as $name) {
					if (substr($name, 0, 1) == '-') {
						$arraydel[] = strtolower(substr($name, 1));
					} else {
						$arrayadd[] = strtolower($name);
					}
				}
				$i = 0;
				foreach ($arraycheck as $icon) {
					if (count($arrayadd > 0)) {
						foreach ($arrayadd as $add) {
							if (strpos($icon, $add) !== false) {
								$arrayicon[] = $icon;
							}
						}
					}
					if (count($arraydel > 0)) {
						foreach ($arraydel as $del) {
							if (strpos($icon, $del) === false) {
								$arrayicon[] = $icon;
							}
						}
					}
					$i++;
				}
			} else {
				$arrayicon = $arraycheck;
			}
			$options = arg2array($_options['title']);
			if (!isset($options['priority'])) {
				$options['priority'] = 100;
			}
			if (!isset($options['timeout'])) {
				$options['timeout'] = null;
			}
			$eqLogic->sendData('display', dotti::getImageData($arrayicon[array_rand($arrayicon)]), $options['priority'], $options['timeout']);
			return;
		}
		if ($this->getLogicalId() == 'resetpriority') {
			$eqLogic->setCache('priority', 0);
			return;
		}
	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
