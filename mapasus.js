var MAP;
var LAYERS_DOTS = [];
var LAYERS_HEAT = [];
var ITEMS = [];
var TERMS = [];

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
  loadTerms();
  
  $(".updateLayer").click(function(){
    var layer = $(this).parent();
    updateLayer(layer);
  });
  $("#newLayer").click(function(){
    var nl=$("#layer_1").clone();
    var num=$("#layers .layer").size()+1;
    nl.attr("id","layer_"+num);
    nl.data("num",num);
    $("legend",nl).text("Layer "+num);
    $("input[name=layer_1_type]",nl).attr("name","layer_"+num+"_type");
    $(".wcolorpicker",nl).data("num",num)
    $(".wcolorpicker",nl).empty();
    nl.insertBefore(this);
    $(".wcolorpicker",nl).each(setupColorpicker);
    $(".s_dsel").each(setupStartDatetimepicker);
    $(".f_dsel").each(setupFinishDatetimepicker);
    $(".updateLayer",nl).click(function(){
      var layer = $(this).parent();
      updateLayer(layer);
    });
    $('#layer_'+(num-1)+' .removeLayer').hide();
    $(".removeLayer",nl).show();
    $(".removeLayer",nl).click(function(){
      var layer = $(this).parent();
      removeLayer(layer);
    });
  });

  $('.removeLayer').hide();
  $(".wcolorpicker").each(setupColorpicker);
  $(".s_dsel").each(setupStartDatetimepicker);
  $(".f_dsel").each(setupFinishDatetimepicker);
  $("fieldset.collapsible").collapse();
  $("fieldset.collapsibleClosed").collapse( { closed: true } );

});

function loadTerms(){
  $.getJSON( "/mapasus/terms", function( data ) {
    TERMS = data;
    $.each(TERMS, function(key, term) {
      $('#layer_1 .terms_sel')
          .append($(
          '<select data-term='+term.term+' class="terms_sel_term">'+
            '<option value="null" selected>Indiferente</option>'+
            '<option value="true">Sim</option>'+
            '<option value="false">Não</option>'+
          '</select><span> '+term.term+'</span>'
          ));
    });
  });
}

function loadData(){
  $.getJSON( "/mapasus/data", function( data ) {
    ITEMS = data;
    var total = updateLayer($("#layer_1"));
//    if (total>0)
//      MAP.fitBounds(LAYERS_DOTS[1].getBounds());
  });
}

function setupStartDatetimepicker(idx){
  var d=new Date();
  d.setFullYear(new Date().getFullYear() - 1);
  $(this).datetimepicker({defaultDate:d,format:'Y-m-d',timepicker:false,closeOnDateSelect:true});
}
function setupFinishDatetimepicker(idx){
  var d=new Date();
  $(this).datetimepicker({defaultDate:d,format:'Y-m-d',timepicker:false,closeOnDateSelect:true});
}

function setupColorpicker(idx){
  var that = this;
  $(this).wColorPicker({
    onSelect: function(color){
//       console.log($('#layer_'+$(that).data("num")).prop("tagName"))
      $('#layer_'+$(that).data("num")).data('color',color);
      $(this).css('background', color).val(color);
    },
    theme:'black',
    mode:'hover',
    effect:'fade',
    position:'rm',
    color:'#000000'
  });
}

function removeLayer(layer){
  var layer_num=layer.data("num");
  if(layer_num>=3){
    $('#layer_'+(layer_num-1)+' .removeLayer').show();
  }

  if (layer_num in LAYERS_HEAT){
    LAYERS_HEAT[layer_num].setLatLngs([]);
  }
  if (layer_num in LAYERS_DOTS){
    LAYERS_DOTS[layer_num].clearLayers();
  }
  $(layer).remove();
}

function updateLayer(layer){
  var layer_num=layer.data("num");

  var type = $("input[name=layer_"+layer_num+"_type]:checked").val();

  if(type=='heat'){
    if (layer_num in LAYERS_HEAT){
      LAYERS_HEAT[layer_num].setLatLngs([]);
    }else{
      LAYERS_HEAT[layer_num] = L.heatLayer([],{minOpacity: 0.5});
      LAYERS_HEAT[layer_num].addTo(MAP);
    }
    if (layer_num in LAYERS_DOTS){
      LAYERS_DOTS[layer_num].clearLayers();
    }
  }else if(type=='dots'){
    if (layer_num in LAYERS_DOTS){
      LAYERS_DOTS[layer_num].clearLayers();
    }else{
      LAYERS_DOTS[layer_num] = L.featureGroup();
      LAYERS_DOTS[layer_num].addTo(MAP);
    }
    if (layer_num in LAYERS_HEAT){
      LAYERS_HEAT[layer_num].setLatLngs([]);
    }
  }

  var color = $(layer).data("color");
  
  // Optei por fazer o filtro na maquina do cliente, isso permite mais opcoes de interacao no futuro
  //     $item['body'] = $row['body'];
  //     $item['uid'] = $row['uid'];
  //     $item['id'] = $row['id'];
  //     $item['term']= $row['term'];
  //     $item['created']= $row['created'];
  //     $item['edited']= $row['edited'];
  var s_dsel = $('.s_dsel',layer).val();
  var f_dsel = $('.f_dsel',layer).val();
//   var ppsus_inc = $('.ppsus_inc',layer).val();
  var keyword = $('.keyword',layer).val();

  var total=0;
  for(var i = 0; i<ITEMS.length;i++){
    if(!ITEMS[i]) continue;

    var item = ITEMS[i];

//     if(ppsus_inc=='true' && item.term!='ppsus-inc')
//       continue;
//     if(ppsus_inc=='false' && item.term=='ppsus-inc')
//       continue;
    var skip = false;
    $('.terms_sel_term',layer).each(function(idx) {
      var val = $(this).val();
      var t = $(this).data("term");
      if(val=='true' && item.terms.indexOf(t)==-1)
        skip = true;
      if(val=='false' && item.terms.indexOf(t)!=-1)
        skip = true;
    });
    if(skip) continue;

    if((s_dsel !="" && item.created<s_dsel) || (f_dsel!="" && f_dsel<item.created))
      continue;

    total++;
    if(type=='heat'){
      LAYERS_HEAT[layer_num].addLatLng([item.lat, item.lon]);
    }else if(type=='dots'){
      var marker = createMarker(layer_num,color,item);
      LAYERS_DOTS[layer_num].addLayer(marker);
    }
  }
  $(".meta .qtd",layer).text(total);
  $(".meta .total",layer).text(ITEMS.length);
  return total;
}

function createMarker(layer_num,color,item){
  var marker = L.circleMarker([item.lat, item.lon], {
    color: color,
    fillColor: color,
    fillOpacity: 0.8 // changed from 0.5 to work over heatmap
  }).bindPopup(
    item.body, {maxWidth: 300, minWidth: 250, maxHeight: 300, autoPan: true, closeButton: true, autoPanPadding: [5, 5]}
  );
  marker.layer_num=layer_num;  
  return marker;
}
