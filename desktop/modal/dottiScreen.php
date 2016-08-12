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
if (init('id') == '') {
    throw new Exception('{{L\'id de l\'équipement ne peut etre vide : }}' . init('op_id'));
}
sendVarToJS('id', init('id'));
?>
<div class="eventDisplay"></div>
 <div class="col-lg-2">
 <div class="form-group">
<span style="margin-right:15px;"><label class="fa fa-circle pixelCircle" style="color : #000000;font-size:2em; margin-top:10px;margin-left:15px; cursor: pointer;"><input class="pixelcolor" type="color" value="#000000" style="width:0;height:0;visibility:hidden"></label>{{Couleur}}</span>
<a class="btn btn-xs btn-danger" id="bt_fill"><i class="fa fa-tint"></i> {{Remplir}}</a>
</div>
<div class="form-group">

</div>
<div class="form-group">
<label class="checkbox-inline"><input class="realtime" type="checkbox" unchecked />{{Temps réel}}</label>
</div>
</div>
 <div class="col-lg-8">
<center>
	<?php
	$i=1;
	while ($i < 65){
		$j=1;
		while ($j < 9){
			echo '<label class="fa fa-square pixel" data-pixel="' . $i .'" style="color : #000000;font-size:5em; margin-top:10px;margin-left:15px; cursor: pointer;"></label>  ';
			$j++;
			
		$i++;
		}
		echo '<br/>';
	}
     ?>
</center>
</div>
<center>
 <div class="col-lg-2">
 <div class="form-group">
<a class="btn btn-warning" id="bt_sendAll"><i class="fa fa-refresh"></i> {{Afficher sur le Dotti}}</a></br>
</div>
<div class="form-group">
	<label class="control-label">{{Nom de l'image}}</label>
   <input class="name form-control" id="texte" type='text'/>
   <select class="memorysave form-control" style="margin-top:5px">
   <?php
        $i = 0;
        while ($i < 256) {
          echo '<option value="' . $i . '">{{Mémoire ' . $i . '}}</option>';
          $i++;
        }
	?>
		</select>
		<a class="btn btn-success" id="bt_saveDotti"><i class="fa fa-refresh"></i> {{Sauver l'image}}</a>
</div>
<div class="form-group">
<select class="memoryload form-control" style="margin-top:5px">
   <?php
        $i = 0;
        while ($i < 256) {
          echo '<option value="' . $i . '">{{Mémoire ' . $i . '}}</option>';
          $i++;
        }
	?>
</select>
<a class="btn btn-danger" id="bt_loadDotti"><i class="fa fa-refresh"></i> {{Charger une image}}</a>
</div>
</div>
</center>
<script>
loadMemoryList(id);
$('#bt_saveDotti').on('click', function () {
	var array = {};
	 $('.pixel').each(function( index ) {
		 array[$(this).attr('data-pixel')] = hexc($(this).css('color'));
	});
	if ($('.name').val() == ''){
		$('.eventDisplay').showAlert({message:  'Vous devez spécifier un nom pour sauver une image',level: 'danger'});
		return;
	}
	bootbox.dialog({
            title: 'Etes-vous sur ?',
            message: 'Vous allez sauver l\'image dans la mémoire "' + $('.memorysave').find('option:selected').text() + '" avec le nom : ' +$('.name').val() +' ! Voulez-vous continuer ?',
            buttons: {
                "{{Annuler}}": {
                    className: "btn-danger",
                    callback: function () {
                    }
                },
                success: {
                    label: "{{Continuer}}",
                    className: "btn-success",
                    callback: function () {
						$('.eventDisplay').showAlert({message:  'Sauvegarde en cours ...',level: 'warning'});
                        $.ajax({// fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/dotti/core/ajax/dotti.ajax.php", // url du fichier php
			data: {
				action: "saveDotti",
				id: id,
				name: $('.name').val(),
				memory: $('.memorysave').value(),
				data : array
			},
			global : false,
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
        success: function(data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
            	$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
                return;
            }
			$('.eventDisplay').showAlert({message:  'Sauvegarde effectuée' ,level: 'success'});
            modifyWithoutSave=false;
			loadMemoryList(id);
        }
    });
                    }
                },
            }
        });
});

$('#bt_loadDotti').on('click', function () {
	$.ajax({// fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/dotti/core/ajax/dotti.ajax.php", // url du fichier php
			data: {
				action: "loadDotti",
				id: id,
				memory: $('.memoryload').value()
			},
			dataType: 'json',
			global: false,
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
        success: function(data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
            	$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
                return;
            }
			console.log(data.result);
			if (!$.isEmptyObject(data.result)){
				for(var pixelId in data.result){
					$('[data-pixel="'+ pixelId.toString() +'"]').css('color', data.result[pixelId]);
				}
			}
            modifyWithoutSave=false;
        }
    });
});

 $('.pixel').on('click', function() {
	var pixelId = $(this).attr('data-pixel');
	$(this).css('color', $('.pixelcolor').val())
	if ($('.realtime').is(':checked')){
		var array = {};
		array[pixelId.toString()] = $('.pixelcolor').val();
		sendPixelArray(array,id);
	}
})

 $('.pixelcolor').on('change', function() {
	$(this).closest('.pixelCircle').css('color', $(this).val())
})

$('#bt_fill').on('click', function() {
	$('.pixel').each(function( index ) {
		 $(this).css('color', $('.pixelcolor').val());
	});
	if ($('.realtime').is(':checked')){
	 var array = {};
	 $('.pixel').each(function( index ) {
		 array[$(this).attr('data-pixel')] = hexc($(this).css('color'));
	});
	sendPixelArray(array,id);
	}
})

 $('#bt_sendAll').on('click', function() {
	 var array = {};
	 $('.pixel').each(function( index ) {
		 array[$(this).attr('data-pixel')] = hexc($(this).css('color'));
	});
	sendPixelArray(array,id);
})

function hexc(colorval) {
    var parts = colorval.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
    delete(parts[0]);
    for (var i = 1; i <= 3; ++i) {
        parts[i] = parseInt(parts[i]).toString(16);
        if (parts[i].length == 1) parts[i] = '0' + parts[i];
    }
    color = '#' + parts.join('');

    return color;
}

function sendPixelArray(_array,_id) {
		
		$.ajax({// fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/dotti/core/ajax/dotti.ajax.php", // url du fichier php
			data: {
				action: "sendPixelArray",
				array: _array,
				id: _id
			},
			global: false,
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
        success: function(data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
            	$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
                return;
            }
            modifyWithoutSave=false;
        }
    });
}

function loadMemoryList(_id) {
		$.ajax({// fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/dotti/core/ajax/dotti.ajax.php", // url du fichier php
			data: {
				action: "loadMemoryList",
				id: _id
			},
			global:false,
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
        success: function(data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
            	$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
                return;
            }
            modifyWithoutSave=false;
			$('.memorysave').empty().append(data.result);
			$('.memoryload').empty().append(data.result);
        }
    });
}
</script>