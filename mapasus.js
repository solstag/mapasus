var MAP;
$(document).ready(function(){
  //centra no m'boi mirim
  MAP = L.map('map').setView([-23.6861106,-46.770556,15], 14);

  // nao usar ssl resulta em warn, mas fina sensivelmente mais rapido
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
               {
                 attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
                 minZoom: 2,
                 maxZoom: 19,
                 subdomains: ['a','b','c']
               }
             ).addTo(MAP);
  loadData();
});

function loadData(){
  $.getJSON( "/mapasus/data", function( data ) {
    for (i = 0; i < data.length; i++) {
      var item=data[i];
      var marker = L.marker([item.lat, item.lon]).addTo(MAP);
      marker.bindPopup(item.body,{'maxHeight':100});
    }
  });
}