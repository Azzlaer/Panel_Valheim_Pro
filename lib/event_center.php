<?php
function ec_data_dir(){ return __DIR__ . '/../data'; }
function ec_ensure_dir(){ $d=ec_data_dir(); if(!is_dir($d)) @mkdir($d,0777,true); return $d; }
function ec_events_file(){ return ec_ensure_dir() . '/events.json'; }
function ec_tasks_file(){ return ec_ensure_dir() . '/automation_tasks.json'; }
function ec_state_file(){ return ec_ensure_dir() . '/server_state.json'; }
function ec_read_json($file,$default=[]){ if(!is_file($file)) return $default; $j=json_decode(@file_get_contents($file),true); return is_array($j)?$j:$default; }
function ec_write_json($file,$data){ return file_put_contents($file,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX)!==false; }
function ec_log($type,$message,$level='info',$meta=[]){
    $items=ec_read_json(ec_events_file(),[]);
    $items[]=['id'=>'evt_'.date('YmdHis').'_'.substr(bin2hex(random_bytes(3)),0,6),'ts'=>time(),'date'=>date('Y-m-d H:i:s'),'type'=>$type,'level'=>$level,'message'=>$message,'meta'=>$meta];
    if(count($items)>500) $items=array_slice($items,-500);
    ec_write_json(ec_events_file(),$items);
}
function ec_set_state($state,$detail='',$meta=[]){
    $payload=['state'=>$state,'detail'=>$detail,'updated_at'=>time(),'updated_date'=>date('Y-m-d H:i:s'),'meta'=>$meta];
    ec_write_json(ec_state_file(),$payload);
    ec_log('server_state',$detail?:$state, in_array($state,['CRASHED','ERROR'],true)?'error':'info',['state'=>$state]+$meta);
    return $payload;
}
function ec_get_state(){ return ec_read_json(ec_state_file(),['state'=>'UNKNOWN','detail'=>'Sin estado persistente','updated_at'=>null,'meta'=>[]]); }
function ec_tasks(){ return ec_read_json(ec_tasks_file(),[]); }
function ec_save_tasks($tasks){ return ec_write_json(ec_tasks_file(),array_values($tasks)); }
function ec_next_run($schedule,$now=null){
    $now=$now??time(); $type=$schedule['type']??'interval';
    if($type==='interval') return $now+max(60,intval($schedule['seconds']??3600));
    if($type==='daily'){
        $time=$schedule['time']??'03:00'; [$h,$m]=array_map('intval',array_pad(explode(':',$time),2,0));
        $next=mktime($h,$m,0,date('n',$now),date('j',$now),date('Y',$now)); if($next<=$now) $next=strtotime('+1 day',$next); return $next;
    }
    return $now+3600;
}
