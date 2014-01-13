$(function() {

	var Actions = {
		// Ajout de la zone de contexte des requetes
        addContext: (function(alreadyInclude) {
			return function(e) {
				e.stopPropagation();
				e.preventDefault();
				if(!alreadyInclude) {
                    $('#context_queries').show();
					alreadyInclude = true;
				}
			}
		})(false),
        // Les infos sur une donnée de l'explain
        showInfos: (function() {
            return function(e, params) {
                e.stopPropagation();
                e.preventDefault();
                $('#infos_text').html(params["infos"]).parent().show();
            }
        })(false)
	};

	$('[data-action]').each(function() {
		$(this).bind({
			click: function(e) {
				var fnName = $(this).data('action');
				if(typeof Actions[fnName] === 'function') {
					var params = $(this).data('params');
					try {
						params = JSON.parse(params);
					} catch(e) { }
					Actions[fnName].apply(null, [e, params]);
				}
			}
		});
	});
});