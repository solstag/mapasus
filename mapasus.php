<?php
/**
 *
 * Name: Mapa SUS
 * Description: Mostra alertas de internação em um mapa.
 * Version: 0.1
 * Author: Oda <oda@member.fsf.org>
 *
 */


function mapasus_load(){
}
function mapasus_unload(){
}
// function mapasus_install(){}
// function mapasus_uninstall(){}

function mapasus_module(){ return; }

function _mapasus_getdata($text,$field){
  $len = strlen($field);
  $idx1 = strpos($text,$field);
  $idx2 = strpos($text,"\n",$idx1);
  $val=substr($text,$idx1+$len,$idx2-$idx1-$len-1);
  return $val;
}

function mapasus_init($a){
  // deixei os if's separados para facilitar leitura
  if(! local_channel())
    return;
  if (! ($a->account['account_service_class'] === 'ppsus') )
    return;
  if(argc()==0)
    return;
  if(argv(1)==="geocode"){
    mapasus_cron_geocode();
    killme();
  }
  if(argv(1)==="data"){
    $r = q("SELECT body, id, uid
            FROM item
            WHERE owner_xchan = '%s' AND
            body like '%s';
            ",
            dbesc("WetA6eJ6XSHEtDUEcbWpXa-7m3ypnNtx1Dec7C6oC-0VVKc29WJ2UUbXHcBd-tdA6HKEk-Zn5sXUDvoj5DHGiA"),
            dbesc("%[b]Coordenadas:[/b]%")
          );
    // AND body LIKE '%[b]EndereÃ§o:[/b]%'
    $ret=array();
    foreach($r as $i=>$row){
      $item=array();
      $item['body'] = $row['body'];
      $item['uid'] = $row['uid'];
      $item['id'] = $row['id'];
      $coords = _mapasus_getdata($item['body'],"[b]Coordenadas:[/b]");
      if($coords!="Falha na geocodificação. Corrija o endereço e remova esta linha."){
        $aux=explode(",",$coords);
        $item['lat']=$aux[0];
        $item['lon']=$aux[1];
        $item['body']=bbcode($item['body']);
        $ret[]=$item;
      }else{
        logger('mapasus geocoding: failed', LOGGER_DEBUG);
      }
    }
    echo json_encode($ret);
    killme();
  }
}
// function mapasus_aside($a){}
// function mapasus_post($a){}
function mapasus_content($a){
  //   for($x = 0; $x < argc(); $x ++)
  //     echo $x . ' ' . argv($x);
  logger('mapasus content', LOGGER_DEBUG);

  // deixei os if's separados para facilitar leitura
  if(! local_channel())
    return;
  if (! ($a->account['account_service_class'] === 'ppsus') )
    return;


  //TODO: servir o leaflet localmente
  /**
   * load css
   */ 
  $a->page['htmlhead'] .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.css" />' . "\r\n";
  $a->page['htmlhead'] .= '<link rel="stylesheet" href="'.$a->get_baseurl().'/addon/mapasus/mapasus.css" type="text/css" media="screen" />' . "\r\n";
  /**
   * load js
   */ 
  $a->page['htmlhead'] .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.js"></script>' . "\r\n";
  $a->page['htmlhead'] .= '<script src="'.$a->get_baseurl().'/addon/mapasus/mapasus.js"></script>' . "\r\n";

  $tpl = get_markup_template('mapasus_mapa.tpl');
  $o = replace_macros($tpl,array(
    '$title' => t('Mapa de Internações')
  ));

  return $o;
}

function mapasus_cron_geocode(){
  $delay=200;
  $max=100;
  $r = q("SELECT body, id, uid
          FROM item
          WHERE owner_xchan = '%s' AND
          body like '%s' AND
          body NOT like '%s'
          LIMIT %d;
          ",
          dbesc("WetA6eJ6XSHEtDUEcbWpXa-7m3ypnNtx1Dec7C6oC-0VVKc29WJ2UUbXHcBd-tdA6HKEk-Zn5sXUDvoj5DHGiA"),
          dbesc("%[b]Endereço:[/b]%"),
          dbesc("%[b]Coordenadas:[/b]%"),
          intval($max)
        );
  foreach($r as $i=>$row){
    $item=array();
    $item['body'] = $row['body'];
    $item['uid'] = $row['uid'];
    $item['id'] = $row['id'];

    logger('mapasus geocoding: '.$item['id'].'-'.$item['uid'], LOGGER_DEBUG);

//     $zip = _mapasus_getdata($item['body'],"[b]CEP:[/b]");
    $addr=array(
      'address'=>_mapasus_getdata($item['body'],"[b]Endereço:[/b]"),
      'components'=>'country:BR|administrative_area:SP|locality:São Paulo',
      'key'=>''
    );
    $jsonurl = "https://maps.googleapis.com/maps/api/geocode/json?".http_build_query($addr);
    $geocode = json_decode(file_get_contents($jsonurl));
    if($geocode->status == "OK" && count($geocode->results)>0 && $geocode->results[0]->types[0]!='locality'){
      logger('mapasus geocoding: success', LOGGER_DEBUG);
      $lat=$geocode->results[0]->geometry->location->lat;
      $lon=$geocode->results[0]->geometry->location->lng;
      $coords=$lat.",".$lon;
      $idx2 = strpos($item['body'],"[b]CEP:[/b]"); // insere as coords depois do cep
      $idx2 = strpos($item['body'],"\n",$idx2); // primeira quebra de linha apos o cep
      $item['body'] = substr_replace($item['body'], "[b]Coordenadas:[/b]".$coords."\r\n", $idx2+1, 0);
      item_store_update($item);
    } else if($geocode->status == "OVER_QUERY_LIMIT") {
      // too fast
      $delay += 200;
    }else{
      logger('mapasus geocoding: fail', LOGGER_DEBUG);
      $coords="Falha na geocodificação. Corrija o endereço e remova esta linha.";
      $idx2 = strpos($item['body'],"[b]CEP:[/b]"); // insere as coords depois do cep
      $idx2 = strpos($item['body'],"\n",$idx2); // primeira quebra de linha apor o cep
      $item['body'] = substr_replace($item['body'], "[b]Coordenadas:[/b]".$coords."\r\n", $idx2+1, 0);
      item_store_update($item);
    }
    usleep($delay);
  }
}