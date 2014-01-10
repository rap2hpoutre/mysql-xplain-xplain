$(function() {

	var Actions = {
		addContext: (function(alreadyInclude) {
			return function(e) {
				e.stopPropagation();
				e.preventDefault();
				if(!alreadyInclude) {
					$('<textarea name="" class="form-control" rows="8" placeholder="Type your SQL query here..."></textarea>').insertBefore('#query');
					alreadyInclude = true;
				}
			}
		})(false)
	};

	$('[data-action]').each(function() {
		$(this).bind({
			click: function(e) {
				var fnName = $(this).data('action');
				if(typeof Actions[fnName] === 'function') {
					Actions[fnName].apply(null, [e]);
				}
			}
		});
	});
});