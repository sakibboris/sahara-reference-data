<?php
declare(strict_types=1);
require __DIR__.'/../src/Catalog.php';
$metadata=(new Sahara\Reference\Catalog)->worshipMetadata();
if(array_keys($metadata['daily_prayers'])!==['fajr','dhuhr','asr','maghrib','isha'])throw new RuntimeException('Daily prayer keys changed');
foreach(['daily_fard','wajib','sunnah','nafl','fard_kifayah','janazah','grave_visit'] as $key){if(empty($metadata['categories'][$key]['name'])||empty($metadata['categories'][$key]['description']))throw new RuntimeException('Missing category');}
if($metadata['categories']['wajib']['classification_authority']!=='user-selected')throw new RuntimeException('Disputed categories must remain configurable');
echo "PASS worship metadata contract\n";
$duas=(new Sahara\Reference\Catalog)->duaExcerpts();
if(count($duas['entries'])!==2 || $duas['entries'][1]['reference']!=='Quran 20:114 (supplication excerpt)')throw new RuntimeException('Source metadata missing');
echo "PASS original-Arabic excerpt provenance\n";
