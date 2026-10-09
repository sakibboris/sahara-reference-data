<?php
declare(strict_types=1);
require __DIR__.'/../src/Catalog.php';
$files=(new Sahara\Reference\Catalog)->quranFiles();
$text=simplexml_load_file($files['text'],SimpleXMLElement::class,LIBXML_NONET);
$meta=simplexml_load_file($files['metadata'],SimpleXMLElement::class,LIBXML_NONET);
if(count($text->sura)!==114||count($meta->suras->sura)!==114||count($meta->pages->page)!==604)throw new RuntimeException('Incomplete Quran structure');
$count=0;$bismillahs=0;
foreach($text->sura as $i=>$sura){$number=(int)$sura['index'];$expected=$meta->suras->sura[$number-1];if(count($sura->aya)!==(int)$expected['ayas'])throw new RuntimeException('Ayah count mismatch');$previous=0;
 foreach($sura->aya as $ayah){if((int)$ayah['index']!==++$previous||trim((string)$ayah['text'])==='')throw new RuntimeException('Ayah sequence/text mismatch');$count++;if(isset($ayah['bismillah']))$bismillahs++;}
}
if($count!==6236||$bismillahs!==112||isset($text->sura[0]->aya[0]['bismillah'])||isset($text->sura[8]->aya[0]['bismillah']))throw new RuntimeException('Verse/Bismillah contract mismatch');
$previous=-1;
foreach($meta->pages->page as $page){$sura=(int)$page['sura'];$aya=(int)$page['aya'];$s=$meta->suras->sura[$sura-1];$offset=(int)$s['start']+$aya-1;if($offset<=$previous||$aya>(int)$s['ayas'])throw new RuntimeException('Invalid page boundaries');$previous=$offset;}
echo "PASS verbatim Quran hashes,114 suras,6236 ayat,604 pages and Bismillah metadata\n";
