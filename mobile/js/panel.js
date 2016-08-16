function initDottiPanel(){
	loadMemoryList();
	loadTrame();
	setTimeout(function() { loadImage()}, 200);
	pencil = 0;
	id = '';
	erase = 0;
	replace = 0;
	realtime = 0;
	$('.realimage').on('change', function () {
		if ($(this).is(':checked')){
			$('.pixelNotFirstLine').css('margin-top' , '-17px');
			$('.pixel').css('margin-left' , '-6px');
			$('.pixelFirstLine').attr('class', 'fa fa-stop pixel pixelFirstLine');
			$('.pixelNotFirstLine').attr('class', 'fa fa-stop pixel pixelNotFirstLine');
		} else {
			$('.pixel').css('margin-top' , '10px');
			$('.pixel').css('margin-left' , '15px');
			$('.pixelFirstLine').attr('class', 'fa fa-square pixel pixelFirstLine');
			$('.pixelNotFirstLine').attr('class', 'fa fa-square pixel pixelNotFirstLine');
		}
	});
	$('.realtime').on('click', function () {
		if (realtime == 0){
			realtime = 1;
			$('.realtime').css('color' , '#ff4c4c');
		} else {
			realtime = 0;
			$('.realtime').css('color' , '');
		}
	});
	$('#bt_erase').on('click', function () {
		if (erase == 0){
			erase = 1;
			pencil = 0;
			replace = 0;
			$('.erasecolor').css('color' , '#ff4c4c');
			$('.replacecolor').css('color' , '');
			$('.copycolor').css('color' , '');
			$('.eventDisplay').hideAlert();
			$('.eventDisplay').showAlert({message:  'Vous êtes en mode gomme. Effacer les pixels que vous voulez',level: 'danger'});
		} else {
			erase = 0;
			$('.erasecolor').css('color' , '');
			$('.eventDisplay').hideAlert();
		}
	});
	$('#bt_replace').on('click', function () {
		if (replace == 0){
			replace = 1;
			pencil = 0;
			erase = 0;
			$('.replacecolor').css('color' , '#ff4c4c');
			$('.erasecolor').css('color' , '');
			$('.copycolor').css('color' , '');
			$('.eventDisplay').hideAlert();
			$('.eventDisplay').showAlert({message:  'Vous êtes en mode pot de peinture.',level: 'warning'});
		} else {
			replace = 0;
			$('.replacecolor').css('color' , '');
			$('.eventDisplay').hideAlert();
		}
	});
	$('#bt_copyColor').on('click', function () {
		if (pencil == 0){
			pencil = 1;
			erase =0;
			replace = 0;
			$('.copycolor').css('color' , '#ff4c4c');
			$('.replacecolor').css('color' , '');
			$('.erasecolor').css('color' , '');
			$('.eventDisplay').hideAlert();
			$('.eventDisplay').showAlert({message:  'Vous êtes en mode pipette.',level: 'warning'});
		} else {
			pencil = 0;
			$('.eventDisplay').hideAlert();
			$('.copycolor').css('color' , '');
		}
	});
	
	$('#bt_saveImage').on('click', function () {
		var array = {};
		$('.pixel').each(function( index ) {
			array[$(this).attr('data-pixel')] = hexc($(this).css('color'));
		});
		if ($('.nameDottiScreen').val() == ''){
			$('.eventDisplay').showAlert({message:  'Vous devez spécifier un nom pour sauver une image',level: 'danger'});
			setTimeout(function() { deleteAlert()}, 2000);
			return;
		}
		var saveprompt = confirm('Vous allez sauver l\'image avec le nom "' +$('.nameDottiScreen').val() +'" ! Voulez-vous continuer ?')
		if (saveprompt != true){
			return;
		} else {
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
						setTimeout(function() { deleteAlert()}, 2000);
						return;
					}
					$('.eventDisplay').showAlert({message:  'Sauvegarde effectuée' ,level: 'success'});
					setTimeout(function() { deleteAlert() }, 2000);
					modifyWithoutSave=false;
					loadMemoryList();
				}
			});
		}
	});

	$('.memoryload').on('change', function () {
		getImageCode();
		loadImage();
		if (realtime == 1){
			setTimeout(function() { sendAll() }, 500);
		}
	});

	$('#bt_delImage').on('click', function () {
		var deleteprompt = confirm('Vous allez supprimer l\'image avec le nom "' +$('.memoryload').find('option:selected').text() +'" ! Voulez-vous continuer ?')
		if (deleteprompt != true){
			return;
		} else {
			$.ajax({
				type: "POST",
				url: "plugins/dotti/core/ajax/dotti.ajax.php",
				data: {
					action: "delImage",
					name: $('select.memoryload').val()
				},
				global : false,
				dataType: 'json',
				error: function(request, status, error) {
					handleAjaxError(request, status, error);
				},
				success: function(data) {
					if (data.state != 'ok') {
						$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
						setTimeout(function() { deleteAlert() }, 2000);
						return;
					}
					$('.eventDisplay').showAlert({message:  'Suppression effectuée' ,level: 'success'});
					setTimeout(function() { deleteAlert() }, 2000);
					loadMemoryList();
					modifyWithoutSave=false;
				}
			});
		}
	});

	$('.pixel').on('click', function() {
		var pixelId = $(this).attr('data-pixel');
		var color = $('.pixelCircle').css('color');
		if (erase == 1){
			color ='rgb(0, 0, 0)';
		}
		if (pencil == 1){
			$('.pixelCircle').css('color', $(this).css('color'));
			$('.pixelcolor').val(hexc($(this).css('color')));
			pencil = 0;
			$('.eventDisplay').hideAlert();
			$('.copycolor').css('color' , '');
			return;
		}
		if (replace ==1){
			var array ={};
			var colortoreplace = $(this).css('color');
			$('.pixel').each(function( index ) {
				if ($(this).css('color') == colortoreplace ){
					array[$(this).attr('data-pixel')] = hexc(color);
					$(this).css('color', color);
				}
			});
			replace = 0;
			$('.eventDisplay').hideAlert();
			$('.replacecolor').css('color' , '');
			if (realtime==1){
				sendPixelArray(array,id);
			}
			return;
		}
		$(this).css('color', color);
		if (realtime==1){
			var array = {};
			array[pixelId.toString()] = hexc(color);
			sendPixelArray(array,id,false);
		}
	})

	$('.pixelcolor').on('change', function() {
		$(this).closest('.pixelCircle').css('color', $(this).val())
	})

	$('#bt_fill').on('click', function() {
		$('.pixel').each(function( index ) {
			$(this).css('color', $('.pixelCircle').css('color'));
		});
		if (realtime==1){
			var array = {};
			$('.pixel').each(function( index ) {
				array[$(this).attr('data-pixel')] = hexc($(this).css('color'));
			});
			sendPixelArray(array,id);
		}
	})
	
	$('#bt_fillblack').on('click', function() {
		$('.pixel').each(function( index ) {
			$(this).css('color', '#000000');
		});
		if (realtime==1){
			var array = {};
			$('.pixel').each(function( index ) {
				array[$(this).attr('data-pixel')] = '#000000';
			});
			sendPixelArray(array,id);
		}
	})

	$('#bt_sendAll').on('click', function() {
		sendAll();
	});
}
	
	
	function sendAll() {
		var array = {};
		$('.pixel').each(function( index ) {
			array[$(this).attr('data-pixel')] = hexc($(this).css('color'));
		});
		sendPixelArray(array,id);
	}
	
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

	function sendPixelArray(_array,_id,_displaymess = true) {
		if (_displaymess){
			$('.eventDisplay').showAlert({message:  'Affichage sur le Dotti en cours ...' ,level: 'warning'});
		}
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
					setTimeout(function() { deleteAlert() }, 2000);
					return;
				}
				if (_displaymess){
					setTimeout(function() { $('.eventDisplay').showAlert({message:  'Affichage effectué' ,level: 'success'}); }, 2000);
					setTimeout(function() { deleteAlert() }, 4000);
				}
				modifyWithoutSave=false;
			}
		});
	}
	
	function deleteAlert() {
		$('.eventDisplay').hideAlert();
	}

	function loadMemoryList() {
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
					setTimeout(function() { deleteAlert() }, 2000);
					return;
				}
				modifyWithoutSave=false;
				if (data.result){
					$('select.memoryload').empty().append(data.result);
				} else {
					$('select.memoryload').empty();
				}
			}
		});
	}
	function loadImage(){
		$.ajax({
			type: "POST",
			url: "plugins/dotti/core/ajax/dotti.ajax.php",
			data: {
				action: "loadImage",
				name: $('select.memoryload').value()
			},
			dataType: 'json',
			global: false,
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) {
				if (data.state != 'ok') {
					$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
					setTimeout(function() { deleteAlert() }, 2000);
					return;
				}
				if (!$.isEmptyObject(data.result)){
					for(var pixelId in data.result){
						$('[data-pixel="'+ pixelId.toString() +'"]').css('color', data.result[pixelId]);
					}
				}
				$('.nameDottiScreen').val($('select.memoryload').find('option:selected').text());
				modifyWithoutSave=false;
			}
		});
	}
	
	function getImageCode(){
		$('.imageDotti').val('');
		$.ajax({
			type: "POST",
			url: "plugins/dotti/core/ajax/dotti.ajax.php",
			data: {
				action: "getImageCode",
				name: $('select.memoryload').val()
			},
			dataType: 'json',
			global: false,
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function(data) {
				if (data.state != 'ok') {
					$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
					setTimeout(function() { deleteAlert()}, 2000);
					return;
				}
				$('.imageDotti').off('change');
				$('.imageDotti').val(data.result);
				modifyWithoutSave=false;
			}
		});
	}
	
function loadTrame(){
var trame = '<center>';
var i = 1;
while (i < 65) {
	var j = 1;
	while (j < 9) {
		var notfirstline = ' pixelFirstLine';
		if (i >= 9){
			var $notfirstline = ' pixelNotFirstLine';
		}
		trame = trame + '<span class="fa fa-square pixel' + notfirstline + '" data-pixel="' + i + '" style="color : #000000;font-size:2.4em; margin-top:0px;margin-left:2x; cursor: pointer;border-radius:0"></span>  ';
		j++;
		i++;
	}
	trame = trame + '</br>';
}
trame = trame + '</center>'
$('.pixelTrame').empty().append(trame);
}