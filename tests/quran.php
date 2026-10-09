<?php
declare(strict_types=1);
require __DIR__.'/../src/KfgqpcPackage.php';
$p=new Sahara\Reference\KfgqpcPackage;
$m=$p->manifest();
if($m['provider_key']!=='kfgqpc'||$m['primary']!==true||$m['status']!=='approved'||array_sum($m['ayah_counts'])!==6236)throw new RuntimeException('Source policy broken');
foreach($m['packages'] as $package) {
 if(parse_url($package['url'],PHP_URL_HOST)!=='download.qurancomplex.gov.sa'||!$package['checksums'])throw new RuntimeException('Unapproved source');
}
try{$p->read(__FILE__);throw new LogicException('Bad archive accepted');}catch(RuntimeException){}
try{$p->parse('<!DOCTYPE DATA><DATA/>','quran');throw new LogicException('Bad XML accepted');}catch(RuntimeException){}
if(glob(__DIR__.'/../data/quran/*'))throw new RuntimeException('Corpus must not be in public Git');
$dir=getenv('KFGQPC_PACKAGE_DIR');
if($dir){foreach(['quran'=>'kfgqpc_hafs_v30.zip','tafsir'=>'hafs_tafseerMouaser_v3.zip'] as $kind=>$file){$result=$p->read($dir.'/'.$file,$kind);echo "PASS official $kind: ".count($result['rows'])." rows, archive/font integrity and full canonical mapping\n";}}
else echo "SKIP private official archive integration: set KFGQPC_PACKAGE_DIR\n";
echo "PASS KFGQPC source policy, checksum rejection and public corpus exclusion\n";
