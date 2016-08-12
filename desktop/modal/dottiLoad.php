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
    throw new Exception('{{401 - Accès non autorisé}}');
}

if (init('id') == '') {
    throw new Exception('{{L\'id de l\'équipement ne peut etre vide : }}' . init('op_id'));
}

$id = init('id');
$dotti = dotti::byId($id);
	if (!is_object($dotti)) { 
			  
	 throw new Exception(__('Aucun equipement ne  correspond : Il faut (re)-enregistrer l\'équipement ', __FILE__) . init('action'));
	 }
sendVarToJS('id', init('id'));	 
?>
<div class="alert alert-info">
            Veuillez choisir quelle mémoire charger
</div>
<div>
   <?php
   echo '<div class="form-group">
        <label class=" control-label">Mémoire</label>
        <div class="col-lg-3">
        <select class="memory" style="margin-top:5px">';
        $i = 0;
        while ($i < 256) {
          echo '<option value="' . $i . '">{{Mémoire ' . $i . '}}</option>';
          $i++;
        }


        echo '</select> </div>
        </div>';
		?>
</div>
<br />
<a class="btn btn-success loadImg"><i class="fa fa-check-circle"></i> Charger l'image</a>
<script>

$('.loadImg').on('click', function() {
	$.ajax({// fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/dotti/core/ajax/dotti.ajax.php", // url du fichier php
			data: {
				action: "loadDotti",
				id: id,
				memory: $('.memory').value()
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
        success: function(data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
            	$('#div_alert').showAlert({message:  data.result,level: 'danger'});
                return;
            }
            modifyWithoutSave=false;
        }
    });
})



</script>