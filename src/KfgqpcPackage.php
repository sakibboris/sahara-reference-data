<?php
declare(strict_types=1);
namespace Sahara\Reference;

/** Only the pinned official developer archives are accepted. Never downloads or extracts paths. */
final class KfgqpcPackage {
 public function __construct(private readonly string $root=__DIR__.'/..') {}
 public function manifest():array { return json_decode(file_get_contents($this->root.'/manifests/kfgqpc.json'),true,512,JSON_THROW_ON_ERROR); }
 public function read(string $archive,string $kind='quran'):array {
  $m=$this->manifest();$p=$m['packages'][$kind]??throw new \InvalidArgumentException('Unknown official package');
  foreach($p['checksums'] as $algorithm=>$expected) {
   if(!is_file($archive)||!hash_equals(strtolower($expected),hash_file($algorithm,$archive)))throw new \RuntimeException('Official KFGQPC archive checksum mismatch: '.$algorithm);
  }
  $zip=new \ZipArchive();
  if($zip->open($archive)!==true)throw new \RuntimeException('Cannot read official archive');
  try {
   $xml=$zip->getFromName($p['entry']);
   if(!is_string($xml)||!hash_equals($p['entry_sha256'],hash('sha256',$xml)))throw new \RuntimeException('Official XML entry mismatch');
   $rows=$this->parse($xml,$kind);
   $fonts=[];
   foreach($p['fonts'] as $key=>$font) {
    $bytes=$zip->getFromName($font['entry']);
    if(!is_string($bytes)||!hash_equals($font['sha256'],hash('sha256',$bytes)))throw new \RuntimeException('Official font mismatch');
    $fonts[$key]=$bytes;
   }
   return ['rows'=>$rows,'fonts'=>$fonts,'manifest'=>$p,'xml'=>$xml];
  } finally {$zip->close();}
 }
 public function parse(string $xml,string $kind):array {
  if(!in_array($kind,['quran','tafsir'],true)||str_contains($xml,'<!DOCTYPE')||str_contains($xml,'<!ENTITY'))throw new \RuntimeException('Invalid XML source');
  $previous=libxml_use_internal_errors(true);
  try {$dom=new \DOMDocument();if(!$dom->loadXML($xml,LIBXML_NONET)||$dom->documentElement?->tagName!=='DATA')throw new \RuntimeException('Malformed official XML');}
  finally {libxml_clear_errors();libxml_use_internal_errors($previous);}
  $rows=[];$counts=[];$names=[];$pages=[];$juz=[];$lastPage=0;
  foreach($dom->documentElement->childNodes as $node) {
   if(!$node instanceof \DOMElement)continue;
   if($node->tagName!=='ROW')throw new \RuntimeException('Unexpected source row');
   $r=[];foreach($node->childNodes as $field) {
    if(!$field instanceof \DOMElement)continue;
    if(isset($r[$field->tagName]))throw new \RuntimeException('Duplicate field');
    if($field->tagName==='aya_tafseer'){$inner='';foreach($field->childNodes as $child)$inner.=$dom->saveXML($child);$r[$field->tagName]=$inner;}
    else $r[$field->tagName]=$field->textContent;
   }
   foreach(['id','jozz','page','sura_no','line_start','line_end','aya_no'] as $field) {
    if(!isset($r[$field])||!preg_match('/^[1-9][0-9]*$/D',$r[$field]))throw new \RuntimeException('Invalid numeric mapping');
    $r[$field]=(int)$r[$field];
   }
   foreach(['sura_name_ar','sura_name_en','aya_text_emlaey',$kind==='quran'?'aya_text_unicode':'aya_tafseer'] as $field) {
    if(!isset($r[$field])||trim($r[$field])===''||str_contains($r[$field],"\u{FFFD}"))throw new \RuntimeException('Invalid Unicode/text field');
   }
   $s=$r['sura_no'];$a=$r['aya_no'];
   if($r['id']!==count($rows)+1||$s<1||$s>114||$a!==($counts[$s]??0)+1||$r['page']<$lastPage||$r['page']>604||$r['jozz']>30||$r['line_start']>$r['line_end']||$r['line_end']>15)throw new \RuntimeException('Invalid canonical sequence/page/line');
   $name=[$r['sura_name_ar'],$r['sura_name_en']];
   if(isset($names[$s])&&$names[$s]!==$name)throw new \RuntimeException('Conflicting Surah name');
   $counts[$s]=$a;$names[$s]=$name;$pages[$r['page']]=true;$juz[$r['jozz']]=true;$lastPage=$r['page'];$rows[]=$r;
  }
  $expected=$this->manifest()['ayah_counts'];
  if(count($rows)!==6236||count($counts)!==114||array_values($counts)!==$expected||count($pages)!==604||count($juz)!==30)throw new \RuntimeException('Incomplete official mapping');
  if($kind==='quran')foreach($this->manifest()['fixtures'] as $f) {
   $r=$rows[$f['id']-1];
   foreach(['sura_no','aya_no','page','jozz','line_start','line_end'] as $key)if($r[$key]!==$f[$key])throw new \RuntimeException('Fixture mapping mismatch');
   foreach(['aya_text_unicode','aya_text_emlaey','sura_name_ar'] as $key)if(!hash_equals($f[$key.'_sha256'],hash('sha256',$r[$key])))throw new \RuntimeException('Fixture Unicode mismatch');
  }
  return $rows;
 }
}
