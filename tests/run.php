<?php
declare(strict_types=1);
require __DIR__.'/../src/Catalog.php';
use Sahara\Reference\Catalog;
$c = new Catalog();
$c->validate();
$methods = $c->prayerMethods();
if ($methods['mwl']['fajr_angle'] !== 18 || $methods['umm_al_qura']['isha_minutes'] !== 90) {
    throw new RuntimeException('Prayer configuration contract broken');
}
$manifest = $c->manifests();
if (count($manifest) < 2) throw new RuntimeException('Required provenance missing');
foreach ($manifest as $m) {
    if ($m['redistribution_status'] === 'pending-confirmation' && isset($m['data_path'])) {
        throw new RuntimeException('Unpermitted dataset included');
    }
}
echo "PASS reference manifest and configuration validation\n";

$root=sys_get_temp_dir().'/sahara-reference-test-'.bin2hex(random_bytes(6));
mkdir($root.'/data/prayer-times',0777,true);mkdir($root.'/manifests');mkdir($root.'/schemas');
copy(__DIR__.'/../schemas/dataset-manifest.schema.json',$root.'/schemas/dataset-manifest.schema.json');
copy(__DIR__.'/../data/prayer-times/methods.json',$root.'/data/prayer-times/methods.json');
$original=json_decode(file_get_contents(__DIR__.'/../manifests/prayer-methods.json'),true);
function rejected(callable $run): void {try{$run();}catch(RuntimeException){return;}throw new RuntimeException('Invalid data was accepted');}
try {
 foreach(['prohibited','pending-confirmation'] as $status){
  $bad=$original;$bad['license_status']=$status;
  file_put_contents($root.'/manifests/methods.json',json_encode($bad));
  rejected(fn()=>(new Catalog($root))->validate());
 }
 file_put_contents($root.'/manifests/methods.json',json_encode($original));
 $bad=json_decode(file_get_contents(__DIR__.'/../data/prayer-times/methods.json'),true);
 $bad['methods']['mwl']['fajr_angle']=['invalid'];
 file_put_contents($root.'/data/prayer-times/methods.json',json_encode($bad));
 rejected(fn()=>(new Catalog($root))->validate());
 echo "PASS prohibited/pending license and malformed parameters rejected\n";
} finally {
 foreach(['/schemas/dataset-manifest.schema.json','/data/prayer-times/methods.json','/manifests/methods.json'] as $file)unlink($root.$file);
 rmdir($root.'/schemas');rmdir($root.'/data/prayer-times');rmdir($root.'/data');rmdir($root.'/manifests');rmdir($root);
}
