var MAP;
$(document).ready(function(){
  //centra no m'boi mirim
  MAP = L.map('map').setView([-23.6921364,-46.7752808,15], 14);

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
      var color= item.term=='ppsus-inc'? 'red' : 'blue';
      var fillColor= item.term=='ppsus-inc'? '#f03' : '#30f';
      var diviconclass= (item.term=='ppsus-inc' ? 'red-div-icon' : 'blue-div-icon');
//    var marker = L.marker([item.lat, item.lon]).addTo(MAP);
//    var marker = L.circle([item.lat, item.lon], 17, { color: color, fillColor: fillColor, fillOpacity: 0.5 }).addTo(MAP);
      var marker = L.marker([item.lat, item.lon], {icon: L.divIcon({className: diviconclass})}).addTo(MAP);
      marker.bindPopup(item.body,{'maxHeight':100});
    }
  });
}
