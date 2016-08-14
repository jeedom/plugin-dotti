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
<div class="row" style="height:100%; width: 100%;background: #303132!important;">
	<div class="col-lg-2">
		<div class="form-group">
			<span style="margin-right:15px;"><label class="fa fa-circle pixelCircle" style="color : #000000;font-size:2em; margin-top:10px;margin-left:15px; cursor: pointer;"><input class="pixelcolor" type="color" value="#000000" style="width:0;height:0;visibility:hidden"></label>{{Couleur}}</span>
		</div>
		<div class="form-group">
			<a class="btn btn-xs btn-success" id="bt_fill" style="margin-left:20px"><i class="fa fa-tint"></i> {{Remplir}}</a>
		</div>
		<div class="form-group">
			<a class="btn btn-xs btn-warning" id="bt_copyColor" style="margin-left:20px"><i class="fa fa-pencil"></i> {{Pipette}}</a>
		</div>
		<div class="form-group">
			<a class="btn btn-xs btn-danger" id="bt_erase" style="margin-left:20px"><i class="fa fa-eraser"></i> {{Gommer}}</a>
		</div>
		<div class="form-group">
			<label class="checkbox-inline"><input class="realtime" type="checkbox" unchecked />{{Temps réel}}</label>
		</div>
		<div class="form-group">
			<label class="checkbox-inline"><input class="realimage" type="checkbox" unchecked />{{Regrouper pixel}}</label>
		</div>
	</div>
	<div class="col-lg-6">
		<center>
			<?php
$i = 1;
while ($i < 65) {
	$j = 1;
	while ($j < 9) {
		echo '<label class="fa fa-square pixel" data-pixel="' . $i . '" style="color : #000000;font-size:5em; margin-top:10px;margin-left:15px; cursor: pointer;border-radius:0"></label>  ';
		$j++;

		$i++;
	}
	echo '<br/>';
}
?>
		</center>
	</div>
	<div class="col-lg-4">
		<div class="form-group">
			<a class="btn btn-warning" id="bt_sendAll"><i class="fa fa-paint-brush"></i> {{Afficher sur le Dotti}}</a>
			<a class="btn btn-success" id="bt_displayExport"><i class="fa fa-download"></i></a>
			<a class="btn btn-danger" id="bt_Import"><i class="fa fa-upload"></i></a>
		</div>
		<div class="form-group">
			<div class="input-group">
				<input class="nameDottiScreen form-control" id="texte" type='text'/>
				<span class="input-group-btn">
					<a class="btn btn-success" id="bt_saveImage"><i class="fa fa-floppy-o"></i></a>
				</span>
			</div>
		</div>
		<div class="form-group">
			<div class="input-group">
				<select class="memoryload form-control"></select>
				<span class="input-group-btn">
					<a class="btn btn-danger" id="bt_delImage"><i class="fa fa-trash-o"></i></a>
				</span>
			</div>
		</div>
		<div class="form-group">
			<textarea class="imageDotti form-control" style="display:none" rows="20"></textarea></br>
			<a class="btn btn-success uploadimageDotti" id="bt_upload" style="display:none"><i class="fa fa-upload"></i>  {{Envoyer}}</a>
			<a class="btn btn-danger closeimageDotti" id="bt_close" style="display:none"><i class="fa fa-times"></i></a>
		</div>
	</div>
</div>
<script>
	loadMemoryList(id);
	var pencil = 0;
	var erase = 0;
	$('.realimage').on('change', function () {
		if ($(this).is(':checked')){
			$('.pixel').css('margin-top' , '-16px');
			$('.pixel').css('margin-left' , '-5px');
			$('.pixel').attr('class', 'fa fa-stop pixel');
		} else {
			$('.pixel').css('margin-top' , '10px');
			$('.pixel').css('margin-left' , '15px');
			$('.pixel').attr('class', 'fa fa-square pixel');
		}
	});
	$('#bt_erase').on('click', function () {
		if (erase == 0){
			erase = 1;
			pencil = 0;
			$('.eventDisplay').empty().append('<div class="alert alert-danger"><strong>Vous êtes en mode gomme. Effacer les pixels que vous voulez puis recliquez sur Gommer pour sortir du mode</strong></div>');
		} else {
			erase = 0;
			$('.eventDisplay').empty();
		}
	});
	$('#bt_displayExport').on('click', function () {
		$('.imageDotti').show();
		$('.closeimageDotti').show();
		$('.uploadimageDotti').hide();
		$('.imageDotti').val('');
		$.ajax({
			type: "POST",
			url: "plugins/dotti/core/ajax/dotti.ajax.php",
			data: {
				action: "getImageCode",
				name: $('.memoryload').val()
			},
			dataType: 'json',
			global: false,
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) {
				if (data.state != 'ok') {
					$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
					return;
				}
				$('.imageDotti').off('change');
				$('.imageDotti').val(data.result);
				autoLoadJson();
				modifyWithoutSave=false;
			}
		});
	});

	function autoLoadJson(){
		$('.imageDotti').on('change',function(){
			try {
				data = json_decode($(this).value());
				for(var pixelId in data){
					$('[data-pixel="'+ pixelId +'"]').css('color', data[pixelId]);
				}
			}catch (e) {

			}
		});
	}

	autoLoadJson();

	$('#bt_Import').on('click', function () {
		$('.imageDotti').show();
		$('.closeimageDotti').show();
		$('.uploadimageDotti').show();
		$('.imageDotti').val('');
	});
	$('#bt_close').on('click', function () {
		$('.imageDotti').hide();
		$('.closeimageDotti').hide();
		$('.uploadimageDotti').hide();
	});

	$('#bt_upload').on('click', function () {
		if ($('.nameDottiScreen').val() == ''){
			$('.eventDisplay').showAlert({message:  'Vous devez spécifier un nom pour sauver une image',level: 'danger'});
			return;
		}
		bootbox.dialog({
			title: 'Etes-vous sur ?',
			message: 'Vous allez sauver l\'image avec le nom "' +$('.nameDottiScreen').val() +'" ! Voulez-vous continuer ?',
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
						$('.eventDisplay').showAlert({message:  'Affichage sur le Dotti en cours ...',level: 'warning'});
						$.ajax({
							type: "POST",
							url: "plugins/dotti/core/ajax/dotti.ajax.php",
							data: {
								action: "saveImagejson",
								id: id,
								name: $('.nameDottiScreen').val(),
								data : $('.imageDotti').val()
							},
							global : false,
							dataType: 'json',
							error: function(request, status, error) {
								handleAjaxError(request, status, error);
							},
							success: function(data) {
								if (data.state != 'ok') {
									$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
									return;
								}
								$('.eventDisplay').showAlert({message:  'Sauvegarde effectuée' ,level: 'success'});
								modifyWithoutSave=false;
								loadMemoryList(id);
								$('.imageDotti').hide();
								$('.closeimageDotti').hide();
								$('.uploadimageDotti').hide();
							}
						});
					}
				},
			}
		});
	});
	$('#bt_copyColor').on('click', function () {
		if (pencil == 0){
			pencil = 1;
			erase =0;
			$('.eventDisplay').empty().append('<div class="alert alert-warning"><strong>Vous êtes en mode pipette. Choisissez la couleur ou sortez du mode en recliquant sur Pipette</strong></div>');
		} else {
			pencil = 0;
			$('.eventDisplay').empty();
		}
	});
	$('#bt_saveImage').on('click', function () {
		var array = {};
		$('.pixel').each(function( index ) {
			array[$(this).attr('data-pixel')] = hexc($(this).css('color'));
		});
		if ($('.nameDottiScreen').val() == ''){
			$('.eventDisplay').showAlert({message:  'Vous devez spécifier un nom pour sauver une image',level: 'danger'});
			return;
		}
		bootbox.dialog({
			title: 'Etes-vous sur ?',
			message: 'Vous allez sauver l\'image avec le nom "' +$('.nameDottiScreen').val() +'" ! Voulez-vous continuer ?',
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
						$('.eventDisplay').showAlert({message:  'Affichage sur le Dotti en cours ...',level: 'warning'});
						$.ajax({
							type: "POST",
							url: "plugins/dotti/core/ajax/dotti.ajax.php",
							data: {
								action: "saveImage",
								id: id,
								name: $('.nameDottiScreen').val(),
								data : array
							},
							global : false,
							dataType: 'json',
							error: function(request, status, error) {
								handleAjaxError(request, status, error);
							},
							success: function(data) {
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

	$('.memoryload').on('change', function () {
		$.ajax({
			type: "POST",
			url: "plugins/dotti/core/ajax/dotti.ajax.php",
			data: {
				action: "loadImage",
				id: id,
				name: $('.memoryload').val()
			},
			dataType: 'json',
			global: false,
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) {
				if (data.state != 'ok') {
					$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
					return;
				}
				if (!$.isEmptyObject(data.result)){
					for(var pixelId in data.result){
						$('[data-pixel="'+ pixelId.toString() +'"]').css('color', data.result[pixelId]);
					}
				}
				$('.nameDottiScreen').val($('.memoryload').find('option:selected').text());
				modifyWithoutSave=false;
			}
		});
	});

	$('#bt_delImage').on('click', function () {
		bootbox.dialog({
			title: 'Etes-vous sur ?',
			message: 'Vous allez supprimer l\'image avec le nom "' +$('.memoryload').find('option:selected').text() +'" ! Voulez-vous continuer ?',
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
						$.ajax({
							type: "POST",
							url: "plugins/dotti/core/ajax/dotti.ajax.php",
							data: {
								action: "delImage",
								name: $('.memoryload').val()
							},
							global : false,
							dataType: 'json',
							error: function(request, status, error) {
								handleAjaxError(request, status, error);
							},
							success: function(data) {
								if (data.state != 'ok') {
									$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
									return;
								}
								$('.eventDisplay').showAlert({message:  'Suppression effectuée' ,level: 'success'});
								loadMemoryList(id);
								modifyWithoutSave=false;
							}
						});
					}
				},
			}
		});
	});

	$('.pixel').on('click', function() {
		var pixelId = $(this).attr('data-pixel');
		var color = $('.pixelCircle').css('color');
		if (erase == 1){
			color ='#000000';
		}
		if (pencil == 1){
			$('.pixelCircle').css('color', $(this).css('color'));
			$('.pixelcolor').val(hexc($(this).css('color')));
			pencil = 0;
			$('.eventDisplay').empty();
			return;
		}
		$(this).css('color', color);
		if ($('.realtime').is(':checked')){
			var array = {};
			array[pixelId.toString()] = color;
			sendPixelArray(array,id);
		}
	})

	$('.pixelcolor').on('change', function() {
		$(this).closest('.pixelCircle').css('color', $(this).val())
	})

	$('#bt_fill').on('click', function() {
		$('.pixel').each(function( index ) {
			$(this).css('color', $('.pixelCircle').css('color'));
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
		$.ajax({
			type: "POST",
			url: "plugins/dotti/core/ajax/dotti.ajax.php",
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
			success: function(data) {
				if (data.state != 'ok') {
					$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
					return;
				}
				modifyWithoutSave=false;
			}
		});
	}

	function loadMemoryList(_id) {
		$.ajax({
			type: "POST",
			url: "plugins/dotti/core/ajax/dotti.ajax.php",
			data: {
				action: "loadMemoryList"
			},
			global:false,
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) {
				if (data.state != 'ok') {
					$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
					return;
				}
				modifyWithoutSave=false;
				if (data.result){
					$('.memoryload').empty().append(data.result);
				} else {
					$('.memoryload').empty();
				}
			}
		});
	}
</script>