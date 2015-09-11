(function() {

  $('#step3Form').on('submit', function(e) {
    var jsonResult = '{ "applyTo": [';
    var hasChecked = false;


    $('.sectionTabs:checked').each(function(e)
    {
      if (hasChecked)
        jsonResult += ', ';
      jsonResult += '"' + $(this).val() + '"';
      hasChecked = true;
    });

    if (!hasChecked)
    {
      e.preventDefault();
      alert('Please select at least one entry type');
    }

    jsonResult += ']}'

    alert(jsonResult);
    $('#applyTo').val(jsonResult);
  });

  $('#export').on('submit', function(e) {
    e.preventDefault();

    $('#exportResultContainer').removeClass('hidden');
    $('#exportResult').text('');

    var action = $(this).find("[name='action']").val();

    Craft.postActionRequest(action, $(this).serialize(), function(response) {
      var json = JSON.stringify(response, undefined, 2);
      $('#exportResult').text(json);
    });
  });

})();
