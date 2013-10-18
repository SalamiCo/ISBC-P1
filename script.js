/**
 * Shows a essage indicating an error or other condition.
 *
 * @param type Type of the message
 * @param message Message to show
 * @param duration Duration of the message, in milliseconds
 */
function showMessage (type, message, duration) {
  var elt = $('<div/>');

  elt.addClass('message');
  elt.addClass('message-' + type);

  elt.html(message);

  var cont = $('#messages');
  cont.append(elt);

  // Remove it in some time
  setTimeout(function(){
    elt.remove();
  }, Math.max(1500, Math.min(5000, duration||2500)));
}

/**
 * Performs the actual Twitter Search. This function is in charge of doing an
 * AJAX call to the PHP code and handling the results.
 *
 * @param searchTerm Search term to send to Twitter
 */
function performSearch (searchTerm) {
  if (!searchTerm.match(/\S/)) {
    showMessage('error', 'Enter a search term!');
  } else {
    var ajax = $.ajax({
      url: 'core.php',
      type: 'GET',
      data: {'term': searchTerm}
    });
    ajax.done(function(resp){
      if (resp.status != 'ok') {
        showMessage('error', 'Error in request: [' + resp.status + '] ' + resp.error);

      } else {
        drawChart($('#summary')[0], resp.summary);
      }
    });
  }
}

/**
 * Draw the chart
 *
 * @param element Element to draw the chart on
 * @param chartData Data for the chart
 */
function drawChart (element, chartData) {
  var data = new google.visualization.DataTable();
  data.addColumn('string', 'Type');
  data.addColumn('number', 'Amount');
  data.addRows([
    ['Positive', chartData.positive],
    ['Negative', chartData.negative],
  ]);

  // Set chart options
  var options = {
    'title':  'Results Summary',
    'width':  240, 'height': 160,
    'colors': ['#0A0', '#D00']
  };

  // Instantiate and draw our chart, passing in some options.
  var chart = new google.visualization.PieChart(element);
  chart.draw(data, options);
}

/**
 * Called when the form is submitted
 */
function onSearchSubmit () {
  var term = $('#form-search-term').val();
  performSearch(term);

  // Don't perform the submit action
  return false;
}

/* Main Script */
$(function(){
  $('#form-search').on('submit', onSearchSubmit);
});

google.load('visualization', '1.0', {'packages':['corechart']});