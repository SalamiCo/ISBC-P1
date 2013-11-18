var packery = null;
var gmap = null;
var gmapMarkers = [];

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

  elt.css({'margin-left': '256px', 'opacity': -0.5});
  elt.animate({'margin-left': 0, 'opacity': 1}, 1000);

  // Remove it in some time
  setTimeout(function(){
    elt.animate({'margin-left': '-256px', 'opacity': -0.5}, 1000, function(){
      elt.remove();
      packery.reloadItems();
      packery.layout();
    });
  }, 1000 + Math.max(1500, Math.min(5000, duration||2500)));

  packery.reloadItems();
  packery.layout();
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
    $('#summary').hide();
    $('.tweet').remove();

    _.each(gmapMarkers, function(obj){
      obj.marker.setMap(null);
    });
    gmapMarkers = [];

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
        $('#summary').show();
        drawChart($('#summary')[0], resp.summary);

        var template = _.template($( "script.template" ).html());
        var eltTweets = $('#tweets');

        var bounds = new google.maps.LatLngBounds();

        for (var t in resp.tweets) {
          var tws = resp.tweets[t];
          tws.user.avatar = 'image.php?url=' + encodeURIComponent(tws.user.avatar);

          var type = tws.positive.length>tws.negative.length?'positive'
              : tws.positive.length<tws.negative.length?'negative'
              : 'neutral';

          var tplData = {
            'tweet': tws,
            'class': 'tweet-' + type
          };
          var twelt = template(tplData);
          eltTweets.append(twelt);

          // Geographic data
          if (tws.geo) {
            var pos = new google.maps.LatLng(
              tws.geo.coordinates[1],
              tws.geo.coordinates[0]
            );

            var marker = new google.maps.Marker({
              'position': pos,
              'map': gmap,
              'icon': {
                'url': tws.user.avatar + '&marker=' + type,
                'size': new google.maps.Size(24, 28),
                'scaledSize': new google.maps.Size(24, 28),
                'origin': new google.maps.Point(0,0),
                'anchor': new google.maps.Point(12, 28)
              },
            });

            google.maps.event.addListener(marker, 'click', function(){
              infowindow.open(gmap, marker);
            });

            gmapMarkers.push({'marker': marker});
            bounds.extend(pos);
          }
        }

        gmap.fitBounds(bounds);
        if (gmap.getZoom() > 16) {
          gmap.setZoom(16);
        }
      }

      packery.reloadItems();
      packery.layout();
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
    ['Negative', chartData.negative],
    ['Neutral', chartData.neutral],
    ['Positive', chartData.positive]
  ]);

  // Set chart options
  var options = {
    'width':  $(element).width(),
    'height': $(element).height(),
    'colors': ['#D00', '#CCC', '#0A0'],
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
  google.visualization.events.addListener(chart, 'select', function(){
    var select = chart.getSelection();

    if (select.length == 0) {
      $('.tweet-negative, .tweet-positive, .tweet-neutral').show();
    } else {
      $('.tweet-negative, .tweet-positive, .tweet-neutral').hide();

      for (var s in select) {
        var row = select[s].row;
        var cls = '.tweet-' + (['negative', 'neutral', 'positive'][row]);
        $(cls).show();
      }
    }

    packery.layout();
  });
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
  
  packery = new Packery($('#tweets')[0], {
    'columnWidth': $('.tweet-sizer')[0],
    'gutter': $('.tweet-gutter-sizer')[0],
    'stamp': '.stamp',
    'itemSelector': '.tweet',
    'transitionDuration': '300ms',
  });

  
});

if (google) {
  google.load('visualization', '1.0', {'packages':['corechart']});
  
  google.maps.visualRefresh = true;
  google.maps.event.addDomListener(window, 'load', function(){
    var mapOptions = {
      center: new google.maps.LatLng(40.40, -3.68),
      zoom: 5,
      mapTypeId: google.maps.MapTypeId.ROADMAP,

      panControl: false,
      zoomControl: true,
      mapTypeControl: false,
      scaleControl: false,
      streetViewControl: false,
      overviewMapControl: false,

      zoomControlOptions: {
        style: google.maps.ZoomControlStyle.SMALL
      }
    };
    
    gmap = new google.maps.Map(document.getElementById("map-canvas"),
        mapOptions);
  });
}