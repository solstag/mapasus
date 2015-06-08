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
  if(argv(1)==="data"){
    $r = q("SELECT body, id, uid
            FROM item
            WHERE author_xchan = '%s' AND
            body like '%s';
            ",
            dbesc("WetA6eJ6XSHEtDUEcbWpXa-7m3ypnNtx1Dec7C6oC-0VVKc29WJ2UUbXHcBd-tdA6HKEk-Zn5sXUDvoj5DHGiA"),
            dbesc("%[b]Endereço:[/b]%")
          );
    // AND body LIKE '%[b]EndereÃ§o:[/b]%'
    $ret=array();
    foreach($r as $i=>$row){
      $item=array();
      $item['body'] = $row['body'];
      $item['uid'] = $row['uid'];
      $item['id'] = $row['id'];
      $idx = strpos($item['body'],"[b]Coordenadas:[/b]");

      if($idx===false){
        //geocode
        logger('mapasus geocoding: '.$item['id'].'-'.$item['uid'], LOGGER_DEBUG);
        $addr1 = _mapasus_getdata($item['body'],"[b]Endereço:[/b]");
        $addr2 = _mapasus_getdata($item['body'],"[b]Complemento:[/b]");
        $zip = _mapasus_getdata($item['body'],"[b]CEP:[/b]");
        $addr=array(
          'street'=>$addr1,
          'state'=>'Sao Paulo',
          'country'=>'BR',
          'format'=>'json',
          'limit'=>1
        );
        $jsonurl = "http://nominatim.openstreetmap.org/search?".http_build_query($addr);
        $geocode = json_decode(file_get_contents($jsonurl));
        if(count($geocode)>0){
          logger('mapasus geocoding: success', LOGGER_DEBUG);
          $coords=$geocode[0]->lat.",".$geocode[0]->lon;
          $idx2 = strpos($item['body'],"[b]CEP:[/b]"); // insere as coords depois do cep
          $idx2 = strpos($item['body'],"\n",$idx2); // primeira quebra de linha apor o cep
          $item['body'] = substr_replace($item['body'], "[b]Coordenadas:[/b]".$coords."\r\n", $idx2+1, 0);
          item_store_update($item);
          $item['lat']=$geocode[0]->lat;
          $item['lon']=$geocode[0]->lon;
          $item['body']=bbcode($item['body']);
          $ret[]=$item;
        }else{
          logger('mapasus geocoding: fail', LOGGER_DEBUG);
          $coords="Falha na geocodificação. Corrija o endereço e remova esta linha.";
          $idx2 = strpos($item['body'],"[b]CEP:[/b]"); // insere as coords depois do cep
          $idx2 = strpos($item['body'],"\n",$idx2); // primeira quebra de linha apor o cep
          $item['body'] = substr_replace($item['body'], "[b]Coordenadas:[/b]".$coords."\r\n", $idx2+1, 0);
          item_store_update($item);
        }
      }else{
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
    }
//     print_r($ret);
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
