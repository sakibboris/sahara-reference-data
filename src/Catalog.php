<?php
declare(strict_types=1);
namespace Sahara\Reference;
final class Catalog {
    public function __construct(private readonly string $root=__DIR__.'/..') {}
    public function manifests(): array {
        return array_map(fn(string $file)=>json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR),glob($this->root.'/manifests/*.json'));
    }
    public function prayerMethods(): array {
        return json_decode(file_get_contents($this->root.'/data/prayer-times/methods.json'),true,512,JSON_THROW_ON_ERROR)['methods'];
    }
    public function worshipMetadata(): array {
        $data=json_decode(file_get_contents($this->root.'/data/worship/metadata.json'),true,512,JSON_THROW_ON_ERROR);
        if(($data['schema_version']??null)!==1||!is_array($data['categories']??null)||!is_array($data['daily_prayers']??null)||!is_array($data['zikr_contexts']??null))throw new \RuntimeException('Invalid worship metadata');
        foreach($data['categories'] as $key=>$category){if(!preg_match('/^[a-z_]{1,40}$/D',$key)||empty($category['name'])||($category['classification_authority']??null)!=='user-selected')throw new \RuntimeException('Invalid worship category');}
        return $data;
    }
    public function duaExcerpts(): array {
        $data=json_decode(file_get_contents($this->root.'/data/worship/duas.json'),true,512,JSON_THROW_ON_ERROR);
        if(($data['schema_version']??null)!==1||!is_array($data['entries']??null))throw new \RuntimeException('Invalid Dua excerpt catalog');
        $keys=[];
        foreach($data['entries'] as $entry){foreach(['key','title','arabic','reference','source_url','context'] as $field){if(!is_string($entry[$field]??null)||trim($entry[$field])==='')throw new \RuntimeException('Invalid excerpt field');}
            if(isset($keys[$entry['key']])||!filter_var($entry['source_url'],FILTER_VALIDATE_URL))throw new \RuntimeException('Invalid excerpt identity/source');$keys[$entry['key']]=true;
        }
        return $data;
    }

    public function quranFiles(): array {
        $files=[];
        foreach(['text'=>'tanzil-quran','metadata'=>'tanzil-metadata'] as $key=>$id){
            $manifest=json_decode(file_get_contents($this->root.'/manifests/'.$id.'.json'),true,512,JSON_THROW_ON_ERROR);
            if(($manifest['license_status']??null)!=='permitted'||($manifest['redistribution_status']??null)!=='permitted')throw new \RuntimeException('Quran source is not permitted');
            $path=realpath($this->root.'/'.$manifest['data_path']);$root=realpath($this->root.'/data/quran');
            if(!$path||!$root||!str_starts_with($path,$root.DIRECTORY_SEPARATOR)||!hash_equals($manifest['sha256'],hash_file('sha256',$path)))throw new \RuntimeException('Quran source integrity check failed');
            $files[$key]=$path;
        }
        return $files;
    }
    public function validate(): void {
        $schema=json_decode(file_get_contents($this->root.'/schemas/dataset-manifest.schema.json'),true,512,JSON_THROW_ON_ERROR);
        $ids=[];
        foreach($this->manifests() as $manifest){
            foreach($schema['required'] as $key)if(!array_key_exists($key,$manifest))throw new \RuntimeException("Missing manifest field: $key");
            foreach($schema['properties'] as $key=>$rule){
                if(!array_key_exists($key,$manifest))continue;$v=$manifest[$key];
                if(isset($rule['enum'])&&!in_array($v,$rule['enum'],true))throw new \RuntimeException("Invalid $key");
                if(isset($rule['const'])&&$v!==$rule['const'])throw new \RuntimeException("Invalid $key");
                if(($rule['type']??'')==='boolean'&&!is_bool($v))throw new \RuntimeException("Invalid $key");
                if(($rule['type']??'')==='string'&&(!is_string($v)||trim($v)===''))throw new \RuntimeException("Invalid $key");
            }
            if(isset($ids[$manifest['id']]))throw new \RuntimeException('Duplicate manifest ID');
            $ids[$manifest['id']]=true;
            if(!filter_var($manifest['source_url'],FILTER_VALIDATE_URL))throw new \RuntimeException('Invalid source URL');
            $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$manifest['last_reviewed']);
            if(!$date||$date->format('Y-m-d')!==$manifest['last_reviewed'])throw new \RuntimeException('Invalid review date');
            if(isset($manifest['data_path'])){
                if($manifest['redistribution_status']!=='permitted'||!in_array($manifest['license_status'],['own-configuration','permitted'],true))throw new \RuntimeException('Unpermitted data');
                $root=realpath($this->root.'/data');$path=realpath($this->root.'/'.$manifest['data_path']);
                if(!$root||!$path||!str_starts_with($path,$root.DIRECTORY_SEPARATOR))throw new \RuntimeException('Data path must remain in data/');
            }
        }
        $data=json_decode(file_get_contents($this->root.'/data/prayer-times/methods.json'),true,512,JSON_THROW_ON_ERROR);
        if(($data['schema_version']??null)!==1||!is_array($data['methods']??null)||$data['methods']===[])throw new \RuntimeException('Invalid prayer schema');
        foreach($data['methods'] as $method){
            if(!is_array($method)||!isset($method['name'],$method['fajr_angle'])||(!isset($method['isha_angle'])&&!isset($method['isha_minutes'])))throw new \RuntimeException('Incomplete prayer method');
            if(!is_string($method['name'])||trim($method['name'])==='')throw new \RuntimeException('Invalid method name');
            foreach(['fajr_angle','isha_angle'] as $key){
                if(!array_key_exists($key,$method))continue;$v=$method[$key];
                if((!is_int($v)&&!is_float($v))||!is_finite((float)$v)||$v<=0||$v>30)throw new \RuntimeException('Invalid twilight angle');
            }
            foreach(['isha_minutes','ramadan_isha_minutes'] as $key){
                if(isset($method[$key])&&(!is_int($method[$key])||$method[$key]<1||$method[$key]>240))throw new \RuntimeException('Invalid Isha interval');
            }
        }
    }
}
