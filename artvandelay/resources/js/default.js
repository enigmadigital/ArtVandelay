(function()
{
	var ACTION_IMPORT = 'import';
	var ACTION_EXPORT = 'export';


	$("input#settings-import, input#settings-export").on('click', function(event)
	{
		var button = $(event.currentTarget);
		var action = button.attr('id').replace('settings-', '');
		var data = { };

		if(action === ACTION_IMPORT)
		{
			data.data = $("textarea#settings-data").val();
		}

		if(action === ACTION_EXPORT)
		{
			data.groups = { };

			$("div.exporter input[type='checkbox']:checked").each(function(index, element)
			{
				data.groups[$(element).val()] = 1;

			});
		}

		Craft.postActionRequest('artVandelay/' + action + '/index', data, function(response)
		{
			if(action === ACTION_EXPORT)
			{
				$("textarea#settings-exported").val(JSON.stringify(response));
			}

		});

	});

})();