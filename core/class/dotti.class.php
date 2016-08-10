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
