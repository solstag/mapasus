<?php
/**
 *
 * Name: Mapa SUS
 * Description: Mostra alertas de internação em um mapa.
 * Version: 0.1
 * Author: Oda <oda@member.fsf.org>
 *
 */

// function mapasus_install(){}
// function mapasus_uninstall(){}

function mapasus_load(){}

function mapasus_unload(){}

function mapasus_module(){ return; }

function mapasus_init($a){
  if(! local_channel())
    return;
  if (! ($a->account['account_service_class'] === 'ppsus') )
    return;

  // uid of comunicamboi at mobiliza.org.br
  $uid=106;

  if(argc()==1)
    return;
  if(argv(1)==="geocode"){
    if(argc()==2)
      mapasus_channel_geocode($uid);
    elseif(argc()==3)
      mapasus_geocode(intval(argv(2)));
    killme();
  }
  if(argv(1)==="clean"){
    if(argc()==2)
      mapasus_channel_clean_geocode($uid);
    elseif(argc()==3)
      mapasus_clean_geocode(intval(argv(2)));
    killme();
  }
  if(argv(1)==="data"){
    mapasus_channel_data($uid);
    killme();
  }
  if(argv(1)==="terms"){
    mapasus_channel_terms($uid);
    killme();
  }
}

// function mapasus_aside($a){}
// function mapasus_post($a){}

function mapasus_content($a){
  logger('mapasus content', LOGGER_DEBUG);

  if(! local_channel())
    return;
  if(! ($a->account['account_service_class'] === 'ppsus') )
    return;

  /**
   * load css
   */ 
  $a->page['htmlhead'] .= '<link rel="stylesheet" href="'.$a->get_baseurl().'/addon/mapasus/leaflet/leaflet.css" />' . "\r\n";
  $a->page['htmlhead'] .= '<link rel="stylesheet" href="'.$a->get_baseurl().'/addon/mapasus/wColorPicker/wColorPicker.min.css" />' . "\r\n";
  $a->page['htmlhead'] .= '<link rel="stylesheet" href="'.$a->get_baseurl().'/addon/mapasus/mapasus.css" type="text/css" media="screen" />' . "\r\n";
  /**
   * load js
   */ 
  $a->page['htmlhead'] .= '<script src="'.$a->get_baseurl().'/addon/mapasus/jquery.collapsible-v.2.1.3.js"></script>' . "\r\n";
  $a->page['htmlhead'] .= '<script src="'.$a->get_baseurl().'/addon/mapasus/leaflet/leaflet.js"></script>' . "\r\n";
  $a->page['htmlhead'] .= '<script src="'.$a->get_baseurl().'/addon/mapasus/Leaflet.heat/dist/leaflet-heat.js"></script>' . "\r\n";
  $a->page['htmlhead'] .= '<script src="'.$a->get_baseurl().'/addon/mapasus/wColorPicker/wColorPicker.min.js"></script>' . "\r\n";
  $a->page['htmlhead'] .= '<script src="'.$a->get_baseurl().'/addon/mapasus/mapasus.js"></script>' . "\r\n";

  $tpl = get_markup_template('mapasus_mapa.tpl','addon/mapasus/');
  
  
  $tz = date_default_timezone_get();
  $year = datetime_convert('UTC', $tz, 'now', 'Y'); // ano atual, talvez usar time zone seja preciosismo
  $f = get_config('system','event_input_format'); // algo como 'ymd' or 'mdy', mas na realidade nao tem suporte

  // TODO: colocar as strings aqui e traduzir
  $o = replace_macros($tpl,array(
    '$title' => t('Mapa de Internações'),
//     '$subtitle' => t('Pontos vermelhos foram incluídos na pesquisa PPSUS'),
//     '$s_text' => t('Event Starts:'),
//     '$s_dsel' => datetimesel($f,DateTime::createFromFormat('Y',$year-5),DateTime::createFromFormat('Y',$year+5),DateTime::createFromFormat('Y',$year-5),'s_dsel',true,false,'','',false),
//     '$f_text' => t('Event Finishes:'),
//     '$f_dsel' => datetimesel($f,DateTime::createFromFormat('Y',$year-5),DateTime::createFromFormat('Y',$year+5),DateTime::createFromFormat('Y',$year+5),'f_dsel',true,false,'s_dsel','',false),
//     '$filter_text' => t('Filter'),
//     '$add_text' => t('New layer')
  ));

  return $o;
}

function _mapasus_getdata($text,$field){
  $len = strlen($field);
  $idx1 = strpos($text,$field);
  $idx2 = strpos($text,"\n",$idx1);
  $val=substr($text,$idx1+$len,$idx2-$idx1-$len-1);
  return trim($val);
}


// SELECT DISTINCT term.term
//           FROM item
//           left join term on item.id=term.oid
//           WHERE item.uid = 106 AND
//           item.body like '%[b]Coordenadas:[/b]%' 
          

function mapasus_channel_data($uid){
//   ini_set ("zlib.output_compression","On");
  $sql_extra = item_permissions_sql($uid);
//term.term, 
//           left join term on item.id=term.oid and term.term='ppsus-inc'
  $r = q("SELECT item.body, item.id, item.uid, item.created, item.edited
          FROM item
          WHERE item.uid = %d AND
          item.body like '%s' $sql_extra
          ",
          intval($uid),
          dbesc("%[b]Coordenadas:[/b]%")
        );

  $ret=array();
  foreach($r as $i=>$row){
    $item=array();
    $item['body'] = $row['body'];
    $item['uid'] = $row['uid'];
    $item['id'] = $row['id'];
//     $item['term']= $row['term'];
    $item['created']= $row['created'];
    $item['edited']= $row['edited'];

    $item['terms']= array();
    $s = q("SELECT term FROM term WHERE term.oid = %d",intval($item['id']));
    foreach($s as $j=>$term){
      $item['terms'][] = $term['term'];
    }

    $coords = _mapasus_getdata($item['body'],"[b]Coordenadas:[/b]");
    if($coords!="Falha na geocodificação. Corrija o endereço e remova esta linha."){
      $aux=explode(",",$coords);
      $item['lat']=$aux[0];
      $item['lon']=$aux[1];
      $item['body']=bbcode($item['body']);
      $ret[]=$item;
//     }else{
//       $item['lat']=0;
//       $item['lon']=0;
//       logger('mapasus geocoding: failed', LOGGER_DEBUG);
    }
  }
  echo json_encode($ret);
}

function mapasus_channel_terms($uid){
  $sql_extra = item_permissions_sql($uid);
  $r = q("SELECT DISTINCT term.term
          FROM item
          left join term on item.id=term.oid
          WHERE item.uid = %d AND
          item.body like '%s' AND
          term.term like '%s'
          $sql_extra
          ",
          intval($uid),
          dbesc("%[b]Coordenadas:[/b]%"),
          dbesc("ppsus%")
        );

  echo json_encode($r);
}



function mapasus_channel_geocode($uid){
  $delay=200;
  $max=100;
  $r = q("SELECT id
          FROM item
          WHERE uid = %d AND
          body like '%s' AND
          body NOT like '%s'
          LIMIT %d;
          ",
          intval($uid),
          dbesc("%[b]Endereço:[/b]%"),
          dbesc("%[b]Coordenadas:[/b]%"),
          intval($max)
        );
  foreach($r as $i=>$row){
    $status=mapasus_geocode($row['id']);
    if($status == "OVER_QUERY_LIMIT") {
      $delay += 200;
    }
    echo $i.". ".$row['id'].": ".$status."\n<br>";
    usleep($delay);
  }
}

function mapasus_channel_clean_geocode($uid){
  $max=100;
  $r = q("SELECT id
          FROM item
          WHERE uid = %d AND
          body like '%s' AND
          body like '%s'
          LIMIT %d;
          ",
          intval($uid),
          dbesc("%[b]Endereço:[/b]%"),
          dbesc("%[b]Coordenadas:[/b]%"),
          intval($max)
        );
  foreach($r as $i=>$row){
    $status=mapasus_clean_geocode($row['id']);
  }
}

function mapasus_geocode($id){
  $r = q("SELECT body, uid
          FROM item
          WHERE id = %d AND
          body like '%s' AND
          body NOT like '%s'
          ",
          intval($id),
          dbesc("%[b]Endereço:[/b]%"),
          dbesc("%[b]Coordenadas:[/b]%")
        );
  if(count($r)){
    if(local_channel() != $r[0]['uid'])
      return "PERMISSION_DENIED";
    $body=$r[0]['body'];
    $uid=$r[0]['uid'];
  }
  else return "POST_NOT_FOUND";

  $item=array();
  $item['body'] = $body;
  $item['uid'] = $uid;
  $item['id'] = $id;

  logger('mapasus geocoding: '.$item['id'], LOGGER_DEBUG);

  $postal_code = _mapasus_getdata($item['body'],"[b]CEP:[/b]");
  $addr=array(
    'address'=>_mapasus_getdata($item['body'],"[b]Endereço:[/b]"),
    'components'=>'country:BR|administrative_area:SP|locality:São Paulo'
                  . ($postal_code != '' ? '|postal_code:'.$postal_code : '' ),
    'key'=>''
  );
  $jsonurl = "https://maps.googleapis.com/maps/api/geocode/json?".http_build_query($addr);
  $geocode = json_decode(file_get_contents($jsonurl));
  echo "count($geocode->results): ".count($geocode->results)."\n<br>";
  echo "$geocode->results[0]->types[0]: ".$geocode->results[0]->types[0]."\n<br>";
  if($geocode->status == "OK" && count($geocode->results)>0 && $geocode->results[0]->types[0]!='locality'){
    logger('mapasus geocoding: success for ' . var_export($addr['address'],true), LOGGER_DEBUG);
    $lat=$geocode->results[0]->geometry->location->lat;
    $lon=$geocode->results[0]->geometry->location->lng;
    $coords=$lat.",".$lon;
    $idx2 = strpos($item['body'],"[b]CEP:[/b]"); // insere as coords depois do cep
    $idx2 = strpos($item['body'],"\n",$idx2); // primeira quebra de linha após o cep
    $item['body'] = substr_replace($item['body'], "[b]Coordenadas:[/b] ".$coords."\r\n", $idx2+1, 0);
    item_store_update($item);
  } else if($geocode->status != "OVER_QUERY_LIMIT") {
    logger('mapasus geocoding: fail for ' . var_export($addr['address'],true), LOGGER_DEBUG);
    $coords="Falha na geocodificação. Corrija o endereço e remova esta linha.";
    $idx2 = strpos($item['body'],"[b]CEP:[/b]"); // insere as coords depois do cep
    $idx2 = strpos($item['body'],"\n",$idx2); // primeira quebra de linha após o cep
    $item['body'] = substr_replace($item['body'], "[b]Coordenadas:[/b] ".$coords."\r\n", $idx2+1, 0);
//    item_store_update($item);
  }
  return $geocode->status;
}

function mapasus_clean_geocode($id){
  $r = q("SELECT body, uid
          FROM item
          WHERE id = %d AND
          body like '%s' AND
          body like '%s'
          ",
          intval($id),
          dbesc("%[b]Endereço:[/b]%"),
          dbesc("%[b]Coordenadas:[/b]%")
        );
  if(count($r)){
    if(local_channel() != $r[0]['uid'])
      return "PERMISSION_DENIED";
    $body=$r[0]['body'];
    $uid=$r[0]['uid'];
  }
  else return "POST_NOT_FOUND";

  $item=array();
  $item['body'] = $body;
  $item['uid'] = $uid;
  $item['id'] = $id;

  logger('mapasus cleaning geocoding: '.$item['id'], LOGGER_DEBUG);

  $idx1 = strpos($item['body'],"[b]Coordenadas:[/b]");
  $idx2 = strpos($item['body'],"\n",$idx1); // primeira quebra de linha após coordenadas
  $item['body'] = substr_replace($item['body'], "", $idx1, $idx2-$idx1+1);
  item_store_update($item);
}

