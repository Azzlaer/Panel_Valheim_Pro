<?php
require_once __DIR__.'/config.php';
if(empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true){http_response_code(403);header('Content-Type: application/json');echo json_encode(['ok'=>false,'error'=>'Acceso denegado']);exit;}
function pl_out(array $p):never{header('Content-Type: application/json; charset=utf-8');echo json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function pl_cmd(string $cmd):array{$out=[];$code=0;@exec($cmd.' 2>&1',$out,$code);return ['code'=>$code,'output'=>implode("\n",$out)];}
try{
 $os=PHP_OS_FAMILY;$data=['ok'=>true,'os'=>$os,'php'=>PHP_VERSION,'docker'=>null,'linux'=>null];
 if($os==='Linux'){
   $pkgs=[];foreach(['libatomic1','libpulse-dev','libpulse0'] as $p){$r=pl_cmd("dpkg-query -W -f='\${Status}' ".escapeshellarg($p));$pkgs[$p]=$r['code']===0&&str_contains($r['output'],'install ok installed');}
   $docker=pl_cmd('docker --version');$daemon=pl_cmd('docker info --format "{{.ServerVersion}}"');
   $glibc=pl_cmd('ldd --version | head -n 1');
   $data['linux']=['packages'=>$pkgs,'glibc'=>$glibc['output'],'start_server_sh'=>is_file(rtrim(SERVER_BASE,'/').'/start_server.sh'),'docker_script'=>is_file(rtrim(SERVER_BASE,'/').'/docker_start_server.sh')];
   $data['docker']=['installed'=>$docker['code']===0,'version'=>$docker['output'],'daemon'=>$daemon['code']===0,'server_version'=>$daemon['output']];
 }else{
   $data['docker']=['installed'=>false,'daemon'=>false,'note'=>'Docker/Linux Center se activa plenamente al ejecutar el panel en Linux.'];
 }
 pl_out($data);
}catch(Throwable $e){pl_out(['ok'=>false,'error'=>$e->getMessage()]);}
