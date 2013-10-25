var msnry;

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
    $('#form-search-send').attr('disabled','disabled');
    $('#loading').show();

    var ajax = $.ajax({
      url: 'core.php',
      type: 'GET',
      data: {'term': searchTerm}
    });

    ajax.always(function(){
      $('#form-search-send').removeAttr('disabled');
      $('#loading').hide();
    });

    ajax.done(function(resp){
      console.log(resp);
      if (resp.status != 'ok') {
        showMessage('error', 'Error in request: [' + resp.status + '] ' + resp.error);

      } else {
        drawChart($('#summary')[0], resp.summary);

        var template = _.template($( "script.template" ).html());
        var eltTweets = $('#tweets');
        $('#tweets .tweet').remove();

        for (var t in resp.tweets) {
          var tws = resp.tweets[t];
          console.log(tws);
          var tplData = {
            'tweet': tws,
            'class':
                tws.positive.length>tws.negative.length?'tweet-positive'
              : tws.positive.length<tws.negative.length?'tweet-negative'
              : 'tweet-neutral'
          };
          console.log(tplData);
          eltTweets.append(template(tplData));
        }
      }

      msnry.reloadItems();
      msnry.layout();
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
    ['Neutral', chartData.neutral],
    ['Negative', chartData.negative]
  ]);

  // Set chart options
  var options = {
    'title':  'Results Summary',
    'width':  $(element).width(), 'height': $(element).height(),
    'colors': ['#0A0', '#CCC', '#D00'],
    'backgroundColor': {
        'opacity': 0
     },
     'legend': {'position': 'none'},
     'title': {'position': 'none'},
     'chartArea': {
        'width': '90%', 'height': '90%'
      }
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
  _.templateSettings.variable = "tpl";
  $('#form-search').on('submit', onSearchSubmit);
  drawChart($('#summary')[0], {positive: 0, negative: 0, neutral: 1});
  
  msnry = new Masonry($('#tweets')[0], {
    'columnWidth': $('.tweet-sizer')[0],
    'itemSelector': '.tweet',
    'transitionDuration': '0s',
    'gutter': 8
  });
});

google.load('visualization', '1.0', {'packages':['corechart']});