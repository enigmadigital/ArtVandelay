(function() {

  $('#export').on('submit', function(e) {
    e.preventDefault();

    $('#exportResultContainer').removeClass('hidden');
    $('#exportResult').text('');

    Craft.postActionRequest('artVandelay/export', $(this).serialize(), function(response) {
      var json = JSON.stringify(response, undefined, 2);
      $('#exportResult').text(json);
    });
  });

})();
