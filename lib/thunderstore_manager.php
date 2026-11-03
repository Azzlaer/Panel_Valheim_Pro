<?php
require_once __DIR__ . '/bepinex_manager.php';

const TM_PACKAGE_LIST = 'https://thunderstore.io/c/valheim/api/v1/package/';
const TM_PACKAGE_API  = 'https://thunderstore.io/api/experimental/package/';

function tm_cache_file(): string { return bm_data_dir() . DIRECTORY_SEPARATOR . 'thunderstore_valheim_packages.json'; }
function tm_registry_file(): string { return bm_data_dir() . DIRECTORY_SEPARATOR . 'thunderstore_mods.json'; }

function tm_registry(): array {
    $f=tm_registry_file(); if(!is_file($f)) return [];
    $d=json_decode((string)file_get_contents($f),true); return is_array($d)?$d:[];
}
function tm_save_registry(array $d): void {
    if(@file_put_contents(tm_registry_file(),json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),LOCK_EX)===false)
        throw new RuntimeException('No se pudo guardar registro de mods');
}

function tm_package_index(bool $refresh=false): array {
    $cache=tm_cache_file();
    if(!$refresh && is_file($cache) && filemtime($cache)>time()-1800){
        $d=json_decode((string)file_get_contents($cache),true); if(is_array($d)) return $d;
    }
    $raw=bm_http_get(TM_PACKAGE_LIST,120);
    $d=json_decode($raw,true);
    if(!is_array($d)) throw new RuntimeException('Índice Thunderstore inválido');
    @file_put_contents($cache,$raw,LOCK_EX);
    return $d;
}

function tm_search(string $q, int $limit=40): array {
    $lower = function(string $s): string { return function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); };\n    $q=$lower(trim($q)); if($q==='') return [];
    $reg=tm_registry(); $out=[];
    foreach(tm_package_index() as $p){
        $owner=(string)($p['owner']??$p['namespace']??'');
        $name=(string)($p['name']??'');
        $versions=$p['versions']??[];
        if(!is_array($versions)||!$versions) continue;
        $latest=$versions[0];
        $desc=(string)($latest['description']??$p['description']??'');
        $hay=$lower($owner.' '.$name.' '.$desc);
        if(!str_contains($hay,$q)) continue;
        $key=$owner.'-'.$name;
        $out[]=[
            'namespace'=>$owner,'name'=>$name,'key'=>$key,
            'description'=>$desc,
            'version'=>(string)($latest['version_number']??''),
            'downloads'=>(int)($latest['downloads']??0),
            'download_url'=>(string)($latest['download_url']??''),
            'dependencies'=>$latest['dependencies']??[],
            'installed_version'=>$reg[$key]['version']??null,
            'deprecated'=>(bool)($p['is_deprecated']??false),
        ];
        if(count($out)>=$limit) break;
    }
    return $out;
}

function tm_package(string $ns,string $name,?string $version=null): array {
    $ns=trim($ns);$name=trim($name);
    if(!preg_match('/^[A-Za-z0-9_]+$/',$ns)||!preg_match('/^[A-Za-z0-9_]+$/',$name)) throw new RuntimeException('Identificador Thunderstore inválido');
    $url=TM_PACKAGE_API.rawurlencode($ns).'/'.rawurlencode($name).'/'.($version?rawurlencode($version).'/':'');
    $d=json_decode(bm_http_get($url,45),true);
    if(!is_array($d)) throw new RuntimeException('Metadata Thunderstore inválida');
    $v=$version ? $d : ($d['latest']??null);
    if(!is_array($v)) throw new RuntimeException('Versión Thunderstore no encontrada');
    return ['package'=>$d,'version'=>$v];
}

function tm_dep_parse(string $dep): ?array {
    if(!preg_match('/^([A-Za-z0-9_]+)-([A-Za-z0-9_]+)-(.+)$/',$dep,$m)) return null;
    return ['namespace'=>$m[1],'name'=>$m[2],'version'=>$m[3]];
}

function tm_safe_zip_entries(ZipArchive $zip): void {
    for($i=0;$i<$zip->numFiles;$i++){
        $n=(string)$zip->getNameIndex($i);$x=str_replace('\\','/',$n);
        if($x===''||str_contains($x,'../')||str_starts_with($x,'/')||preg_match('/^[A-Za-z]:/',$x))
            throw new RuntimeException('ZIP inseguro: '.$n);
    }
}

function tm_copy_package(string $extract,string $root,array &$files): void {
    $ignore=['manifest.json','README.md','CHANGELOG.md','icon.png'];
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($extract,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
    foreach($it as $item){
        $rel=substr($item->getPathname(),strlen($extract)+1);
        if($rel===''||in_array(str_replace('\\','/',$rel),$ignore,true)) continue;
        $target=$root.DIRECTORY_SEPARATOR.$rel;
        if($item->isDir()){if(!is_dir($target))@mkdir($target,0775,true);continue;}
        $parent=dirname($target);if(!is_dir($parent)&&!@mkdir($parent,0775,true))throw new RuntimeException('No se pudo crear '.$parent);
        if(!@copy($item->getPathname(),$target))throw new RuntimeException('No se pudo copiar '.$rel);
        $files[]=[
            'path'=>str_replace('\\','/',$rel),
            'sha256'=>hash_file('sha256',$target) ?: null
        ];
    }
}

function tm_install(string $ns,string $name,?string $version=null,array &$seen=[]): array {
    if(bm_server_running()) throw new RuntimeException('Detén Valheim antes de instalar mods');
    $id=$ns.'-'.$name.'-'.($version??'latest');if(isset($seen[$id])) return ['skipped'=>true];$seen[$id]=true;

    if($ns==='denikson' && $name==='BepInExPack_Valheim'){
        $r=bm_install_latest(false); return ['bepinex'=>true,'result'=>$r];
    }
    if(!bm_installed_info()['installed']) bm_install_latest(false);

    $meta=tm_package($ns,$name,$version);$v=$meta['version'];
    $ver=(string)($v['version_number']??$version??'');$url=(string)($v['download_url']??'');
    if($ver===''||$url==='') throw new RuntimeException('Metadata de versión incompleta');

    foreach(($v['dependencies']??[]) as $dep){
        $d=tm_dep_parse((string)$dep);if($d) tm_install($d['namespace'],$d['name'],$d['version'],$seen);
    }

    $host=strtolower((string)parse_url($url,PHP_URL_HOST));
    if($host===''||(!str_ends_with($host,'thunderstore.io')&&!str_ends_with($host,'thunderstorecdn.com')))
        throw new RuntimeException('Host de descarga no permitido');

    $tmp=sys_get_temp_dir().DIRECTORY_SEPARATOR.'vhmod_'.bin2hex(random_bytes(5));$zipPath=$tmp.'.zip';$extract=$tmp.'_x';
    try{
        bm_download_to($url,$zipPath);@mkdir($extract,0775,true);
        $zip=new ZipArchive();if($zip->open($zipPath)!==true)throw new RuntimeException('No se pudo abrir ZIP del mod');tm_safe_zip_entries($zip);
        if(!$zip->extractTo($extract)){$zip->close();throw new RuntimeException('No se pudo extraer mod');}$zip->close();

        $manifest=$extract.DIRECTORY_SEPARATOR.'manifest.json';
        $mf=is_file($manifest)?json_decode((string)file_get_contents($manifest),true):[];
        $files=[];tm_copy_package($extract,bm_server_root(),$files);

        $reg=tm_registry();$key=$ns.'-'.$name;
        $reg[$key]=[
            'namespace'=>$ns,'name'=>$name,'version'=>$ver,'installed_at'=>date('c'),
            'dependencies'=>$v['dependencies']??[],'files'=>$files,'website_url'=>$mf['website_url']??null,
            'description'=>$mf['description']??($v['description']??null)
        ];
        tm_save_registry($reg);
        return $reg[$key];
    }finally{@unlink($zipPath);bm_remove_tree($extract);}
}

function tm_uninstall(string $ns,string $name): array {
    if(bm_server_running()) throw new RuntimeException('Detén Valheim antes de desinstalar mods');
    $reg=tm_registry();$key=$ns.'-'.$name;if(empty($reg[$key]))throw new RuntimeException('Mod no registrado como instalado');
    $root=bm_server_root();$removed=0;
    foreach(($reg[$key]['files']??[]) as $entry){
        if(is_array($entry)){
            $rel=(string)($entry['path']??'');
            $expected=(string)($entry['sha256']??'');
        }else{
            // Compatibilidad con registros iniciales.
            $rel=(string)$entry;
            $expected='';
        }
        if($rel===''||str_contains($rel,'..')) continue;
        $rel=str_replace('/',DIRECTORY_SEPARATOR,$rel);
        $p=$root.DIRECTORY_SEPARATOR.$rel;
        if(!is_file($p)) continue;

        // Si otro mod o el usuario modificó el archivo, no lo borres silenciosamente.
        if($expected!=='' && (hash_file('sha256',$p) ?: '') !== $expected) continue;
        if(@unlink($p)) $removed++;
    }
    unset($reg[$key]);tm_save_registry($reg);
    return ['removed'=>$removed];
}

function tm_installed_with_updates(): array {
    $reg=tm_registry();$out=[];
    foreach($reg as $key=>$m){
        $latest=null;$status='unknown';
        try{$x=tm_package($m['namespace'],$m['name']);$latest=(string)($x['version']['version_number']??'');if($latest!=='')$status=version_compare((string)$m['version'],$latest,'<')?'outdated':'current';}catch(Throwable $e){}
        $m['latest_version']=$latest;$m['status']=$status;$out[$key]=$m;
    }
    return $out;
}
