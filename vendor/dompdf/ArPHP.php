<?php

$web = 'index.php';

if (in_array('phar', stream_get_wrappers()) && class_exists('Phar', 0)) {
Phar::interceptFileFuncs();
set_include_path('phar://' . __FILE__ . PATH_SEPARATOR . get_include_path());
Phar::webPhar(null, $web);
include 'phar://' . __FILE__ . '/' . Extract_Phar::START;
return;
}

if (@(isset($_SERVER['REQUEST_URI']) && isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'POST'))) {
Extract_Phar::go(true);
$mimes = array(
'phps' => 2,
'c' => 'text/plain',
'cc' => 'text/plain',
'cpp' => 'text/plain',
'c++' => 'text/plain',
'dtd' => 'text/plain',
'h' => 'text/plain',
'log' => 'text/plain',
'rng' => 'text/plain',
'txt' => 'text/plain',
'xsd' => 'text/plain',
'php' => 1,
'inc' => 1,
'avi' => 'video/avi',
'bmp' => 'image/bmp',
'css' => 'text/css',
'gif' => 'image/gif',
'htm' => 'text/html',
'html' => 'text/html',
'htmls' => 'text/html',
'ico' => 'image/x-ico',
'jpe' => 'image/jpeg',
'jpg' => 'image/jpeg',
'jpeg' => 'image/jpeg',
'js' => 'application/x-javascript',
'midi' => 'audio/midi',
'mid' => 'audio/midi',
'mod' => 'audio/mod',
'mov' => 'movie/quicktime',
'mp3' => 'audio/mp3',
'mpg' => 'video/mpeg',
'mpeg' => 'video/mpeg',
'pdf' => 'application/pdf',
'png' => 'image/png',
'swf' => 'application/shockwave-flash',
'tif' => 'image/tiff',
'tiff' => 'image/tiff',
'wav' => 'audio/wav',
'xbm' => 'image/xbm',
'xml' => 'text/xml',
);

header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$basename = basename(__FILE__);
if (!strpos($_SERVER['REQUEST_URI'], $basename)) {
chdir(Extract_Phar::$temp);
include $web;
return;
}
$pt = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], $basename) + strlen($basename));
if (!$pt || $pt == '/') {
$pt = $web;
header('HTTP/1.1 301 Moved Permanently');
header('Location: ' . $_SERVER['REQUEST_URI'] . '/' . $pt);
exit;
}
$a = realpath(Extract_Phar::$temp . DIRECTORY_SEPARATOR . $pt);
if (!$a || strlen(dirname($a)) < strlen(Extract_Phar::$temp)) {
header('HTTP/1.0 404 Not Found');
echo "<html>\n <head>\n  <title>File Not Found<title>\n </head>\n <body>\n  <h1>404 - File Not Found</h1>\n </body>\n</html>";
exit;
}
$b = pathinfo($a);
if (!isset($b['extension'])) {
header('Content-Type: text/plain');
header('Content-Length: ' . filesize($a));
readfile($a);
exit;
}
if (isset($mimes[$b['extension']])) {
if ($mimes[$b['extension']] === 1) {
include $a;
exit;
}
if ($mimes[$b['extension']] === 2) {
highlight_file($a);
exit;
}
header('Content-Type: ' .$mimes[$b['extension']]);
header('Content-Length: ' . filesize($a));
readfile($a);
exit;
}
}

class Extract_Phar
{
static $temp;
static $origdir;
const GZ = 0x1000;
const BZ2 = 0x2000;
const MASK = 0x3000;
const START = 'index.php';
const LEN = 6643;

static function go($return = false)
{
$fp = fopen(__FILE__, 'rb');
fseek($fp, self::LEN);
$L = unpack('V', $a = fread($fp, 4));
$m = '';

do {
$read = 8192;
if ($L[1] - strlen($m) < 8192) {
$read = $L[1] - strlen($m);
}
$last = fread($fp, $read);
$m .= $last;
} while (strlen($last) && strlen($m) < $L[1]);

if (strlen($m) < $L[1]) {
die('ERROR: manifest length read was "' .
strlen($m) .'" should be "' .
$L[1] . '"');
}

$info = self::_unpack($m);
$f = $info['c'];

if ($f & self::GZ) {
if (!function_exists('gzinflate')) {
die('Error: zlib extension is not enabled -' .
' gzinflate() function needed for zlib-compressed .phars');
}
}

if ($f & self::BZ2) {
if (!function_exists('bzdecompress')) {
die('Error: bzip2 extension is not enabled -' .
' bzdecompress() function needed for bz2-compressed .phars');
}
}

$temp = self::tmpdir();

if (!$temp || !is_writable($temp)) {
$sessionpath = session_save_path();
if (strpos ($sessionpath, ";") !== false)
$sessionpath = substr ($sessionpath, strpos ($sessionpath, ";")+1);
if (!file_exists($sessionpath) || !is_dir($sessionpath)) {
die('Could not locate temporary directory to extract phar');
}
$temp = $sessionpath;
}

$temp .= '/pharextract/'.basename(__FILE__, '.phar');
self::$temp = $temp;
self::$origdir = getcwd();
@mkdir($temp, 0777, true);
$temp = realpath($temp);

if (!file_exists($temp . DIRECTORY_SEPARATOR . md5_file(__FILE__))) {
self::_removeTmpFiles($temp, getcwd());
@mkdir($temp, 0777, true);
@file_put_contents($temp . '/' . md5_file(__FILE__), '');

foreach ($info['m'] as $path => $file) {
$a = !file_exists(dirname($temp . '/' . $path));
@mkdir(dirname($temp . '/' . $path), 0777, true);
clearstatcache();

if ($path[strlen($path) - 1] == '/') {
@mkdir($temp . '/' . $path, 0777);
} else {
file_put_contents($temp . '/' . $path, self::extractFile($path, $file, $fp));
@chmod($temp . '/' . $path, 0666);
}
}
}

chdir($temp);

if (!$return) {
include self::START;
}
}

static function tmpdir()
{
if (strpos(PHP_OS, 'WIN') !== false) {
if ($var = getenv('TMP') ? getenv('TMP') : getenv('TEMP')) {
return $var;
}
if (is_dir('/temp') || mkdir('/temp')) {
return realpath('/temp');
}
return false;
}
if ($var = getenv('TMPDIR')) {
return $var;
}
return realpath('/tmp');
}

static function _unpack($m)
{
$info = unpack('V', substr($m, 0, 4));
 $l = unpack('V', substr($m, 10, 4));
$m = substr($m, 14 + $l[1]);
$s = unpack('V', substr($m, 0, 4));
$o = 0;
$start = 4 + $s[1];
$ret['c'] = 0;

for ($i = 0; $i < $info[1]; $i++) {
 $len = unpack('V', substr($m, $start, 4));
$start += 4;
 $savepath = substr($m, $start, $len[1]);
$start += $len[1];
   $ret['m'][$savepath] = array_values(unpack('Va/Vb/Vc/Vd/Ve/Vf', substr($m, $start, 24)));
$ret['m'][$savepath][3] = sprintf('%u', $ret['m'][$savepath][3]
& 0xffffffff);
$ret['m'][$savepath][7] = $o;
$o += $ret['m'][$savepath][2];
$start += 24 + $ret['m'][$savepath][5];
$ret['c'] |= $ret['m'][$savepath][4] & self::MASK;
}
return $ret;
}

static function extractFile($path, $entry, $fp)
{
$data = '';
$c = $entry[2];

while ($c) {
if ($c < 8192) {
$data .= @fread($fp, $c);
$c = 0;
} else {
$c -= 8192;
$data .= @fread($fp, 8192);
}
}

if ($entry[4] & self::GZ) {
$data = gzinflate($data);
} elseif ($entry[4] & self::BZ2) {
$data = bzdecompress($data);
}

if (strlen($data) != $entry[0]) {
die("Invalid internal .phar file (size error " . strlen($data) . " != " .
$stat[7] . ")");
}

if ($entry[3] != sprintf("%u", crc32($data) & 0xffffffff)) {
die("Invalid internal .phar file (checksum error)");
}

return $data;
}

static function _removeTmpFiles($temp, $origdir)
{
chdir($temp);

foreach (glob('*') as $f) {
if (file_exists($f)) {
is_dir($f) ? @rmdir($f) : @unlink($f);
if (file_exists($f) && is_dir($f)) {
self::_removeTmpFiles($f, getcwd());
}
}
}

@rmdir($temp);
clearstatcache();
chdir($origdir);
}
}

Extract_Phar::go();
__HALT_COMPILER(); ?>�         
   ArPHP.phar    
   Arabic.php��  ��`��  �
R��         data/arabizi.json�  ��`�  s���         data/ar_countries.xml�[  ��`�[  ���         data/ar_date.json{  ��`{  ���.�         data/ar_female.txt�  ��`�  ��         data/ar_keyswap.json�'  ��`�'  �A^ж         data/ar_numbers.json8  ��`8  ��C��         data/ar_plurals.json�
  ��`�
  0�]�         data/ar_query.json�  ��`�  ��)ж         data/ar_soundex.json=  ��`=  �`+C�         data/ar_stopwords.txtJ  ��`J  �         data/ar_stopwords_extra.txt%E ��`%E �y*�         data/ar_strtotime.json!  ��`!  `��ֶ         data/ar_transliteration.json�4  ��`�4  �Γj�         data/en_stopwords.txt�  ��`�  =&�T�         data/important_words.txtE  ��`E  >/k��         data/logodd_ar.txt�p  ��`�p  �5U�         data/logodd_en.txt:  ��`:  �S;�         data/logodd_negative.txt  ��`  ��I�         data/logodd_positive.txt�  ��`�  �@���         data/logodd_stem.txt�  ��`�  ��8�         data/stems.txt�  ��`�  I��#�         data/strtotime_replace.txt  ��`  ����         data/strtotime_search.txt�  ��`�  /R%�         data/um_alqoura.txt�)  ��`�)  n��      <?php
namespace ArPHP\I18N;
class Arabic
{
public $version='6.2.0';
private $arStandardPatterns=array();
private $arStandardReplacements=array();
private $arFemaleNames;
private $strToTimeSearch=array();
private $strToTimeReplace=array();
private $hj=array();
private $strToTimePatterns=array();
private $strToTimeReplacements=array();
private $umAlqoura;
private $arFinePatterns=array("/'+/u", "/([\- ])'/u", '/(.)#/u');
private $arFineReplacements=array("'", '\\1', "\\1'\\1");
private $diariticalSearch=array();
private $diariticalReplace=array();
private $en2arPregSearch=array();
private $en2arPregReplace=array();
private $en2arStrSearch=array();
private $en2arStrReplace=array();
private $ar2enPregSearch=array();
private $ar2enPregReplace=array();
private $ar2enStrSearch=array();
private $ar2enStrReplace=array();
private $iso233Search=array();
private $iso233Replace=array();
private $rjgcSearch=array();
private $rjgcReplace=array();
private $sesSearch=array();
private $sesReplace=array();
private $arDateMode=1;
private $arDateJSON;
private $arNumberIndividual=array();
private $arNumberComplications=array();
private $arNumberArabicIndic=array();
private $arNumberOrdering=array();
private $arNumberCurrency=array();
private $arNumberSpell=array();
private $arNumberFeminine=1;
private $arNumberFormat=1;
private $arNumberOrder=1;
private $arabizi=array();
private $arLogodd;
private $enLogodd;
private $arKeyboard;
private $enKeyboard;
private $frKeyboard;
private $soundexTransliteration=array();
private $soundexMap=array();
private $arSoundexCode=array();
private $arPhonixCode=array();
private $soundexLen=4;
private $soundexLang='en';
private $soundexCode='soundex';
private $arGlyphs=null;
private $arGlyphsHex=null;
private $arGlyphsPrevLink=null;
private $arGlyphsNextLink=null;
private $arGlyphsVowel=null;
private $arQueryFields=array();
private $arQueryLexPatterns=array();
private $arQueryLexReplacements=array();
private $arQueryMode=0;
private $salatYear=1975;
private $salatMonth=8;
private $salatDay=2;
private $salatZone=2;
private $salatLong=37.15861;
private $salatLat=36.20278;
private $salatElevation=0;
private $salatAB2=-0.833333;
private $salatAG2=-18;
private $salatAJ2=-18;
private $salatSchool='Shafi';
private $salatView='Sunni';
private $arNormalizeAlef=array('أ','إ','آ');
private $arNormalizeDiacritics=array('َ','ً','ُ','ٌ','ِ','ٍ','ْ','ّ');
private $arSeparators=array('.',"\n",'،','؛','(','[','{',')',']','}',',',';');
private $arCommonChars=array('ة','ه','ي','ن','و','ت','ل','ا','س','م',
'e', 't', 'a', 'o', 'i', 'n', 's');
private $arSummaryCommonWords=array();
private $arSummaryImportantWords=array();
private $arPluralsForms=array();
private $logOddPositive=array();
private $logOddNegative=array();
private $logOddStem=array();
private $allStems=array();
private $rootDirectory;
public function __construct()
{
mb_internal_encoding('UTF-8');
$this->rootDirectory=dirname(__FILE__);
$this->arFemaleNames=file($this->rootDirectory . '/data/ar_female.txt', FILE_IGNORE_NEW_LINES);
$this->umAlqoura=file_get_contents($this->rootDirectory . '/data/um_alqoura.txt');
$this->arDateJSON=json_decode(file_get_contents($this->rootDirectory . '/data/ar_date.json'), true);
$json=json_decode(file_get_contents($this->rootDirectory . '/data/ar_plurals.json'), true);
$this->arPluralsForms=$json['arPluralsForms'];
$this->arStandardInit();
$this->arStrToTimeInit();
$this->arTransliterateInit();
$this->arNumbersInit();
$this->arKeySwapInit();
$this->arSoundexInit();
$this->arGlyphsInit();
$this->arQueryInit();
$this->arSummaryInit();
$this->arSentimentInit();
}
private function arStandardInit()
{
$this->arStandardPatterns[]='/\r\n/u';
$this->arStandardPatterns[]='/([^\@])\n([^\@])/u';
$this->arStandardPatterns[]='/\r/u';
$this->arStandardReplacements[]="\n@@@\n";
$this->arStandardReplacements[]="\\1\n&&&\n\\2";
$this->arStandardReplacements[]="\n###\n";
$this->arStandardPatterns[]='/\s*([\.\،\؛\:\!\؟])\s*/u';
$this->arStandardReplacements[]='\\1 ';
$this->arStandardPatterns[]='/(\. ){2,}/u';
$this->arStandardReplacements[]='...';
$this->arStandardPatterns[]='/\s*([\(\{\[])\s*/u';
$this->arStandardPatterns[]='/\s*([\)\}\]])\s*/u';
$this->arStandardReplacements[]=' \\1';
$this->arStandardReplacements[]='\\1 ';
$this->arStandardPatterns[]='/\s*\"\s*(.+)((?<!\s)\"|\s+\")\s*/u';
$this->arStandardReplacements[]=' "\\1" ';
$this->arStandardPatterns[]='/\s*\-\s*(.+)((?<!\s)\-|\s+\-)\s*/u';
$this->arStandardReplacements[]=' -\\1- ';
$this->arStandardPatterns[]='/\sو\s+([^و])/u';
$this->arStandardReplacements[]=' و\\1';
$this->arStandardPatterns[]='/\s+(\w+)\s*(\d+)\s+/';
$this->arStandardPatterns[]='/\s+(\d+)\s*(\w+)\s+/';
$this->arStandardReplacements[]=' <span dir="ltr">\\2 \\1</span> ';
$this->arStandardReplacements[]=' <span dir="ltr">\\1 \\2</span> ';
$this->arStandardPatterns[]='/\s+(\d+)\s*\%\s+/u';
$this->arStandardPatterns[]='/\n?@@@\n?/u';
$this->arStandardPatterns[]='/\n?&&&\n?/u';
$this->arStandardPatterns[]='/\n?###\n?/u';
$this->arStandardReplacements[]=' %\\1 ';
$this->arStandardReplacements[]="\r\n";
$this->arStandardReplacements[]="\n";
$this->arStandardReplacements[]="\r";
}
private function arStrToTimeInit()
{
$this->strToTimeSearch=file($this->rootDirectory . '/data/strtotime_search.txt', FILE_IGNORE_NEW_LINES);
$this->strToTimeReplace=file($this->rootDirectory . '/data/strtotime_replace.txt', FILE_IGNORE_NEW_LINES);
foreach ($this->arDateJSON['ar_hj_month'] as $month) {
$this->hj[]=(string)$month;
}
$this->strToTimePatterns[]='/َ|ً|ُ|ٌ|ِ|ٍ|ْ|ّ/';
$this->strToTimePatterns[]='/\s*ال(\S{3,})\s+ال(\S{3,})/';
$this->strToTimePatterns[]='/\s*ال(\S{3,})/';
$this->strToTimeReplacements[]='';
$this->strToTimeReplacements[]=' \\2 \\1';
$this->strToTimeReplacements[]=' \\1';
}
private function arTransliterateInit()
{
$json=json_decode(file_get_contents($this->rootDirectory . '/data/ar_transliteration.json'), true);
foreach ($json['preg_replace_en2ar'] as $item) {
$this->en2arPregSearch[]=$item['search'];
$this->en2arPregReplace[]=$item['replace'];
}
foreach ($json['str_replace_en2ar'] as $item) {
$this->en2arStrSearch[]=$item['search'];
$this->en2arStrReplace[]=$item['replace'];
}
foreach ($json['preg_replace_ar2en'] as $item) {
$this->ar2enPregSearch[]=$item['search'];
$this->ar2enPregReplace[]=$item['replace'];
}
foreach ($json['str_replace_ar2en'] as $item) {
$this->ar2enStrSearch[]=$item['search'];
$this->ar2enStrReplace[]=$item['replace'];
}
foreach ($json['str_replace_diaritical'] as $item) {
$this->diariticalSearch[]=$item['search'];
$this->diariticalReplace[]=$item['replace'];
}
foreach ($json['str_replace_RJGC'] as $item) {
$this->rjgcSearch[]=$item['search'];
$this->rjgcReplace[]=$item['replace'];
}
foreach ($json['str_replace_SES'] as $item) {
$this->sesSearch[]=$item['search'];
$this->sesReplace[]=$item['replace'];
}
foreach ($json['str_replace_ISO233'] as $item) {
$this->iso233Search[]=$item['search'];
$this->iso233Replace[]=$item['replace'];
}
}
private function arNumbersInit()
{
$json=json_decode(file_get_contents($this->rootDirectory . '/data/ar_numbers.json'), true);
foreach ($json['individual']['male'] as $num) {
if (isset($num['grammar'])) {
$grammar=$num['grammar'];
$this->arNumberIndividual["{$num['value']}"][1]["$grammar"]=(string)$num['text'];
} else {
$this->arNumberIndividual["{$num['value']}"][1]=(string)$num['text'];
}
}
foreach ($json['individual']['female'] as $num) {
if (isset($num['grammar'])) {
$grammar=$num['grammar'];
$this->arNumberIndividual["{$num['value']}"][2]["$grammar"]=(string)$num['text'];
} else {
$this->arNumberIndividual["{$num['value']}"][2]=(string)$num['text'];
}
}
foreach ($json['individual']['gt19'] as $num) {
if (isset($num['grammar'])) {
$grammar=$num['grammar'];
$this->arNumberIndividual["{$num['value']}"]["$grammar"]=(string)$num['text'];
} else {
$this->arNumberIndividual["{$num['value']}"]=(string)$num['text'];
}
}
foreach ($json['complications'] as $num) {
$scale=$num['scale'];
$format=$num['format'];
$this->arNumberComplications["$scale"]["$format"]=(string)$num['text'];
}
foreach ($json['arabicIndic'] as $html) {
$value=$html['value'];
$this->arNumberArabicIndic["$value"]=$html['text'];
}
foreach ($json['order']['male'] as $num) {
$this->arNumberOrdering["{$num['value']}"][1]=(string)$num['text'];
}
foreach ($json['order']['female'] as $num) {
$this->arNumberOrdering["{$num['value']}"][2]=(string)$num['text'];
}
foreach ($json['individual']['male'] as $num) {
if ($num['value'] < 11) {
$str=str_replace(array('أ','إ','آ'), 'ا', (string)$num['text']);
$this->arNumberSpell[$str]=(int)$num['value'];
}
}
foreach ($json['individual']['female'] as $num) {
if ($num['value'] < 11) {
$str=str_replace(array('أ','إ','آ'), 'ا', (string)$num['text']);
$this->arNumberSpell[$str]=(int)$num['value'];
}
}
foreach ($json['individual']['gt19'] as $num) {
$str=str_replace(array('أ','إ','آ'), 'ا', (string)$num['text']);
$this->arNumberSpell[$str]=(int)$num['value'];
}
foreach ($json['currency'] as $money) {
$this->arNumberCurrency[$money['iso']]['ar']['basic']=$money['ar_basic'];
$this->arNumberCurrency[$money['iso']]['ar']['fraction']=$money['ar_fraction'];
$this->arNumberCurrency[$money['iso']]['en']['basic']=$money['en_basic'];
$this->arNumberCurrency[$money['iso']]['en']['fraction']=$money['en_fraction'];
$this->arNumberCurrency[$money['iso']]['decimals']=$money['decimals'];
}
}
private function arKeySwapInit()
{
$json=json_decode(file_get_contents($this->rootDirectory . '/data/arabizi.json'), true);
foreach ($json['transliteration'] as $item) {
$index=$item['id'];
$this->arabizi["$index"]=(string)$item['text'];
}
$json=json_decode(file_get_contents($this->rootDirectory . '/data/ar_keyswap.json'), true);
foreach ($json['arabic'] as $key) {
$index=(int)$key['id'];
$this->arKeyboard[$index]=(string)$key['text'];
}
foreach ($json['english'] as $key) {
$index=(int)$key['id'];
$this->enKeyboard[$index]=(string)$key['text'];
}
foreach ($json['french'] as $key) {
$index=(int)$key['id'];
$this->frKeyboard[$index]=(string)$key['text'];
}
$this->arLogodd=unserialize(file_get_contents($this->rootDirectory . '/data/logodd_ar.txt'));
$this->enLogodd=unserialize(file_get_contents($this->rootDirectory . '/data/logodd_en.txt'));
}
private function arSoundexInit()
{
$json=json_decode(file_get_contents($this->rootDirectory . '/data/ar_soundex.json'), true);
foreach ($json['arSoundexCode'] as $item) {
$index=$item['search'];
$this->arSoundexCode["$index"]=(string)$item['replace'];
}
foreach ($json['arPhonixCode'] as $item) {
$index=$item['search'];
$this->arPhonixCode["$index"]=(string)$item['replace'];
}
foreach ($json['soundexTransliteration'] as $item) {
$index=$item['search'];
$this->soundexTransliteration["$index"]=(string)$item['replace'];
}
$this->soundexMap=$this->arSoundexCode;
}
private function arGlyphsInit()
{
$this->arGlyphsPrevLink='،؟؛ـئبتثجحخسشصضطظعغفقكلمنهي';
$this->arGlyphsNextLink='ـآأؤإائبةتثجحخدذرز';
$this->arGlyphsNextLink .='سشصضطظعغفقكلمنهوىي';
$this->arGlyphsVowel='ًٌٍَُِّْ';
$this->arGlyphs='ًٌٍَُِّْٰ';
$this->arGlyphsHex='064B064B064B064B064C064C064C064C064D064D064D064D064E064E064E064E';
$this->arGlyphsHex .='064F064F064F064F065006500650065006510651065106510652065206520652';
$this->arGlyphsHex .='0670067006700670';
$this->arGlyphs    .='ءآأؤإئاب';
$this->arGlyphsHex .='FE80FE80FE80FE80FE81FE82FE81FE82FE83FE84FE83FE84FE85FE86FE85FE86';
$this->arGlyphsHex .='FE87FE88FE87FE88FE89FE8AFE8BFE8CFE8DFE8EFE8DFE8EFE8FFE90FE91FE92';
$this->arGlyphs    .='ةتثجحخدذ';
$this->arGlyphsHex .='FE93FE94FE93FE94FE95FE96FE97FE98FE99FE9AFE9BFE9CFE9DFE9EFE9FFEA0';
$this->arGlyphsHex .='FEA1FEA2FEA3FEA4FEA5FEA6FEA7FEA8FEA9FEAAFEA9FEAAFEABFEACFEABFEAC';
$this->arGlyphs    .='رزسشصضطظ';
$this->arGlyphsHex .='FEADFEAEFEADFEAEFEAFFEB0FEAFFEB0FEB1FEB2FEB3FEB4FEB5FEB6FEB7FEB8';
$this->arGlyphsHex .='FEB9FEBAFEBBFEBCFEBDFEBEFEBFFEC0FEC1FEC2FEC3FEC4FEC5FEC6FEC7FEC8';
$this->arGlyphs    .='عغفقكلمن';
$this->arGlyphsHex .='FEC9FECAFECBFECCFECDFECEFECFFED0FED1FED2FED3FED4FED5FED6FED7FED8';
$this->arGlyphsHex .='FED9FEDAFEDBFEDCFEDDFEDEFEDFFEE0FEE1FEE2FEE3FEE4FEE5FEE6FEE7FEE8';
$this->arGlyphs    .='هوىيـ،؟؛';
$this->arGlyphsHex .='FEE9FEEAFEEBFEECFEEDFEEEFEEDFEEEFEEFFEF0FEEFFEF0FEF1FEF2FEF3FEF4';
$this->arGlyphsHex .='0640064006400640060C060C060C060C061F061F061F061F061B061B061B061B';
$this->arGlyphs    .='پچژگی';
$this->arGlyphsHex .='FB56FB57FB58FB59FB7AFB7BFB7CFB7DFB8AFB8BFB8AFB8B';
$this->arGlyphsHex .='FB92FB93FB94FB95FBFCFBFDFBFEFBFF';
$this->arGlyphsPrevLink .='پچگی';
$this->arGlyphsNextLink .='پچژگی';
$this->arGlyphs    .='لآلألإلا';
$this->arGlyphsHex .='FEF5FEF6FEF5FEF6FEF7FEF8FEF7FEF8FEF9FEFAFEF9FEFAFEFBFEFCFEFBFEFC';
}
private function arQueryInit()
{
$json=json_decode(file_get_contents($this->rootDirectory . '/data/ar_query.json'), true);
foreach ($json['preg_replace'] as $pair) {
$this->arQueryLexPatterns[]=(string)$pair['search'];
$this->arQueryLexReplacements[]=(string)$pair['replace'];
}
}
private function arSummaryInit()
{
$words=file($this->rootDirectory . '/data/ar_stopwords.txt', FILE_IGNORE_NEW_LINES);
$en_words=file($this->rootDirectory . '/data/en_stopwords.txt', FILE_IGNORE_NEW_LINES);
$words=array_merge($words, $en_words);
$this->arSummaryCommonWords=$words;
$words=file($this->rootDirectory . '/data/important_words.txt', FILE_IGNORE_NEW_LINES);
$this->arSummaryImportantWords=$words;
}
private function arSentimentInit()
{
$this->allStems=file($this->rootDirectory . '/data/stems.txt', FILE_IGNORE_NEW_LINES);
$this->logOddStem=file($this->rootDirectory . '/data/logodd_stem.txt', FILE_IGNORE_NEW_LINES);
$this->logOddPositive=file($this->rootDirectory . '/data/logodd_positive.txt', FILE_IGNORE_NEW_LINES);
$this->logOddNegative=file($this->rootDirectory . '/data/logodd_negative.txt', FILE_IGNORE_NEW_LINES);
}
public function standard($text)
{
$text=preg_replace($this->arStandardPatterns, $this->arStandardReplacements, $text);
return $text;
}
public function isFemale($str)
{
$female=false;
$words=explode(' ', $str);
$str=$words[0];
$str=str_replace(array('أ','إ','آ'), 'ا', $str);
$last=mb_substr($str, -1, 1);
$beforeLast=mb_substr($str, -2, 1);
if ($last=='ا' || $last=='ة' || $last=='ى' || ($last=='ء' && $beforeLast=='ا')) {
$female=true;
} elseif (preg_match("/^[اإ].{2}ا.$/u", $str) || preg_match("/^[إا].ت.ا.+$/u", $str)) {
$female=true;
} elseif (array_search($str, $this->arFemaleNames) > 0) {
$female=true;
}
return $female;
}
public function strtotime($text, $now)
{
$int=0;
for ($i=0; $i < 12; $i++) {
if (strpos($text, $this->hj[$i]) > 0) {
preg_match('/.*(\d{1,2}).*(\d{4}).*/', $text, $matches);
$fix=$this->mktimeCorrection($i + 1, $matches[2]);
$int=$this->mktime(0, 0, 0, $i + 1, $matches[1], $matches[2], $fix);
$temp=null;
break;
}
}
if ($int==0) {
$text=preg_replace($this->strToTimePatterns, $this->strToTimeReplacements, $text);
$text=str_replace($this->strToTimeSearch, $this->strToTimeReplace, $text);
$pattern='[ابتثجحخدذرزسشصضطظعغفقكلمنهوي]';
$text=preg_replace("/$pattern/", '', $text);
$int=strtotime($text, $now);
}
return $int;
}
public function mktime($hour, $minute, $second, $hj_month, $hj_day, $hj_year, $correction=0)
{
list($year, $month, $day)=$this->arDateIslamicToGreg($hj_year, $hj_month, $hj_day);
$unixTimeStamp=mktime($hour, $minute, $second, $month, $day, $year);
$unixTimeStamp=$unixTimeStamp + 3600 * 24 * $correction;
return $unixTimeStamp;
}
private function arDateIslamicToGreg($y, $m, $d)
{
$str=jdtogregorian($this->arDateIslamicToJd($y, $m, $d));
list($month, $day, $year)=explode('/', $str);
return array($year, $month, $day);
}
public function mktimeCorrection($m, $y)
{
if ($y >=1420 && $y < 1460) {
$calc=$this->mktime(0, 0, 0, $m, 1, $y);
$offset=(($y - 1420) * 12 + $m) * 11;
$d=substr($this->umAlqoura, $offset, 2);
$m=substr($this->umAlqoura, $offset + 3, 2);
$y=substr($this->umAlqoura, $offset + 6, 4);
$real=mktime(0, 0, 0, (int)$m, (int)$d, (int)$y);
$diff=(int)(($real - $calc) / (3600 * 24));
} else {
$diff=0;
}
return $diff;
}
public function hijriMonthDays($m, $y, $umAlqoura=true)
{
if ($y >=1320 && $y < 1460) {
$begin=$this->mktime(0, 0, 0, $m, 1, $y);
if ($m==12) {
$m2=1;
$y2=$y + 1;
} else {
$m2=$m + 1;
$y2=$y;
}
$end=$this->mktime(0, 0, 0, $m2, 1, $y2);
if ($umAlqoura===true) {
$c1=$this->mktimeCorrection($m, $y);
$c2=$this->mktimeCorrection($m2, $y2);
} else {
$c1=0;
$c2=0;
}
$days=($end - $begin) / (3600 * 24);
$days=$days - $c1 + $c2;
} else {
$days=false;
}
return $days;
}
public function en2ar($string, $locale='en_US')
{
setlocale(LC_ALL, $locale);
$string=iconv("UTF-8", "ASCII//TRANSLIT", $string);
$string=preg_replace('/[^\w\s]/', '', $string);
$string=strtolower($string);
$words=explode(' ', $string);
$string='';
foreach ($words as $word) {
if ($word=='el' || $word=='al') {
$space='';
} else {
$space=' ';
}
if (preg_match('/[a-z]/i', $word)) {
$word=preg_replace($this->en2arPregSearch, $this->en2arPregReplace, $word);
$word=strtr($word, array_combine($this->en2arStrSearch, $this->en2arStrReplace));
}
$string .=$word . $space;
}
return trim($string);
}
public function ar2en($string, $standard='UNGEGN')
{
$words=explode(' ', $string);
$string='';
for ($i=0; $i < count($words) - 1; $i++) {
$words[$i]=strtr($words[$i], 'ة', 'ت');
}
foreach ($words as $word) {
$temp=$word;
if ($standard=='UNGEGN+') {
$temp=strtr($temp, array_combine($this->diariticalSearch, $this->diariticalReplace));
} elseif ($standard=='RJGC') {
$temp=strtr($temp, array_combine($this->diariticalSearch, $this->diariticalReplace));
$temp=strtr($temp, array_combine($this->rjgcSearch, $this->rjgcReplace));
} elseif ($standard=='SES') {
$temp=strtr($temp, array_combine($this->diariticalSearch, $this->diariticalReplace));
$temp=strtr($temp, array_combine($this->sesSearch, $this->sesReplace));
} elseif ($standard=='ISO233') {
$temp=strtr($temp, array_combine($this->iso233Search, $this->iso233Replace));
}
$temp=preg_replace($this->ar2enPregSearch, $this->ar2enPregReplace, $temp);
$temp=strtr($temp, array_combine($this->ar2enStrSearch, $this->ar2enStrReplace));
$temp=preg_replace($this->arFinePatterns, $this->arFineReplacements, $temp);
if (preg_match('/[a-z]/', mb_substr($temp, 0, 1))) {
$temp=ucwords($temp);
}
$pos=strpos($temp, '-');
if ($pos > 0) {
if (preg_match('/[a-z]/', mb_substr($temp, $pos + 1, 1))) {
$temp2=substr($temp, 0, $pos);
$temp2 .='-' . strtoupper($temp[$pos + 1]);
$temp2 .=substr($temp, $pos + 2);
} else {
$temp2=$temp;
}
} else {
$temp2=$temp;
}
$string .=' ' . $temp2;
}
return trim($string);
}
public function setDateMode($mode=1)
{
$mode=(int) $mode;
if ($mode > 0 && $mode < 9) {
$this->arDateMode=$mode;
}
return $this;
}
public function getDateMode()
{
return $this->arDateMode;
}
public function date($format, $timestamp, $correction=0)
{
if ($this->arDateMode==1 || $this->arDateMode==8) {
$hj_txt_month=array();
if ($this->arDateMode==1) {
foreach ($this->arDateJSON['ar_hj_month'] as $id=> $month) {
$id++;
$hj_txt_month["$id"]=(string)$month;
}
}
if ($this->arDateMode==8) {
foreach ($this->arDateJSON['en_hj_month'] as $id=> $month) {
$id++;
$hj_txt_month["$id"]=(string)$month;
}
}
$patterns=array();
$replacements=array();
$patterns[]='Y';
$patterns[]='y';
$patterns[]='M';
$patterns[]='F';
$patterns[]='n';
$patterns[]='m';
$patterns[]='j';
$patterns[]='d';
$replacements[]='x1';
$replacements[]='x2';
$replacements[]='x3';
$replacements[]='x3';
$replacements[]='x4';
$replacements[]='x5';
$replacements[]='x6';
$replacements[]='x7';
if ($this->arDateMode==8) {
$patterns[]='S';
$replacements[]='';
}
$format=strtr($format, array_combine($patterns, $replacements));
$str=date($format, $timestamp);
if ($this->arDateMode==1) {
$str=$this->arDateEn2ar($str);
}
$timestamp=$timestamp + 3600 * 24 * $correction;
list($y, $m, $d)=explode(' ', date('Y m d', $timestamp));
list($hj_y, $hj_m, $hj_d)=$this->arDateGregToIslamic($y, $m, $d);
list($y, $m, $d)=explode(' ', date('Y m d', $timestamp));
list($hj_y, $hj_m, $hj_d)=$this->arDateGregToIslamic((int)$y, (int)$m, (int)$d);
$hj_d +=$correction;
if ($hj_d <=0) {
$hj_d=30;
list($hj_y, $hj_m, $temp)=$this->arDateGregToIslamic((int)$y, (int)$m, (int)$d + $correction);
} elseif ($hj_d > 30) {
list($hj_y, $hj_m, $hj_d)=$this->arDateGregToIslamic((int)$y, (int)$m, (int)$d + $correction);
}
$patterns=array();
$replacements=array();
$patterns[]='x1';
$patterns[]='x2';
$patterns[]='x3';
$patterns[]='x4';
$patterns[]='x5';
$patterns[]='x6';
$patterns[]='x7';
$replacements[]=$hj_y;
$replacements[]=substr($hj_y, -2);
$replacements[]=$hj_txt_month[$hj_m];
$replacements[]=$hj_m;
$replacements[]=sprintf('%02d', $hj_m);
$replacements[]=$hj_d;
$replacements[]=sprintf('%02d', $hj_d);
$str=strtr($str, array_combine($patterns, $replacements));
} elseif ($this->arDateMode==5) {
$year=date('Y', $timestamp);
$year -=632;
$yr=substr("$year", -2);
$format=str_replace('Y', (string)$year, $format);
$format=str_replace('y', $yr, $format);
$str=date($format, $timestamp);
$str=$this->arDateEn2ar($str);
} else {
$str=date($format, $timestamp);
$str=$this->arDateEn2ar($str);
}
return $str;
}
private function arDateEn2ar($str)
{
$patterns=array();
$replacements=array();
$str=strtolower($str);
foreach ($this->arDateJSON['en_day']['mode_full'] as $day) {
$patterns[]=(string)$day;
}
foreach ($this->arDateJSON['ar_day'] as $day) {
$replacements[]=(string)$day;
}
foreach ($this->arDateJSON['en_month']['mode_full'] as $month) {
$patterns[]=(string)$month;
}
$replacements=array_merge($replacements, $this->arDateArabicMonths($this->arDateMode));
foreach ($this->arDateJSON['en_day']['mode_short'] as $day) {
$patterns[]=(string)$day;
}
foreach ($this->arDateJSON['ar_day'] as $day) {
$replacements[]=(string)$day;
}
foreach ($this->arDateJSON['en_month']['mode_short'] as $m) {
$patterns[]=(string)$m;
}
$replacements=array_merge($replacements, $this->arDateArabicMonths($this->arDateMode));
foreach ($this->arDateJSON['preg_replace_en2ar'] as $p) {
$patterns[]=(string)$p['search'];
$replacements[]=(string)$p['replace'];
}
$str=strtr($str, array_combine($patterns, $replacements));
return $str;
}
private function arDateArabicMonths($mode)
{
$replacements=array();
foreach ($this->arDateJSON['ar_month']["mode_$mode"] as $month) {
$replacements[]=(string)$month;
}
return $replacements;
}
private function arDateGregToIslamic($y, $m, $d)
{
$jd=gregoriantojd($m, $d, $y);
list($year, $month, $day)=$this->arDateJdToIslamic($jd);
return array($year, $month, $day);
}
private function arDateJdToIslamic($jd)
{
$l=(int)$jd - 1948440 + 10632;
$n=(int)(($l - 1) / 10631);
$l=$l - 10631 * $n + 354;
$j=(int)((10985 - $l) / 5316) * (int)((50 * $l) / 17719) + (int)($l / 5670) * (int)((43 * $l) / 15238);
$l=$l - (int)((30 - $j) / 15) * (int)((17719 * $j) / 50) - (int)($j / 16) * (int)((15238 * $j) / 43) + 29;
$m=(int)((24 * $l) / 709);
$d=$l - (int)((709 * $m) / 24);
$y=(int)(30 * $n + $j - 30);
return array($y, $m, $d);
}
private function arDateIslamicToJd($y, $m, $d)
{
$jd=(int)((11 * $y + 3) / 30) + (int)(354 * $y) + (int)(30 * $m) - (int)(($m - 1) / 2) + $d + 1948440 - 385;
return $jd;
}
public function dateCorrection($time)
{
$calc=$time - (int)$this->date('j', $time) * 3600 * 24;
$y=$this->date('Y', $time);
$m=$this->date('n', $time);
$offset=(((int)$y - 1420) * 12 + (int)$m) * 11;
$d=substr($this->umAlqoura, $offset, 2);
$m=substr($this->umAlqoura, $offset + 3, 2);
$y=substr($this->umAlqoura, $offset + 6, 4);
$real=mktime(0, 0, 0, (int)$m, (int)$d, (int)$y);
$diff=(int)(($calc - $real) / (3600 * 24));
return $diff;
}
public function setNumberFeminine($value)
{
if ($value==1 || $value==2) {
$this->arNumberFeminine=$value;
}
return $this;
}
public function setNumberFormat($value)
{
if ($value==1 || $value==2) {
$this->arNumberFormat=$value;
}
return $this;
}
public function setNumberOrder($value)
{
if ($value==1 || $value==2) {
$this->arNumberOrder=$value;
}
return $this;
}
public function getNumberFeminine()
{
return $this->arNumberFeminine;
}
public function getNumberFormat()
{
return $this->arNumberFormat;
}
public function getNumberOrder()
{
return $this->arNumberOrder;
}
public function int2str($number)
{
if ($number==1 && $this->arNumberOrder==2) {
if ($this->arNumberFeminine==1) {
$string='الأول';
} else {
$string='الأولى';
}
} else {
if ($number < 0) {
$string='سالب ';
$number=(string) (-1 * $number);
} else {
$string='';
}
$temp=explode('.', (string)$number);
$string .=$this->arNumbersSubStr("{$temp[0]}");
if (!empty($temp[1])) {
$dec=$this->arNumbersSubStr("{$temp[1]}");
$string .=' فاصلة ' . $dec;
}
}
return $string;
}
public function int2strItem($count, $word)
{
$feminine=$this->isFemale($word) ? 2 : 1;
$this->setNumberFeminine($feminine);
$str1=$this->int2str($count);
$str2=$this->arPlural($word, $count);
$string=str_replace('%d', $str1, $str2);
return $string;
}
public function money2str($number, $iso='SYP', $lang='ar')
{
$iso=strtoupper($iso);
$lang=strtolower($lang);
$number=sprintf("%01.{$this->arNumberCurrency[$iso]['decimals']}f", $number);
$temp=explode('.', $number);
$string='';
if ($temp[0] !=0) {
if ($lang=='ar') {
$string .=$this->int2strItem((int)$temp[0], $this->arNumberCurrency[$iso][$lang]['basic']);
} else {
$string .=$temp[0] . ' ' . $this->arNumberCurrency[$iso][$lang]['basic'];
}
}
if (!empty($temp[1]) && $temp[1] !=0) {
if ($string !='') {
if ($lang=='ar') {
$string .=' و';
} else {
$string .=' and ';
}
}
if ($lang=='ar') {
$string .=$this->int2strItem((int)$temp[1], $this->arNumberCurrency[$iso][$lang]['fraction']);
} else {
$string .=$temp[1] . ' ' . $this->arNumberCurrency[$iso][$lang]['fraction'];
}
}
return $string;
}
public function str2int($str)
{
$str=str_replace(array('أ','إ','آ'), 'ا', $str);
$str=str_replace('ه', 'ة', $str);
$str=preg_replace('/\s+/', ' ', $str);
$ptr=array('ـ', 'َ','ً','ُ','ٌ','ِ','ٍ','ْ','ّ');
$str=str_replace($ptr, '', $str);
$str=str_replace('مائة', 'مئة', $str);
$ptr=array('/احدى\s/u','/احد\s/u');
$str=preg_replace($ptr, 'واحد ', $str);
$ptr=array('/اثنا\s/u','/اثني\s/u','/اثنتا\s/u', '/اثنتي\s/u','/اثنين\s/u','/اثنتان\s/u', '/اثنتين\s/u');
$str=preg_replace($ptr, 'اثنان ', $str);
$str=trim($str);
if (strpos($str, 'ناقص')===false && strpos($str, 'سالب')===false) {
$negative=false;
} else {
$negative=true;
}
$segment=array();
$max=count($this->arNumberComplications);
for ($scale=$max; $scale > 0; $scale--) {
$key=pow(1000, $scale);
$pattern=array('أ','إ','آ');
$format1=str_replace($pattern, 'ا', $this->arNumberComplications[$scale][1]);
$format2=str_replace($pattern, 'ا', $this->arNumberComplications[$scale][2]);
$format3=str_replace($pattern, 'ا', $this->arNumberComplications[$scale][3]);
$format4=str_replace($pattern, 'ا', $this->arNumberComplications[$scale][4]);
if (strpos($str, $format1) !==false) {
list($temp, $str)=explode($format1, $str);
$segment[$key]='اثنان';
} elseif (strpos($str, $format2) !==false) {
list($temp, $str)=explode($format2, $str);
$segment[$key]='اثنان';
} elseif (strpos($str, $format3) !==false) {
list($segment[$key], $str)=explode($format3, $str);
} elseif (strpos($str, $format4) !==false) {
list($segment[$key], $str)=explode($format4, $str);
if ($segment[$key]=='') {
$segment[$key]='واحد';
}
}
if (isset($segment[$key])) {
$segment[$key]=trim($segment[$key]);
}
}
$segment[1]=trim($str);
$total=0;
$subTotal=0;
foreach ($segment as $scale=> $str) {
$str=" $str ";
foreach ($this->arNumberSpell as $word=> $value) {
if (strpos($str, "$word ") !==false) {
$str=str_replace("$word ", ' ', $str);
$subTotal +=$value;
}
}
$total   +=$subTotal * $scale;
$subTotal=0;
}
if ($negative) {
$total=-1 * $total;
}
return $total;
}
private function arNumbersSubStr($number, $zero=true)
{
$blocks=array();
$items=array();
$zeros='';
$string='';
$number=($zero !=false) ? trim((string)$number) : trim((string)(float)$number);
if ($number > 0) {
if ($zero !=false) {
$fulnum=$number;
while (($fulnum[0])=='0') {
$zeros='صفر ' . $zeros;
$fulnum=substr($fulnum, 1, strlen($fulnum));
};
$zeros=trim($zeros);
};
while (strlen($number) > 3) {
$blocks[]=substr($number, -3);
$number=substr($number, 0, strlen($number) - 3);
}
$blocks[]=$number;
$blocks_num=count($blocks) - 1;
for ($i=$blocks_num; $i >=0; $i--) {
$number=floor((float)$blocks[$i]);
$text=$this->arNumberWrittenBlock((int)$number);
if ($text) {
if ($number==1 && $i !=0) {
$text=$this->arNumberComplications[$i][4];
} elseif ($number==2 && $i !=0) {
$text=$this->arNumberComplications[$i][$this->arNumberFormat];
} elseif ($number > 2 && $number < 11 && $i !=0) {
$text .=' ' . $this->arNumberComplications[$i][3];
} elseif ($i !=0) {
$text .=' ' . $this->arNumberComplications[$i][4];
}
if ($this->arNumberOrder==2 && ($number > 1 && $number < 11)) {
$text='ال' . $text;
}
if ($text !='' && $zeros !='' && $zero !=false) {
$text=$zeros . ' ' . $text;
$zeros='';
};
$items[]=$text;
}
}
$string=implode(' و', $items);
} else {
$string='صفر';
}
return $string;
}
private function arNumberWrittenBlock($number)
{
$items=array();
$string='';
if ($number > 99) {
$hundred=floor($number / 100) * 100;
$number=$number % 100;
if ($this->arNumberOrder==2) {
$pre='ال';
} else {
$pre='';
}
if ($hundred==200) {
$items[]=$pre . $this->arNumberIndividual[$hundred][$this->arNumberFormat];
} else {
$items[]=$pre . $this->arNumberIndividual[$hundred];
}
}
if ($number !=0) {
if ($this->arNumberOrder==2) {
if ($number <=10) {
$items[]=$this->arNumberOrdering[$number][$this->arNumberFeminine];
} elseif ($number < 20) {
$number -=10;
$item='ال' . $this->arNumberOrdering[$number][$this->arNumberFeminine];
if ($this->arNumberFeminine==1) {
$item .=' عشر';
} else {
$item .=' عشرة';
}
$items[]=$item;
} else {
$ones=$number % 10;
$tens=floor($number / 10) * 10;
if ($ones > 0) {
$items[]='ال' . $this->arNumberOrdering[$ones][$this->arNumberFeminine];
}
$items[]='ال' . $this->arNumberIndividual[$tens][$this->arNumberFormat];
}
} else {
if ($number==2 || $number==12) {
$items[]=$this->arNumberIndividual[$number][$this->arNumberFeminine][$this->arNumberFormat];
} elseif ($number < 20) {
$items[]=$this->arNumberIndividual[$number][$this->arNumberFeminine];
} else {
$ones=$number % 10;
$tens=floor($number / 10) * 10;
if ($ones==2) {
$items[]=$this->arNumberIndividual[2][$this->arNumberFeminine][$this->arNumberFormat];
} elseif ($ones > 0) {
$items[]=$this->arNumberIndividual[$ones][$this->arNumberFeminine];
}
$items[]=$this->arNumberIndividual[$tens][$this->arNumberFormat];
}
}
}
$items=array_diff($items, array(''));
$string=implode(' و', $items);
return $string;
}
public function int2indic($number)
{
$str=strtr("$number", $this->arNumberArabicIndic);
return $str;
}
public function swapAe($text)
{
$output=$this->swapCore($text, 'ar', 'en');
return $output;
}
public function swapEa($text)
{
$output=$this->swapCore($text, 'en', 'ar');
return $output;
}
public function swapAf($text)
{
$output=$this->swapCore($text, 'ar', 'fr');
return $output;
}
public function swapFa($text)
{
$output=$this->swapCore($text, 'fr', 'ar');
return $output;
}
private function swapCore($text, $in, $out)
{
$output='';
$text=stripslashes($text);
$max=mb_strlen($text);
$inputMap=array();
$outputMap=array();
switch ($in) {
case 'ar':
$inputMap=$this->arKeyboard;
break;
case 'en':
$inputMap=$this->enKeyboard;
break;
case 'fr':
$inputMap=$this->frKeyboard;
break;
}
switch ($out) {
case 'ar':
$outputMap=$this->arKeyboard;
break;
case 'en':
$outputMap=$this->enKeyboard;
break;
case 'fr':
$outputMap=$this->frKeyboard;
break;
}
for ($i=0; $i < $max; $i++) {
$chr=mb_substr($text, $i, 1);
$key=array_search($chr, $inputMap);
if ($key===false) {
$output .=$chr;
} else {
$output .=$outputMap[$key];
}
}
return $output;
}
private function checkEn($str)
{
$str=mb_strtolower($str);
$max=mb_strlen($str);
$rank=0;
for ($i=1; $i < $max; $i++) {
$first=mb_substr($str, $i - 1, 1);
$second=mb_substr($str, $i, 1);
if (isset($this->enLogodd["$first"]["$second"])) {
$rank +=$this->enLogodd["$first"]["$second"];
} else {
$rank -=10;
}
}
return $rank;
}
private function checkAr($str)
{
$max=mb_strlen($str);
$rank=0;
for ($i=1; $i < $max; $i++) {
$first=mb_substr($str, $i - 1, 1);
$second=mb_substr($str, $i, 1);
if (isset($this->arLogodd["$first"]["$second"])) {
$rank +=$this->arLogodd["$first"]["$second"];
} else {
$rank -=10;
}
}
return $rank;
}
public function fixKeyboardLang($str)
{
preg_match_all("/([\x{0600}-\x{06FF}])/u", $str, $matches);
$arNum=count($matches[0]);
$nonArNum=mb_strlen(str_replace(' ', '', $str)) - $arNum;
$capital='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
$small='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$strCaps=strtr($str, $capital, $small);
$arStrCaps=$this->swapEa($strCaps);
if ($arNum > $nonArNum) {
$arStr=$str;
$enStr=$this->swapAe($str);
$isAr=true;
$enRank=$this->checkEn($enStr);
$arRank=$this->checkAr($arStr);
$arCapsRank=$arRank;
} else {
$arStr=$this->swapEa($str);
$enStr=$str;
$isAr=false;
$enRank=$this->checkEn($enStr);
$arRank=$this->checkAr($arStr);
$arCapsRank=$this->checkAr($arStrCaps);
}
if ($enRank > $arRank && $enRank > $arCapsRank) {
if ($isAr) {
$fix=$enStr;
} else {
preg_match_all("/([A-Z])/u", $enStr, $matches);
$capsNum=count($matches[0]);
preg_match_all("/([a-z])/u", $enStr, $matches);
$nonCapsNum=count($matches[0]);
if ($capsNum > $nonCapsNum && $nonCapsNum > 0) {
$enCapsStr=strtr($enStr, $capital, $small);
$fix=$enCapsStr;
} else {
$fix='';
}
}
} else {
if ($arCapsRank > $arRank) {
$arStr=$arStrCaps;
$arRank=$arCapsRank;
}
if (!$isAr) {
$fix=$arStr;
} else {
$fix='';
}
}
return $fix;
}
public function setSoundexLen($integer)
{
$this->soundexLen=(int)$integer;
return $this;
}
public function setSoundexLang($str)
{
$str=strtolower($str);
if ($str=='ar' || $str=='en') {
$this->soundexLang=$str;
}
return $this;
}
public function setSoundexCode($str)
{
$str=strtolower($str);
if ($str=='soundex' || $str=='phonix') {
$this->soundexCode=$str;
if ($str=='phonix') {
$this->soundexMap=$this->arPhonixCode;
} else {
$this->soundexMap=$this->arSoundexCode;
}
}
return $this;
}
public function getSoundexLen()
{
return $this->soundexLen;
}
public function getSoundexLang()
{
return $this->soundexLang;
}
public function getSoundexCode()
{
return $this->soundexCode;
}
private function arSoundexMapCode($word)
{
$encodedWord='';
$max=mb_strlen($word);
for ($i=0; $i < $max; $i++) {
$char=mb_substr($word, $i, 1);
if (isset($this->soundexMap["$char"])) {
$encodedWord .=$this->soundexMap["$char"];
} else {
$encodedWord .='0';
}
}
return $encodedWord;
}
private function arSoundexTrimRep($word)
{
$lastChar=null;
$cleanWord=null;
$max=mb_strlen($word);
for ($i=0; $i < $max; $i++) {
$char=mb_substr($word, $i, 1);
if ($char !=$lastChar) {
$cleanWord .=$char;
}
$lastChar=$char;
}
return $cleanWord;
}
public function soundex($word)
{
$soundex=mb_substr($word, 0, 1);
$rest=mb_substr($word, 1, mb_strlen($word));
if ($this->soundexLang=='en') {
$soundex=$this->soundexTransliteration[$soundex];
}
$encodedRest=$this->arSoundexMapCode($rest);
$cleanEncodedRest=$this->arSoundexTrimRep($encodedRest);
$soundex .=$cleanEncodedRest;
$soundex=str_replace('0', '', $soundex);
$totalLen=mb_strlen($soundex);
if ($totalLen > $this->soundexLen) {
$soundex=mb_substr($soundex, 0, $this->soundexLen);
} else {
$soundex .=str_repeat('0', $this->soundexLen - $totalLen);
}
return $soundex;
}
public function addGlyphs($char, $hex, $prevLink=true, $nextLink=true)
{
if ($prevLink) {
$this->arGlyphsPrevLink=$char . $this->arGlyphsPrevLink;
}
if ($nextLink) {
$this->arGlyphsNextLink=$char . $this->arGlyphsNextLink;
}
$this->arGlyphs=$char . $this->arGlyphs;
$this->arGlyphsHex=$hex . $this->arGlyphsHex;
}
private function getArGlyphs($char, $type)
{
$pos=mb_strpos($this->arGlyphs, $char, 0);
$lastSimpleCharOffset=mb_strlen($this->arGlyphs) - 8;
if ($pos > $lastSimpleCharOffset) {
$pos=($pos - $lastSimpleCharOffset) / 2 + $lastSimpleCharOffset;
}
$pos=$pos * 16 + $type * 4;
return substr($this->arGlyphsHex, $pos, 4);
}
private function arGlyphsPreConvert($str)
{
$crntChar=null;
$prevChar=null;
$nextChar=null;
$output='';
$number='';
$chars=array();
$_temp=mb_strlen($str);
for ($i=0; $i < $_temp; $i++) {
$chars[]=mb_substr($str, $i, 1);
}
$max=count($chars);
for ($i=$max - 1; $i >=0; $i--) {
$crntChar=$chars[$i];
$prevChar=' ';
if ($i > 0) {
$prevChar=$chars[$i - 1];
}
if (is_numeric($crntChar)) {
$number=$crntChar . $number;
continue;
} elseif (strlen($number) > 0) {
$output .=$number;
$number='';
}
if ($prevChar && mb_strpos($this->arGlyphsVowel, $prevChar, 0) !==false) {
$prevChar=$chars[$i - 2];
if ($prevChar && mb_strpos($this->arGlyphsVowel, $prevChar, 0) !==false) {
$prevChar=$chars[$i - 3];
}
}
$Reversed=false;
$flip_arr=')]>}';
$ReversedChr='([<{';
if ($crntChar && mb_strpos($flip_arr, $crntChar, 0) !==false) {
$crntChar=$ReversedChr[mb_strpos($flip_arr, $crntChar, 0)];
$Reversed=true;
} else {
$Reversed=false;
}
if ($crntChar && !$Reversed && (mb_strpos($ReversedChr, $crntChar, 0) !==false)) {
$crntChar=$flip_arr[mb_strpos($ReversedChr, $crntChar, 0)];
}
if (ord($crntChar) < 128) {
$output  .=$crntChar;
$nextChar=$crntChar;
continue;
}
if (
$crntChar=='ل' && isset($chars[$i + 1])
&& (mb_strpos('آأإا', $chars[$i + 1], 0) !==false)
) {
continue;
}
if ($crntChar && mb_strpos($this->arGlyphsVowel, $crntChar, 0) !==false) {
if (
isset($chars[$i + 1])
&& (mb_strpos($this->arGlyphsNextLink, $chars[$i + 1], 0) !==false)
&& (mb_strpos($this->arGlyphsPrevLink, $prevChar, 0) !==false)
) {
$output .='&#x' . $this->getArGlyphs($crntChar, 1) . ';';
} else {
$output .='&#x' . $this->getArGlyphs($crntChar, 0) . ';';
}
continue;
}
$form=0;
if (
($prevChar=='لا' || $prevChar=='لآ' || $prevChar=='لأ'
|| $prevChar=='لإ' || $prevChar=='ل')
&& (mb_strpos('آأإا', $crntChar, 0) !==false)
) {
if ($i > 1) {
if (mb_strpos($this->arGlyphsPrevLink, $chars[$i - 2], 0) !==false) {
$form++;
}
}
if (mb_strpos($this->arGlyphsVowel, $chars[$i - 1], 0)) {
$output .='&#x';
$output .=$this->getArGlyphs($crntChar, $form) . ';';
} else {
$output .='&#x';
$output .=$this->getArGlyphs($prevChar . $crntChar, $form) . ';';
}
$nextChar=$prevChar;
continue;
}
if ($prevChar && mb_strpos($this->arGlyphsPrevLink, $prevChar, 0) !==false) {
$form++;
}
if ($nextChar && mb_strpos($this->arGlyphsNextLink, $nextChar, 0) !==false) {
$form +=2;
}
$output  .='&#x' . $this->getArGlyphs($crntChar, $form) . ';';
$nextChar=$crntChar;
}
$output=$this->arGlyphsDecodeEntities($output, $exclude=array('&'));
return $output;
}
 public function utf8Glyphs($str, $max_chars = 150, $hindo=true, $forcertl=false)
{
$lines=array();
$userLines=explode("\n", $text);
foreach ($userLines as $line) {
while (mb_strlen($line) > $max_chars) {
$last=mb_strrpos(mb_substr($line, 0, $max_chars), ' ');
$lines[]=mb_substr($line, 0, $last);
$line=mb_substr($line, $last + 1, mb_strlen($line) - $last);
}
$lines[]=$line;
}
$outLines=array();
foreach ($lines as $str) {
$p=$this->arIdentify($str);
if (count($p) > 0) {
if ($forcertl==true || $p[0]==0) {
$rtl=true;
} else {
$rtl=false;
}
$block=array();
if ($p[0] !=0) {
$block[]=substr($str, 0, $p[0] - 1);
}
$max=count($p);
if ($rtl==true) {
for ($i=0; $i < $max; $i +=2) {
$p[$i]=strlen(preg_replace('/\)\s*$/', '', substr($str, 0, $p[$i])));
}
}
for ($i=0; $i < $max; $i +=2) {
$block[]=$this->arGlyphsPreConvert(substr($str, $p[$i], $p[$i + 1] - $p[$i]));
if ($i + 2 < $max) {
$block[]=substr($str, $p[$i + 1], $p[$i + 2] - $p[$i + 1]);
} elseif ($p[$i + 1] !=strlen($str)) {
$block[]=substr($str, $p[$i + 1], strlen($str) - $p[$i + 1]);
}
}
if ($rtl==true) {
$block=array_reverse($block);
}
$str=implode(' ', $block);
}
$outLines[]=$str;
}
$output=implode("\n", $outLines);
$num=array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
$arNum=array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
if ($hindo==true) {
$output=str_replace($num, $arNum, $output);
}
return $output;
}
private function arGlyphsDecodeEntities($text, $exclude=array())
{
$table=array_flip(get_html_translation_table(HTML_ENTITIES, ENT_COMPAT, 'UTF-8'));
$table['&apos;']="'";
$newtable=array_diff($table, $exclude);
$text=preg_replace_callback('/&(#x?)?([A-Fa-f0-9]+);/u', function ($matches) use ($newtable, $exclude) {
return $this->arGlyphsDecodeEntities2($matches[1], $matches[2], $matches[0], $newtable, $exclude);
}, $text);
return $text;
}
private function arGlyphsDecodeEntities2($prefix, $codepoint, $original, &$table, &$exclude)
{
if (!$prefix) {
if (isset($table[$original])) {
return $table[$original];
} else {
return $original;
}
}
if ($prefix=='#x') {
$codepoint=base_convert($codepoint, 16, 10);
}
$str='';
if ($codepoint < 0x80) {
$str=chr((int)$codepoint);
} elseif ($codepoint < 0x800) {
$str=chr(0xC0 | ((int)$codepoint >> 6)) . chr(0x80 | ((int)$codepoint & 0x3F));
} elseif ($codepoint < 0x10000) {
$str=chr(0xE0 | ((int)$codepoint >> 12)) . chr(0x80 | (((int)$codepoint >> 6) & 0x3F)) .
chr(0x80 | ((int)$codepoint & 0x3F));
} elseif ($codepoint < 0x200000) {
$str=chr(0xF0 | ((int)$codepoint >> 18)) . chr(0x80 | (((int)$codepoint >> 12) & 0x3F)) .
chr(0x80 | (((int)$codepoint >> 6) & 0x3F)) . chr(0x80 | ((int)$codepoint & 0x3F));
}
if (in_array($str, $exclude, true)) {
return $original;
} else {
return $str;
}
}
public function setQueryArrFields($arrConfig)
{
if (is_array($arrConfig)) {
$this->arQueryFields=$arrConfig;
}
return $this;
}
public function setQueryStrFields($strConfig)
{
if (is_string($strConfig)) {
$this->arQueryFields=explode(',', $strConfig);
}
return $this;
}
public function setQueryMode($mode)
{
if (in_array($mode, array('0', '1'))) {
$this->arQueryMode=$mode;
}
return $this;
}
public function getQueryMode()
{
return $this->arQueryMode;
}
public function getQueryArrFields()
{
$fields=$this->arQueryFields;
return $fields;
}
public function getQueryStrFields()
{
$fields=implode(',', $this->arQueryFields);
return $fields;
}
public function arQueryWhereCondition($arg)
{
$sql='';
$search=array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
$replace=array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");
$arg=str_replace($search, $replace, $arg);
$phrase=explode("\"", $arg);
if (count($phrase) > 2) {
$arg='';
for ($i=0; $i < count($phrase); $i++) {
$subPhrase=$phrase[$i];
if ($i % 2==0 && $subPhrase !='') {
$arg .=$subPhrase;
} elseif ($i % 2==1 && $subPhrase !='') {
$wordCondition[]=$this->getWordLike($subPhrase);
}
}
}
$words=preg_split('/\s+/', trim($arg));
foreach ($words as $word) {
$exclude=array('(', ')', '[', ']', '{', '}', ',', ';', ':', '?', '!', '،', '؛', '؟');
$word=str_replace($exclude, '', $word);
$wordCondition[]=$this->getWordRegExp($word);
}
if (!empty($wordCondition)) {
if ($this->arQueryMode==0) {
$sql='(' . implode(') OR (', $wordCondition) . ')';
} elseif ($this->arQueryMode==1) {
$sql='(' . implode(') AND (', $wordCondition) . ')';
}
}
return $sql;
}
private function getWordRegExp($arg)
{
$arg=$this->arQueryLex($arg);
$sql=' REPLACE(' . implode(", 'ـ', '') REGEXP '$arg' OR REPLACE(", $this->arQueryFields) .
", 'ـ', '') REGEXP '$arg'";
return $sql;
}
private function getWordLike($arg)
{
$sql=implode(" LIKE '$arg' OR ", $this->arQueryFields) . " LIKE '$arg'";
return $sql;
}
public function arQueryOrderBy($arg)
{
$wordOrder=array();
$phrase=explode("\"", $arg);
if (count($phrase) > 2) {
$arg='';
for ($i=0; $i < count($phrase); $i++) {
if ($i % 2==0 && isset($phrase[$i])) {
$arg .=$phrase[$i];
} elseif ($i % 2==1 && isset($phrase[$i])) {
$wordOrder[]=$this->getWordLike($phrase[$i]);
}
}
}
$words=explode(' ', $arg);
foreach ($words as $word) {
if ($word !='') {
$wordOrder[]='CASE WHEN ' . $this->getWordRegExp($word) . ' THEN 1 ELSE 0 END';
}
}
$order='((' . implode(') + (', $wordOrder) . ')) DESC';
return $order;
}
private function arQueryLex($arg)
{
$arg=preg_replace($this->arQueryLexPatterns, $this->arQueryLexReplacements, $arg);
return $arg;
}
private function arQueryAllWordForms($word)
{
$wordForms=array($word);
$postfix1=array('كم', 'كن', 'نا', 'ها', 'هم', 'هن');
$postfix2=array('ين', 'ون', 'ان', 'ات', 'وا');
if (mb_substr($word, 0, 2)=='ال') {
$word=mb_substr($word, 2, mb_strlen($word));
}
$len=mb_strlen($word);
$wordForms[]=$word;
$str1=mb_substr($word, 0, -1);
$str2=mb_substr($word, 0, -2);
$str3=mb_substr($word, 0, -3);
$last1=mb_substr($word, -1, $len);
$last2=mb_substr($word, -2, $len);
$last3=mb_substr($word, -3, $len);
if ($len >=6 && $last3=='تين') {
$wordForms[]=$str3;
$wordForms[]=$str3 . 'ة';
$wordForms[]=$word . 'ة';
}
if ($len >=6 && ($last3=='كما' || $last3=='هما')) {
$wordForms[]=$str3;
$wordForms[]=$str3 . 'كما';
$wordForms[]=$str3 . 'هما';
}
if ($len >=5 && in_array($last2, $postfix2)) {
$wordForms[]=$str2;
$wordForms[]=$str2 . 'ة';
$wordForms[]=$str2 . 'تين';
foreach ($postfix2 as $postfix) {
$wordForms[]=$str2 . $postfix;
}
}
if ($len >=5 && in_array($last2, $postfix1)) {
$wordForms[]=$str2;
$wordForms[]=$str2 . 'ي';
$wordForms[]=$str2 . 'ك';
$wordForms[]=$str2 . 'كما';
$wordForms[]=$str2 . 'هما';
foreach ($postfix1 as $postfix) {
$wordForms[]=$str2 . $postfix;
}
}
if ($len >=5 && $last2=='ية') {
$wordForms[]=$str1;
$wordForms[]=$str2;
}
if (
($len >=4 && ($last1=='ة' || $last1=='ه' || $last1=='ت'))
|| ($len >=5 && $last2=='ات')
) {
$wordForms[]=$str1;
$wordForms[]=$str1 . 'ة';
$wordForms[]=$str1 . 'ه';
$wordForms[]=$str1 . 'ت';
$wordForms[]=$str1 . 'ات';
}
if ($len >=4 && $last1=='ى') {
$wordForms[]=$str1 . 'ا';
}
$trans=array('أ'=> 'ا', 'إ'=> 'ا', 'آ'=> 'ا');
foreach ($wordForms as $word) {
$normWord=strtr($word, $trans);
if ($normWord !=$word) {
$wordForms[]=$normWord;
}
}
$wordForms=array_unique($wordForms);
return $wordForms;
}
public function arQueryAllForms($arg)
{
$wordForms=array();
$words=explode(' ', $arg);
foreach ($words as $word) {
$wordForms=array_merge($wordForms, $this->arQueryAllWordForms($word));
}
$str=implode(' ', $wordForms);
return $str;
}
public function setSalatDate($m=8, $d=2, $y=1975)
{
if (is_numeric($y) && $y > 0 && $y < 3000) {
$this->salatYear=floor($y);
}
if (is_numeric($m) && $m >=1 && $m <=12) {
$this->salatMonth=floor($m);
}
if (is_numeric($d) && $d >=1 && $d <=31) {
$this->salatDay=floor($d);
}
return $this;
}
public function setSalatLocation($l1=36.20278, $l2=37.15861, $z=2, $e=0)
{
if (is_numeric($l1) && $l1 >=-180 && $l1 <=180) {
$this->salatLat=$l1;
}
if (is_numeric($l2) && $l2 >=-180 && $l2 <=180) {
$this->salatLong=$l2;
}
if (is_numeric($z) && $z >=-12 && $z <=12) {
$this->salatZone=floor($z);
}
if (is_numeric($e)) {
$this->salatElevation=$e;
}
return $this;
}
public function setSalatConf(
$sch='Shafi',
$sunriseArc=-0.833333,
$ishaArc=-17.5,
$fajrArc=-19.5,
$view='Sunni'
) {
$sch=ucfirst($sch);
if ($sch=='Shafi' || $sch=='Hanafi') {
$this->salatSchool=$sch;
}
if (is_numeric($sunriseArc) && $sunriseArc >=-180 && $sunriseArc <=180) {
$this->salatAB2=$sunriseArc;
}
if (is_numeric($ishaArc) && $ishaArc >=-180 && $ishaArc <=180) {
$this->salatAG2=$ishaArc;
}
if (is_numeric($fajrArc) && $fajrArc >=-180 && $fajrArc <=180) {
$this->salatAJ2=$fajrArc;
}
if ($view=='Sunni' || $view=='Shia') {
$this->salatView=$view;
}
return $this;
}
public function getPrayTime()
{
$unixtimestamp=mktime(0, 0, 0, $this->salatMonth, $this->salatDay, $this->salatYear);
if ($this->salatMonth <=2) {
$year=$this->salatYear - 1;
$month=$this->salatMonth + 12;
} else {
$year=$this->salatYear;
$month=$this->salatMonth;
}
$A=floor($year / 100);
$B=2 - $A + floor($A / 4);
$jd=floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $this->salatDay + $B - 1524.5;
$d=$jd - 2451545.0;  // jd is the given Julian date
$g=357.529 + 0.98560028 * $d;
$g=$g % 360 + ($g - ceil($g) + 1);
$q=280.459 + 0.98564736 * $d;
$q=$q % 360 + ($q - ceil($q) + 1);
$L=$q + 1.915 * sin(deg2rad($g)) + 0.020 * sin(deg2rad(2 * $g));
$L=$L % 360 + ($L - ceil($L) + 1);
$R=1.00014 - 0.01671 * cos(deg2rad($g)) - 0.00014 * cos(deg2rad(2 * $g));
$e=23.439 - 0.00000036 * $d;
$RA=rad2deg(atan2(cos(deg2rad($e)) * sin(deg2rad($L)), cos(deg2rad($L)))) / 15;
if ($RA < 0) {
$RA=24 + $RA;
}
$D=rad2deg(asin(sin(deg2rad($e)) * sin(deg2rad($L))));
$EqT=($q / 15) - $RA;  // equation of time
$Dhuhr=12 + $this->salatZone - ($this->salatLong / 15) - $EqT;
if ($Dhuhr < 0) {
$Dhuhr=24 + $Dhuhr;
}
$alpha=0.833 + 0.0347 * sqrt($this->salatElevation);
$n=-1 * sin(deg2rad($alpha)) - sin(deg2rad($this->salatLat)) * sin(deg2rad($D));
$d=cos(deg2rad($this->salatLat)) * cos(deg2rad($D));
$Sunrise=$Dhuhr - (1 / 15) * rad2deg(acos($n / $d));
$Sunset=$Dhuhr + (1 / 15) * rad2deg(acos($n / $d));
$n=-1 * sin(deg2rad(abs($this->salatAJ2))) - sin(deg2rad($this->salatLat)) * sin(deg2rad($D));
$Fajr=$Dhuhr - (1 / 15) * rad2deg(acos($n / $d));
$Imsak=$Fajr - (10 / 60);
$n=-1 * sin(deg2rad(abs($this->salatAG2))) - sin(deg2rad($this->salatLat)) * sin(deg2rad($D));
$Isha=$Dhuhr + (1 / 15) * rad2deg(acos($n / $d));
if ($this->salatSchool=='Shafi') {
$n=sin(atan(1 / (1 + tan(deg2rad($this->salatLat - $D))))) -
sin(deg2rad($this->salatLat)) * sin(deg2rad($D));
} else {
$n=sin(atan(1 / (2 + tan(deg2rad($this->salatLat - $D))))) -
sin(deg2rad($this->salatLat)) * sin(deg2rad($D));
}
$Asr=$Dhuhr + (1 / 15) * rad2deg(acos($n / $d));
if ($this->salatView=='Sunni') {
$Maghrib=$Sunset + 2 / 60;
} else {
$n=-1 * sin(deg2rad(4)) - sin(deg2rad($this->salatLat)) * sin(deg2rad($D));
$Maghrib=$Dhuhr + (1 / 15) * rad2deg(acos($n / $d));
}
if ($this->salatView=='Sunni') {
$Midnight=$Sunset + 0.5 * ($Sunrise - $Sunset);
} else {
$Midnight=$Sunset + 0.5 * ($Fajr - $Sunset);
}
if ($Midnight > 12) {
$Midnight=$Midnight - 12;
} else {
$Midnight=$Midnight + 12;
}
$times=array($Fajr, $Sunrise, $Dhuhr, $Asr, $Maghrib, $Isha, $Sunset, $Midnight, $Imsak);
foreach ($times as $index=> $time) {
$hours=floor($time);
$minutes=round(($time - $hours) * 60);
if ($minutes < 10) {
$minutes="0$minutes";
}
$times[$index]="$hours:$minutes";
$times[9][$index]=$unixtimestamp + 3600 * $hours + 60 * $minutes;
if ($index==7 && $hours < 6) {
$times[9][$index] +=24 * 3600;
}
}
return $times;
}
public function getQibla()
{
$K_latitude=21.423333;
$K_longitude=39.823333;
$latitude=$this->salatLat;
$longitude=$this->salatLong;
$numerator=sin(deg2rad($K_longitude - $longitude));
$denominator=(cos(deg2rad($latitude)) * tan(deg2rad($K_latitude))) -
(sin(deg2rad($latitude)) * cos(deg2rad($K_longitude - $longitude)));
$q=atan($numerator / $denominator);
$q=rad2deg($q);
if ($this->salatLat > 21.423333) {
$q +=180;
}
return $q;
}
public function dms2dd($value)
{
$pattern="/(\d{1,2})°((\d{1,2})')?((\d{1,2})\")?([NSEW])/i";
preg_match($pattern, $value, $matches);
$degree=$matches[1] + ($matches[3] / 60) + ($matches[5] / 3600);
$direction=strtoupper($matches[6]);
if ($direction=='S' || $direction=='W') {
$degree=-1 * $degree;
}
return $degree;
}
public function dd2dms($value)
{
if ($value < 0) {
$value=abs($value);
$dd='-';
} else {
$dd='';
}
$degrees=(int)$value;
$minutes=(int)(($value - $degrees) * 60);
$seconds=round(((($value - $degrees) * 60) - $minutes) * 60, 4);
if ($degrees > 0) {
$dd .=$degrees . '°';
}
if ($minutes >=10) {
$dd .=$minutes . '\'';
} else {
$dd .='0' . $minutes . '\'';
}
if ($seconds >=10) {
$dd .=$seconds . '"';
} else {
$dd .='0' . $seconds . '"';
}
return $dd;
}
public function arSummaryLoadExtra()
{
$extra_words=file($this->rootDirectory . '/data/ar_stopwords_extra.txt');
$extra_words=array_map('trim', $extra_words);
$this->arSummaryCommonWords=array_merge($this->arSummaryCommonWords, $extra_words);
}
public function arSummary($str, $keywords, $int, $mode, $output, $style=null)
{
preg_match_all("/[^\.\n\،\؛\,\;](.+?)[\.\n\،\؛\,\;]/u", $str, $sentences);
$_sentences=$sentences[0];
if ($mode==1) {
$str=preg_replace("/\s{2,}/u", ' ', $str);
$totalChars=mb_strlen($str);
$totalSentences=count($_sentences);
$maxChars=round($int * $totalChars / 100);
$int=round($int * $totalSentences / 100);
} else {
$maxChars=99999;
}
$summary='';
$str=strip_tags($str);
$normalizedStr=$this->arNormalize($str);
$cleanedStr=$this->arCleanCommon($normalizedStr);
$stemStr=$this->arDraftStem($cleanedStr);
preg_match_all("/[^\.\n\،\؛\,\;](.+?)[\.\n\،\؛\,\;]/u", $stemStr, $sentences);
$_stemmedSentences=$sentences[0];
$wordRanks=$this->arSummaryRankWords($stemStr);
if ($keywords) {
$keywords=$this->arNormalize($keywords);
$keywords=$this->arDraftStem($keywords);
$words=explode(' ', $keywords);
foreach ($words as $word) {
$wordRanks[$word]=1000;
}
}
$sentencesRanks=$this->arSummaryRankSentences($_sentences, $_stemmedSentences, $wordRanks);
list($sentences, $ranks)=$sentencesRanks;
$minRank=$this->arSummaryMinAcceptedRank($sentences, $ranks, $int, $maxChars);
$totalSentences=count($ranks);
for ($i=0; $i < $totalSentences; $i++) {
if ($sentencesRanks[1][$i] >=$minRank) {
if ($output==1) {
$summary .=' ' . $sentencesRanks[0][$i];
} else {
$summary .='<span class="' . $style . '">' . $sentencesRanks[0][$i] . '</span>';
}
} else {
if ($output==2) {
$summary .=$sentencesRanks[0][$i];
}
}
}
if ($output==2) {
$summary=str_replace("\n", '<br />', $summary);
}
return $summary;
}
public function arSummaryKeywords($str, $int)
{
$patterns=array();
$replacements=array();
$metaKeywords='';
$patterns[]='/\.|\n|\،|\؛|\(|\[|\{|\)|\]|\}|\,|\;/u';
$replacements[]=' ';
$str=preg_replace($patterns, $replacements, $str);
$normalizedStr=$this->arNormalize($str);
$cleanedStr=$this->arCleanCommon($normalizedStr);
$str=preg_replace('/(\W)ال(\w{3,})/u', '\\1\\2', $cleanedStr);
$str=preg_replace('/(\W)وال(\w{3,})/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})هما(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})كما(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})تين(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})هم(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})هن(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})ها(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})نا(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})ني(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})كم(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})تم(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})كن(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})ات(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})ين(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})تن(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})ون(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})ان(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})تا(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})وا(\W)/u', '\\1\\2', $str);
$str=preg_replace('/(\w{3,})ة(\W)/u', '\\1\\2', $str);
$stemStr=preg_replace('/(\W)\w{1,3}(\W)/u', '\\2', $str);
$wordRanks=$this->arSummaryRankWords($stemStr);
arsort($wordRanks, SORT_NUMERIC);
$i=1;
foreach ($wordRanks as $key=> $value) {
if ($this->arSummaryAcceptedWord($key)) {
$metaKeywords .=$key . '، ';
$i++;
}
if ($i > $int) {
break;
}
}
$metaKeywords=mb_substr($metaKeywords, 0, -2);
return $metaKeywords;
}
private function arNormalize($str)
{
$str=str_replace($this->arNormalizeAlef, 'ا', $str);
$str=str_replace($this->arNormalizeDiacritics, '', $str);
$str=strtr($str, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
return $str;
}
private function arCleanCommon($str)
{
$str=str_replace($this->arSummaryCommonWords, ' ', $str);
return $str;
}
private function arDraftStem($str)
{
$str=str_replace($this->arCommonChars, '', $str);
return $str;
}
private function arSummaryRankWords($str)
{
$wordsRanks=array();
$str=str_replace($this->arSeparators, ' ', $str);
$words=preg_split("/[\s,]+/u", $str);
foreach ($words as $word) {
if (isset($wordsRanks[$word])) {
$wordsRanks[$word]++;
} else {
$wordsRanks[$word]=1;
}
}
return $wordsRanks;
}
private function arSummaryRankSentences($sentences, $stemmedSentences, $arr)
{
$sentenceArr=array();
$rankArr=array();
$importent=implode('|', $this->arSummaryImportantWords);
$max=count($sentences);
for ($i=0; $i < $max; $i++) {
$sentence=$sentences[$i];
$w=0;
$first=mb_substr($sentence, 0, 1);
$last=mb_substr($sentence, -1, 1);
if ($first=="\n") {
$w +=3;
} elseif (in_array($first, $this->arSeparators, true)) {
$w +=2;
} else {
$w +=1;
}
if ($last=="\n") {
$w +=3;
} elseif (in_array($last, $this->arSeparators, true)) {
$w +=2;
} else {
$w +=1;
}
preg_match_all('/(' . $importent . ')/', $sentence, $out);
$w +=count($out[0]);
$_sentence=mb_substr($sentence, 0, -1);
$sentence=mb_substr($_sentence, 1, mb_strlen($_sentence));
if (!in_array($first, $this->arSeparators, true)) {
$sentence=$first . $sentence;
}
$stemStr=$stemmedSentences[$i];
$stemStr=mb_substr($stemStr, 0, -1);
$words=preg_split("/[\s,]+/u", $stemStr);
$totalWords=count($words);
if ($totalWords > 4) {
$totalWordsRank=0;
foreach ($words as $word) {
if (isset($arr[$word])) {
$totalWordsRank +=$arr[$word];
}
}
$wordsRank=$totalWordsRank / $totalWords;
$sentenceRanks=$w * $wordsRank;
$sentenceArr[]=$sentence . $last;
$rankArr[]=$sentenceRanks;
}
}
$sentencesRanks=array($sentenceArr, $rankArr);
return $sentencesRanks;
}
private function arSummaryMinAcceptedRank($str, $arr, $int, $max)
{
$len=array();
foreach ($str as $line) {
$len[]=mb_strlen($line);
}
rsort($arr, SORT_NUMERIC);
$totalChars=0;
$minRank=0;
for ($i=0; $i <=$int; $i++) {
if (!isset($arr[$i])) {
$minRank=0;
break;
}
$totalChars +=$len[$i];
if ($totalChars >=$max) {
$minRank=$arr[$i];
break;
}
$minRank=$arr[$i];
}
return $minRank;
}
private function arSummaryAcceptedWord($word)
{
$accept=true;
if (mb_strlen($word) < 3) {
$accept=false;
}
return $accept;
}
public function arIdentify($str)
{
$minAr=55436;
$maxAr=55698;
$probAr=false;
$arFlag=false;
$arRef=array();
$max=strlen($str);
$ascii=unpack('C*', $str);
$i=-1;
while (++$i < $max) {
$cDec=$ascii[$i + 1];
if ($cDec >=33 && $cDec <=58) {
continue;
}
if (!$probAr && ($cDec==216 || $cDec==217)) {
$probAr=true;
continue;
}
if ($i > 0) {
$pDec=$ascii[$i];
} else {
$pDec=null;
}
if ($probAr) {
$utfDecCode=($pDec << 8) + $cDec;
if ($utfDecCode >=$minAr && $utfDecCode <=$maxAr) {
if (!$arFlag) {
$arFlag=true;
$sp=strlen(rtrim(substr($str, 0, $i - 1))) - 1;
if ($str[$sp]=='(') {
$arRef[]=$sp;
} else {
$arRef[]=$i - 1;
}
}
} else {
if ($arFlag) {
$arFlag=false;
$arRef[]=$i - 1;
}
}
$probAr=false;
continue;
}
if ($arFlag && !preg_match("/^\s$/", $str[$i])) {
$arFlag=false;
$sp=$i - strlen(rtrim(substr($str, 0, $i)));
$arRef[]=$i - $sp;
}
}
if ($arFlag) {
$arRef[]=$i;
}
return $arRef;
}
public function isArabic($str)
{
$val=false;
$arr=$this->arIdentify($str);
if (count($arr)==2 && $arr[0]==0 && $arr[1]==strlen($str)) {
$val=true;
}
return $val;
}
public function dd2olc($latitude, $longitude, $codeLength=10)
{
$codeLength=$codeLength / 2;
$validChars='23456789CFGHJMPQRVWX';
$latitude=$latitude + 90;
$longitude=$longitude + 180;
$latitude=round($latitude * pow(20, $codeLength - 2), 0);
$longitude=round($longitude * pow(20, $codeLength - 2), 0);
$olc='';
for ($i=1; $i <=$codeLength; $i++) {
$x=$longitude % 20;
$y=$latitude % 20;
$longitude=floor($longitude / 20);
$latitude=floor($latitude / 20);
$olc=substr($validChars, $y, 1) . substr($validChars, $x, 1) . $olc;
if ($i==1) {
$olc='+' . $olc;
}
}
return $olc;
}
public function olc2dd($olc, $codeLength=10)
{
$coordinates=array();
if ($this->volc($olc, $codeLength)) {
$codeLength=$codeLength / 2;
$validChars='23456789CFGHJMPQRVWX';
$olc=strtoupper(str_replace('+', '', $olc));
$latitude=0;
$longitude=0;
for ($i=1; $i <=$codeLength; $i++) {
$latitude=$latitude + strpos($validChars, substr($olc, 2 * $i - 2, 1)) * pow(20, 2 - $i);
$longitude=$longitude + strpos($validChars, substr($olc, 2 * $i - 1, 1)) * pow(20, 2 - $i);
}
$coordinates[]=$latitude - 90;
$coordinates[]=$longitude - 180;
} else {
$coordinates[]=null;
$coordinates[]=null;
}
return $coordinates;
}
public function volc($olc, $codeLength=10)
{
if (strlen($olc) !=$codeLength + 1) {
$isValid=false;
} elseif (substr($olc, -3, 1) !='+') {
$isValid=false;
} elseif (preg_match('/[^2-9CFGHJMPQRVWX+]/', strtoupper($olc))) {
$isValid=false;
} else {
$isValid=true;
}
return $isValid;
}
public function arPlural($singular, $count, $plural2=null, $plural3=null, $plural4=null)
{
if ($count==0) {
$plural=is_null($plural2) ? $this->arPluralsForms[$singular][0] : "لا $plural3";
} elseif ($count==1) {
$plural=is_null($plural2) ? $this->arPluralsForms[$singular][1] : "$singular واحد";
} elseif ($count==2) {
$plural=is_null($plural2) ? $this->arPluralsForms[$singular][2] : $plural2;
} elseif ($count % 100 >=3 && $count % 100 <=10) {
$plural=is_null($plural2) ? $this->arPluralsForms[$singular][3] : "%d $plural3";
} elseif ($count % 100 >=11) {
$plural=is_null($plural2) ? $this->arPluralsForms[$singular][4] : "%d $plural4";
} else {
$plural=is_null($plural2) ? $this->arPluralsForms[$singular][5] : "%d $singular";
}
return $plural;
}
public function stripHarakat($text, $tatweel=true, $tanwen=true, $shadda=true, $last=true, $harakat=true)
{
$lastHarakat=array('/َ(\s)/u', '/ُ(\s)/u', '/ِ(\s)/u', '/ْ(\s)/u', '/[َُِْ]$/u');
$bodyHarakat=array('/َ(\S)/u', '/ُ(\S)/u', '/ِ(\S)/u', '/ْ(\S)/u');
$allTanwen=array('ً', 'ٍ', 'ٌ');
if ($harakat) {
$text=preg_replace($bodyHarakat, '\\1', $text);
}
if ($last) {
$text=preg_replace($lastHarakat, '\\1', $text);
}
if ($tatweel) {
$text=str_replace('ـ', '', $text);
}
if ($tanwen) {
$text=str_replace($allTanwen, '', $text);
}
if ($shadda) {
$text=str_replace('ّ', '', $text);
}
return $text;
}
public function arSentiment($text)
{
# remove mentions
$text=preg_replace('/@\\S+/u', '', $text);
# remove hashtags
$text=preg_replace('/#\\S+/u', '', $text);
# normalise Alef
$text=preg_replace('/[أإآى]/u', 'ا', $text);
# normalise Hamza
$text=preg_replace('/[ؤئء]/u', 'ء', $text);
# replace taa marbouta by taa maftouha
$text=preg_replace('/ة/u', 'ه', $text);
# filter only Arabic text (white list)
$text=preg_replace('/[^ ءابتثجحخدذرزسشصضطظعغفقكلمنهوي]+/u', ' ', $text);
# exclude one letter words
$text=preg_replace('/\\b\\S{1}\\b/u', ' ', $text);
# remove extra spaces
$text=preg_replace('/\\s{2,}/u', ' ', $text);
$text=preg_replace('/^\\s+/u', '', $text);
$text=preg_replace('/\\s+$/u', '', $text);
# split string to words
$words=preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
# set initial scores
$positiveScore=0;
$negativeScore=0;
# add a simple rule-based mechanism to handle the negation words
$negationWords=array('لا', 'ليس', 'غير', 'ما', 'لم', 'لن',
'لست', 'ليست', 'ليسا', 'ليستا', 'لستما',
'لسنا', 'لستم', 'ليسوا', 'لسن', 'لستن');
$negationFlag=false;
# for each word
foreach ($words as $word) {
# split word to letters
$letters=preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
$stems=array();
$n=count($letters);
# get all possible 2 letters stems of current word
for ($i=0; $i < $n - 1; $i++) {
for ($j=$i + 1; $j < $n; $j++) {
# get stem key
$stems[]=array_search($letters[$i] . $letters[$j], $this->allStems);
}
}
$log_odds=array();
# get log odd for all word stems
foreach ($stems as $key) {
$log_odds[]=$this->logOddStem[$key];
}
# select the most probable stem for current word
$sel_stem=$stems[array_search(min($log_odds), $log_odds)];
if ($negationFlag) {
$positiveScore +=-1 * $this->logOddPositive[$sel_stem];
$negativeScore +=-1 * $this->logOddNegative[$sel_stem];
$negationFlag=false;
} else {
# retrive the positive and negative log odd scores and accumulate them
$positiveScore +=$this->logOddPositive[$sel_stem];
$negativeScore +=$this->logOddNegative[$sel_stem];
}
if (in_array($word, $negationWords)) {
$negationFlag=true;
}
}
# claculate the sentiment score
$sentiment=$positiveScore - $negativeScore;
if ($positiveScore > $negativeScore) {
$isPositive=true;
$probability=exp($positiveScore) / (1 + exp($positiveScore));
} else {
$isPositive=false;
$probability=exp($negativeScore) / (1 + exp($negativeScore));
}
return array('isPositive'=> $isPositive, 'probability'=> $probability);
}
public function noDots($text)
{
$text=preg_replace('/ن(\b)/u', 'ں$1', $text);
$text=preg_replace('/[بتثن]/u', 'ٮ', $text);
$text=preg_replace('/ي/u', 'ى', $text);
$text=preg_replace('/ف/u', 'ڡ', $text);
$text=preg_replace('/ق/u', 'ٯ', $text);
$text=preg_replace('/ك(\b)/u', 'ک$1', $text);
$text=preg_replace('/ش/u', 'س', $text);
$text=preg_replace('/غ/u', 'ع', $text);
$text=preg_replace('/ذ/u', 'د', $text);
$text=preg_replace('/ز/u', 'ر', $text);
$text=preg_replace('/ض/u', 'ص', $text);
$text=preg_replace('/ظ/u', 'ط', $text);
$text=preg_replace('/ة/u', 'ه', $text);
$text=preg_replace('/[جخ]/u', 'ح', $text);
$text=preg_replace('/[أإآ]/u', 'ا', $text);
$text=preg_replace('/ؤ/u', 'و', $text);
$text=preg_replace('/ئ/u', 'ى', $text);
return $text;
}
}
{
  "@file": "Arabizi",
  "transliteration": [
    {
      "id": "ا",
      "text": "a"
    },
    {
      "id": "ب",
      "text": "b"
    },
    {
      "id": "ت",
      "text": "t"
    },
    {
      "id": "ث",
      "text": "t'"
    },
    {
      "id": "ث",
      "text": "th"
    },
    {
      "id": "ج",
      "text": "j"
    },
    {
      "id": "ح",
      "text": "7"
    },
    {
      "id": "خ",
      "text": "5"
    },
    {
      "id": "خ",
      "text": "7'"
    },
    {
      "id": "خ",
      "text": "kh"
    },
    {
      "id": "د",
      "text": "d"
    },
    {
      "id": "ذ",
      "text": "d'"
    },
    {
      "id": "ر",
      "text": "r"
    },
    {
      "id": "ز",
      "text": "z"
    },
    {
      "id": "س",
      "text": "s"
    },
    {
      "id": "ش",
      "text": "s^"
    },
    {
      "id": "ش",
      "text": "ch"
    },
    {
      "id": "ش",
      "text": "$"
    },
    {
      "id": "ش",
      "text": "sh"
    },
    {
      "id": "ص",
      "text": "S"
    },
    {
      "id": "ص",
      "text": "9"
    },
    {
      "id": "ض",
      "text": "D"
    },
    {
      "id": "ض",
      "text": "9'"
    },
    {
      "id": "ط",
      "text": "6"
    },
    {
      "id": "ظ",
      "text": "Z"
    },
    {
      "id": "ظ",
      "text": "6'"
    },
    {
      "id": "ع",
      "text": "3"
    },
    {
      "id": "غ",
      "text": "3'"
    },
    {
      "id": "غ",
      "text": "gh"
    },
    {
      "id": "ف",
      "text": "f"
    },
    {
      "id": "ق",
      "text": "8"
    },
    {
      "id": "ق",
      "text": "q"
    },
    {
      "id": "ك",
      "text": "k"
    },
    {
      "id": "ل",
      "text": "l"
    },
    {
      "id": "م",
      "text": "m"
    },
    {
      "id": "ن",
      "text": "n"
    },
    {
      "id": "ه",
      "text": "h"
    },
    {
      "id": "و",
      "text": "w"
    },
    {
      "id": "ي",
      "text": "i"
    }
  ]
}<?xml version="1.0" encoding="utf-8" ?>
<countries>
  <country>
    <name>
      <english>Algeria</english>
      <arabic>الجزائر</arabic>
    </name>
    <longname>
      <english>People’s Democratic Republic of Algeria</english>
      <arabic>الجمهورية الجزائرية الديمقراطية الشعبية</arabic>
    </longname>
    <capital>
      <english>Algiers</english>
      <arabic>الجزائر</arabic>
      <latitude>36.77</latitude>
      <longitude>3.04</longitude>
	  <elevation>131</elevation>
    </capital>
    <iso3166>
      <a2>DZ</a2>
      <a3>DZA</a3>
      <number>012</number>
    </iso3166>
    <timezone>+1</timezone>
    <summertime used="false"></summertime>
    <dialcode>213</dialcode>
    <currency>
      <iso>DZD</iso>
      <arabic>دينار جزائري</arabic>
      <english>Algerian dinar</english>
      <money>
        <arabic>
          <basic>دينار</basic>
          <fraction>فلس</fraction>
        </arabic>
        <english>
          <basic>Dinar</basic>
          <fraction>Fils</fraction>
        </english>
        <decimals>3</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Bahrain</english>
      <arabic>البحرين</arabic>
    </name>
    <longname>
      <english>Kingdom of Bahrain</english>
      <arabic>مملكة البحرين</arabic>
    </longname>
    <capital>
      <english>Manama</english>
      <arabic>المنامة</arabic>
      <latitude>26.21</latitude>
      <longitude>50.58</longitude>
	  <elevation>9</elevation>
    </capital>
    <iso3166>
      <a2>BH</a2>
      <a3>BHR</a3>
      <number>048</number>
    </iso3166>
    <timezone>+3</timezone>
    <summertime used="false"></summertime>
    <dialcode>973</dialcode>
    <currency>
      <iso>BHD</iso>
      <arabic>دينار بحريني</arabic>
      <english>Bahraini dinar</english>
      <money>
        <arabic>
          <basic>دينار</basic>
          <fraction>فلس</fraction>
        </arabic>
        <english>
          <basic>Dinar</basic>
          <fraction>Fils</fraction>
        </english>
        <decimals>3</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Djibouti</english>
      <arabic>جيبوتي</arabic>
    </name>
    <longname>
      <english>Republic of Djibouti</english>
      <arabic>جمهورية جيبوتي</arabic>
    </longname>
    <capital>
      <english>Djibouti</english>
      <arabic>جيبوتي</arabic>
      <latitude>11.59</latitude>
      <longitude>43.15</longitude>
	  <elevation>7</elevation>
    </capital>
    <iso3166>
      <a2>DJ</a2>
      <a3>DJI</a3>
      <number>262</number>
    </iso3166>
    <timezone>+3</timezone>
    <summertime used="false"></summertime>
    <dialcode>253</dialcode>
    <currency>
      <iso>DJF</iso>
      <arabic>فرنك جيبوتي</arabic>
      <english>Djiboutian franc</english>
      <money>
        <arabic>
          <basic>فرنك</basic>
          <fraction>سنتيم</fraction>
        </arabic>
        <english>
          <basic>Franc</basic>
          <fraction>Centime</fraction>
        </english>
        <decimals>2</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Egypt</english>
      <arabic>مصر</arabic>
    </name>
    <longname>
      <english>Arab Republic of Egypt</english>
      <arabic>جمهورية مصر العربية</arabic>
    </longname>
    <capital>
      <english>Cairo</english>
      <arabic>القاهرة</arabic>
      <latitude>30.06</latitude>
      <longitude>31.25</longitude>
	  <elevation>26</elevation>
    </capital>
    <iso3166>
      <a2>EG</a2>
      <a3>EGY</a3>
      <number>818</number>
    </iso3166>
    <timezone>+2</timezone>
    <summertime used="true">
      <start>Last Friday of April</start>
      <end>Last Friday of September</end>
    </summertime>
    <dialcode>20</dialcode>
    <currency>
      <iso>EGP</iso>
      <arabic>جنيه مصري</arabic>
      <english>Egyptian pound</english>
      <money>
        <arabic>
          <basic>جنيه</basic>
          <fraction>قرش</fraction>
        </arabic>
        <english>
          <basic>Pound</basic>
          <fraction>Piastre</fraction>
        </english>
        <decimals>2</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Iraq</english>
      <arabic>العراق</arabic>
    </name>
    <longname>
      <english>Republic of Iraq</english>
      <arabic>جمهورية العـراق</arabic>
    </longname>
    <capital>
      <english>Bagdād</english>
      <arabic>بغداد</arabic>
      <latitude>33.33</latitude>
      <longitude>44.44</longitude>
	  <elevation>46</elevation>
    </capital>
    <iso3166>
      <a2>IQ</a2>
      <a3>IRQ</a3>
      <number>368</number>
    </iso3166>
    <timezone>+3</timezone>
    <summertime used="true">
      <start>1 April</start>
      <end>1 October</end>
    </summertime>
    <dialcode>964</dialcode>
    <currency>
      <iso>IQD</iso>
      <arabic>دينار عراقي</arabic>
      <english>Iraqi dinar</english>
      <money>
        <arabic>
          <basic>دينار</basic>
          <fraction>فلس</fraction>
        </arabic>
        <english>
          <basic>Dinar</basic>
          <fraction>Fils</fraction>
        </english>
        <decimals>3</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Jordan</english>
      <arabic>الأردن</arabic>
    </name>
    <longname>
      <english>Hashemite Kingdom of Jordan</english>
      <arabic>المملكة الأردنية الهاشمية</arabic>
    </longname>
    <capital>
      <english>Ammān</english>
      <arabic>عمان</arabic>
      <latitude>31.95</latitude>
      <longitude>35.93</longitude>
	  <elevation>1100</elevation>
    </capital>
    <iso3166>
      <a2>JO</a2>
      <a3>JOR</a3>
      <number>400</number>
    </iso3166>
    <timezone>+2</timezone>
    <summertime used="true">
      <start>Last Thursday of March</start>
      <end>30 September</end>
    </summertime>
    <dialcode>962</dialcode>
    <currency>
      <iso>JOD</iso>
      <arabic>دينار أردني</arabic>
      <english>Jordanian dinar</english>
      <money>
        <arabic>
          <basic>دينار</basic>
          <fraction>فلس</fraction>
        </arabic>
        <english>
          <basic>Dinar</basic>
          <fraction>Fils</fraction>
        </english>
        <decimals>3</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Kuwait</english>
      <arabic>الكويت</arabic>
    </name>
    <longname>
      <english>State of Kuwait</english>
      <arabic>دولة الكويت</arabic>
    </longname>
    <capital>
      <english>Al-Kuwayt</english>
      <arabic>الكويت</arabic>
      <latitude>29.38</latitude>
      <longitude>47.99</longitude>
	  <elevation>15</elevation>
    </capital>
    <iso3166>
      <a2>KW</a2>
      <a3>KWT</a3>
      <number>414</number>
    </iso3166>
    <timezone>+3</timezone>
    <summertime used="false"></summertime>
    <dialcode>965</dialcode>
    <currency>
      <iso>KWD</iso>
      <arabic>دينار كويتي</arabic>
      <english>Kuwaiti dinar</english>
      <money>
        <arabic>
          <basic>دينار</basic>
          <fraction>فلس</fraction>
        </arabic>
        <english>
          <basic>Dinar</basic>
          <fraction>Fils</fraction>
        </english>
        <decimals>3</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Lebanon</english>
      <arabic>لبنان</arabic>
    </name>
    <longname>
      <english>Lebanese Republic</english>
      <arabic>الجمهورية اللبنانية</arabic>
    </longname>
    <capital>
      <english>Bayrūt</english>
      <arabic>بيروت</arabic>
      <latitude>33.89</latitude>
      <longitude>35.50</longitude>
	  <elevation>46</elevation>
    </capital>
    <iso3166>
      <a2>LB</a2>
      <a3>LBN</a3>
      <number>422</number>
    </iso3166>
    <timezone>+2</timezone>
    <summertime used="true">
      <start>Last Sunday of March</start>
      <end>Last Sunday of October</end>
    </summertime>
    <dialcode>961</dialcode>
    <currency>
      <iso>LBP</iso>
      <arabic>ليرة لبنانية</arabic>
      <english>Lebanese lira</english>
      <money>
        <arabic>
          <basic>ليرة</basic>
          <fraction>قرش</fraction>
        </arabic>
        <english>
          <basic>Pound</basic>
          <fraction>Piastre</fraction>
        </english>
        <decimals>2</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Libya</english>
      <arabic>ليبيا</arabic>
    </name>
    <longname>
      <english>Libya</english>
      <arabic>ليبيا</arabic>
    </longname>
    <capital>
      <english>Tripoli</english>
      <arabic>طرابلس</arabic>
      <latitude>32.87</latitude>
      <longitude>13.18</longitude>
	  <elevation>7</elevation>
    </capital>
    <iso3166>           
      <a2>LY</a2>
      <a3>LBY</a3>
      <number>434</number>
    </iso3166>
    <timezone>+2</timezone>
    <summertime used="false"></summertime>
    <dialcode>218</dialcode>
    <currency>
      <iso>LYD</iso>
      <arabic>دينار ليبي</arabic>
      <english>Libyan dinar</english>
      <money>
        <arabic>
          <basic>دينار</basic>
          <fraction>درهم</fraction>
        </arabic>
        <english>
          <basic>Dinar</basic>
          <fraction>Dirham</fraction>
        </english>
        <decimals>3</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Mauritania</english>
      <arabic>موريتانيا</arabic>
    </name>
    <longname>
      <english>Islamic Republic of Mauritania</english>
      <arabic>الجمهورية الإسلامية الموريتانية</arabic>
    </longname>
    <capital>
      <english>Nouakchott</english>
      <arabic>نواكشوط</arabic>
      <latitude>18.09</latitude>
      <longitude>-15.98</longitude>
	  <elevation>3</elevation>
    </capital>
    <iso3166>
      <a2>MR</a2>
      <a3>MRT</a3>
      <number>478</number>
    </iso3166>
    <timezone>0</timezone>
    <summertime used="false"></summertime>
    <dialcode>222</dialcode>
    <currency>
      <iso>MRO</iso>
      <arabic>أوقية موريتانية</arabic>
      <english>Mauritanian ouguiya</english>
      <money>
        <arabic>
          <basic>أوقية</basic>
          <fraction></fraction>
        </arabic>
        <english>
          <basic>Ouguiya</basic>
          <fraction></fraction>
        </english>
        <decimals>0</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Morocco</english>
      <arabic>المغرب</arabic>
    </name>
    <longname>
      <english>Kingdom of Morocco</english>
      <arabic>المملكة المغربية</arabic>
    </longname>
    <capital>
      <english>Rabat</english>
      <arabic>الرباط</arabic>
      <latitude>34.02</latitude>
      <longitude>-6.84</longitude>
	  <elevation>23</elevation>
    </capital>
    <iso3166>
      <a2>MA</a2>
      <a3>MAR</a3>
      <number>504</number>
    </iso3166>
    <timezone>0</timezone>
    <summertime used="true">
      <start>First Sunday of May</start>
      <end>27 September</end>
    </summertime>
    <dialcode>212</dialcode>
    <currency>
      <iso>MAD</iso>
      <arabic>درهم مغربي</arabic>
      <english>Moroccan dirham</english>
      <money>
        <arabic>
          <basic>درهم</basic>
          <fraction>سنتيم</fraction>
        </arabic>
        <english>
          <basic>Dirham</basic>
          <fraction>Centime</fraction>
        </english>
        <decimals>2</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Oman</english>
      <arabic>عمان</arabic>
    </name>
    <longname>
      <english>Sultanate of Oman</english>
      <arabic>سلطنة عمان</arabic>
    </longname>
    <capital>
      <english>Muscat</english>
      <arabic>مسقط</arabic>
      <latitude>23.61</latitude>
      <longitude>58.54</longitude>
	  <elevation>21</elevation>
    </capital>
    <iso3166>
      <a2>OM</a2>
      <a3>OMN</a3>
      <number>512</number>
    </iso3166>
    <timezone>+4</timezone>
    <summertime used="false"></summertime>
    <dialcode>968</dialcode>
    <currency>
      <iso>OMR</iso>
      <arabic>ريال عماني</arabic>
      <english>Omani rial</english>
      <money>
        <arabic>
          <basic>ريال</basic>
          <fraction>بايزاس</fraction>
        </arabic>
        <english>
          <basic>Rial</basic>
          <fraction>Baisa</fraction>
        </english>
        <decimals>3</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Palestine</english>
      <arabic>فلسطين</arabic>
    </name>
    <longname>
      <english>Palestinian National Authority</english>
      <arabic>السلطة الفلسطينية</arabic>
    </longname>
    <capital>
      <english>Jerusalem</english>
      <arabic>القدس</arabic>
      <latitude>31.78</latitude>
      <longitude>35.22</longitude>
	  <elevation>819</elevation>
    </capital>
    <iso3166>
      <a2>PS</a2>
      <a3>PSE</a3>
      <number>275</number>
    </iso3166>
    <timezone>+2</timezone>
    <summertime used="false"></summertime>
    <dialcode>970</dialcode>
    <currency>
      <iso>USD</iso>
      <arabic>دولار أمريكي</arabic>
      <english>US Dollar</english>
      <money>
        <arabic>
          <basic>دولار</basic>
          <fraction>سنت</fraction>
        </arabic>
        <english>
          <basic>Dollar</basic>
          <fraction>Cent</fraction>
        </english>
        <decimals>2</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Qatar</english>
      <arabic>قطر</arabic>
    </name>
    <longname>
      <english>State of Qatar</english>
      <arabic>دولة قطر</arabic>
    </longname>
    <capital>
      <english>Doha</english>
      <arabic>الدوحة</arabic>
      <latitude>25.30</latitude>
      <longitude>51.51</longitude>
	  <elevation>31</elevation>
    </capital>
    <iso3166>
      <a2>QA</a2>
      <a3>QAT</a3>
      <number>634</number>
    </iso3166>
    <timezone>+3</timezone>
    <summertime used="false"></summertime>
    <dialcode>974</dialcode>
    <currency>
      <iso>QAR</iso>
      <arabic>ريال قطري</arabic>
      <english>Qatari riyal</english>
      <money>
        <arabic>
          <basic>ريال</basic>
          <fraction>درهم</fraction>
        </arabic>
        <english>
          <basic>Riyal</basic>
          <fraction>Dirham</fraction>
        </english>
        <decimals>2</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Saudi Arabia</english>
      <arabic>السعودية</arabic>
    </name>
    <longname>
      <english>Kingdom of Saudi Arabia</english>
      <arabic>المملكة العربية السعودية</arabic>
    </longname>
    <capital>			
      <english>Riyadh</english>
      <arabic>الرياض</arabic>
      <latitude>24.65</latitude>
      <longitude>46.77</longitude>
	  <elevation>610</elevation>
    </capital>
    <iso3166>
      <a2>SA</a2>
      <a3>SAU</a3>
      <number>682</number>
    </iso3166>
    <timezone>+3</timezone>
    <summertime used="false"></summertime>
    <dialcode>966</dialcode>
    <currency>
      <iso>SAR</iso>
      <arabic>ريال سعودي</arabic>
      <english>Saudi riyal</english>
      <money>
        <arabic>
          <basic>ريال</basic>
          <fraction>هللة</fraction>
        </arabic>
        <english>
          <basic>Riyal</basic>
          <fraction>Halala</fraction>
        </english>
        <decimals>2</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Somalia</english>
      <arabic>الصومال</arabic>
    </name>
    <longname>
      <english>Somali Republic</english>
      <arabic>جمهورية الصومال</arabic>
    </longname>
    <capital>
      <english>Muqdisho</english>
      <arabic>مقديشو</arabic>
      <latitude>2.05</latitude>
      <longitude>45.33</longitude>
	  <elevation>57</elevation>
    </capital>
    <iso3166>
      <a2>SO</a2>
      <a3>SOM</a3>
      <number>706</number>
    </iso3166>
    <timezone>+3</timezone>
    <summertime used="false"></summertime>
    <dialcode>252</dialcode>
    <currency>
      <iso>SOS</iso>
      <arabic>شلن صومالي</arabic>
      <english>Somali shilling</english>
      <money>
        <arabic>
          <basic>شلن</basic>
          <fraction>سنتسيمي</fraction>
        </arabic>
        <english>
          <basic>Shilling</basic>
          <fraction>Cent</fraction>
        </english>
        <decimals>2</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Sudan</english>
      <arabic>السودان</arabic>
    </name>
    <longname>
      <english>Republic of the Sudan</english>
      <arabic>جمهورية السودان</arabic>
    </longname>
    <capital>
      <english>Khartoum</english>
      <arabic>الخرطوم</arabic>
      <latitude>15.58</latitude>
      <longitude>32.52</longitude>
	  <elevation>385</elevation>
    </capital>
    <iso3166>
      <a2>SD</a2>
      <a3>SDN</a3>
      <number>736</number>
    </iso3166>
    <timezone>+2</timezone>
    <summertime used="false"></summertime>
    <dialcode>249</dialcode>
    <currency>
      <iso>SDG</iso>
      <arabic>جنيه سوداني</arabic>
      <english>Sudanese pound</english>
      <money>
        <arabic>
          <basic>جنيه</basic>
          <fraction>قرش</fraction>
        </arabic>
        <english>
          <basic>Pound</basic>
          <fraction>Piastre</fraction>
        </english>
        <decimals>2</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Syria</english>
      <arabic>سوريا</arabic>
    </name>
    <longname>
      <english>Syrian Arab Republic</english>
      <arabic>الجمهورية العربية السورية</arabic>
    </longname>
    <capital>
      <english>Damascus</english>
      <arabic>دمشق</arabic>
      <latitude>33.50</latitude>
      <longitude>36.32</longitude>
	  <elevation>691</elevation>
    </capital>
    <iso3166>
      <a2>SY</a2>
      <a3>SYR</a3>
      <number>760</number>
    </iso3166>
    <timezone>+2</timezone>
    <summertime used="true">
      <start>1 April</start>
      <end>1 October</end>
    </summertime>
    <dialcode>963</dialcode>
    <currency>
      <iso>SYP</iso>
      <arabic>ليرة سورية</arabic>
      <english>Syrian pound</english>
      <money>
        <arabic>
          <basic>ليرة</basic>
          <fraction>قرش</fraction>
        </arabic>
        <english>
          <basic>Pound</basic>
          <fraction>Piastre</fraction>
        </english>
        <decimals>2</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Tunisia</english>
      <arabic>تونس</arabic>
    </name>
    <longname>
      <english>Republic of Tunisia</english>
      <arabic>الجمهورية التونسية</arabic>
    </longname>
    <capital>
      <english>Tunis</english>
      <arabic>تونس</arabic>
      <latitude>36.84</latitude>
      <longitude>10.22</longitude>
	  <elevation>8</elevation>
    </capital>
    <iso3166>
      <a2>TN</a2>
      <a3>TUN</a3>
      <number>788</number>
    </iso3166>
    <timezone>+1</timezone>
    <summertime used="false"></summertime>
    <dialcode>216</dialcode>
    <currency>
      <iso>TND</iso>
      <arabic>دينار تونسي</arabic>
      <english>Tunisian dinar</english>
      <money>
        <arabic>
          <basic>دينار</basic>
          <fraction>مليم</fraction>
        </arabic>
        <english>
          <basic>Dinar</basic>
          <fraction>Millime</fraction>
        </english>
        <decimals>3</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>UAE</english>
      <arabic>الإمارات</arabic>
    </name>
    <longname>
      <english>United Arab Emirates</english>
      <arabic>الإمارات العربية المتحدة</arabic>
    </longname>
    <capital>
      <english>Abu Dhabi</english>
      <arabic>أبو ظبى</arabic>
      <latitude>24.48</latitude>
      <longitude>54.37</longitude>
	  <elevation>150</elevation>
    </capital>
    <iso3166>
      <a2>AE</a2>
      <a3>ARE</a3>
      <number>784</number>
    </iso3166>
    <timezone>+4</timezone>
    <summertime used="false"></summertime>
    <dialcode>971</dialcode>
    <currency>
      <iso>AED</iso>
      <arabic>درهم إماراتي</arabic>
      <english>United Arab Emirates dirham</english>
      <money>
        <arabic>
          <basic>درهم</basic>
          <fraction>فلس</fraction>
        </arabic>
        <english>
          <basic>Dirham</basic>
          <fraction>Fils</fraction>
        </english>
        <decimals>2</decimals>
      </money>
    </currency>
  </country>
  <country>
    <name>
      <english>Yemen</english>
      <arabic>اليمن</arabic>
    </name>
    <longname>
      <english>Republic of Yemen</english>
      <arabic>الجمهورية اليمنية</arabic>
    </longname>
    <capital>
      <english>Şan'ā</english>
      <arabic>صنعاء</arabic>
      <latitude>15.38</latitude>
      <longitude>44.21</longitude>
	  <elevation>2972</elevation>
    </capital>
    <iso3166>           
      <a2>YE</a2>
      <a3>YEM</a3>
      <number>887</number>
    </iso3166>
    <timezone>+3</timezone>
    <summertime used="false"></summertime>
    <dialcode>967</dialcode>
    <currency>
      <iso>YER</iso>
      <arabic>ريال يمني</arabic>
      <english>Yemeni rial</english>
      <money>
        <arabic>
          <basic>ريال</basic>
          <fraction>فلس</fraction>
        </arabic>
        <english>
          <basic>Rial</basic>
          <fraction>Fils</fraction>
        </english>
        <decimals>2</decimals>
      </money>
    </currency>
  </country>
</countries>
{
  "@file": "ArDate",
  "ar_hj_month": [
    "محرم",
    "صفر",
    "ربيع الأول",
    "ربيع الثاني",
    "جمادى الأولى",
    "جمادى الثانية",
    "رجب",
    "شعبان",
    "رمضان",
    "شوال",
    "ذو القعدة",
    "ذو الحجة"
  ],
  "en_hj_month": [
    "Muharram",
    "Safar",
    "Rabi' I",
    "Rabi' II",
    "Jumada I",
    "Jumada II",
    "Rajab",
    "Sha'aban",
    "Ramadan",
    "Shawwal",
    "Dhu al-Qi'dah",
    "Dhu al-Hijjah"
  ],
  "ar_month": {
    "mode_1": [
      "",
      "",
      "",
      "",
      "",
      "",
      "",
      "",
      "",
      "",
      "",
      ""
    ],
    "mode_2": [
      "كانون ثاني",
      "شباط",
      "آذار",
      "نيسان",
      "أيار",
      "حزيران",
      "تموز",
      "آب",
      "أيلول",
      "تشرين أول",
      "تشرين ثاني",
      "كانون أول"
    ],
    "mode_3": [
      "يناير",
      "فبراير",
      "مارس",
      "أبريل",
      "مايو",
      "يونيو",
      "يوليو",
      "أغسطس",
      "سبتمبر",
      "أكتوبر",
      "نوفمبر",
      "ديسمبر"
    ],
    "mode_4": [
      "كانون ثاني/يناير",
      "شباط/فبراير",
      "آذار/مارس",
      "نيسان/أبريل",
      "أيار/مايو",
      "حزيران/يونيو",
      "تموز/يوليو",
      "آب/أغسطس",
      "أيلول/سبتمبر",
      "تشرين أول/أكتوبر",
      "تشرين ثاني/نوفمبر",
      "كانون أول/ديسمبر"
    ],
    "mode_5": [
      "أي النار",
      "النوار",
      "الربيع",
      "الطير",
      "الماء",
      "الصيف",
      "ناصر",
      "هانيبال",
      "الفاتح",
      "التمور",
      "الحرث",
      "الكانون"
    ],
    "mode_6": [
      "جانفي",
      "فيفري",
      "مارس",
      "أفريل",
      "ماي",
      "جوان",
      "جويلية",
      "أوت",
      "سبتمبر",
      "أكتوبر",
      "نوفمبر",
      "ديسمبر"
    ],
    "mode_7": [
      "يناير",
      "فبراير",
      "مارس",
      "أبريل",
      "ماي",
      "يونيو",
      "يوليوز",
      "غشت",
      "شتنبر",
      "أكتوبر",
      "نوفنبر",
      "دجنبر"
    ]
  },
  "en_month": {
    "mode_full": [
      "january",
      "february",
      "march",
      "april",
      "may",
      "june",
      "july",
      "august",
      "september",
      "october",
      "november",
      "december"
    ],
    "mode_short": [
      "jan",
      "feb",
      "mar",
      "apr",
      "may",
      "jun",
      "jul",
      "aug",
      "sep",
      "oct",
      "nov",
      "dec"
    ]
  },
  "ar_day": [
    "السبت",
    "الأحد",
    "الاثنين",
    "الثلاثاء",
    "الأربعاء",
    "الخميس",
    "الجمعة"
  ],
  "en_day": {
    "mode_full": [
      "saturday",
      "sunday",
      "monday",
      "tuesday",
      "wednesday",
      "thursday",
      "friday"
    ],
    "mode_short": [
      "sat",
      "sun",
      "mon",
      "tue",
      "wed",
      "thu",
      "fri"
    ]
  },
  "preg_replace_en2ar": [
    {
      "search": "am",
      "replace": "صباحاً"
    },
    {
      "search": "pm",
      "replace": "مساءً"
    },
    {
      "search": "st",
      "replace": ""
    },
    {
      "search": "nd",
      "replace": ""
    },
    {
      "search": "rd",
      "replace": ""
    },
    {
      "search": "th",
      "replace": ""
    }
  ]
}﻿
اريج
ازدهار
اسمهان
اسيل
افراح
الين
اماني
امل
اناس
بتول
بدور
بريهان
بنان
بوران
بيان
بيسان
تالين
تبسم
ترنيم
تسنيم
تغريد
جلنار
جمان
جنان
جواهر
جود
جوليا
جيهان
حنان
حنين
ختام
خلود
دارين
دعد
دلال
دلع
رباب
رحاب
رزان
رغد
رفيف
رند
رنيم
رنين
رهام
رهف
روان
روز
ريان
ريعان
ريم
ريهام
زمزم
زهور
زين
زينب
سابين
سحر
سعاد
سلاف
سماح
سماهر
سمر
سنابل
سندس
سهر
سهير
سوزان
سوسن
سيرين
شام
شهد
شهرزاد
شهيناز
شوق
شيرين
صابرين
صباح
صدف
ظلال
عبير
عفاف
عهد
عواطف
غدير
غزل
غصون
غفران
فاتن
فرح
فردوس
فريال
فلك
فوز
فيروز
قمر
لجين
لمار
لميس
لواحظ
لولو
ليال
لين
مادلين
مايا
محاسن
مرام
مرح
مريم
ملك
منار
منال
مناهل
مي
ميار
ميس
ميسم
ميسون
نادين
ناريمان
ناهد
نبال
نجاح
نرمين
نسرين
نسيم
نظلي
نغم
نهاد
نهاوند
نوال
نور
نوف
نيلي
هاجر
هديل
هنادي
هند
هيام
وجد
وداد
وسن
وصال
وعد
ياسمين
ياقوت{
  "@file": "KeySwap",
  "arabic": [
    {
      "id": "1",
      "text": "ذ"
    },
    {
      "id": "2",
      "text": "ض"
    },
    {
      "id": "3",
      "text": "ص"
    },
    {
      "id": "4",
      "text": "ث"
    },
    {
      "id": "5",
      "text": "ق"
    },
    {
      "id": "6",
      "text": "ف"
    },
    {
      "id": "7",
      "text": "غ"
    },
    {
      "id": "8",
      "text": "ع"
    },
    {
      "id": "9",
      "text": "ه"
    },
    {
      "id": "10",
      "text": "خ"
    },
    {
      "id": "11",
      "text": "ح"
    },
    {
      "id": "12",
      "text": "ج"
    },
    {
      "id": "13",
      "text": "د"
    },
    {
      "id": "14",
      "text": "ش"
    },
    {
      "id": "15",
      "text": "س"
    },
    {
      "id": "16",
      "text": "ي"
    },
    {
      "id": "17",
      "text": "ب"
    },
    {
      "id": "18",
      "text": "ل"
    },
    {
      "id": "19",
      "text": "ا"
    },
    {
      "id": "20",
      "text": "ت"
    },
    {
      "id": "21",
      "text": "ن"
    },
    {
      "id": "22",
      "text": "م"
    },
    {
      "id": "23",
      "text": "ك"
    },
    {
      "id": "24",
      "text": "ط"
    },
    {
      "id": "25",
      "text": "ئ"
    },
    {
      "id": "26",
      "text": "ء"
    },
    {
      "id": "27",
      "text": "ؤ"
    },
    {
      "id": "28",
      "text": "ر"
    },
    {
      "id": "29",
      "text": "لا"
    },
    {
      "id": "30",
      "text": "ى"
    },
    {
      "id": "31",
      "text": "ة"
    },
    {
      "id": "32",
      "text": "و"
    },
    {
      "id": "33",
      "text": "ز"
    },
    {
      "id": "34",
      "text": "ظ"
    },
    {
      "id": "35",
      "text": "ّ"
    },
    {
      "id": "36",
      "text": "َ"
    },
    {
      "id": "37",
      "text": "ً"
    },
    {
      "id": "38",
      "text": "ُ"
    },
    {
      "id": "39",
      "text": "ٌ"
    },
    {
      "id": "40",
      "text": "لإ"
    },
    {
      "id": "41",
      "text": "إ"
    },
    {
      "id": "42",
      "text": "‘"
    },
    {
      "id": "43",
      "text": "÷"
    },
    {
      "id": "44",
      "text": "×"
    },
    {
      "id": "45",
      "text": "؛"
    },
    {
      "id": "46",
      "text": "<"
    },
    {
      "id": "47",
      "text": ">"
    },
    {
      "id": "48",
      "text": "ِ"
    },
    {
      "id": "49",
      "text": "ٍ"
    },
    {
      "id": "50",
      "text": "]"
    },
    {
      "id": "51",
      "text": "["
    },
    {
      "id": "52",
      "text": "لأ"
    },
    {
      "id": "53",
      "text": "أ"
    },
    {
      "id": "54",
      "text": "ـ"
    },
    {
      "id": "55",
      "text": "،"
    },
    {
      "id": "56",
      "text": "/"
    },
    {
      "id": "57",
      "text": ":"
    },
    {
      "id": "58",
      "text": "\""
    },
    {
      "id": "59",
      "text": "~"
    },
    {
      "id": "60",
      "text": "ْ"
    },
    {
      "id": "61",
      "text": "}"
    },
    {
      "id": "62",
      "text": "{"
    },
    {
      "id": "63",
      "text": "لآ"
    },
    {
      "id": "64",
      "text": "آ"
    },
    {
      "id": "65",
      "text": "’"
    },
    {
      "id": "66",
      "text": ","
    },
    {
      "id": "67",
      "text": "."
    },
    {
      "id": "68",
      "text": "؟"
    }
  ],
  "english": [
    {
      "id": "1",
      "text": "`"
    },
    {
      "id": "2",
      "text": "q"
    },
    {
      "id": "3",
      "text": "w"
    },
    {
      "id": "4",
      "text": "e"
    },
    {
      "id": "5",
      "text": "r"
    },
    {
      "id": "6",
      "text": "t"
    },
    {
      "id": "7",
      "text": "y"
    },
    {
      "id": "8",
      "text": "u"
    },
    {
      "id": "9",
      "text": "i"
    },
    {
      "id": "10",
      "text": "o"
    },
    {
      "id": "11",
      "text": "p"
    },
    {
      "id": "12",
      "text": "["
    },
    {
      "id": "13",
      "text": "]"
    },
    {
      "id": "14",
      "text": "a"
    },
    {
      "id": "15",
      "text": "s"
    },
    {
      "id": "16",
      "text": "d"
    },
    {
      "id": "17",
      "text": "f"
    },
    {
      "id": "18",
      "text": "g"
    },
    {
      "id": "19",
      "text": "h"
    },
    {
      "id": "20",
      "text": "j"
    },
    {
      "id": "21",
      "text": "k"
    },
    {
      "id": "22",
      "text": "l"
    },
    {
      "id": "23",
      "text": ";"
    },
    {
      "id": "24",
      "text": "'"
    },
    {
      "id": "25",
      "text": "z"
    },
    {
      "id": "26",
      "text": "x"
    },
    {
      "id": "27",
      "text": "c"
    },
    {
      "id": "28",
      "text": "v"
    },
    {
      "id": "29",
      "text": "b"
    },
    {
      "id": "30",
      "text": "n"
    },
    {
      "id": "31",
      "text": "m"
    },
    {
      "id": "32",
      "text": ","
    },
    {
      "id": "33",
      "text": "."
    },
    {
      "id": "34",
      "text": "/"
    },
    {
      "id": "35",
      "text": "~"
    },
    {
      "id": "36",
      "text": "Q"
    },
    {
      "id": "37",
      "text": "W"
    },
    {
      "id": "38",
      "text": "E"
    },
    {
      "id": "39",
      "text": "R"
    },
    {
      "id": "40",
      "text": "T"
    },
    {
      "id": "41",
      "text": "Y"
    },
    {
      "id": "42",
      "text": "U"
    },
    {
      "id": "43",
      "text": "I"
    },
    {
      "id": "44",
      "text": "O"
    },
    {
      "id": "45",
      "text": "P"
    },
    {
      "id": "46",
      "text": "{"
    },
    {
      "id": "47",
      "text": "}"
    },
    {
      "id": "48",
      "text": "A"
    },
    {
      "id": "49",
      "text": "S"
    },
    {
      "id": "50",
      "text": "D"
    },
    {
      "id": "51",
      "text": "F"
    },
    {
      "id": "52",
      "text": "G"
    },
    {
      "id": "53",
      "text": "H"
    },
    {
      "id": "54",
      "text": "J"
    },
    {
      "id": "55",
      "text": "K"
    },
    {
      "id": "56",
      "text": "L"
    },
    {
      "id": "57",
      "text": ":"
    },
    {
      "id": "58",
      "text": "\""
    },
    {
      "id": "59",
      "text": "Z"
    },
    {
      "id": "60",
      "text": "X"
    },
    {
      "id": "61",
      "text": "C"
    },
    {
      "id": "62",
      "text": "V"
    },
    {
      "id": "63",
      "text": "B"
    },
    {
      "id": "64",
      "text": "N"
    },
    {
      "id": "65",
      "text": "M"
    },
    {
      "id": "66",
      "text": "<"
    },
    {
      "id": "67",
      "text": ">"
    },
    {
      "id": "68",
      "text": "?"
    }
  ],
  "french": [
    {
      "id": "1",
      "text": "²"
    },
    {
      "id": "2",
      "text": "a"
    },
    {
      "id": "3",
      "text": "z"
    },
    {
      "id": "4",
      "text": "e"
    },
    {
      "id": "5",
      "text": "r"
    },
    {
      "id": "6",
      "text": "t"
    },
    {
      "id": "7",
      "text": "y"
    },
    {
      "id": "8",
      "text": "u"
    },
    {
      "id": "9",
      "text": "i"
    },
    {
      "id": "10",
      "text": "o"
    },
    {
      "id": "11",
      "text": "p"
    },
    {
      "id": "12",
      "text": ""
    },
    {
      "id": "13",
      "text": "$"
    },
    {
      "id": "14",
      "text": "q"
    },
    {
      "id": "15",
      "text": "s"
    },
    {
      "id": "16",
      "text": "d"
    },
    {
      "id": "17",
      "text": "f"
    },
    {
      "id": "18",
      "text": "g"
    },
    {
      "id": "19",
      "text": "h"
    },
    {
      "id": "20",
      "text": "j"
    },
    {
      "id": "21",
      "text": "k"
    },
    {
      "id": "22",
      "text": "l"
    },
    {
      "id": "23",
      "text": "m"
    },
    {
      "id": "24",
      "text": "ù"
    },
    {
      "id": "25",
      "text": "w"
    },
    {
      "id": "26",
      "text": "x"
    },
    {
      "id": "27",
      "text": "c"
    },
    {
      "id": "28",
      "text": "v"
    },
    {
      "id": "29",
      "text": "b"
    },
    {
      "id": "30",
      "text": "n"
    },
    {
      "id": "31",
      "text": ","
    },
    {
      "id": "32",
      "text": ";"
    },
    {
      "id": "33",
      "text": ":"
    },
    {
      "id": "34",
      "text": "!"
    },
    {
      "id": "35",
      "text": ""
    },
    {
      "id": "36",
      "text": "A"
    },
    {
      "id": "37",
      "text": "Z"
    },
    {
      "id": "38",
      "text": "E"
    },
    {
      "id": "39",
      "text": "R"
    },
    {
      "id": "40",
      "text": "T"
    },
    {
      "id": "41",
      "text": "Y"
    },
    {
      "id": "42",
      "text": "U"
    },
    {
      "id": "43",
      "text": "I"
    },
    {
      "id": "44",
      "text": "O"
    },
    {
      "id": "45",
      "text": "P"
    },
    {
      "id": "46",
      "text": ""
    },
    {
      "id": "47",
      "text": "£"
    },
    {
      "id": "48",
      "text": "Q"
    },
    {
      "id": "49",
      "text": "S"
    },
    {
      "id": "50",
      "text": "D"
    },
    {
      "id": "51",
      "text": "F"
    },
    {
      "id": "52",
      "text": "G"
    },
    {
      "id": "53",
      "text": "H"
    },
    {
      "id": "54",
      "text": "J"
    },
    {
      "id": "55",
      "text": "K"
    },
    {
      "id": "56",
      "text": "L"
    },
    {
      "id": "57",
      "text": "M"
    },
    {
      "id": "58",
      "text": "%"
    },
    {
      "id": "59",
      "text": "W"
    },
    {
      "id": "60",
      "text": "X"
    },
    {
      "id": "61",
      "text": "C"
    },
    {
      "id": "62",
      "text": "V"
    },
    {
      "id": "63",
      "text": "B"
    },
    {
      "id": "64",
      "text": "N"
    },
    {
      "id": "65",
      "text": "?"
    },
    {
      "id": "66",
      "text": "."
    },
    {
      "id": "67",
      "text": "/"
    },
    {
      "id": "68",
      "text": "§"
    }
  ]
}{
  "@file": "ArNumbers",
  "currency": [
     {
       "iso": "DZD",
       "arabic": "دينار جزائري",
       "english": "Algerian dinar",
       "ar_basic": "دينار",
       "ar_fraction": "فلس",
       "en_basic": "Dinar",
       "en_fraction": "Fils",
       "decimals": 3
     },
     {
       "iso": "BHD",
       "arabic": "دينار بحريني",
       "english": "Bahraini dinar",
       "ar_basic": "دينار",
       "ar_fraction": "فلس",
       "en_basic": "Dinar",
       "en_fraction": "Fils",
       "decimals": 3
     },
     {
       "iso": "DJF",
       "arabic": "فرنك جيبوتي",
       "english": "Djiboutian franc",
       "ar_basic": "فرنك",
       "ar_fraction": "سنتيم",
       "en_basic": "Franc",
       "en_fraction": "Centime",
       "decimals": 2
     },
     {
       "iso": "EGP",
       "arabic": "جنيه مصري",
       "english": "Egyptian pound",
       "ar_basic": "جنيه",
       "ar_fraction": "قرش",
       "en_basic": "Pound",
       "en_fraction": "Piastre",
       "decimals": 2
     },
     {
       "iso": "IQD",
       "arabic": "دينار عراقي",
       "english": "Iraqi dinar",
       "ar_basic": "دينار",
       "ar_fraction": "فلس",
       "en_basic": "Dinar",
       "en_fraction": "Fils",
       "decimals": 3
     },
     {
       "iso": "JOD",
       "arabic": "دينار أردني",
       "english": "Jordanian dinar",
       "ar_basic": "دينار",
       "ar_fraction": "فلس",
       "en_basic": "Dinar",
       "en_fraction": "Fils",
       "decimals": 3
     },
     {
       "iso": "KWD",
       "arabic": "دينار كويتي",
       "english": "Kuwaiti dinar",
       "ar_basic": "دينار",
       "ar_fraction": "فلس",
       "en_basic": "Dinar",
       "en_fraction": "Fils",
       "decimals": 3
     },
     {
       "iso": "LBP",
       "arabic": "ليرة لبنانية",
       "english": "Lebanese lira",
       "ar_basic": "ليرة",
       "ar_fraction": "قرش",
       "en_basic": "Pound",
       "en_fraction": "Piastre",
       "decimals": 2
     },
     {
       "iso": "LYD",
       "arabic": "دينار ليبي",
       "english": "Libyan dinar",
       "ar_basic": "دينار",
       "ar_fraction": "درهم",
       "en_basic": "Dinar",
       "en_fraction": "Dirham",
       "decimals": 3
     },
     {
       "iso": "MRO",
       "arabic": "أوقية موريتانية",
       "english": "Mauritanian ouguiya",
       "ar_basic": "أوقية",
       "ar_fraction": "",
       "en_basic": "Ouguiya",
       "en_fraction": "",
       "decimals": 0
     },
     {
       "iso": "MAD",
       "arabic": "درهم مغربي",
       "english": "Moroccan dirham",
       "ar_basic": "درهم",
       "ar_fraction": "سنتيم",
       "en_basic": "Dirham",
       "en_fraction": "Centime",
       "decimals": 2
     },
     {
       "iso": "OMR",
       "arabic": "ريال عماني",
       "english": "Omani rial",
       "ar_basic": "ريال",
       "ar_fraction": "بايزاس",
       "en_basic": "Rial",
       "en_fraction": "Baisa",
       "decimals": 3
     },
     {
       "iso": "USD",
       "arabic": "دولار أمريكي",
       "english": "US Dollar",
       "ar_basic": "دولار",
       "ar_fraction": "سنت",
       "en_basic": "Dollar",
       "en_fraction": "Cent",
       "decimals": 2
     },
     {
       "iso": "QAR",
       "arabic": "ريال قطري",
       "english": "Qatari riyal",
       "ar_basic": "ريال",
       "ar_fraction": "درهم",
       "en_basic": "Riyal",
       "en_fraction": "Dirham",
       "decimals": 2
     },
     {
       "iso": "SAR",
       "arabic": "ريال سعودي",
       "english": "Saudi riyal",
       "ar_basic": "ريال",
       "ar_fraction": "هللة",
       "en_basic": "Riyal",
       "en_fraction": "Halala",
       "decimals": 2
     },
     {
       "iso": "SOS",
       "arabic": "شلن صومالي",
       "english": "Somali shilling",
       "ar_basic": "شلن",
       "ar_fraction": "سنتسيمي",
       "en_basic": "Shilling",
       "en_fraction": "Cent",
       "decimals": 2
     },
     {
       "iso": "SDG",
       "arabic": "جنيه سوداني",
       "english": "Sudanese pound",
       "ar_basic": "جنيه",
       "ar_fraction": "قرش",
       "en_basic": "Pound",
       "en_fraction": "Piastre",
       "decimals": 2
     },
     {
       "iso": "SYP",
       "arabic": "ليرة سورية",
       "english": "Syrian pound",
       "ar_basic": "ليرة",
       "ar_fraction": "قرش",
       "en_basic": "Pound",
       "en_fraction": "Piastre",
       "decimals": 2
     },
     {
       "iso": "TND",
       "arabic": "دينار تونسي",
       "english": "Tunisian dinar",
       "ar_basic": "دينار",
       "ar_fraction": "مليم",
       "en_basic": "Dinar",
       "en_fraction": "Millime",
       "decimals": 3
     },
     {
       "iso": "AED",
       "arabic": "درهم إماراتي",
       "english": "United Arab Emirates dirham",
       "ar_basic": "درهم",
       "ar_fraction": "فلس",
       "en_basic": "Dirham",
       "en_fraction": "Fils",
       "decimals": 2
     },
     {
       "iso": "YER",
       "arabic": "ريال يمني",
       "english": "Yemeni rial",
       "ar_basic": "ريال",
       "ar_fraction": "فلس",
       "en_basic": "Rial",
       "en_fraction": "Fils",
       "decimals": 2
     }
  ],
  "individual": {
    "male": [
      {
        "value": "1",
        "text": "واحد"
      },
      {
        "value": "2",
        "text": "اثنان",
        "grammar": "1"
      },
      {
        "value": "2",
        "text": "اثنين",
        "grammar": "2"
      },
      {
        "value": "3",
        "text": "ثلاثة"
      },
      {
        "value": "4",
        "text": "أربعة"
      },
      {
        "value": "5",
        "text": "خمسة"
      },
      {
        "value": "6",
        "text": "ستة"
      },
      {
        "value": "7",
        "text": "سبعة"
      },
      {
        "value": "8",
        "text": "ثمانية"
      },
      {
        "value": "9",
        "text": "تسعة"
      },
      {
        "value": "10",
        "text": "عشرة"
      },
      {
        "value": "11",
        "text": "أحد عشر"
      },
      {
        "value": "12",
        "text": "اثنا عشر",
        "grammar": "1"
      },
      {
        "value": "12",
        "text": "اثني عشر",
        "grammar": "2"
      },
      {
        "value": "13",
        "text": "ثلاثة عشر"
      },
      {
        "value": "14",
        "text": "أربعة عشر"
      },
      {
        "value": "15",
        "text": "خمسة عشر"
      },
      {
        "value": "16",
        "text": "ستة عشر"
      },
      {
        "value": "17",
        "text": "سبعة عشر"
      },
      {
        "value": "18",
        "text": "ثمانية عشر"
      },
      {
        "value": "19",
        "text": "تسعة عشر"
      }
    ],
    "female": [
      {
        "value": "1",
        "text": "واحدة"
      },
      {
        "value": "2",
        "text": "اثنتان",
        "grammar": "1"
      },
      {
        "value": "2",
        "text": "اثنتين",
        "grammar": "2"
      },
      {
        "value": "3",
        "text": "ثلاث"
      },
      {
        "value": "4",
        "text": "أربع"
      },
      {
        "value": "5",
        "text": "خمس"
      },
      {
        "value": "6",
        "text": "ست"
      },
      {
        "value": "7",
        "text": "سبع"
      },
      {
        "value": "8",
        "text": "ثماني"
      },
      {
        "value": "9",
        "text": "تسع"
      },
      {
        "value": "10",
        "text": "عشر"
      },
      {
        "value": "11",
        "text": "إحدى عشرة"
      },
      {
        "value": "12",
        "text": "اثنتا عشرة",
        "grammar": "1"
      },
      {
        "value": "12",
        "text": "اثنتي عشرة",
        "grammar": "2"
      },
      {
        "value": "13",
        "text": "ثلاث عشرة"
      },
      {
        "value": "14",
        "text": "أربع عشرة"
      },
      {
        "value": "15",
        "text": "خمس عشرة"
      },
      {
        "value": "16",
        "text": "ست عشرة"
      },
      {
        "value": "17",
        "text": "سبع عشرة"
      },
      {
        "value": "18",
        "text": "ثماني عشرة"
      },
      {
        "value": "19",
        "text": "تسع عشرة"
      }
    ],
    "gt19": [
      {
        "value": "300",
        "text": "ثلاثمئة"
      },
      {
        "value": "400",
        "text": "أربعمئة"
      },
      {
        "value": "500",
        "text": "خمسمئة"
      },
      {
        "value": "600",
        "text": "ستمئة"
      },
      {
        "value": "700",
        "text": "سبعمئة"
      },
      {
        "value": "800",
        "text": "ثمانمئة"
      },
      {
        "value": "900",
        "text": "تسعمئة"
      },
      {
        "value": "100",
        "text": "مئة"
      },
      {
        "value": "20",
        "text": "عشرون",
        "grammar": "1"
      },
      {
        "value": "30",
        "text": "ثلاثون",
        "grammar": "1"
      },
      {
        "value": "40",
        "text": "أربعون",
        "grammar": "1"
      },
      {
        "value": "50",
        "text": "خمسون",
        "grammar": "1"
      },
      {
        "value": "60",
        "text": "ستون",
        "grammar": "1"
      },
      {
        "value": "70",
        "text": "سبعون",
        "grammar": "1"
      },
      {
        "value": "80",
        "text": "ثمانون",
        "grammar": "1"
      },
      {
        "value": "90",
        "text": "تسعون",
        "grammar": "1"
      },
      {
        "value": "200",
        "text": "مئتان",
        "grammar": "1"
      },
      {
        "value": "20",
        "text": "عشرين",
        "grammar": "2"
      },
      {
        "value": "30",
        "text": "ثلاثين",
        "grammar": "2"
      },
      {
        "value": "40",
        "text": "أربعين",
        "grammar": "2"
      },
      {
        "value": "50",
        "text": "خمسين",
        "grammar": "2"
      },
      {
        "value": "60",
        "text": "ستين",
        "grammar": "2"
      },
      {
        "value": "70",
        "text": "سبعين",
        "grammar": "2"
      },
      {
        "value": "80",
        "text": "ثمانين",
        "grammar": "2"
      },
      {
        "value": "90",
        "text": "تسعين",
        "grammar": "2"
      },
      {
        "value": "200",
        "text": "مئتين",
        "grammar": "2"
      }
    ]
  },
  "complications": [
    {
      "scale": "1",
      "format": "1",
      "text": "ألفان"
    },
    {
      "scale": "1",
      "format": "2",
      "text": "ألفين"
    },
    {
      "scale": "1",
      "format": "3",
      "text": "آلاف"
    },
    {
      "scale": "1",
      "format": "4",
      "text": "ألف"
    },
    {
      "scale": "2",
      "format": "1",
      "text": "مليونان"
    },
    {
      "scale": "2",
      "format": "2",
      "text": "مليونين"
    },
    {
      "scale": "2",
      "format": "3",
      "text": "ملايين"
    },
    {
      "scale": "2",
      "format": "4",
      "text": "مليون"
    },
    {
      "scale": "3",
      "format": "1",
      "text": "ملياران"
    },
    {
      "scale": "3",
      "format": "2",
      "text": "مليارين"
    },
    {
      "scale": "3",
      "format": "3",
      "text": "مليارات"
    },
    {
      "scale": "3",
      "format": "4",
      "text": "مليار"
    },
    {
      "scale": "4",
      "format": "1",
      "text": "تريليونان"
    },
    {
      "scale": "4",
      "format": "2",
      "text": "تريليونين"
    },
    {
      "scale": "4",
      "format": "3",
      "text": "تريليونات"
    },
    {
      "scale": "4",
      "format": "4",
      "text": "تريليون"
    }
  ],
  "arabicIndic": [
    {
      "value": "0",
      "text": "&#1632;"
    },
    {
      "value": "1",
      "text": "&#1633;"
    },
    {
      "value": "2",
      "text": "&#1634;"
    },
    {
      "value": "3",
      "text": "&#1635;"
    },
    {
      "value": "4",
      "text": "&#1636;"
    },
    {
      "value": "5",
      "text": "&#1637;"
    },
    {
      "value": "6",
      "text": "&#1638;"
    },
    {
      "value": "7",
      "text": "&#1639;"
    },
    {
      "value": "8",
      "text": "&#1640;"
    },
    {
      "value": "9",
      "text": "&#1641;"
    }
  ],
  "order": {
    "male": [
      {
        "value": "1",
        "text": "حادي"
      },
      {
        "value": "2",
        "text": "ثاني"
      },
      {
        "value": "3",
        "text": "ثالث"
      },
      {
        "value": "4",
        "text": "رابع"
      },
      {
        "value": "5",
        "text": "خامس"
      },
      {
        "value": "6",
        "text": "سادس"
      },
      {
        "value": "7",
        "text": "سابع"
      },
      {
        "value": "8",
        "text": "ثامن"
      },
      {
        "value": "9",
        "text": "تاسع"
      },
      {
        "value": "10",
        "text": "عاشر"
      }
    ],
    "female": [
      {
        "value": "1",
        "text": "حادية"
      },
      {
        "value": "2",
        "text": "ثانية"
      },
      {
        "value": "3",
        "text": "ثالثة"
      },
      {
        "value": "4",
        "text": "رابعة"
      },
      {
        "value": "5",
        "text": "خامسة"
      },
      {
        "value": "6",
        "text": "سادسة"
      },
      {
        "value": "7",
        "text": "سابعة"
      },
      {
        "value": "8",
        "text": "ثامنة"
      },
      {
        "value": "9",
        "text": "تاسعة"
      },
      {
        "value": "10",
        "text": "عاشرة"
      }
    ]
  }
}
{
  "@file": "ArPlurals",
  "@formula": "nplurals=6; plural=n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5;",
  "arPluralsForms": {
    "عنصر": ["لا عناصر", "عنصر واحد", "عنصران", "%d عناصر", "%d عنصرا", "%d عنصر"],
    "تعليق": ["لا تعليقات", "تعليق واحد", "تعليقان", "%d تعليقات", "%d تعليقا", "%d تعليق"],
    "تقييم": ["لا تقييمات", "تقييم واحد", "تقييمان", "%d تقييمات", "%d تقييما", "%d تقييم"],
    "مشاهدة": ["لا مشاهدات", "مشاهدة واحدة", "مشاهدتان", "%d مشاهدات", "%d مشاهدة", "%d مشاهدة"],
    "نتيجة": ["لا نتائج", "نتيجة واحدة", "نتيجتان", "%d نتائج", "%d نتيجة", "%d نتيجة"],
    "مقالة": ["لا مقالات", "مقالة واحدة", "مقالتان", "%d مقالات", "%d مقالة", "%d مقالة"],
    "إجابة": ["لا إجابات", "إجابة واحدة", "إجابتان", "%d إجابات", "%d إجابة", "%d إجابة"],
    "خبر": ["لا أخبار", "خبر واحد", "خبران", "%d أخبار", "%d خبرا", "%d خبر"],
    "دولار": ["لا دولارات", "دولار واحد", "دولارات", "%d دولارات", "%d دولارا", "%d دولار"],
    "فرنك": ["لا فرنكات", "فرنك واحد", "فرنكان", "%d فرنكات", "%d فرنكا", "%d فرنك"],
    "سنتيم": ["لا سنتيمات", "سنتيم واحد", "سنتيمان", "%d سنتيمات", "%d سنتيما", "%d سنتيم"],
    "جنيه": ["لا جنيهات", "جنيه واحد", "جنيهان", "%d جنيهات", "%d جنيها", "%d جنيه"],
    "ليرة": ["لا ليرات", "ليرة واحدة", "ليرتان", "%d ليرات", "%d ليرة", "%d ليرة"],
    "أوقية": ["لا أوقيات", "أوقية واحدة", "أوقيتان", "%d أوقيات", "%d أوقية", "%d أوقية"],
    "دينار": ["لا دنانير", "دينار واحد", "ديناران", "%d دنانير", "%d دينارا", "%d دينار"],
    "فلس": ["لا فلوس", "فلس واحد", "فلسان", "%d فلوس", "%d فلسا", "%d فلس"],
    "قرش": ["لا قروش", "قرش واحد", "قرشان", "%d قروش", "%d قرشا", "%d قرش"],
    "درهم": ["لا دراهم", "درهم واحد", "درهمان", "%d دراهم", "%d درهما", "%d درهم"],
    "ريال": ["لا ريالات", "ريال واحد", "ريالان", "%d ريالات", "%d ريالا", "%d ريال"],
    "سنت": ["لا سنتات", "سنت واحد", "سنتان", "%d سنتات", "%d سنتا", "%d سنت"],
    "هللة": ["لا هللات", "هللة واحدة", "هللتان", "%d هللات", "%d هللة", "%d هللة"],
    "شلن": ["لا شلنات", "شلن واحد", "شلنان", "%d شلنات", "%d شلنا", "%d شلن"],
    "مليم": ["لا مليمات", "مليم واحد", "مليمان", "%d مليمات", "%d مليما", "%d مليم"],
    "سنتسيمي": ["لا سنتسيمات", "سنتسيمي واحد", "سنتسيمان", "%d سنتسيمات", "%d سنتسيما", "%d سنتسيمي"],
    "بايزاس": ["لا بايزاسات", "بايزاس واحد", "بايزاسان", "%d بايزاسات", "%d بايزاسا", "%d بايزاس"],
    "يورو": ["لا يوروات", "يورو واحد", "يوروان", "%d يوروات", "%d يورو", "%d يورو"]
  }
}{
  "@file": "ArQuery",
  "preg_replace": [
      {
        "search": "/^ال/",
        "replace": "(ال)?"
      },
      {
        "search": "/(\\S{3,})تين$/",
        "replace": "\\1(تين|ة)?"
      },
      {
        "search": "/(\\S{3,})ين$/",
        "replace": "\\1(ين)?"
      },
      {
        "search": "/(\\S{3,})ون$/",
        "replace": "\\1(ون)?"
      },
      {
        "search": "/(\\S{3,})ان$/",
        "replace": "\\1(ان)?"
      },
      {
        "search": "/(\\S{3,})تا$/",
        "replace": "\\1(تا)?"
      },
      {
        "search": "/(\\S{3,})ا$/",
        "replace": "\\1(ا)?"
      },
      {
        "search": "/(\\S{3,})(ة|ات)$/",
        "replace": "\\1(ة|ات)?"
      },
      {
        "search": "/(\\S{3,})هما$/",
        "replace": "\\1(هما)?"
      },
      {
        "search": "/(\\S{3,})كما$/",
        "replace": "\\1(كما)?"
      },
      {
        "search": "/(\\S{3,})ني$/",
        "replace": "\\1(ني)?"
      },
      {
        "search": "/(\\S{3,})كم$/",
        "replace": "\\1(كم)?"
      },
      {
        "search": "/(\\S{3,})تم$/",
        "replace": "\\1(تم)?"
      },
      {
        "search": "/(\\S{3,})كن$/",
        "replace": "\\1(كن)?"
      },
      {
        "search": "/(\\S{3,})تن$/",
        "replace": "\\1(تن)?"
      },
      {
        "search": "/(\\S{3,})نا$/",
        "replace": "\\1(نا)?"
      },
      {
        "search": "/(\\S{3,})ها$/",
        "replace": "\\1(ها)?"
      },
      {
        "search": "/(\\S{3,})هم$/",
        "replace": "\\1(هم)?"
      },
      {
        "search": "/(\\S{3,})هن$/",
        "replace": "\\1(هن)?"
      },
      {
        "search": "/(\\S{3,})وا$/",
        "replace": "\\1(وا)?"
      },
      {
        "search": "/(\\S{3,})ية$/",
        "replace": "\\1(ي|ية)?"
      },
      {
        "search": "/(\\S{3,})ن$/",
        "replace": "\\1(ن)?"
      },
      {
        "search": "/(ة|ه)$/",
        "replace": "(ة|ه)"
      },
      {
        "search": "/(ة|ت)$/",
        "replace": "(ة|ت)"
      },
      {
        "search": "/(ي|ى)$/",
        "replace": "(ي|ى)"
      },
      {
        "search": "/(ا|ى)$/",
        "replace": "(ا|ى)"
      },
      {
        "search": "/(ئ|ىء|ؤ|وء|ء)/",
        "replace": "(ئ|ىء|ؤ|وء|ء)"
      },
      {
        "search": "/(ظ|ض)/",
        "replace": "(ظ|ض)"
      },
      {
        "search": "/ڨ/",
        "replace": "(ف|ڨ)"
      },
      {
        "search": "/پ/",
        "replace": "(ب|پ)"
      },
      {
        "search": "/چ/",
        "replace": "(ج|چ)"
      },
      {
        "search": "/ژ/",
        "replace": "(ز|ژ)"
      },
      {
        "search": "/(ڪ|گ)/",
        "replace": "(ڪ|ك|گ)"
      },
      {
        "search": "/ّ|َ|ً|ُ|ٌ|ِ|ٍ|ْ/",
        "replace": "(ّ|َ|ً|ُ|ٌ|ِ|ٍ|ْ)?"
      },
      {
        "search": "/ا|أ|إ|آ/",
        "replace": "(ا|أ|إ|آ)"
      }
    ]
}{
  "@file": "ArSoundex",
  "arSoundexCode": [
    {
      "search": "ا",
      "replace": "0"
    },
    {
      "search": "و",
      "replace": "0"
    },
    {
      "search": "ي",
      "replace": "0"
    },
    {
      "search": "ع",
      "replace": "0"
    },
    {
      "search": "ح",
      "replace": "0"
    },
    {
      "search": "ه",
      "replace": "0"
    },
    {
      "search": "ف",
      "replace": "1"
    },
    {
      "search": "ب",
      "replace": "1"
    },
    {
      "search": "خ",
      "replace": "2"
    },
    {
      "search": "ج",
      "replace": "2"
    },
    {
      "search": "ز",
      "replace": "2"
    },
    {
      "search": "س",
      "replace": "2"
    },
    {
      "search": "ص",
      "replace": "2"
    },
    {
      "search": "ظ",
      "replace": "2"
    },
    {
      "search": "ق",
      "replace": "2"
    },
    {
      "search": "ك",
      "replace": "2"
    },
    {
      "search": "غ",
      "replace": "2"
    },
    {
      "search": "ش",
      "replace": "2"
    },
    {
      "search": "ت",
      "replace": "3"
    },
    {
      "search": "ث",
      "replace": "3"
    },
    {
      "search": "د",
      "replace": "3"
    },
    {
      "search": "ذ",
      "replace": "3"
    },
    {
      "search": "ض",
      "replace": "3"
    },
    {
      "search": "ط",
      "replace": "3"
    },
    {
      "search": "ة",
      "replace": "3"
    },
    {
      "search": "ل",
      "replace": "4"
    },
    {
      "search": "م",
      "replace": "5"
    },
    {
      "search": "ن",
      "replace": "5"
    },
    {
      "search": "ر",
      "replace": "6"
    }
  ],
  "arPhonixCode": [
    {
      "search": "ا",
      "replace": "0"
    },
    {
      "search": "و",
      "replace": "0"
    },
    {
      "search": "ي",
      "replace": "0"
    },
    {
      "search": "ع",
      "replace": "0"
    },
    {
      "search": "ح",
      "replace": "0"
    },
    {
      "search": "ه",
      "replace": "0"
    },
    {
      "search": "ب",
      "replace": "1"
    },
    {
      "search": "خ",
      "replace": "2"
    },
    {
      "search": "ج",
      "replace": "2"
    },
    {
      "search": "ص",
      "replace": "2"
    },
    {
      "search": "ظ",
      "replace": "2"
    },
    {
      "search": "ق",
      "replace": "2"
    },
    {
      "search": "ك",
      "replace": "2"
    },
    {
      "search": "غ",
      "replace": "2"
    },
    {
      "search": "ش",
      "replace": "2"
    },
    {
      "search": "ت",
      "replace": "3"
    },
    {
      "search": "ث",
      "replace": "3"
    },
    {
      "search": "د",
      "replace": "3"
    },
    {
      "search": "ذ",
      "replace": "3"
    },
    {
      "search": "ض",
      "replace": "3"
    },
    {
      "search": "ط",
      "replace": "3"
    },
    {
      "search": "ة",
      "replace": "3"
    },
    {
      "search": "ل",
      "replace": "4"
    },
    {
      "search": "م",
      "replace": "5"
    },
    {
      "search": "ن",
      "replace": "5"
    },
    {
      "search": "ر",
      "replace": "6"
    },
    {
      "search": "ف",
      "replace": "7"
    },
    {
      "search": "ز",
      "replace": "8"
    },
    {
      "search": "س",
      "replace": "8"
    }
  ],
  "soundexTransliteration": [
    {
      "search": "ا",
      "replace": "A"
    },
    {
      "search": "ب",
      "replace": "B"
    },
    {
      "search": "ت",
      "replace": "T"
    },
    {
      "search": "ث",
      "replace": "T"
    },
    {
      "search": "ج",
      "replace": "J"
    },
    {
      "search": "ح",
      "replace": "H"
    },
    {
      "search": "خ",
      "replace": "K"
    },
    {
      "search": "د",
      "replace": "D"
    },
    {
      "search": "ذ",
      "replace": "Z"
    },
    {
      "search": "ر",
      "replace": "R"
    },
    {
      "search": "ز",
      "replace": "Z"
    },
    {
      "search": "س",
      "replace": "S"
    },
    {
      "search": "ش",
      "replace": "S"
    },
    {
      "search": "ص",
      "replace": "S"
    },
    {
      "search": "ض",
      "replace": "D"
    },
    {
      "search": "ط",
      "replace": "T"
    },
    {
      "search": "ظ",
      "replace": "Z"
    },
    {
      "search": "ع",
      "replace": "A"
    },
    {
      "search": "غ",
      "replace": "G"
    },
    {
      "search": "ف",
      "replace": "F"
    },
    {
      "search": "ق",
      "replace": "Q"
    },
    {
      "search": "ك",
      "replace": "K"
    },
    {
      "search": "ل",
      "replace": "L"
    },
    {
      "search": "م",
      "replace": "M"
    },
    {
      "search": "ن",
      "replace": "N"
    },
    {
      "search": "ه",
      "replace": "H"
    },
    {
      "search": "و",
      "replace": "W"
    },
    {
      "search": "ي",
      "replace": "Y"
    }
  ]
}﻿أب
أخ
حم
فو
ذو
أو
ثم
أنا 
نحن 
أنت 
أنتما 
أنتم 
أنتن 
هو 
هي 
هما 
هم 
هن 
يناير
فبراير
مارس
أبريل
مايو
يونيو
يوليو
أغسطس
سبتمبر
أكتوبر
نوفمبر
ديسمبر
جانفي
فيفري
أفريل
ماي
جوان
جويلية
أوت
كانون
شباط
آذار
نيسان
أيار
حزيران
تموز
آب
أيلول
تشرين
دولار
دينار
ريال
درهم
ليرة
جنيه
قرش
مليم
فلس
هللة
سنتيم
يورو
ين
يوان
شيكل
واحد
اثنان
ثلاثة
أربعة
خمسة
ستة
سبعة
ثمانية
تسعة
عشرة
أحد
اثنا
اثني
إحدى
ثلاث
أربع
خمس
ست
سبع
ثماني
تسع
عشر
ثمان
سبت
اثنين
ثلاثاء
أربعاء
خميس
جمعة
أول
ثان
ثاني
ثالث
رابع
خامس
سادس
سابع
ثامن
تاسع
عاشر
حادي
ألف
باء
تاء
ثاء
جيم
حاء
خاء
دال
ذال
راء
زاي
سين
شين
صاد
ضاد
طاء
ظاء
عين
غين
فاء
قاف
كاف
لام
ميم
نون
هاء
واو
ياء
همزة
إياه
إياها
إياهما
إياهم
إياهن
إياك
إياكما
إياكم
إياكن
إياي
إيانا
أولاء
أولئك
أولالك
تلك
ثمة
ذا
ذاك
ذان
ذانك
ذه
ذي
ذين
ذينك
هؤلاء
هاتان
هاته
هاتي
هاتين
هذا
هذان
هذه
هذي
هذين
هنا
هناك
هنالك
التي
الذي
الذين
اللاتي
اللتان
اللتيا
اللتين
اللذان
اللذين
اللواتي
الألى
الألاء
من
ما
أل
أنى
أي
أين
أيان
حيثما
كيفما
متى
مهما
كم
كيف
ماذا
ذيت
كأي
كأين
كذا
كيت
بضع
فلان
آمين
آه
أُف
أمامك
أوه
إليك
إليكم
إليكما
إليكن
إيه
بخ
بس
حذارِ
حي
دونك
رويدك
سرعان
شتان
صه
طاق
طق
عليك
مكانك
مكانكم
مكانكما
مكانكن
ها
هاك
هلم
هيا
هيهات
وراءك
وي
يفعلان
تفعلان
يفعلون
تفعلون
تفعلين
اتخذ
ألفى
تخذ
ترك
تعلم
جعل
حجا
حبيب
خال
حسب
درى
رأى
زعم
صبر
ظن
عد
علم
غادر
ذهب
وجد
ورد
وهب
أسكن
أطعم
أعطى
رزق
زود
سقى
كسا
أخبر
أرى
أعلم
أنبأ
حدث
خبر
نبا
بئس
حبذا
ساء
نعم
طالما
قلما
إن
لا
لات
أن
كأن
لعل
لكن
ليت
أجل
إذما
إذن
إذا
ألا
إلى
أم
أما
أى
إى
أيا
بل
بلى
جلل
جير
حتى
رب
سوف
عل
في
كلا
كي
لم
لن
لو
لولا
لوما
هل
هلا
وا
إذ
إلا
على
عن
قد
لما
مذ
منذ
حاشا
خلا
عدا
بعض
تجاه
تلقاء
جميع
سبحان
سوى
شبه
غير
كل
لعمر
مثل
مع
معاذ
نحو
حيث
أبو 
أخو
حمو 
مئة
مئتان
ثلاثمئة
أربعمئة
خمسمئة
ستمئة
سبعمئة
ثمنمئة
تسعمئة
مائة
ثلاثمائة
أربعمائة
خمسمائة
ستمائة
سبعمائة
ثمانمئة
تسعمائة
عشرون
ثلاثون
أربعون
خمسون
ستون
سبعون
ثمانون
تسعون
عشرين
ثلاثين
أربعين
خمسين
ستين
سبعين
ثمانين
تسعين
نيف
أجمع
عامة
كلتا
نفس
بيد
سيما
أصلا
أهلا
أيضا
بؤسا
بعدا
بغتة
تعسا
حقا
حمدا
خلافا
خاصة
دواليك
سحقا
سرا
سمعا
صبرا
صدقا
صراحة
طرا
عجبا
عيانا
غالبا
فرادى
فضلا
قاطبة
كثيرا
لبيك
أبدا
إزاء
الآن
أمد
أمس
آنفا
آناء
بين
تارة
ذات
صباح
مساء
ريث
ضحوة
عند
عوض
غدا
غداة
قط
كلما
لدن
لدى
مرة
بعد
دون
قبل
خلف
أمام
فوق
تحت
يمين
شمال
ارتد
استحال
أصبح
أضحى
آض
أمسى
انقلب
بات
تبدل
تحول
حار
رجع
راح
صار
ظل
عاد
كان
ليس
انفك
برح
مادام
مازال
مافتئ
ابتدأ
أخذ
اخلولق
أقبل
انبرى
أنشأ
أوشك
حرى
شرع
طفق
عسى
علق
قام
كرب
كاد
هب﻿#
#file
بيد
وبيد
فبيد
سوى
وسوى
فسوى
غير
بغير
كغير
لغير
وغير
فغير
وبغير
فبغير
وكغير
فكغير
ولغير
فلغير
أغير
أبغير
أكغير
ألغير
أوغير
أفغير
أوبغير
أفبغير
أوكغير
أفكغير
أولغير
أفلغير
غيري
غيرك
غيره
غيركم
غيركن
غيرها
غيرهم
غيرهن
غيرنا
غيركما
غيرهما
بغيري
بغيرك
بغيره
بغيركم
بغيركن
بغيرها
بغيرهم
بغيرهن
بغيرنا
بغيركما
بغيرهما
كغيري
كغيرك
كغيره
كغيركم
كغيركن
كغيرها
كغيرهم
كغيرهن
كغيرنا
كغيركما
كغيرهما
لغيري
لغيرك
لغيره
لغيركم
لغيركن
لغيرها
لغيرهم
لغيرهن
لغيرنا
لغيركما
لغيرهما
وغيري
وغيرك
وغيره
وغيركم
وغيركن
وغيرها
وغيرهم
وغيرهن
وغيرنا
وغيركما
وغيرهما
فغيري
فغيرك
فغيره
فغيركم
فغيركن
فغيرها
فغيرهم
فغيرهن
فغيرنا
فغيركما
فغيرهما
وبغيري
وبغيرك
وبغيره
وبغيركم
وبغيركن
وبغيرها
وبغيرهم
وبغيرهن
وبغيرنا
وبغيركما
وبغيرهما
فبغيري
فبغيرك
فبغيره
فبغيركم
فبغيركن
فبغيرها
فبغيرهم
فبغيرهن
فبغيرنا
فبغيركما
فبغيرهما
وكغيري
وكغيرك
وكغيره
وكغيركم
وكغيركن
وكغيرها
وكغيرهم
وكغيرهن
وكغيرنا
وكغيركما
وكغيرهما
فكغيري
فكغيرك
فكغيره
فكغيركم
فكغيركن
فكغيرها
فكغيرهم
فكغيرهن
فكغيرنا
فكغيركما
فكغيرهما
ولغيري
ولغيرك
ولغيره
ولغيركم
ولغيركن
ولغيرها
ولغيرهم
ولغيرهن
ولغيرنا
ولغيركما
ولغيرهما
فلغيري
فلغيرك
فلغيره
فلغيركم
فلغيركن
فلغيرها
فلغيرهم
فلغيرهن
فلغيرنا
فلغيركما
فلغيرهما
أغيري
أغيرك
أغيره
أغيركم
أغيركن
أغيرها
أغيرهم
أغيرهن
أغيرنا
أغيركما
أغيرهما
أبغيري
أبغيرك
أبغيره
أبغيركم
أبغيركن
أبغيرها
أبغيرهم
أبغيرهن
أبغيرنا
أبغيركما
أبغيرهما
أكغيري
أكغيرك
أكغيره
أكغيركم
أكغيركن
أكغيرها
أكغيرهم
أكغيرهن
أكغيرنا
أكغيركما
أكغيرهما
ألغيري
ألغيرك
ألغيره
ألغيركم
ألغيركن
ألغيرها
ألغيرهم
ألغيرهن
ألغيرنا
ألغيركما
ألغيرهما
أوغيري
أوغيرك
أوغيره
أوغيركم
أوغيركن
أوغيرها
أوغيرهم
أوغيرهن
أوغيرنا
أوغيركما
أوغيرهما
أفغيري
أفغيرك
أفغيره
أفغيركم
أفغيركن
أفغيرها
أفغيرهم
أفغيرهن
أفغيرنا
أفغيركما
أفغيرهما
أوبغيري
أوبغيرك
أوبغيره
أوبغيركم
أوبغيركن
أوبغيرها
أوبغيرهم
أوبغيرهن
أوبغيرنا
أوبغيركما
أوبغيرهما
أفبغيري
أفبغيرك
أفبغيره
أفبغيركم
أفبغيركن
أفبغيرها
أفبغيرهم
أفبغيرهن
أفبغيرنا
أفبغيركما
أفبغيرهما
أوكغيري
أوكغيرك
أوكغيره
أوكغيركم
أوكغيركن
أوكغيرها
أوكغيرهم
أوكغيرهن
أوكغيرنا
أوكغيركما
أوكغيرهما
أفكغيري
أفكغيرك
أفكغيره
أفكغيركم
أفكغيركن
أفكغيرها
أفكغيرهم
أفكغيرهن
أفكغيرنا
أفكغيركما
أفكغيرهما
أولغيري
أولغيرك
أولغيره
أولغيركم
أولغيركن
أولغيرها
أولغيرهم
أولغيرهن
أولغيرنا
أولغيركما
أولغيرهما
أفلغيري
أفلغيرك
أفلغيره
أفلغيركم
أفلغيركن
أفلغيرها
أفلغيرهم
أفلغيرهن
أفلغيرنا
أفلغيركما
أفلغيرهما
لاسيما
ولاسيما
فلاسيما
متى
ومتى
فمتى
أنى
وأنى
فأنى
أي
وأي
فأي
أيان
وأيان
فأيان
أين
وأين
فأين
بكم
وبكم
فبكم
بما
وبما
فبما
أبما
أوبما
أفبما
بماذا
وبماذا
فبماذا
بمن
وبمن
فبمن
كم
وكم
فكم
كيف
وكيف
فكيف
ما
وما
فما
ماذا
وماذا
فماذا
أماذا
أوماذا
أفماذا
مما
ومما
فمما
أمما
أومما
أفمما
ممن
وممن
فممن
من
ومن
فمن
أينما
وأينما
فأينما
حيثما
وحيثما
فحيثما
كيفما
وكيفما
فكيفما
مهما
ومهما
فمهما
أمهما
أومهما
أفمهما
أولئك
بأولئك
كأولئك
لأولئك
وأولئك
فأولئك
وبأولئك
فبأولئك
وكأولئك
فكأولئك
ولأولئك
فلأولئك
أأولئك
أبأولئك
أكأولئك
ألأولئك
أوأولئك
أفأولئك
أوبأولئك
أفبأولئك
أوكأولئك
أفكأولئك
أولأولئك
أفلأولئك
أولئكم
بأولئكم
كأولئكم
لأولئكم
وأولئكم
فأولئكم
وبأولئكم
فبأولئكم
وكأولئكم
فكأولئكم
ولأولئكم
فلأولئكم
أأولئكم
أبأولئكم
أكأولئكم
ألأولئكم
أوأولئكم
أفأولئكم
أوبأولئكم
أفبأولئكم
أوكأولئكم
أفكأولئكم
أولأولئكم
أفلأولئكم
أولاء
بأولاء
كأولاء
لأولاء
وأولاء
فأولاء
وبأولاء
فبأولاء
وكأولاء
فكأولاء
ولأولاء
فلأولاء
أأولاء
أبأولاء
أكأولاء
ألأولاء
أوأولاء
أفأولاء
أوبأولاء
أفبأولاء
أوكأولاء
أفكأولاء
أولأولاء
أفلأولاء
أولالك
بأولالك
كأولالك
لأولالك
وأولالك
فأولالك
وبأولالك
فبأولالك
وكأولالك
فكأولالك
ولأولالك
فلأولالك
أأولالك
أبأولالك
أكأولالك
ألأولالك
أوأولالك
أفأولالك
أوبأولالك
أفبأولالك
أوكأولالك
أفكأولالك
أولأولالك
أفلأولالك
تان
وتان
فتان
تانك
بتانك
كتانك
لتانك
وتانك
فتانك
وبتانك
فبتانك
وكتانك
فكتانك
ولتانك
فلتانك
تلك
بتلك
كتلك
لتلك
وتلك
فتلك
وبتلك
فبتلك
وكتلك
فكتلك
ولتلك
فلتلك
أتلك
أبتلك
أكتلك
ألتلك
أوتلك
أفتلك
أوبتلك
أفبتلك
أوكتلك
أفكتلك
أولتلك
أفلتلك
تلكم
بتلكم
كتلكم
لتلكم
وتلكم
فتلكم
وبتلكم
فبتلكم
وكتلكم
فكتلكم
ولتلكم
فلتلكم
أتلكم
أبتلكم
أكتلكم
ألتلكم
أوتلكم
أفتلكم
أوبتلكم
أفبتلكم
أوكتلكم
أفكتلكم
أولتلكم
أفلتلكم
تلكما
بتلكما
كتلكما
لتلكما
وتلكما
فتلكما
وبتلكما
فبتلكما
وكتلكما
فكتلكما
ولتلكما
فلتلكما
أتلكما
أبتلكما
أكتلكما
ألتلكما
أوتلكما
أفتلكما
أوبتلكما
أفبتلكما
أوكتلكما
أفكتلكما
أولتلكما
أفلتلكما
ته
بته
كته
لته
وته
فته
وبته
فبته
وكته
فكته
ولته
فلته
تي
بتي
كتي
لتي
وتي
فتي
وبتي
فبتي
وكتي
فكتي
ولتي
فلتي
تين
بتين
كتين
لتين
وتين
فتين
وبتين
فبتين
وكتين
فكتين
ولتين
فلتين
تينك
بتينك
كتينك
لتينك
وتينك
فتينك
وبتينك
فبتينك
وكتينك
فكتينك
ولتينك
فلتينك
أتينك
أبتينك
أكتينك
ألتينك
أوتينك
أفتينك
أوبتينك
أفبتينك
أوكتينك
أفكتينك
أولتينك
أفلتينك
ثم
وثم
فثم
أثم
أوثم
أفثم
ثمة
وثمة
فثمة
أثمة
أوثمة
أفثمة
ذا
بذا
كذا
لذا
وذا
فذا
وبذا
فبذا
وكذا
فكذا
ولذا
فلذا
ذاك
بذاك
كذاك
لذاك
وذاك
فذاك
وبذاك
فبذاك
وكذاك
فكذاك
ولذاك
فلذاك
أذاك
أبذاك
أكذاك
ألذاك
أوذاك
أفذاك
أوبذاك
أفبذاك
أوكذاك
أفكذاك
أولذاك
أفلذاك
ذان
وذان
فذان
ذانك
بذانك
كذانك
لذانك
وذانك
فذانك
وبذانك
فبذانك
وكذانك
فكذانك
ولذانك
فلذانك
أذانك
أبذانك
أكذانك
ألذانك
أوذانك
أفذانك
أوبذانك
أفبذانك
أوكذانك
أفكذانك
أولذانك
أفلذانك
ذلك
بذلك
كذلك
لذلك
وذلك
فذلك
وبذلك
فبذلك
وكذلك
فكذلك
ولذلك
فلذلك
أذلك
أبذلك
أكذلك
ألذلك
أوذلك
أفذلك
أوبذلك
أفبذلك
أوكذلك
أفكذلك
أولذلك
أفلذلك
ذلكم
بذلكم
كذلكم
لذلكم
وذلكم
فذلكم
وبذلكم
فبذلكم
وكذلكم
فكذلكم
ولذلكم
فلذلكم
أذلكم
أبذلكم
أكذلكم
ألذلكم
أوذلكم
أفذلكم
أوبذلكم
أفبذلكم
أوكذلكم
أفكذلكم
أولذلكم
أفلذلكم
ذلكما
بذلكما
كذلكما
لذلكما
وذلكما
فذلكما
وبذلكما
فبذلكما
وكذلكما
فكذلكما
ولذلكما
فلذلكما
أذلكما
أبذلكما
أكذلكما
ألذلكما
أوذلكما
أفذلكما
أوبذلكما
أفبذلكما
أوكذلكما
أفكذلكما
أولذلكما
أفلذلكما
ذلكن
بذلكن
كذلكن
لذلكن
وذلكن
فذلكن
وبذلكن
فبذلكن
وكذلكن
فكذلكن
ولذلكن
فلذلكن
أذلكن
أبذلكن
أكذلكن
ألذلكن
أوذلكن
أفذلكن
أوبذلكن
أفبذلكن
أوكذلكن
أفكذلكن
أولذلكن
أفلذلكن
ذه
بذه
كذه
لذه
وذه
فذه
وبذه
فبذه
وكذه
فكذه
ولذه
فلذه
ذوا
وذوا
فذوا
ذواتا
وذواتا
فذواتا
أذواتا
أوذواتا
أفذواتا
ذواتي
بذواتي
كذواتي
لذواتي
وذواتي
فذواتي
وبذواتي
فبذواتي
وكذواتي
فكذواتي
ولذواتي
فلذواتي
أذواتي
أبذواتي
أكذواتي
ألذواتي
أوذواتي
أفذواتي
أوبذواتي
أفبذواتي
أوكذواتي
أفكذواتي
أولذواتي
أفلذواتي
ذي
بذي
كذي
لذي
وذي
فذي
وبذي
فبذي
وكذي
فكذي
ولذي
فلذي
أذي
أبذي
أكذي
ألذي
أوذي
أفذي
أوبذي
أفبذي
أوكذي
أفكذي
أولذي
أفلذي
ذين
بذين
كذين
لذين
وذين
فذين
وبذين
فبذين
وكذين
فكذين
ولذين
فلذين
أذين
أبذين
أكذين
ألذين
أوذين
أفذين
أوبذين
أفبذين
أوكذين
أفكذين
أولذين
أفلذين
ذينك
بذينك
كذينك
لذينك
وذينك
فذينك
وبذينك
فبذينك
وكذينك
فكذينك
ولذينك
فلذينك
أذينك
أبذينك
أكذينك
ألذينك
أوذينك
أفذينك
أوبذينك
أفبذينك
أوكذينك
أفكذينك
أولذينك
أفلذينك
هؤلاء
بهؤلاء
كهؤلاء
لهؤلاء
وهؤلاء
فهؤلاء
وبهؤلاء
فبهؤلاء
وكهؤلاء
فكهؤلاء
ولهؤلاء
فلهؤلاء
أهؤلاء
أبهؤلاء
أكهؤلاء
ألهؤلاء
أوهؤلاء
أفهؤلاء
أوبهؤلاء
أفبهؤلاء
أوكهؤلاء
أفكهؤلاء
أولهؤلاء
أفلهؤلاء
هاتان
وهاتان
فهاتان
أهاتان
أوهاتان
أفهاتان
هاته
بهاته
كهاته
لهاته
وهاته
فهاته
وبهاته
فبهاته
وكهاته
فكهاته
ولهاته
فلهاته
أهاته
أبهاته
أكهاته
ألهاته
أوهاته
أفهاته
أوبهاته
أفبهاته
أوكهاته
أفكهاته
أولهاته
أفلهاته
هاتي
بهاتي
كهاتي
لهاتي
وهاتي
فهاتي
وبهاتي
فبهاتي
وكهاتي
فكهاتي
ولهاتي
فلهاتي
أهاتي
أبهاتي
أكهاتي
ألهاتي
أوهاتي
أفهاتي
أوبهاتي
أفبهاتي
أوكهاتي
أفكهاتي
أولهاتي
أفلهاتي
هاتين
بهاتين
كهاتين
لهاتين
وهاتين
فهاتين
وبهاتين
فبهاتين
وكهاتين
فكهاتين
ولهاتين
فلهاتين
أهاتين
أبهاتين
أكهاتين
ألهاتين
أوهاتين
أفهاتين
أوبهاتين
أفبهاتين
أوكهاتين
أفكهاتين
أولهاتين
أفلهاتين
هاهنا
وهاهنا
فهاهنا
أهاهنا
أوهاهنا
أفهاهنا
هذا
بهذا
كهذا
لهذا
وهذا
فهذا
وبهذا
فبهذا
وكهذا
فكهذا
ولهذا
فلهذا
أهذا
أبهذا
أكهذا
ألهذا
أوهذا
أفهذا
أوبهذا
أفبهذا
أوكهذا
أفكهذا
أولهذا
أفلهذا
هذان
وهذان
فهذان
أهذان
أوهذان
أفهذان
هذه
بهذه
كهذه
لهذه
وهذه
فهذه
وبهذه
فبهذه
وكهذه
فكهذه
ولهذه
فلهذه
أهذه
أبهذه
أكهذه
ألهذه
أوهذه
أفهذه
أوبهذه
أفبهذه
أوكهذه
أفكهذه
أولهذه
أفلهذه
هذي
بهذي
كهذي
لهذي
وهذي
فهذي
وبهذي
فبهذي
وكهذي
فكهذي
ولهذي
فلهذي
هذين
بهذين
كهذين
لهذين
وهذين
فهذين
وبهذين
فبهذين
وكهذين
فكهذين
ولهذين
فلهذين
هكذا
وهكذا
فهكذا
أهكذا
أوهكذا
أفهكذا
هنا
وهنا
فهنا
أهنا
أوهنا
أفهنا
هناك
بهناك
كهناك
لهناك
وهناك
فهناك
وبهناك
فبهناك
وكهناك
فكهناك
ولهناك
فلهناك
أهناك
أبهناك
أكهناك
ألهناك
أوهناك
أفهناك
أوبهناك
أفبهناك
أوكهناك
أفكهناك
أولهناك
أفلهناك
هنالك
بهنالك
كهنالك
لهنالك
وهنالك
فهنالك
وبهنالك
فبهنالك
وكهنالك
فكهنالك
ولهنالك
فلهنالك
أهنالك
أبهنالك
أكهنالك
ألهنالك
أوهنالك
أفهنالك
أوبهنالك
أفبهنالك
أوكهنالك
أفكهنالك
أولهنالك
أفلهنالك
بأي
كأي
لأي
وبأي
فبأي
وكأي
فكأي
ولأي
فلأي
أيي
أيك
أيه
أيكم
أيكن
أيها
أيهم
أيهن
أينا
أيكما
أيهما
بأيي
بأيك
بأيه
بأيكم
بأيكن
بأيها
بأيهم
بأيهن
بأينا
بأيكما
بأيهما
كأيي
كأيك
كأيه
كأيكم
كأيكن
كأيها
كأيهم
كأيهن
كأينا
كأيكما
كأيهما
لأيي
لأيك
لأيه
لأيكم
لأيكن
لأيها
لأيهم
لأيهن
لأينا
لأيكما
لأيهما
وأيي
وأيك
وأيه
وأيكم
وأيكن
وأيها
وأيهم
وأيهن
وأينا
وأيكما
وأيهما
فأيي
فأيك
فأيه
فأيكم
فأيكن
فأيها
فأيهم
فأيهن
فأينا
فأيكما
فأيهما
وبأيي
وبأيك
وبأيه
وبأيكم
وبأيكن
وبأيها
وبأيهم
وبأيهن
وبأينا
وبأيكما
وبأيهما
فبأيي
فبأيك
فبأيه
فبأيكم
فبأيكن
فبأيها
فبأيهم
فبأيهن
فبأينا
فبأيكما
فبأيهما
وكأيي
وكأيك
وكأيه
وكأيكم
وكأيكن
وكأيها
وكأيهم
وكأيهن
وكأينا
وكأيكما
وكأيهما
فكأيي
فكأيك
فكأيه
فكأيكم
فكأيكن
فكأيها
فكأيهم
فكأيهن
فكأينا
فكأيكما
فكأيهما
ولأيي
ولأيك
ولأيه
ولأيكم
ولأيكن
ولأيها
ولأيهم
ولأيهن
ولأينا
ولأيكما
ولأيهما
فلأيي
فلأيك
فلأيه
فلأيكم
فلأيكن
فلأيها
فلأيهم
فلأيهن
فلأينا
فلأيكما
فلأيهما
إذ
وإذ
فإذ
إذا
وإذا
فإذا
بعض
ببعض
كبعض
لبعض
وبعض
فبعض
وببعض
فببعض
وكبعض
فكبعض
ولبعض
فلبعض
أبعض
أببعض
أكبعض
ألبعض
أوبعض
أفبعض
أوببعض
أفببعض
أوكبعض
أفكبعض
أولبعض
أفلبعض
بعضي
بعضك
بعضه
بعضكم
بعضكن
بعضها
بعضهم
بعضهن
بعضنا
بعضكما
بعضهما
ببعضي
ببعضك
ببعضه
ببعضكم
ببعضكن
ببعضها
ببعضهم
ببعضهن
ببعضنا
ببعضكما
ببعضهما
كبعضي
كبعضك
كبعضه
كبعضكم
كبعضكن
كبعضها
كبعضهم
كبعضهن
كبعضنا
كبعضكما
كبعضهما
لبعضي
لبعضك
لبعضه
لبعضكم
لبعضكن
لبعضها
لبعضهم
لبعضهن
لبعضنا
لبعضكما
لبعضهما
وبعضي
وبعضك
وبعضه
وبعضكم
وبعضكن
وبعضها
وبعضهم
وبعضهن
وبعضنا
وبعضكما
وبعضهما
فبعضي
فبعضك
فبعضه
فبعضكم
فبعضكن
فبعضها
فبعضهم
فبعضهن
فبعضنا
فبعضكما
فبعضهما
وببعضي
وببعضك
وببعضه
وببعضكم
وببعضكن
وببعضها
وببعضهم
وببعضهن
وببعضنا
وببعضكما
وببعضهما
فببعضي
فببعضك
فببعضه
فببعضكم
فببعضكن
فببعضها
فببعضهم
فببعضهن
فببعضنا
فببعضكما
فببعضهما
وكبعضي
وكبعضك
وكبعضه
وكبعضكم
وكبعضكن
وكبعضها
وكبعضهم
وكبعضهن
وكبعضنا
وكبعضكما
وكبعضهما
فكبعضي
فكبعضك
فكبعضه
فكبعضكم
فكبعضكن
فكبعضها
فكبعضهم
فكبعضهن
فكبعضنا
فكبعضكما
فكبعضهما
ولبعضي
ولبعضك
ولبعضه
ولبعضكم
ولبعضكن
ولبعضها
ولبعضهم
ولبعضهن
ولبعضنا
ولبعضكما
ولبعضهما
فلبعضي
فلبعضك
فلبعضه
فلبعضكم
فلبعضكن
فلبعضها
فلبعضهم
فلبعضهن
فلبعضنا
فلبعضكما
فلبعضهما
أبعضي
أبعضك
أبعضه
أبعضكم
أبعضكن
أبعضها
أبعضهم
أبعضهن
أبعضنا
أبعضكما
أبعضهما
أببعضي
أببعضك
أببعضه
أببعضكم
أببعضكن
أببعضها
أببعضهم
أببعضهن
أببعضنا
أببعضكما
أببعضهما
أكبعضي
أكبعضك
أكبعضه
أكبعضكم
أكبعضكن
أكبعضها
أكبعضهم
أكبعضهن
أكبعضنا
أكبعضكما
أكبعضهما
ألبعضي
ألبعضك
ألبعضه
ألبعضكم
ألبعضكن
ألبعضها
ألبعضهم
ألبعضهن
ألبعضنا
ألبعضكما
ألبعضهما
أوبعضي
أوبعضك
أوبعضه
أوبعضكم
أوبعضكن
أوبعضها
أوبعضهم
أوبعضهن
أوبعضنا
أوبعضكما
أوبعضهما
أفبعضي
أفبعضك
أفبعضه
أفبعضكم
أفبعضكن
أفبعضها
أفبعضهم
أفبعضهن
أفبعضنا
أفبعضكما
أفبعضهما
أوببعضي
أوببعضك
أوببعضه
أوببعضكم
أوببعضكن
أوببعضها
أوببعضهم
أوببعضهن
أوببعضنا
أوببعضكما
أوببعضهما
أفببعضي
أفببعضك
أفببعضه
أفببعضكم
أفببعضكن
أفببعضها
أفببعضهم
أفببعضهن
أفببعضنا
أفببعضكما
أفببعضهما
أوكبعضي
أوكبعضك
أوكبعضه
أوكبعضكم
أوكبعضكن
أوكبعضها
أوكبعضهم
أوكبعضهن
أوكبعضنا
أوكبعضكما
أوكبعضهما
أفكبعضي
أفكبعضك
أفكبعضه
أفكبعضكم
أفكبعضكن
أفكبعضها
أفكبعضهم
أفكبعضهن
أفكبعضنا
أفكبعضكما
أفكبعضهما
أولبعضي
أولبعضك
أولبعضه
أولبعضكم
أولبعضكن
أولبعضها
أولبعضهم
أولبعضهن
أولبعضنا
أولبعضكما
أولبعضهما
أفلبعضي
أفلبعضك
أفلبعضه
أفلبعضكم
أفلبعضكن
أفلبعضها
أفلبعضهم
أفلبعضهن
أفلبعضنا
أفلبعضكما
أفلبعضهما
تجاه
بتجاه
كتجاه
لتجاه
وتجاه
فتجاه
وبتجاه
فبتجاه
وكتجاه
فكتجاه
ولتجاه
فلتجاه
تجاهي
تجاهك
تجاهه
تجاهكم
تجاهكن
تجاهها
تجاههم
تجاههن
تجاهنا
تجاهكما
تجاههما
بتجاهي
بتجاهك
بتجاهه
بتجاهكم
بتجاهكن
بتجاهها
بتجاههم
بتجاههن
بتجاهنا
بتجاهكما
بتجاههما
كتجاهي
كتجاهك
كتجاهه
كتجاهكم
كتجاهكن
كتجاهها
كتجاههم
كتجاههن
كتجاهنا
كتجاهكما
كتجاههما
لتجاهي
لتجاهك
لتجاهه
لتجاهكم
لتجاهكن
لتجاهها
لتجاههم
لتجاههن
لتجاهنا
لتجاهكما
لتجاههما
وتجاهي
وتجاهك
وتجاهه
وتجاهكم
وتجاهكن
وتجاهها
وتجاههم
وتجاههن
وتجاهنا
وتجاهكما
وتجاههما
فتجاهي
فتجاهك
فتجاهه
فتجاهكم
فتجاهكن
فتجاهها
فتجاههم
فتجاههن
فتجاهنا
فتجاهكما
فتجاههما
وبتجاهي
وبتجاهك
وبتجاهه
وبتجاهكم
وبتجاهكن
وبتجاهها
وبتجاههم
وبتجاههن
وبتجاهنا
وبتجاهكما
وبتجاههما
فبتجاهي
فبتجاهك
فبتجاهه
فبتجاهكم
فبتجاهكن
فبتجاهها
فبتجاههم
فبتجاههن
فبتجاهنا
فبتجاهكما
فبتجاههما
وكتجاهي
وكتجاهك
وكتجاهه
وكتجاهكم
وكتجاهكن
وكتجاهها
وكتجاههم
وكتجاههن
وكتجاهنا
وكتجاهكما
وكتجاههما
فكتجاهي
فكتجاهك
فكتجاهه
فكتجاهكم
فكتجاهكن
فكتجاهها
فكتجاههم
فكتجاههن
فكتجاهنا
فكتجاهكما
فكتجاههما
ولتجاهي
ولتجاهك
ولتجاهه
ولتجاهكم
ولتجاهكن
ولتجاهها
ولتجاههم
ولتجاههن
ولتجاهنا
ولتجاهكما
ولتجاههما
فلتجاهي
فلتجاهك
فلتجاهه
فلتجاهكم
فلتجاهكن
فلتجاهها
فلتجاههم
فلتجاههن
فلتجاهنا
فلتجاهكما
فلتجاههما
تلقاء
بتلقاء
كتلقاء
لتلقاء
وتلقاء
فتلقاء
وبتلقاء
فبتلقاء
وكتلقاء
فكتلقاء
ولتلقاء
فلتلقاء
تلقاءي
تلقاءك
تلقاءه
تلقاءكم
تلقاءكن
تلقاءها
تلقاءهم
تلقاءهن
تلقاءنا
تلقاءكما
تلقاءهما
بتلقاءي
بتلقاءك
بتلقاءه
بتلقاءكم
بتلقاءكن
بتلقاءها
بتلقاءهم
بتلقاءهن
بتلقاءنا
بتلقاءكما
بتلقاءهما
كتلقاءي
كتلقاءك
كتلقاءه
كتلقاءكم
كتلقاءكن
كتلقاءها
كتلقاءهم
كتلقاءهن
كتلقاءنا
كتلقاءكما
كتلقاءهما
لتلقاءي
لتلقاءك
لتلقاءه
لتلقاءكم
لتلقاءكن
لتلقاءها
لتلقاءهم
لتلقاءهن
لتلقاءنا
لتلقاءكما
لتلقاءهما
وتلقاءي
وتلقاءك
وتلقاءه
وتلقاءكم
وتلقاءكن
وتلقاءها
وتلقاءهم
وتلقاءهن
وتلقاءنا
وتلقاءكما
وتلقاءهما
فتلقاءي
فتلقاءك
فتلقاءه
فتلقاءكم
فتلقاءكن
فتلقاءها
فتلقاءهم
فتلقاءهن
فتلقاءنا
فتلقاءكما
فتلقاءهما
وبتلقاءي
وبتلقاءك
وبتلقاءه
وبتلقاءكم
وبتلقاءكن
وبتلقاءها
وبتلقاءهم
وبتلقاءهن
وبتلقاءنا
وبتلقاءكما
وبتلقاءهما
فبتلقاءي
فبتلقاءك
فبتلقاءه
فبتلقاءكم
فبتلقاءكن
فبتلقاءها
فبتلقاءهم
فبتلقاءهن
فبتلقاءنا
فبتلقاءكما
فبتلقاءهما
وكتلقاءي
وكتلقاءك
وكتلقاءه
وكتلقاءكم
وكتلقاءكن
وكتلقاءها
وكتلقاءهم
وكتلقاءهن
وكتلقاءنا
وكتلقاءكما
وكتلقاءهما
فكتلقاءي
فكتلقاءك
فكتلقاءه
فكتلقاءكم
فكتلقاءكن
فكتلقاءها
فكتلقاءهم
فكتلقاءهن
فكتلقاءنا
فكتلقاءكما
فكتلقاءهما
ولتلقاءي
ولتلقاءك
ولتلقاءه
ولتلقاءكم
ولتلقاءكن
ولتلقاءها
ولتلقاءهم
ولتلقاءهن
ولتلقاءنا
ولتلقاءكما
ولتلقاءهما
فلتلقاءي
فلتلقاءك
فلتلقاءه
فلتلقاءكم
فلتلقاءكن
فلتلقاءها
فلتلقاءهم
فلتلقاءهن
فلتلقاءنا
فلتلقاءكما
فلتلقاءهما
جميع
بجميع
كجميع
لجميع
وجميع
فجميع
وبجميع
فبجميع
وكجميع
فكجميع
ولجميع
فلجميع
أجميع
أبجميع
أكجميع
ألجميع
أوجميع
أفجميع
أوبجميع
أفبجميع
أوكجميع
أفكجميع
أولجميع
أفلجميع
جميعي
جميعك
جميعه
جميعكم
جميعكن
جميعها
جميعهم
جميعهن
جميعنا
جميعكما
جميعهما
بجميعي
بجميعك
بجميعه
بجميعكم
بجميعكن
بجميعها
بجميعهم
بجميعهن
بجميعنا
بجميعكما
بجميعهما
كجميعي
كجميعك
كجميعه
كجميعكم
كجميعكن
كجميعها
كجميعهم
كجميعهن
كجميعنا
كجميعكما
كجميعهما
لجميعي
لجميعك
لجميعه
لجميعكم
لجميعكن
لجميعها
لجميعهم
لجميعهن
لجميعنا
لجميعكما
لجميعهما
وجميعي
وجميعك
وجميعه
وجميعكم
وجميعكن
وجميعها
وجميعهم
وجميعهن
وجميعنا
وجميعكما
وجميعهما
فجميعي
فجميعك
فجميعه
فجميعكم
فجميعكن
فجميعها
فجميعهم
فجميعهن
فجميعنا
فجميعكما
فجميعهما
وبجميعي
وبجميعك
وبجميعه
وبجميعكم
وبجميعكن
وبجميعها
وبجميعهم
وبجميعهن
وبجميعنا
وبجميعكما
وبجميعهما
فبجميعي
فبجميعك
فبجميعه
فبجميعكم
فبجميعكن
فبجميعها
فبجميعهم
فبجميعهن
فبجميعنا
فبجميعكما
فبجميعهما
وكجميعي
وكجميعك
وكجميعه
وكجميعكم
وكجميعكن
وكجميعها
وكجميعهم
وكجميعهن
وكجميعنا
وكجميعكما
وكجميعهما
فكجميعي
فكجميعك
فكجميعه
فكجميعكم
فكجميعكن
فكجميعها
فكجميعهم
فكجميعهن
فكجميعنا
فكجميعكما
فكجميعهما
ولجميعي
ولجميعك
ولجميعه
ولجميعكم
ولجميعكن
ولجميعها
ولجميعهم
ولجميعهن
ولجميعنا
ولجميعكما
ولجميعهما
فلجميعي
فلجميعك
فلجميعه
فلجميعكم
فلجميعكن
فلجميعها
فلجميعهم
فلجميعهن
فلجميعنا
فلجميعكما
فلجميعهما
أجميعي
أجميعك
أجميعه
أجميعكم
أجميعكن
أجميعها
أجميعهم
أجميعهن
أجميعنا
أجميعكما
أجميعهما
أبجميعي
أبجميعك
أبجميعه
أبجميعكم
أبجميعكن
أبجميعها
أبجميعهم
أبجميعهن
أبجميعنا
أبجميعكما
أبجميعهما
أكجميعي
أكجميعك
أكجميعه
أكجميعكم
أكجميعكن
أكجميعها
أكجميعهم
أكجميعهن
أكجميعنا
أكجميعكما
أكجميعهما
ألجميعي
ألجميعك
ألجميعه
ألجميعكم
ألجميعكن
ألجميعها
ألجميعهم
ألجميعهن
ألجميعنا
ألجميعكما
ألجميعهما
أوجميعي
أوجميعك
أوجميعه
أوجميعكم
أوجميعكن
أوجميعها
أوجميعهم
أوجميعهن
أوجميعنا
أوجميعكما
أوجميعهما
أفجميعي
أفجميعك
أفجميعه
أفجميعكم
أفجميعكن
أفجميعها
أفجميعهم
أفجميعهن
أفجميعنا
أفجميعكما
أفجميعهما
أوبجميعي
أوبجميعك
أوبجميعه
أوبجميعكم
أوبجميعكن
أوبجميعها
أوبجميعهم
أوبجميعهن
أوبجميعنا
أوبجميعكما
أوبجميعهما
أفبجميعي
أفبجميعك
أفبجميعه
أفبجميعكم
أفبجميعكن
أفبجميعها
أفبجميعهم
أفبجميعهن
أفبجميعنا
أفبجميعكما
أفبجميعهما
أوكجميعي
أوكجميعك
أوكجميعه
أوكجميعكم
أوكجميعكن
أوكجميعها
أوكجميعهم
أوكجميعهن
أوكجميعنا
أوكجميعكما
أوكجميعهما
أفكجميعي
أفكجميعك
أفكجميعه
أفكجميعكم
أفكجميعكن
أفكجميعها
أفكجميعهم
أفكجميعهن
أفكجميعنا
أفكجميعكما
أفكجميعهما
أولجميعي
أولجميعك
أولجميعه
أولجميعكم
أولجميعكن
أولجميعها
أولجميعهم
أولجميعهن
أولجميعنا
أولجميعكما
أولجميعهما
أفلجميعي
أفلجميعك
أفلجميعه
أفلجميعكم
أفلجميعكن
أفلجميعها
أفلجميعهم
أفلجميعهن
أفلجميعنا
أفلجميعكما
أفلجميعهما
حسب
بحسب
كحسب
لحسب
وحسب
فحسب
وبحسب
فبحسب
وكحسب
فكحسب
ولحسب
فلحسب
أحسب
أبحسب
أكحسب
ألحسب
أوحسب
أفحسب
أوبحسب
أفبحسب
أوكحسب
أفكحسب
أولحسب
أفلحسب
حسبي
حسبك
حسبه
حسبكم
حسبكن
حسبها
حسبهم
حسبهن
حسبنا
حسبكما
حسبهما
بحسبي
بحسبك
بحسبه
بحسبكم
بحسبكن
بحسبها
بحسبهم
بحسبهن
بحسبنا
بحسبكما
بحسبهما
كحسبي
كحسبك
كحسبه
كحسبكم
كحسبكن
كحسبها
كحسبهم
كحسبهن
كحسبنا
كحسبكما
كحسبهما
لحسبي
لحسبك
لحسبه
لحسبكم
لحسبكن
لحسبها
لحسبهم
لحسبهن
لحسبنا
لحسبكما
لحسبهما
وحسبي
وحسبك
وحسبه
وحسبكم
وحسبكن
وحسبها
وحسبهم
وحسبهن
وحسبنا
وحسبكما
وحسبهما
فحسبي
فحسبك
فحسبه
فحسبكم
فحسبكن
فحسبها
فحسبهم
فحسبهن
فحسبنا
فحسبكما
فحسبهما
وبحسبي
وبحسبك
وبحسبه
وبحسبكم
وبحسبكن
وبحسبها
وبحسبهم
وبحسبهن
وبحسبنا
وبحسبكما
وبحسبهما
فبحسبي
فبحسبك
فبحسبه
فبحسبكم
فبحسبكن
فبحسبها
فبحسبهم
فبحسبهن
فبحسبنا
فبحسبكما
فبحسبهما
وكحسبي
وكحسبك
وكحسبه
وكحسبكم
وكحسبكن
وكحسبها
وكحسبهم
وكحسبهن
وكحسبنا
وكحسبكما
وكحسبهما
فكحسبي
فكحسبك
فكحسبه
فكحسبكم
فكحسبكن
فكحسبها
فكحسبهم
فكحسبهن
فكحسبنا
فكحسبكما
فكحسبهما
ولحسبي
ولحسبك
ولحسبه
ولحسبكم
ولحسبكن
ولحسبها
ولحسبهم
ولحسبهن
ولحسبنا
ولحسبكما
ولحسبهما
فلحسبي
فلحسبك
فلحسبه
فلحسبكم
فلحسبكن
فلحسبها
فلحسبهم
فلحسبهن
فلحسبنا
فلحسبكما
فلحسبهما
أحسبي
أحسبك
أحسبه
أحسبكم
أحسبكن
أحسبها
أحسبهم
أحسبهن
أحسبنا
أحسبكما
أحسبهما
أبحسبي
أبحسبك
أبحسبه
أبحسبكم
أبحسبكن
أبحسبها
أبحسبهم
أبحسبهن
أبحسبنا
أبحسبكما
أبحسبهما
أكحسبي
أكحسبك
أكحسبه
أكحسبكم
أكحسبكن
أكحسبها
أكحسبهم
أكحسبهن
أكحسبنا
أكحسبكما
أكحسبهما
ألحسبي
ألحسبك
ألحسبه
ألحسبكم
ألحسبكن
ألحسبها
ألحسبهم
ألحسبهن
ألحسبنا
ألحسبكما
ألحسبهما
أوحسبي
أوحسبك
أوحسبه
أوحسبكم
أوحسبكن
أوحسبها
أوحسبهم
أوحسبهن
أوحسبنا
أوحسبكما
أوحسبهما
أفحسبي
أفحسبك
أفحسبه
أفحسبكم
أفحسبكن
أفحسبها
أفحسبهم
أفحسبهن
أفحسبنا
أفحسبكما
أفحسبهما
أوبحسبي
أوبحسبك
أوبحسبه
أوبحسبكم
أوبحسبكن
أوبحسبها
أوبحسبهم
أوبحسبهن
أوبحسبنا
أوبحسبكما
أوبحسبهما
أفبحسبي
أفبحسبك
أفبحسبه
أفبحسبكم
أفبحسبكن
أفبحسبها
أفبحسبهم
أفبحسبهن
أفبحسبنا
أفبحسبكما
أفبحسبهما
أوكحسبي
أوكحسبك
أوكحسبه
أوكحسبكم
أوكحسبكن
أوكحسبها
أوكحسبهم
أوكحسبهن
أوكحسبنا
أوكحسبكما
أوكحسبهما
أفكحسبي
أفكحسبك
أفكحسبه
أفكحسبكم
أفكحسبكن
أفكحسبها
أفكحسبهم
أفكحسبهن
أفكحسبنا
أفكحسبكما
أفكحسبهما
أولحسبي
أولحسبك
أولحسبه
أولحسبكم
أولحسبكن
أولحسبها
أولحسبهم
أولحسبهن
أولحسبنا
أولحسبكما
أولحسبهما
أفلحسبي
أفلحسبك
أفلحسبه
أفلحسبكم
أفلحسبكن
أفلحسبها
أفلحسبهم
أفلحسبهن
أفلحسبنا
أفلحسبكما
أفلحسبهما
حيث
بحيث
كحيث
لحيث
وحيث
فحيث
وبحيث
فبحيث
وكحيث
فكحيث
ولحيث
فلحيث
سبحان
وسبحان
فسبحان
سبحاني
سبحانك
سبحانه
سبحانكم
سبحانكن
سبحانها
سبحانهم
سبحانهن
سبحاننا
سبحانكما
سبحانهما
وسبحاني
وسبحانك
وسبحانه
وسبحانكم
وسبحانكن
وسبحانها
وسبحانهم
وسبحانهن
وسبحاننا
وسبحانكما
وسبحانهما
فسبحاني
فسبحانك
فسبحانه
فسبحانكم
فسبحانكن
فسبحانها
فسبحانهم
فسبحانهن
فسبحاننا
فسبحانكما
فسبحانهما
بسوى
كسوى
لسوى
وبسوى
فبسوى
وكسوى
فكسوى
ولسوى
فلسوى
أسوى
أبسوى
أكسوى
ألسوى
أوسوى
أفسوى
أوبسوى
أفبسوى
أوكسوى
أفكسوى
أولسوى
أفلسوى
سوي
سويك
سويه
سويكم
سويكن
سويها
سويهم
سويهن
سوينا
سويكما
سويهما
بسوي
بسويك
بسويه
بسويكم
بسويكن
بسويها
بسويهم
بسويهن
بسوينا
بسويكما
بسويهما
كسوي
كسويك
كسويه
كسويكم
كسويكن
كسويها
كسويهم
كسويهن
كسوينا
كسويكما
كسويهما
لسوي
لسويك
لسويه
لسويكم
لسويكن
لسويها
لسويهم
لسويهن
لسوينا
لسويكما
لسويهما
وسوي
وسويك
وسويه
وسويكم
وسويكن
وسويها
وسويهم
وسويهن
وسوينا
وسويكما
وسويهما
فسوي
فسويك
فسويه
فسويكم
فسويكن
فسويها
فسويهم
فسويهن
فسوينا
فسويكما
فسويهما
وبسوي
وبسويك
وبسويه
وبسويكم
وبسويكن
وبسويها
وبسويهم
وبسويهن
وبسوينا
وبسويكما
وبسويهما
فبسوي
فبسويك
فبسويه
فبسويكم
فبسويكن
فبسويها
فبسويهم
فبسويهن
فبسوينا
فبسويكما
فبسويهما
وكسوي
وكسويك
وكسويه
وكسويكم
وكسويكن
وكسويها
وكسويهم
وكسويهن
وكسوينا
وكسويكما
وكسويهما
فكسوي
فكسويك
فكسويه
فكسويكم
فكسويكن
فكسويها
فكسويهم
فكسويهن
فكسوينا
فكسويكما
فكسويهما
ولسوي
ولسويك
ولسويه
ولسويكم
ولسويكن
ولسويها
ولسويهم
ولسويهن
ولسوينا
ولسويكما
ولسويهما
فلسوي
فلسويك
فلسويه
فلسويكم
فلسويكن
فلسويها
فلسويهم
فلسويهن
فلسوينا
فلسويكما
فلسويهما
أسوي
أسويك
أسويه
أسويكم
أسويكن
أسويها
أسويهم
أسويهن
أسوينا
أسويكما
أسويهما
أبسوي
أبسويك
أبسويه
أبسويكم
أبسويكن
أبسويها
أبسويهم
أبسويهن
أبسوينا
أبسويكما
أبسويهما
أكسوي
أكسويك
أكسويه
أكسويكم
أكسويكن
أكسويها
أكسويهم
أكسويهن
أكسوينا
أكسويكما
أكسويهما
ألسوي
ألسويك
ألسويه
ألسويكم
ألسويكن
ألسويها
ألسويهم
ألسويهن
ألسوينا
ألسويكما
ألسويهما
أوسوي
أوسويك
أوسويه
أوسويكم
أوسويكن
أوسويها
أوسويهم
أوسويهن
أوسوينا
أوسويكما
أوسويهما
أفسوي
أفسويك
أفسويه
أفسويكم
أفسويكن
أفسويها
أفسويهم
أفسويهن
أفسوينا
أفسويكما
أفسويهما
أوبسوي
أوبسويك
أوبسويه
أوبسويكم
أوبسويكن
أوبسويها
أوبسويهم
أوبسويهن
أوبسوينا
أوبسويكما
أوبسويهما
أفبسوي
أفبسويك
أفبسويه
أفبسويكم
أفبسويكن
أفبسويها
أفبسويهم
أفبسويهن
أفبسوينا
أفبسويكما
أفبسويهما
أوكسوي
أوكسويك
أوكسويه
أوكسويكم
أوكسويكن
أوكسويها
أوكسويهم
أوكسويهن
أوكسوينا
أوكسويكما
أوكسويهما
أفكسوي
أفكسويك
أفكسويه
أفكسويكم
أفكسويكن
أفكسويها
أفكسويهم
أفكسويهن
أفكسوينا
أفكسويكما
أفكسويهما
أولسوي
أولسويك
أولسويه
أولسويكم
أولسويكن
أولسويها
أولسويهم
أولسويهن
أولسوينا
أولسويكما
أولسويهما
أفلسوي
أفلسويك
أفلسويه
أفلسويكم
أفلسويكن
أفلسويها
أفلسويهم
أفلسويهن
أفلسوينا
أفلسويكما
أفلسويهما
شبه
بشبه
كشبه
لشبه
وشبه
فشبه
وبشبه
فبشبه
وكشبه
فكشبه
ولشبه
فلشبه
أشبه
أبشبه
أكشبه
ألشبه
أوشبه
أفشبه
أوبشبه
أفبشبه
أوكشبه
أفكشبه
أولشبه
أفلشبه
شبهي
شبهك
شبهه
شبهكم
شبهكن
شبهها
شبههم
شبههن
شبهنا
شبهكما
شبههما
بشبهي
بشبهك
بشبهه
بشبهكم
بشبهكن
بشبهها
بشبههم
بشبههن
بشبهنا
بشبهكما
بشبههما
كشبهي
كشبهك
كشبهه
كشبهكم
كشبهكن
كشبهها
كشبههم
كشبههن
كشبهنا
كشبهكما
كشبههما
لشبهي
لشبهك
لشبهه
لشبهكم
لشبهكن
لشبهها
لشبههم
لشبههن
لشبهنا
لشبهكما
لشبههما
وشبهي
وشبهك
وشبهه
وشبهكم
وشبهكن
وشبهها
وشبههم
وشبههن
وشبهنا
وشبهكما
وشبههما
فشبهي
فشبهك
فشبهه
فشبهكم
فشبهكن
فشبهها
فشبههم
فشبههن
فشبهنا
فشبهكما
فشبههما
وبشبهي
وبشبهك
وبشبهه
وبشبهكم
وبشبهكن
وبشبهها
وبشبههم
وبشبههن
وبشبهنا
وبشبهكما
وبشبههما
فبشبهي
فبشبهك
فبشبهه
فبشبهكم
فبشبهكن
فبشبهها
فبشبههم
فبشبههن
فبشبهنا
فبشبهكما
فبشبههما
وكشبهي
وكشبهك
وكشبهه
وكشبهكم
وكشبهكن
وكشبهها
وكشبههم
وكشبههن
وكشبهنا
وكشبهكما
وكشبههما
فكشبهي
فكشبهك
فكشبهه
فكشبهكم
فكشبهكن
فكشبهها
فكشبههم
فكشبههن
فكشبهنا
فكشبهكما
فكشبههما
ولشبهي
ولشبهك
ولشبهه
ولشبهكم
ولشبهكن
ولشبهها
ولشبههم
ولشبههن
ولشبهنا
ولشبهكما
ولشبههما
فلشبهي
فلشبهك
فلشبهه
فلشبهكم
فلشبهكن
فلشبهها
فلشبههم
فلشبههن
فلشبهنا
فلشبهكما
فلشبههما
أشبهي
أشبهك
أشبهه
أشبهكم
أشبهكن
أشبهها
أشبههم
أشبههن
أشبهنا
أشبهكما
أشبههما
أبشبهي
أبشبهك
أبشبهه
أبشبهكم
أبشبهكن
أبشبهها
أبشبههم
أبشبههن
أبشبهنا
أبشبهكما
أبشبههما
أكشبهي
أكشبهك
أكشبهه
أكشبهكم
أكشبهكن
أكشبهها
أكشبههم
أكشبههن
أكشبهنا
أكشبهكما
أكشبههما
ألشبهي
ألشبهك
ألشبهه
ألشبهكم
ألشبهكن
ألشبهها
ألشبههم
ألشبههن
ألشبهنا
ألشبهكما
ألشبههما
أوشبهي
أوشبهك
أوشبهه
أوشبهكم
أوشبهكن
أوشبهها
أوشبههم
أوشبههن
أوشبهنا
أوشبهكما
أوشبههما
أفشبهي
أفشبهك
أفشبهه
أفشبهكم
أفشبهكن
أفشبهها
أفشبههم
أفشبههن
أفشبهنا
أفشبهكما
أفشبههما
أوبشبهي
أوبشبهك
أوبشبهه
أوبشبهكم
أوبشبهكن
أوبشبهها
أوبشبههم
أوبشبههن
أوبشبهنا
أوبشبهكما
أوبشبههما
أفبشبهي
أفبشبهك
أفبشبهه
أفبشبهكم
أفبشبهكن
أفبشبهها
أفبشبههم
أفبشبههن
أفبشبهنا
أفبشبهكما
أفبشبههما
أوكشبهي
أوكشبهك
أوكشبهه
أوكشبهكم
أوكشبهكن
أوكشبهها
أوكشبههم
أوكشبههن
أوكشبهنا
أوكشبهكما
أوكشبههما
أفكشبهي
أفكشبهك
أفكشبهه
أفكشبهكم
أفكشبهكن
أفكشبهها
أفكشبههم
أفكشبههن
أفكشبهنا
أفكشبهكما
أفكشبههما
أولشبهي
أولشبهك
أولشبهه
أولشبهكم
أولشبهكن
أولشبهها
أولشبههم
أولشبههن
أولشبهنا
أولشبهكما
أولشبههما
أفلشبهي
أفلشبهك
أفلشبهه
أفلشبهكم
أفلشبهكن
أفلشبهها
أفلشبههم
أفلشبههن
أفلشبهنا
أفلشبهكما
أفلشبههما
كل
بكل
ككل
لكل
وكل
فكل
وبكل
فبكل
وككل
فككل
ولكل
فلكل
أكل
أبكل
أككل
ألكل
أوكل
أفكل
أوبكل
أفبكل
أوككل
أفككل
أولكل
أفلكل
كلي
كلك
كله
كلكم
كلكن
كلها
كلهم
كلهن
كلنا
كلكما
كلهما
بكلي
بكلك
بكله
بكلكم
بكلكن
بكلها
بكلهم
بكلهن
بكلنا
بكلكما
بكلهما
ككلي
ككلك
ككله
ككلكم
ككلكن
ككلها
ككلهم
ككلهن
ككلنا
ككلكما
ككلهما
لكلي
لكلك
لكله
لكلكم
لكلكن
لكلها
لكلهم
لكلهن
لكلنا
لكلكما
لكلهما
وكلي
وكلك
وكله
وكلكم
وكلكن
وكلها
وكلهم
وكلهن
وكلنا
وكلكما
وكلهما
فكلي
فكلك
فكله
فكلكم
فكلكن
فكلها
فكلهم
فكلهن
فكلنا
فكلكما
فكلهما
وبكلي
وبكلك
وبكله
وبكلكم
وبكلكن
وبكلها
وبكلهم
وبكلهن
وبكلنا
وبكلكما
وبكلهما
فبكلي
فبكلك
فبكله
فبكلكم
فبكلكن
فبكلها
فبكلهم
فبكلهن
فبكلنا
فبكلكما
فبكلهما
وككلي
وككلك
وككله
وككلكم
وككلكن
وككلها
وككلهم
وككلهن
وككلنا
وككلكما
وككلهما
فككلي
فككلك
فككله
فككلكم
فككلكن
فككلها
فككلهم
فككلهن
فككلنا
فككلكما
فككلهما
ولكلي
ولكلك
ولكله
ولكلكم
ولكلكن
ولكلها
ولكلهم
ولكلهن
ولكلنا
ولكلكما
ولكلهما
فلكلي
فلكلك
فلكله
فلكلكم
فلكلكن
فلكلها
فلكلهم
فلكلهن
فلكلنا
فلكلكما
فلكلهما
أكلي
أكلك
أكله
أكلكم
أكلكن
أكلها
أكلهم
أكلهن
أكلنا
أكلكما
أكلهما
أبكلي
أبكلك
أبكله
أبكلكم
أبكلكن
أبكلها
أبكلهم
أبكلهن
أبكلنا
أبكلكما
أبكلهما
أككلي
أككلك
أككله
أككلكم
أككلكن
أككلها
أككلهم
أككلهن
أككلنا
أككلكما
أككلهما
ألكلي
ألكلك
ألكله
ألكلكم
ألكلكن
ألكلها
ألكلهم
ألكلهن
ألكلنا
ألكلكما
ألكلهما
أوكلي
أوكلك
أوكله
أوكلكم
أوكلكن
أوكلها
أوكلهم
أوكلهن
أوكلنا
أوكلكما
أوكلهما
أفكلي
أفكلك
أفكله
أفكلكم
أفكلكن
أفكلها
أفكلهم
أفكلهن
أفكلنا
أفكلكما
أفكلهما
أوبكلي
أوبكلك
أوبكله
أوبكلكم
أوبكلكن
أوبكلها
أوبكلهم
أوبكلهن
أوبكلنا
أوبكلكما
أوبكلهما
أفبكلي
أفبكلك
أفبكله
أفبكلكم
أفبكلكن
أفبكلها
أفبكلهم
أفبكلهن
أفبكلنا
أفبكلكما
أفبكلهما
أوككلي
أوككلك
أوككله
أوككلكم
أوككلكن
أوككلها
أوككلهم
أوككلهن
أوككلنا
أوككلكما
أوككلهما
أفككلي
أفككلك
أفككله
أفككلكم
أفككلكن
أفككلها
أفككلهم
أفككلهن
أفككلنا
أفككلكما
أفككلهما
أولكلي
أولكلك
أولكله
أولكلكم
أولكلكن
أولكلها
أولكلهم
أولكلهن
أولكلنا
أولكلكما
أولكلهما
أفلكلي
أفلكلك
أفلكله
أفلكلكم
أفلكلكن
أفلكلها
أفلكلهم
أفلكلهن
أفلكلنا
أفلكلكما
أفلكلهما
لعمر
ولعمر
فلعمر
لعمري
لعمرك
لعمره
لعمركم
لعمركن
لعمرها
لعمرهم
لعمرهن
لعمرنا
لعمركما
لعمرهما
ولعمري
ولعمرك
ولعمره
ولعمركم
ولعمركن
ولعمرها
ولعمرهم
ولعمرهن
ولعمرنا
ولعمركما
ولعمرهما
فلعمري
فلعمرك
فلعمره
فلعمركم
فلعمركن
فلعمرها
فلعمرهم
فلعمرهن
فلعمرنا
فلعمركما
فلعمرهما
لما
ولما
فلما
ألما
أولما
أفلما
مثل
بمثل
كمثل
لمثل
ومثل
فمثل
وبمثل
فبمثل
وكمثل
فكمثل
ولمثل
فلمثل
أمثل
أبمثل
أكمثل
ألمثل
أومثل
أفمثل
أوبمثل
أفبمثل
أوكمثل
أفكمثل
أولمثل
أفلمثل
مثلي
مثلك
مثله
مثلكم
مثلكن
مثلها
مثلهم
مثلهن
مثلنا
مثلكما
مثلهما
بمثلي
بمثلك
بمثله
بمثلكم
بمثلكن
بمثلها
بمثلهم
بمثلهن
بمثلنا
بمثلكما
بمثلهما
كمثلي
كمثلك
كمثله
كمثلكم
كمثلكن
كمثلها
كمثلهم
كمثلهن
كمثلنا
كمثلكما
كمثلهما
لمثلي
لمثلك
لمثله
لمثلكم
لمثلكن
لمثلها
لمثلهم
لمثلهن
لمثلنا
لمثلكما
لمثلهما
ومثلي
ومثلك
ومثله
ومثلكم
ومثلكن
ومثلها
ومثلهم
ومثلهن
ومثلنا
ومثلكما
ومثلهما
فمثلي
فمثلك
فمثله
فمثلكم
فمثلكن
فمثلها
فمثلهم
فمثلهن
فمثلنا
فمثلكما
فمثلهما
وبمثلي
وبمثلك
وبمثله
وبمثلكم
وبمثلكن
وبمثلها
وبمثلهم
وبمثلهن
وبمثلنا
وبمثلكما
وبمثلهما
فبمثلي
فبمثلك
فبمثله
فبمثلكم
فبمثلكن
فبمثلها
فبمثلهم
فبمثلهن
فبمثلنا
فبمثلكما
فبمثلهما
وكمثلي
وكمثلك
وكمثله
وكمثلكم
وكمثلكن
وكمثلها
وكمثلهم
وكمثلهن
وكمثلنا
وكمثلكما
وكمثلهما
فكمثلي
فكمثلك
فكمثله
فكمثلكم
فكمثلكن
فكمثلها
فكمثلهم
فكمثلهن
فكمثلنا
فكمثلكما
فكمثلهما
ولمثلي
ولمثلك
ولمثله
ولمثلكم
ولمثلكن
ولمثلها
ولمثلهم
ولمثلهن
ولمثلنا
ولمثلكما
ولمثلهما
فلمثلي
فلمثلك
فلمثله
فلمثلكم
فلمثلكن
فلمثلها
فلمثلهم
فلمثلهن
فلمثلنا
فلمثلكما
فلمثلهما
أمثلي
أمثلك
أمثله
أمثلكم
أمثلكن
أمثلها
أمثلهم
أمثلهن
أمثلنا
أمثلكما
أمثلهما
أبمثلي
أبمثلك
أبمثله
أبمثلكم
أبمثلكن
أبمثلها
أبمثلهم
أبمثلهن
أبمثلنا
أبمثلكما
أبمثلهما
أكمثلي
أكمثلك
أكمثله
أكمثلكم
أكمثلكن
أكمثلها
أكمثلهم
أكمثلهن
أكمثلنا
أكمثلكما
أكمثلهما
ألمثلي
ألمثلك
ألمثله
ألمثلكم
ألمثلكن
ألمثلها
ألمثلهم
ألمثلهن
ألمثلنا
ألمثلكما
ألمثلهما
أومثلي
أومثلك
أومثله
أومثلكم
أومثلكن
أومثلها
أومثلهم
أومثلهن
أومثلنا
أومثلكما
أومثلهما
أفمثلي
أفمثلك
أفمثله
أفمثلكم
أفمثلكن
أفمثلها
أفمثلهم
أفمثلهن
أفمثلنا
أفمثلكما
أفمثلهما
أوبمثلي
أوبمثلك
أوبمثله
أوبمثلكم
أوبمثلكن
أوبمثلها
أوبمثلهم
أوبمثلهن
أوبمثلنا
أوبمثلكما
أوبمثلهما
أفبمثلي
أفبمثلك
أفبمثله
أفبمثلكم
أفبمثلكن
أفبمثلها
أفبمثلهم
أفبمثلهن
أفبمثلنا
أفبمثلكما
أفبمثلهما
أوكمثلي
أوكمثلك
أوكمثله
أوكمثلكم
أوكمثلكن
أوكمثلها
أوكمثلهم
أوكمثلهن
أوكمثلنا
أوكمثلكما
أوكمثلهما
أفكمثلي
أفكمثلك
أفكمثله
أفكمثلكم
أفكمثلكن
أفكمثلها
أفكمثلهم
أفكمثلهن
أفكمثلنا
أفكمثلكما
أفكمثلهما
أولمثلي
أولمثلك
أولمثله
أولمثلكم
أولمثلكن
أولمثلها
أولمثلهم
أولمثلهن
أولمثلنا
أولمثلكما
أولمثلهما
أفلمثلي
أفلمثلك
أفلمثله
أفلمثلكم
أفلمثلكن
أفلمثلها
أفلمثلهم
أفلمثلهن
أفلمثلنا
أفلمثلكما
أفلمثلهما
مع
لمع
ومع
فمع
ولمع
فلمع
أمع
ألمع
أومع
أفمع
أولمع
أفلمع
معي
معك
معه
معكم
معكن
معها
معهم
معهن
معنا
معكما
معهما
لمعي
لمعك
لمعه
لمعكم
لمعكن
لمعها
لمعهم
لمعهن
لمعنا
لمعكما
لمعهما
ومعي
ومعك
ومعه
ومعكم
ومعكن
ومعها
ومعهم
ومعهن
ومعنا
ومعكما
ومعهما
فمعي
فمعك
فمعه
فمعكم
فمعكن
فمعها
فمعهم
فمعهن
فمعنا
فمعكما
فمعهما
ولمعي
ولمعك
ولمعه
ولمعكم
ولمعكن
ولمعها
ولمعهم
ولمعهن
ولمعنا
ولمعكما
ولمعهما
فلمعي
فلمعك
فلمعه
فلمعكم
فلمعكن
فلمعها
فلمعهم
فلمعهن
فلمعنا
فلمعكما
فلمعهما
أمعي
أمعك
أمعه
أمعكم
أمعكن
أمعها
أمعهم
أمعهن
أمعنا
أمعكما
أمعهما
ألمعي
ألمعك
ألمعه
ألمعكم
ألمعكن
ألمعها
ألمعهم
ألمعهن
ألمعنا
ألمعكما
ألمعهما
أومعي
أومعك
أومعه
أومعكم
أومعكن
أومعها
أومعهم
أومعهن
أومعنا
أومعكما
أومعهما
أفمعي
أفمعك
أفمعه
أفمعكم
أفمعكن
أفمعها
أفمعهم
أفمعهن
أفمعنا
أفمعكما
أفمعهما
أولمعي
أولمعك
أولمعه
أولمعكم
أولمعكن
أولمعها
أولمعهم
أولمعهن
أولمعنا
أولمعكما
أولمعهما
أفلمعي
أفلمعك
أفلمعه
أفلمعكم
أفلمعكن
أفلمعها
أفلمعهم
أفلمعهن
أفلمعنا
أفلمعكما
أفلمعهما
معاذ
ومعاذ
فمعاذ
معاذي
معاذك
معاذه
معاذكم
معاذكن
معاذها
معاذهم
معاذهن
معاذنا
معاذكما
معاذهما
ومعاذي
ومعاذك
ومعاذه
ومعاذكم
ومعاذكن
ومعاذها
ومعاذهم
ومعاذهن
ومعاذنا
ومعاذكما
ومعاذهما
فمعاذي
فمعاذك
فمعاذه
فمعاذكم
فمعاذكن
فمعاذها
فمعاذهم
فمعاذهن
فمعاذنا
فمعاذكما
فمعاذهما
نحو
بنحو
كنحو
لنحو
ونحو
فنحو
وبنحو
فبنحو
وكنحو
فكنحو
ولنحو
فلنحو
أنحو
أبنحو
أكنحو
ألنحو
أونحو
أفنحو
أوبنحو
أفبنحو
أوكنحو
أفكنحو
أولنحو
أفلنحو
نحوي
نحوك
نحوه
نحوكم
نحوكن
نحوها
نحوهم
نحوهن
نحونا
نحوكما
نحوهما
بنحوي
بنحوك
بنحوه
بنحوكم
بنحوكن
بنحوها
بنحوهم
بنحوهن
بنحونا
بنحوكما
بنحوهما
كنحوي
كنحوك
كنحوه
كنحوكم
كنحوكن
كنحوها
كنحوهم
كنحوهن
كنحونا
كنحوكما
كنحوهما
لنحوي
لنحوك
لنحوه
لنحوكم
لنحوكن
لنحوها
لنحوهم
لنحوهن
لنحونا
لنحوكما
لنحوهما
ونحوي
ونحوك
ونحوه
ونحوكم
ونحوكن
ونحوها
ونحوهم
ونحوهن
ونحونا
ونحوكما
ونحوهما
فنحوي
فنحوك
فنحوه
فنحوكم
فنحوكن
فنحوها
فنحوهم
فنحوهن
فنحونا
فنحوكما
فنحوهما
وبنحوي
وبنحوك
وبنحوه
وبنحوكم
وبنحوكن
وبنحوها
وبنحوهم
وبنحوهن
وبنحونا
وبنحوكما
وبنحوهما
فبنحوي
فبنحوك
فبنحوه
فبنحوكم
فبنحوكن
فبنحوها
فبنحوهم
فبنحوهن
فبنحونا
فبنحوكما
فبنحوهما
وكنحوي
وكنحوك
وكنحوه
وكنحوكم
وكنحوكن
وكنحوها
وكنحوهم
وكنحوهن
وكنحونا
وكنحوكما
وكنحوهما
فكنحوي
فكنحوك
فكنحوه
فكنحوكم
فكنحوكن
فكنحوها
فكنحوهم
فكنحوهن
فكنحونا
فكنحوكما
فكنحوهما
ولنحوي
ولنحوك
ولنحوه
ولنحوكم
ولنحوكن
ولنحوها
ولنحوهم
ولنحوهن
ولنحونا
ولنحوكما
ولنحوهما
فلنحوي
فلنحوك
فلنحوه
فلنحوكم
فلنحوكن
فلنحوها
فلنحوهم
فلنحوهن
فلنحونا
فلنحوكما
فلنحوهما
أنحوي
أنحوك
أنحوه
أنحوكم
أنحوكن
أنحوها
أنحوهم
أنحوهن
أنحونا
أنحوكما
أنحوهما
أبنحوي
أبنحوك
أبنحوه
أبنحوكم
أبنحوكن
أبنحوها
أبنحوهم
أبنحوهن
أبنحونا
أبنحوكما
أبنحوهما
أكنحوي
أكنحوك
أكنحوه
أكنحوكم
أكنحوكن
أكنحوها
أكنحوهم
أكنحوهن
أكنحونا
أكنحوكما
أكنحوهما
ألنحوي
ألنحوك
ألنحوه
ألنحوكم
ألنحوكن
ألنحوها
ألنحوهم
ألنحوهن
ألنحونا
ألنحوكما
ألنحوهما
أونحوي
أونحوك
أونحوه
أونحوكم
أونحوكن
أونحوها
أونحوهم
أونحوهن
أونحونا
أونحوكما
أونحوهما
أفنحوي
أفنحوك
أفنحوه
أفنحوكم
أفنحوكن
أفنحوها
أفنحوهم
أفنحوهن
أفنحونا
أفنحوكما
أفنحوهما
أوبنحوي
أوبنحوك
أوبنحوه
أوبنحوكم
أوبنحوكن
أوبنحوها
أوبنحوهم
أوبنحوهن
أوبنحونا
أوبنحوكما
أوبنحوهما
أفبنحوي
أفبنحوك
أفبنحوه
أفبنحوكم
أفبنحوكن
أفبنحوها
أفبنحوهم
أفبنحوهن
أفبنحونا
أفبنحوكما
أفبنحوهما
أوكنحوي
أوكنحوك
أوكنحوه
أوكنحوكم
أوكنحوكن
أوكنحوها
أوكنحوهم
أوكنحوهن
أوكنحونا
أوكنحوكما
أوكنحوهما
أفكنحوي
أفكنحوك
أفكنحوه
أفكنحوكم
أفكنحوكن
أفكنحوها
أفكنحوهم
أفكنحوهن
أفكنحونا
أفكنحوكما
أفكنحوهما
أولنحوي
أولنحوك
أولنحوه
أولنحوكم
أولنحوكن
أولنحوها
أولنحوهم
أولنحوهن
أولنحونا
أولنحوكما
أولنحوهما
أفلنحوي
أفلنحوك
أفلنحوه
أفلنحوكم
أفلنحوكن
أفلنحوها
أفلنحوهم
أفلنحوهن
أفلنحونا
أفلنحوكما
أفلنحوهما
أقل
بأقل
كأقل
لأقل
وأقل
فأقل
وبأقل
فبأقل
وكأقل
فكأقل
ولأقل
فلأقل
أقلي
أقلك
أقله
أقلكم
أقلكن
أقلها
أقلهم
أقلهن
أقلنا
أقلكما
أقلهما
بأقلي
بأقلك
بأقله
بأقلكم
بأقلكن
بأقلها
بأقلهم
بأقلهن
بأقلنا
بأقلكما
بأقلهما
كأقلي
كأقلك
كأقله
كأقلكم
كأقلكن
كأقلها
كأقلهم
كأقلهن
كأقلنا
كأقلكما
كأقلهما
لأقلي
لأقلك
لأقله
لأقلكم
لأقلكن
لأقلها
لأقلهم
لأقلهن
لأقلنا
لأقلكما
لأقلهما
وأقلي
وأقلك
وأقله
وأقلكم
وأقلكن
وأقلها
وأقلهم
وأقلهن
وأقلنا
وأقلكما
وأقلهما
فأقلي
فأقلك
فأقله
فأقلكم
فأقلكن
فأقلها
فأقلهم
فأقلهن
فأقلنا
فأقلكما
فأقلهما
وبأقلي
وبأقلك
وبأقله
وبأقلكم
وبأقلكن
وبأقلها
وبأقلهم
وبأقلهن
وبأقلنا
وبأقلكما
وبأقلهما
فبأقلي
فبأقلك
فبأقله
فبأقلكم
فبأقلكن
فبأقلها
فبأقلهم
فبأقلهن
فبأقلنا
فبأقلكما
فبأقلهما
وكأقلي
وكأقلك
وكأقله
وكأقلكم
وكأقلكن
وكأقلها
وكأقلهم
وكأقلهن
وكأقلنا
وكأقلكما
وكأقلهما
فكأقلي
فكأقلك
فكأقله
فكأقلكم
فكأقلكن
فكأقلها
فكأقلهم
فكأقلهن
فكأقلنا
فكأقلكما
فكأقلهما
ولأقلي
ولأقلك
ولأقله
ولأقلكم
ولأقلكن
ولأقلها
ولأقلهم
ولأقلهن
ولأقلنا
ولأقلكما
ولأقلهما
فلأقلي
فلأقلك
فلأقله
فلأقلكم
فلأقلكن
فلأقلها
فلأقلهم
فلأقلهن
فلأقلنا
فلأقلكما
فلأقلهما
أكثر
بأكثر
كأكثر
لأكثر
وأكثر
فأكثر
وبأكثر
فبأكثر
وكأكثر
فكأكثر
ولأكثر
فلأكثر
أكثري
أكثرك
أكثره
أكثركم
أكثركن
أكثرها
أكثرهم
أكثرهن
أكثرنا
أكثركما
أكثرهما
بأكثري
بأكثرك
بأكثره
بأكثركم
بأكثركن
بأكثرها
بأكثرهم
بأكثرهن
بأكثرنا
بأكثركما
بأكثرهما
كأكثري
كأكثرك
كأكثره
كأكثركم
كأكثركن
كأكثرها
كأكثرهم
كأكثرهن
كأكثرنا
كأكثركما
كأكثرهما
لأكثري
لأكثرك
لأكثره
لأكثركم
لأكثركن
لأكثرها
لأكثرهم
لأكثرهن
لأكثرنا
لأكثركما
لأكثرهما
وأكثري
وأكثرك
وأكثره
وأكثركم
وأكثركن
وأكثرها
وأكثرهم
وأكثرهن
وأكثرنا
وأكثركما
وأكثرهما
فأكثري
فأكثرك
فأكثره
فأكثركم
فأكثركن
فأكثرها
فأكثرهم
فأكثرهن
فأكثرنا
فأكثركما
فأكثرهما
وبأكثري
وبأكثرك
وبأكثره
وبأكثركم
وبأكثركن
وبأكثرها
وبأكثرهم
وبأكثرهن
وبأكثرنا
وبأكثركما
وبأكثرهما
فبأكثري
فبأكثرك
فبأكثره
فبأكثركم
فبأكثركن
فبأكثرها
فبأكثرهم
فبأكثرهن
فبأكثرنا
فبأكثركما
فبأكثرهما
وكأكثري
وكأكثرك
وكأكثره
وكأكثركم
وكأكثركن
وكأكثرها
وكأكثرهم
وكأكثرهن
وكأكثرنا
وكأكثركما
وكأكثرهما
فكأكثري
فكأكثرك
فكأكثره
فكأكثركم
فكأكثركن
فكأكثرها
فكأكثرهم
فكأكثرهن
فكأكثرنا
فكأكثركما
فكأكثرهما
ولأكثري
ولأكثرك
ولأكثره
ولأكثركم
ولأكثركن
ولأكثرها
ولأكثرهم
ولأكثرهن
ولأكثرنا
ولأكثركما
ولأكثرهما
فلأكثري
فلأكثرك
فلأكثره
فلأكثركم
فلأكثركن
فلأكثرها
فلأكثرهم
فلأكثرهن
فلأكثرنا
فلأكثركما
فلأكثرهما
آها
بس
حاي
صه
طاق
طق
عدس
كخ
نخ
هج
وا
واها
وي
آمين
وآمين
فآمين
آه
وآه
فآه
أف
وأف
فأف
أمامك
وأمامك
فأمامك
أوه
وأوه
فأوه
إليك
وإليك
فإليك
إليكم
وإليكم
فإليكم
إليكما
وإليكما
فإليكما
إليكن
وإليكن
فإليكن
إيه
وإيه
فإيه
بخ
وبخ
فبخ
وبس
فبس
بطآن
وبطآن
فبطآن
بله
وبله
فبله
حذار
وحذار
فحذار
حي
وحي
فحي
دونك
ودونك
فدونك
رويدك
ورويدك
فرويدك
سرعان
وسرعان
فسرعان
شتان
وشتان
فشتان
عليك
وعليك
فعليك
مكانك
ومكانك
فمكانك
مكانكم
ومكانكم
فمكانكم
مكانكما
ومكانكما
فمكانكما
مكانكن
ومكانكن
فمكانكن
مه
ها
هاؤم
وهاؤم
فهاؤم
هاك
هلم
هيا
هيت
وهيت
فهيت
هيهات
وراءك
وشكان
ويكأن
ويكأني
ويكأنك
ويكأنه
ويكأنكم
ويكأنكن
ويكأنها
ويكأنهم
ويكأنهن
ويكأننا
ويكأنكما
ويكأنهما
الألاء
بالألاء
كالألاء
للألاء
والألاء
فالألاء
وبالألاء
فبالألاء
وكالألاء
فكالألاء
وللألاء
فللألاء
الألى
بالألى
كالألى
للألى
والألى
فالألى
وبالألى
فبالألى
وكالألى
فكالألى
وللألى
فللألى
التي
بالتي
كالتي
للتي
والتي
فالتي
وبالتي
فبالتي
وكالتي
فكالتي
وللتي
فللتي
الذي
بالذي
كالذي
للذي
والذي
فالذي
وبالذي
فبالذي
وكالذي
فكالذي
وللذي
فللذي
الذين
بالذين
كالذين
للذين
والذين
فالذين
وبالذين
فبالذين
وكالذين
فكالذين
وللذين
فللذين
اللائي
باللائي
كاللائي
للائي
واللائي
فاللائي
وباللائي
فباللائي
وكاللائي
فكاللائي
وللائي
فللائي
اللاتي
باللاتي
كاللاتي
للاتي
واللاتي
فاللاتي
وباللاتي
فباللاتي
وكاللاتي
فكاللاتي
وللاتي
فللاتي
اللتان
واللتان
فاللتان
اللتيا
باللتيا
كاللتيا
للتيا
واللتيا
فاللتيا
وباللتيا
فباللتيا
وكاللتيا
فكاللتيا
وللتيا
فللتيا
اللتين
باللتين
كاللتين
للتين
واللتين
فاللتين
وباللتين
فباللتين
وكاللتين
فكاللتين
وللتين
فللتين
اللذان
واللذان
فاللذان
اللذين
باللذين
كاللذين
واللذين
فاللذين
وباللذين
فباللذين
وكاللذين
فكاللذين
اللواتي
باللواتي
كاللواتي
للواتي
واللواتي
فاللواتي
وباللواتي
فباللواتي
وكاللواتي
فكاللواتي
وللواتي
فللواتي
ذات
بذات
كذات
لذات
وذات
فذات
وبذات
فبذات
وكذات
فكذات
ولذات
فلذات
كمن
لمن
وكمن
فكمن
ولمن
فلمن
أمن
أبمن
أكمن
ألمن
أومن
أفمن
أوبمن
أفبمن
أوكمن
أفكمن
أولمن
أفلمن
أب
بأب
كأب
لأب
وأب
فأب
وبأب
فبأب
وكأب
فكأب
ولأب
فلأب
أبي
أبك
أبه
أبكم
أبكن
أبها
أبهم
أبهن
أبنا
أبكما
أبهما
الأب
بأبي
بأبك
بأبه
بأبكم
بأبكن
بأبها
بأبهم
بأبهن
بأبنا
بأبكما
بأبهما
البأب
كأبي
كأبك
كأبه
كأبكم
كأبكن
كأبها
كأبهم
كأبهن
كأبنا
كأبكما
كأبهما
الكأب
لأبي
لأبك
لأبه
لأبكم
لأبكن
لأبها
لأبهم
لأبهن
لأبنا
لأبكما
لأبهما
اللأب
وأبي
وأبك
وأبه
وأبكم
وأبكن
وأبها
وأبهم
وأبهن
وأبنا
وأبكما
وأبهما
الوأب
فأبي
فأبك
فأبه
فأبكم
فأبكن
فأبها
فأبهم
فأبهن
فأبنا
فأبكما
فأبهما
الفأب
وبأبي
وبأبك
وبأبه
وبأبكم
وبأبكن
وبأبها
وبأبهم
وبأبهن
وبأبنا
وبأبكما
وبأبهما
الوبأب
فبأبي
فبأبك
فبأبه
فبأبكم
فبأبكن
فبأبها
فبأبهم
فبأبهن
فبأبنا
فبأبكما
فبأبهما
الفبأب
وكأبي
وكأبك
وكأبه
وكأبكم
وكأبكن
وكأبها
وكأبهم
وكأبهن
وكأبنا
وكأبكما
وكأبهما
الوكأب
فكأبي
فكأبك
فكأبه
فكأبكم
فكأبكن
فكأبها
فكأبهم
فكأبهن
فكأبنا
فكأبكما
فكأبهما
الفكأب
ولأبي
ولأبك
ولأبه
ولأبكم
ولأبكن
ولأبها
ولأبهم
ولأبهن
ولأبنا
ولأبكما
ولأبهما
الولأب
فلأبي
فلأبك
فلأبه
فلأبكم
فلأبكن
فلأبها
فلأبهم
فلأبهن
فلأبنا
فلأبكما
فلأبهما
الفلأب
أخ
بأخ
كأخ
لأخ
وأخ
فأخ
وبأخ
فبأخ
وكأخ
فكأخ
ولأخ
فلأخ
أخي
أخك
أخه
أخكم
أخكن
أخها
أخهم
أخهن
أخنا
أخكما
أخهما
الأخ
بأخي
بأخك
بأخه
بأخكم
بأخكن
بأخها
بأخهم
بأخهن
بأخنا
بأخكما
بأخهما
البأخ
كأخي
كأخك
كأخه
كأخكم
كأخكن
كأخها
كأخهم
كأخهن
كأخنا
كأخكما
كأخهما
الكأخ
لأخي
لأخك
لأخه
لأخكم
لأخكن
لأخها
لأخهم
لأخهن
لأخنا
لأخكما
لأخهما
اللأخ
وأخي
وأخك
وأخه
وأخكم
وأخكن
وأخها
وأخهم
وأخهن
وأخنا
وأخكما
وأخهما
الوأخ
فأخي
فأخك
فأخه
فأخكم
فأخكن
فأخها
فأخهم
فأخهن
فأخنا
فأخكما
فأخهما
الفأخ
وبأخي
وبأخك
وبأخه
وبأخكم
وبأخكن
وبأخها
وبأخهم
وبأخهن
وبأخنا
وبأخكما
وبأخهما
الوبأخ
فبأخي
فبأخك
فبأخه
فبأخكم
فبأخكن
فبأخها
فبأخهم
فبأخهن
فبأخنا
فبأخكما
فبأخهما
الفبأخ
وكأخي
وكأخك
وكأخه
وكأخكم
وكأخكن
وكأخها
وكأخهم
وكأخهن
وكأخنا
وكأخكما
وكأخهما
الوكأخ
فكأخي
فكأخك
فكأخه
فكأخكم
فكأخكن
فكأخها
فكأخهم
فكأخهن
فكأخنا
فكأخكما
فكأخهما
الفكأخ
ولأخي
ولأخك
ولأخه
ولأخكم
ولأخكن
ولأخها
ولأخهم
ولأخهن
ولأخنا
ولأخكما
ولأخهما
الولأخ
فلأخي
فلأخك
فلأخه
فلأخكم
فلأخكن
فلأخها
فلأخهم
فلأخهن
فلأخنا
فلأخكما
فلأخهما
الفلأخ
حم
بحم
كحم
لحم
وحم
فحم
وبحم
فبحم
وكحم
فكحم
ولحم
فلحم
حمي
حمك
حمه
حمكم
حمكن
حمها
حمهم
حمهن
حمنا
حمكما
حمهما
الحم
بحمي
بحمك
بحمه
بحمكم
بحمكن
بحمها
بحمهم
بحمهن
بحمنا
بحمكما
بحمهما
البحم
كحمي
كحمك
كحمه
كحمكم
كحمكن
كحمها
كحمهم
كحمهن
كحمنا
كحمكما
كحمهما
الكحم
لحمي
لحمك
لحمه
لحمكم
لحمكن
لحمها
لحمهم
لحمهن
لحمنا
لحمكما
لحمهما
اللحم
وحمي
وحمك
وحمه
وحمكم
وحمكن
وحمها
وحمهم
وحمهن
وحمنا
وحمكما
وحمهما
الوحم
فحمي
فحمك
فحمه
فحمكم
فحمكن
فحمها
فحمهم
فحمهن
فحمنا
فحمكما
فحمهما
الفحم
وبحمي
وبحمك
وبحمه
وبحمكم
وبحمكن
وبحمها
وبحمهم
وبحمهن
وبحمنا
وبحمكما
وبحمهما
الوبحم
فبحمي
فبحمك
فبحمه
فبحمكم
فبحمكن
فبحمها
فبحمهم
فبحمهن
فبحمنا
فبحمكما
فبحمهما
الفبحم
وكحمي
وكحمك
وكحمه
وكحمكم
وكحمكن
وكحمها
وكحمهم
وكحمهن
وكحمنا
وكحمكما
وكحمهما
الوكحم
فكحمي
فكحمك
فكحمه
فكحمكم
فكحمكن
فكحمها
فكحمهم
فكحمهن
فكحمنا
فكحمكما
فكحمهما
الفكحم
ولحمي
ولحمك
ولحمه
ولحمكم
ولحمكن
ولحمها
ولحمهم
ولحمهن
ولحمنا
ولحمكما
ولحمهما
الولحم
فلحمي
فلحمك
فلحمه
فلحمكم
فلحمكن
فلحمها
فلحمهم
فلحمهن
فلحمنا
فلحمكما
فلحمهما
الفلحم
ذو
وذو
فذو
فو
بفو
كفو
لفو
وفو
ففو
وبفو
فبفو
وكفو
فكفو
ولفو
فلفو
فوي
فوك
فوه
فوكم
فوكن
فوها
فوهم
فوهن
فونا
فوكما
فوهما
الفو
بفوي
بفوك
بفوه
بفوكم
بفوكن
بفوها
بفوهم
بفوهن
بفونا
بفوكما
بفوهما
البفو
كفوي
كفوك
كفوه
كفوكم
كفوكن
كفوها
كفوهم
كفوهن
كفونا
كفوكما
كفوهما
الكفو
لفوي
لفوك
لفوه
لفوكم
لفوكن
لفوها
لفوهم
لفوهن
لفونا
لفوكما
لفوهما
اللفو
وفوي
وفوك
وفوه
وفوكم
وفوكن
وفوها
وفوهم
وفوهن
وفونا
وفوكما
وفوهما
الوفو
ففوي
ففوك
ففوه
ففوكم
ففوكن
ففوها
ففوهم
ففوهن
ففونا
ففوكما
ففوهما
الففو
وبفوي
وبفوك
وبفوه
وبفوكم
وبفوكن
وبفوها
وبفوهم
وبفوهن
وبفونا
وبفوكما
وبفوهما
الوبفو
فبفوي
فبفوك
فبفوه
فبفوكم
فبفوكن
فبفوها
فبفوهم
فبفوهن
فبفونا
فبفوكما
فبفوهما
الفبفو
وكفوي
وكفوك
وكفوه
وكفوكم
وكفوكن
وكفوها
وكفوهم
وكفوهن
وكفونا
وكفوكما
وكفوهما
الوكفو
فكفوي
فكفوك
فكفوه
فكفوكم
فكفوكن
فكفوها
فكفوهم
فكفوهن
فكفونا
فكفوكما
فكفوهما
الفكفو
ولفوي
ولفوك
ولفوه
ولفوكم
ولفوكن
ولفوها
ولفوهم
ولفوهن
ولفونا
ولفوكما
ولفوهما
الولفو
فلفوي
فلفوك
فلفوه
فلفوكم
فلفوكن
فلفوها
فلفوهم
فلفوهن
فلفونا
فلفوكما
فلفوهما
الفلفو
لن
ولن
فلن
ألن
أولن
أفلن
لو
ولو
فلو
ألو
أولو
أفلو
لولا
ولولا
فلولا
لوما
ولوما
فلوما
نعم
ونعم
فنعم
بئس
وبئس
فبئس
حبذا
وحبذا
فحبذا
ساء
وساء
فساء
ساءما
وساءما
فساءما
نعما
ونعما
فنعما
إن
بإن
كإن
لإن
وإن
فإن
وبإن
فبإن
وكإن
فكإن
ولإن
فلإن
لات
ولات
فلات
لا
ولا
فلا
ألا
أولا
أفلا
أن
بأن
كأن
لأن
وأن
فأن
وبأن
فبأن
وكأن
فكأن
ولأن
فلأن
أني
أنك
أنه
أنكم
أنكن
أنها
أنهم
أنهن
أننا
أنكما
أنهما
بأني
بأنك
بأنه
بأنكم
بأنكن
بأنها
بأنهم
بأنهن
بأننا
بأنكما
بأنهما
كأني
كأنك
كأنه
كأنكم
كأنكن
كأنها
كأنهم
كأنهن
كأننا
كأنكما
كأنهما
لأني
لأنك
لأنه
لأنكم
لأنكن
لأنها
لأنهم
لأنهن
لأننا
لأنكما
لأنهما
وأني
وأنك
وأنه
وأنكم
وأنكن
وأنها
وأنهم
وأنهن
وأننا
وأنكما
وأنهما
فأني
فأنك
فأنه
فأنكم
فأنكن
فأنها
فأنهم
فأنهن
فأننا
فأنكما
فأنهما
وبأني
وبأنك
وبأنه
وبأنكم
وبأنكن
وبأنها
وبأنهم
وبأنهن
وبأننا
وبأنكما
وبأنهما
فبأني
فبأنك
فبأنه
فبأنكم
فبأنكن
فبأنها
فبأنهم
فبأنهن
فبأننا
فبأنكما
فبأنهما
وكأني
وكأنك
وكأنه
وكأنكم
وكأنكن
وكأنها
وكأنهم
وكأنهن
وكأننا
وكأنكما
وكأنهما
فكأني
فكأنك
فكأنه
فكأنكم
فكأنكن
فكأنها
فكأنهم
فكأنهن
فكأننا
فكأنكما
فكأنهما
ولأني
ولأنك
ولأنه
ولأنكم
ولأنكن
ولأنها
ولأنهم
ولأنهن
ولأننا
ولأنكما
ولأنهما
فلأني
فلأنك
فلأنه
فلأنكم
فلأنكن
فلأنها
فلأنهم
فلأنهن
فلأننا
فلأنكما
فلأنهما
أإن
أبإن
أكإن
ألإن
أوإن
أفإن
أوبإن
أفبإن
أوكإن
أفكإن
أولإن
أفلإن
إني
إنك
إنه
إنكم
إنكن
إنها
إنهم
إنهن
إننا
إنكما
إنهما
بإني
بإنك
بإنه
بإنكم
بإنكن
بإنها
بإنهم
بإنهن
بإننا
بإنكما
بإنهما
كإني
كإنك
كإنه
كإنكم
كإنكن
كإنها
كإنهم
كإنهن
كإننا
كإنكما
كإنهما
لإني
لإنك
لإنه
لإنكم
لإنكن
لإنها
لإنهم
لإنهن
لإننا
لإنكما
لإنهما
وإني
وإنك
وإنه
وإنكم
وإنكن
وإنها
وإنهم
وإنهن
وإننا
وإنكما
وإنهما
فإني
فإنك
فإنه
فإنكم
فإنكن
فإنها
فإنهم
فإنهن
فإننا
فإنكما
فإنهما
وبإني
وبإنك
وبإنه
وبإنكم
وبإنكن
وبإنها
وبإنهم
وبإنهن
وبإننا
وبإنكما
وبإنهما
فبإني
فبإنك
فبإنه
فبإنكم
فبإنكن
فبإنها
فبإنهم
فبإنهن
فبإننا
فبإنكما
فبإنهما
وكإني
وكإنك
وكإنه
وكإنكم
وكإنكن
وكإنها
وكإنهم
وكإنهن
وكإننا
وكإنكما
وكإنهما
فكإني
فكإنك
فكإنه
فكإنكم
فكإنكن
فكإنها
فكإنهم
فكإنهن
فكإننا
فكإنكما
فكإنهما
ولإني
ولإنك
ولإنه
ولإنكم
ولإنكن
ولإنها
ولإنهم
ولإنهن
ولإننا
ولإنكما
ولإنهما
فلإني
فلإنك
فلإنه
فلإنكم
فلإنكن
فلإنها
فلإنهم
فلإنهن
فلإننا
فلإنكما
فلإنهما
أإني
أإنك
أإنه
أإنكم
أإنكن
أإنها
أإنهم
أإنهن
أإننا
أإنكما
أإنهما
أبإني
أبإنك
أبإنه
أبإنكم
أبإنكن
أبإنها
أبإنهم
أبإنهن
أبإننا
أبإنكما
أبإنهما
أكإني
أكإنك
أكإنه
أكإنكم
أكإنكن
أكإنها
أكإنهم
أكإنهن
أكإننا
أكإنكما
أكإنهما
ألإني
ألإنك
ألإنه
ألإنكم
ألإنكن
ألإنها
ألإنهم
ألإنهن
ألإننا
ألإنكما
ألإنهما
أوإني
أوإنك
أوإنه
أوإنكم
أوإنكن
أوإنها
أوإنهم
أوإنهن
أوإننا
أوإنكما
أوإنهما
أفإني
أفإنك
أفإنه
أفإنكم
أفإنكن
أفإنها
أفإنهم
أفإنهن
أفإننا
أفإنكما
أفإنهما
أوبإني
أوبإنك
أوبإنه
أوبإنكم
أوبإنكن
أوبإنها
أوبإنهم
أوبإنهن
أوبإننا
أوبإنكما
أوبإنهما
أفبإني
أفبإنك
أفبإنه
أفبإنكم
أفبإنكن
أفبإنها
أفبإنهم
أفبإنهن
أفبإننا
أفبإنكما
أفبإنهما
أوكإني
أوكإنك
أوكإنه
أوكإنكم
أوكإنكن
أوكإنها
أوكإنهم
أوكإنهن
أوكإننا
أوكإنكما
أوكإنهما
أفكإني
أفكإنك
أفكإنه
أفكإنكم
أفكإنكن
أفكإنها
أفكإنهم
أفكإنهن
أفكإننا
أفكإنكما
أفكإنهما
أولإني
أولإنك
أولإنه
أولإنكم
أولإنكن
أولإنها
أولإنهم
أولإنهن
أولإننا
أولإنكما
أولإنهما
أفلإني
أفلإنك
أفلإنه
أفلإنكم
أفلإنكن
أفلإنها
أفلإنهم
أفلإنهن
أفلإننا
أفلإنكما
أفلإنهما
عل
وعل
فعل
لكأن
ولكأن
فلكأن
أكأن
ألكأن
أوكأن
أفكأن
أولكأن
أفلكأن
لكأني
لكأنك
لكأنه
لكأنكم
لكأنكن
لكأنها
لكأنهم
لكأنهن
لكأننا
لكأنكما
لكأنهما
ولكأني
ولكأنك
ولكأنه
ولكأنكم
ولكأنكن
ولكأنها
ولكأنهم
ولكأنهن
ولكأننا
ولكأنكما
ولكأنهما
فلكأني
فلكأنك
فلكأنه
فلكأنكم
فلكأنكن
فلكأنها
فلكأنهم
فلكأنهن
فلكأننا
فلكأنكما
فلكأنهما
أكأني
أكأنك
أكأنه
أكأنكم
أكأنكن
أكأنها
أكأنهم
أكأنهن
أكأننا
أكأنكما
أكأنهما
ألكأني
ألكأنك
ألكأنه
ألكأنكم
ألكأنكن
ألكأنها
ألكأنهم
ألكأنهن
ألكأننا
ألكأنكما
ألكأنهما
أوكأني
أوكأنك
أوكأنه
أوكأنكم
أوكأنكن
أوكأنها
أوكأنهم
أوكأنهن
أوكأننا
أوكأنكما
أوكأنهما
أفكأني
أفكأنك
أفكأنه
أفكأنكم
أفكأنكن
أفكأنها
أفكأنهم
أفكأنهن
أفكأننا
أفكأنكما
أفكأنهما
أولكأني
أولكأنك
أولكأنه
أولكأنكم
أولكأنكن
أولكأنها
أولكأنهم
أولكأنهن
أولكأننا
أولكأنكما
أولكأنهما
أفلكأني
أفلكأنك
أفلكأنه
أفلكأنكم
أفلكأنكن
أفلكأنها
أفلكأنهم
أفلكأنهن
أفلكأننا
أفلكأنكما
أفلكأنهما
لعل
ولعل
فلعل
ألعل
أولعل
أفلعل
لعلي
لعلك
لعله
لعلكم
لعلكن
لعلها
لعلهم
لعلهن
لعلنا
لعلكما
لعلهما
ولعلي
ولعلك
ولعله
ولعلكم
ولعلكن
ولعلها
ولعلهم
ولعلهن
ولعلنا
ولعلكما
ولعلهما
فلعلي
فلعلك
فلعله
فلعلكم
فلعلكن
فلعلها
فلعلهم
فلعلهن
فلعلنا
فلعلكما
فلعلهما
ألعلي
ألعلك
ألعله
ألعلكم
ألعلكن
ألعلها
ألعلهم
ألعلهن
ألعلنا
ألعلكما
ألعلهما
أولعلي
أولعلك
أولعله
أولعلكم
أولعلكن
أولعلها
أولعلهم
أولعلهن
أولعلنا
أولعلكما
أولعلهما
أفلعلي
أفلعلك
أفلعله
أفلعلكم
أفلعلكن
أفلعلها
أفلعلهم
أفلعلهن
أفلعلنا
أفلعلكما
أفلعلهما
لكن
ولكن
فلكن
لكني
لكنك
لكنه
لكنكم
لكنكن
لكنها
لكنهم
لكنهن
لكننا
لكنكما
لكنهما
ولكني
ولكنك
ولكنه
ولكنكم
ولكنكن
ولكنها
ولكنهم
ولكنهن
ولكننا
ولكنكما
ولكنهما
فلكني
فلكنك
فلكنه
فلكنكم
فلكنكن
فلكنها
فلكنهم
فلكنهن
فلكننا
فلكنكما
فلكنهما
ليت
وليت
فليت
ليتي
ليتك
ليته
ليتكم
ليتكن
ليتها
ليتهم
ليتهن
ليتنا
ليتكما
ليتهما
وليتي
وليتك
وليته
وليتكم
وليتكن
وليتها
وليتهم
وليتهن
وليتنا
وليتكما
وليتهما
فليتي
فليتك
فليته
فليتكم
فليتكن
فليتها
فليتهم
فليتهن
فليتنا
فليتكما
فليتهما
آي
كي
وكي
فكي
أجمع
بأجمع
كأجمع
لأجمع
وأجمع
فأجمع
وبأجمع
فبأجمع
وكأجمع
فكأجمع
ولأجمع
فلأجمع
أأجمع
أبأجمع
أكأجمع
ألأجمع
أوأجمع
أفأجمع
أوبأجمع
أفبأجمع
أوكأجمع
أفكأجمع
أولأجمع
أفلأجمع
أجمعي
أجمعك
أجمعه
أجمعكم
أجمعكن
أجمعها
أجمعهم
أجمعهن
أجمعنا
أجمعكما
أجمعهما
بأجمعي
بأجمعك
بأجمعه
بأجمعكم
بأجمعكن
بأجمعها
بأجمعهم
بأجمعهن
بأجمعنا
بأجمعكما
بأجمعهما
كأجمعي
كأجمعك
كأجمعه
كأجمعكم
كأجمعكن
كأجمعها
كأجمعهم
كأجمعهن
كأجمعنا
كأجمعكما
كأجمعهما
لأجمعي
لأجمعك
لأجمعه
لأجمعكم
لأجمعكن
لأجمعها
لأجمعهم
لأجمعهن
لأجمعنا
لأجمعكما
لأجمعهما
وأجمعي
وأجمعك
وأجمعه
وأجمعكم
وأجمعكن
وأجمعها
وأجمعهم
وأجمعهن
وأجمعنا
وأجمعكما
وأجمعهما
فأجمعي
فأجمعك
فأجمعه
فأجمعكم
فأجمعكن
فأجمعها
فأجمعهم
فأجمعهن
فأجمعنا
فأجمعكما
فأجمعهما
وبأجمعي
وبأجمعك
وبأجمعه
وبأجمعكم
وبأجمعكن
وبأجمعها
وبأجمعهم
وبأجمعهن
وبأجمعنا
وبأجمعكما
وبأجمعهما
فبأجمعي
فبأجمعك
فبأجمعه
فبأجمعكم
فبأجمعكن
فبأجمعها
فبأجمعهم
فبأجمعهن
فبأجمعنا
فبأجمعكما
فبأجمعهما
وكأجمعي
وكأجمعك
وكأجمعه
وكأجمعكم
وكأجمعكن
وكأجمعها
وكأجمعهم
وكأجمعهن
وكأجمعنا
وكأجمعكما
وكأجمعهما
فكأجمعي
فكأجمعك
فكأجمعه
فكأجمعكم
فكأجمعكن
فكأجمعها
فكأجمعهم
فكأجمعهن
فكأجمعنا
فكأجمعكما
فكأجمعهما
ولأجمعي
ولأجمعك
ولأجمعه
ولأجمعكم
ولأجمعكن
ولأجمعها
ولأجمعهم
ولأجمعهن
ولأجمعنا
ولأجمعكما
ولأجمعهما
فلأجمعي
فلأجمعك
فلأجمعه
فلأجمعكم
فلأجمعكن
فلأجمعها
فلأجمعهم
فلأجمعهن
فلأجمعنا
فلأجمعكما
فلأجمعهما
أأجمعي
أأجمعك
أأجمعه
أأجمعكم
أأجمعكن
أأجمعها
أأجمعهم
أأجمعهن
أأجمعنا
أأجمعكما
أأجمعهما
أبأجمعي
أبأجمعك
أبأجمعه
أبأجمعكم
أبأجمعكن
أبأجمعها
أبأجمعهم
أبأجمعهن
أبأجمعنا
أبأجمعكما
أبأجمعهما
أكأجمعي
أكأجمعك
أكأجمعه
أكأجمعكم
أكأجمعكن
أكأجمعها
أكأجمعهم
أكأجمعهن
أكأجمعنا
أكأجمعكما
أكأجمعهما
ألأجمعي
ألأجمعك
ألأجمعه
ألأجمعكم
ألأجمعكن
ألأجمعها
ألأجمعهم
ألأجمعهن
ألأجمعنا
ألأجمعكما
ألأجمعهما
أوأجمعي
أوأجمعك
أوأجمعه
أوأجمعكم
أوأجمعكن
أوأجمعها
أوأجمعهم
أوأجمعهن
أوأجمعنا
أوأجمعكما
أوأجمعهما
أفأجمعي
أفأجمعك
أفأجمعه
أفأجمعكم
أفأجمعكن
أفأجمعها
أفأجمعهم
أفأجمعهن
أفأجمعنا
أفأجمعكما
أفأجمعهما
أوبأجمعي
أوبأجمعك
أوبأجمعه
أوبأجمعكم
أوبأجمعكن
أوبأجمعها
أوبأجمعهم
أوبأجمعهن
أوبأجمعنا
أوبأجمعكما
أوبأجمعهما
أفبأجمعي
أفبأجمعك
أفبأجمعه
أفبأجمعكم
أفبأجمعكن
أفبأجمعها
أفبأجمعهم
أفبأجمعهن
أفبأجمعنا
أفبأجمعكما
أفبأجمعهما
أوكأجمعي
أوكأجمعك
أوكأجمعه
أوكأجمعكم
أوكأجمعكن
أوكأجمعها
أوكأجمعهم
أوكأجمعهن
أوكأجمعنا
أوكأجمعكما
أوكأجمعهما
أفكأجمعي
أفكأجمعك
أفكأجمعه
أفكأجمعكم
أفكأجمعكن
أفكأجمعها
أفكأجمعهم
أفكأجمعهن
أفكأجمعنا
أفكأجمعكما
أفكأجمعهما
أولأجمعي
أولأجمعك
أولأجمعه
أولأجمعكم
أولأجمعكن
أولأجمعها
أولأجمعهم
أولأجمعهن
أولأجمعنا
أولأجمعكما
أولأجمعهما
أفلأجمعي
أفلأجمعك
أفلأجمعه
أفلأجمعكم
أفلأجمعكن
أفلأجمعها
أفلأجمعهم
أفلأجمعهن
أفلأجمعنا
أفلأجمعكما
أفلأجمعهما
عامة
بعامة
كعامة
لعامة
وعامة
فعامة
وبعامة
فبعامة
وكعامة
فكعامة
ولعامة
فلعامة
أعامة
أبعامة
أكعامة
ألعامة
أوعامة
أفعامة
أوبعامة
أفبعامة
أوكعامة
أفكعامة
أولعامة
أفلعامة
عامتي
عامتك
عامته
عامتكم
عامتكن
عامتها
عامتهم
عامتهن
عامتنا
عامتكما
عامتهما
بعامتي
بعامتك
بعامته
بعامتكم
بعامتكن
بعامتها
بعامتهم
بعامتهن
بعامتنا
بعامتكما
بعامتهما
كعامتي
كعامتك
كعامته
كعامتكم
كعامتكن
كعامتها
كعامتهم
كعامتهن
كعامتنا
كعامتكما
كعامتهما
لعامتي
لعامتك
لعامته
لعامتكم
لعامتكن
لعامتها
لعامتهم
لعامتهن
لعامتنا
لعامتكما
لعامتهما
وعامتي
وعامتك
وعامته
وعامتكم
وعامتكن
وعامتها
وعامتهم
وعامتهن
وعامتنا
وعامتكما
وعامتهما
فعامتي
فعامتك
فعامته
فعامتكم
فعامتكن
فعامتها
فعامتهم
فعامتهن
فعامتنا
فعامتكما
فعامتهما
وبعامتي
وبعامتك
وبعامته
وبعامتكم
وبعامتكن
وبعامتها
وبعامتهم
وبعامتهن
وبعامتنا
وبعامتكما
وبعامتهما
فبعامتي
فبعامتك
فبعامته
فبعامتكم
فبعامتكن
فبعامتها
فبعامتهم
فبعامتهن
فبعامتنا
فبعامتكما
فبعامتهما
وكعامتي
وكعامتك
وكعامته
وكعامتكم
وكعامتكن
وكعامتها
وكعامتهم
وكعامتهن
وكعامتنا
وكعامتكما
وكعامتهما
فكعامتي
فكعامتك
فكعامته
فكعامتكم
فكعامتكن
فكعامتها
فكعامتهم
فكعامتهن
فكعامتنا
فكعامتكما
فكعامتهما
ولعامتي
ولعامتك
ولعامته
ولعامتكم
ولعامتكن
ولعامتها
ولعامتهم
ولعامتهن
ولعامتنا
ولعامتكما
ولعامتهما
فلعامتي
فلعامتك
فلعامته
فلعامتكم
فلعامتكن
فلعامتها
فلعامتهم
فلعامتهن
فلعامتنا
فلعامتكما
فلعامتهما
أعامتي
أعامتك
أعامته
أعامتكم
أعامتكن
أعامتها
أعامتهم
أعامتهن
أعامتنا
أعامتكما
أعامتهما
أبعامتي
أبعامتك
أبعامته
أبعامتكم
أبعامتكن
أبعامتها
أبعامتهم
أبعامتهن
أبعامتنا
أبعامتكما
أبعامتهما
أكعامتي
أكعامتك
أكعامته
أكعامتكم
أكعامتكن
أكعامتها
أكعامتهم
أكعامتهن
أكعامتنا
أكعامتكما
أكعامتهما
ألعامتي
ألعامتك
ألعامته
ألعامتكم
ألعامتكن
ألعامتها
ألعامتهم
ألعامتهن
ألعامتنا
ألعامتكما
ألعامتهما
أوعامتي
أوعامتك
أوعامته
أوعامتكم
أوعامتكن
أوعامتها
أوعامتهم
أوعامتهن
أوعامتنا
أوعامتكما
أوعامتهما
أفعامتي
أفعامتك
أفعامته
أفعامتكم
أفعامتكن
أفعامتها
أفعامتهم
أفعامتهن
أفعامتنا
أفعامتكما
أفعامتهما
أوبعامتي
أوبعامتك
أوبعامته
أوبعامتكم
أوبعامتكن
أوبعامتها
أوبعامتهم
أوبعامتهن
أوبعامتنا
أوبعامتكما
أوبعامتهما
أفبعامتي
أفبعامتك
أفبعامته
أفبعامتكم
أفبعامتكن
أفبعامتها
أفبعامتهم
أفبعامتهن
أفبعامتنا
أفبعامتكما
أفبعامتهما
أوكعامتي
أوكعامتك
أوكعامته
أوكعامتكم
أوكعامتكن
أوكعامتها
أوكعامتهم
أوكعامتهن
أوكعامتنا
أوكعامتكما
أوكعامتهما
أفكعامتي
أفكعامتك
أفكعامته
أفكعامتكم
أفكعامتكن
أفكعامتها
أفكعامتهم
أفكعامتهن
أفكعامتنا
أفكعامتكما
أفكعامتهما
أولعامتي
أولعامتك
أولعامته
أولعامتكم
أولعامتكن
أولعامتها
أولعامتهم
أولعامتهن
أولعامتنا
أولعامتكما
أولعامتهما
أفلعامتي
أفلعامتك
أفلعامته
أفلعامتكم
أفلعامتكن
أفلعامتها
أفلعامتهم
أفلعامتهن
أفلعامتنا
أفلعامتكما
أفلعامتهما
عين
بعين
كعين
لعين
وعين
فعين
وبعين
فبعين
وكعين
فكعين
ولعين
فلعين
أعين
أبعين
أكعين
ألعين
أوعين
أفعين
أوبعين
أفبعين
أوكعين
أفكعين
أولعين
أفلعين
عيني
عينك
عينه
عينكم
عينكن
عينها
عينهم
عينهن
عيننا
عينكما
عينهما
بعيني
بعينك
بعينه
بعينكم
بعينكن
بعينها
بعينهم
بعينهن
بعيننا
بعينكما
بعينهما
كعيني
كعينك
كعينه
كعينكم
كعينكن
كعينها
كعينهم
كعينهن
كعيننا
كعينكما
كعينهما
لعيني
لعينك
لعينه
لعينكم
لعينكن
لعينها
لعينهم
لعينهن
لعيننا
لعينكما
لعينهما
وعيني
وعينك
وعينه
وعينكم
وعينكن
وعينها
وعينهم
وعينهن
وعيننا
وعينكما
وعينهما
فعيني
فعينك
فعينه
فعينكم
فعينكن
فعينها
فعينهم
فعينهن
فعيننا
فعينكما
فعينهما
وبعيني
وبعينك
وبعينه
وبعينكم
وبعينكن
وبعينها
وبعينهم
وبعينهن
وبعيننا
وبعينكما
وبعينهما
فبعيني
فبعينك
فبعينه
فبعينكم
فبعينكن
فبعينها
فبعينهم
فبعينهن
فبعيننا
فبعينكما
فبعينهما
وكعيني
وكعينك
وكعينه
وكعينكم
وكعينكن
وكعينها
وكعينهم
وكعينهن
وكعيننا
وكعينكما
وكعينهما
فكعيني
فكعينك
فكعينه
فكعينكم
فكعينكن
فكعينها
فكعينهم
فكعينهن
فكعيننا
فكعينكما
فكعينهما
ولعيني
ولعينك
ولعينه
ولعينكم
ولعينكن
ولعينها
ولعينهم
ولعينهن
ولعيننا
ولعينكما
ولعينهما
فلعيني
فلعينك
فلعينه
فلعينكم
فلعينكن
فلعينها
فلعينهم
فلعينهن
فلعيننا
فلعينكما
فلعينهما
أعيني
أعينك
أعينه
أعينكم
أعينكن
أعينها
أعينهم
أعينهن
أعيننا
أعينكما
أعينهما
أبعيني
أبعينك
أبعينه
أبعينكم
أبعينكن
أبعينها
أبعينهم
أبعينهن
أبعيننا
أبعينكما
أبعينهما
أكعيني
أكعينك
أكعينه
أكعينكم
أكعينكن
أكعينها
أكعينهم
أكعينهن
أكعيننا
أكعينكما
أكعينهما
ألعيني
ألعينك
ألعينه
ألعينكم
ألعينكن
ألعينها
ألعينهم
ألعينهن
ألعيننا
ألعينكما
ألعينهما
أوعيني
أوعينك
أوعينه
أوعينكم
أوعينكن
أوعينها
أوعينهم
أوعينهن
أوعيننا
أوعينكما
أوعينهما
أفعيني
أفعينك
أفعينه
أفعينكم
أفعينكن
أفعينها
أفعينهم
أفعينهن
أفعيننا
أفعينكما
أفعينهما
أوبعيني
أوبعينك
أوبعينه
أوبعينكم
أوبعينكن
أوبعينها
أوبعينهم
أوبعينهن
أوبعيننا
أوبعينكما
أوبعينهما
أفبعيني
أفبعينك
أفبعينه
أفبعينكم
أفبعينكن
أفبعينها
أفبعينهم
أفبعينهن
أفبعيننا
أفبعينكما
أفبعينهما
أوكعيني
أوكعينك
أوكعينه
أوكعينكم
أوكعينكن
أوكعينها
أوكعينهم
أوكعينهن
أوكعيننا
أوكعينكما
أوكعينهما
أفكعيني
أفكعينك
أفكعينه
أفكعينكم
أفكعينكن
أفكعينها
أفكعينهم
أفكعينهن
أفكعيننا
أفكعينكما
أفكعينهما
أولعيني
أولعينك
أولعينه
أولعينكم
أولعينكن
أولعينها
أولعينهم
أولعينهن
أولعيننا
أولعينكما
أولعينهما
أفلعيني
أفلعينك
أفلعينه
أفلعينكم
أفلعينكن
أفلعينها
أفلعينهم
أفلعينهن
أفلعيننا
أفلعينكما
أفلعينهما
كلا
وكلا
فكلا
أكلا
أوكلا
أفكلا
كلاهما
وكلاهما
فكلاهما
أكلاهما
أوكلاهما
أفكلاهما
كلتا
بكلتا
ككلتا
لكلتا
وكلتا
فكلتا
وبكلتا
فبكلتا
وككلتا
فككلتا
ولكلتا
فلكلتا
أكلتا
أبكلتا
أككلتا
ألكلتا
أوكلتا
أفكلتا
أوبكلتا
أفبكلتا
أوككلتا
أفككلتا
أولكلتا
أفلكلتا
كليكما
بكليكما
ككليكما
لكليكما
وكليكما
فكليكما
وبكليكما
فبكليكما
وككليكما
فككليكما
ولكليكما
فلكليكما
أكليكما
أبكليكما
أككليكما
ألكليكما
أوكليكما
أفكليكما
أوبكليكما
أفبكليكما
أوككليكما
أفككليكما
أولكليكما
أفلكليكما
كليهما
بكليهما
ككليهما
لكليهما
وكليهما
فكليهما
وبكليهما
فبكليهما
وككليهما
فككليهما
ولكليهما
فلكليهما
أكليهما
أبكليهما
أككليهما
ألكليهما
أوكليهما
أفكليهما
أوبكليهما
أفبكليهما
أوككليهما
أفككليهما
أولكليهما
أفلكليهما
نفس
بنفس
كنفس
لنفس
ونفس
فنفس
وبنفس
فبنفس
وكنفس
فكنفس
ولنفس
فلنفس
أنفس
أبنفس
أكنفس
ألنفس
أونفس
أفنفس
أوبنفس
أفبنفس
أوكنفس
أفكنفس
أولنفس
أفلنفس
نفسي
نفسك
نفسه
نفسكم
نفسكن
نفسها
نفسهم
نفسهن
نفسنا
نفسكما
نفسهما
بنفسي
بنفسك
بنفسه
بنفسكم
بنفسكن
بنفسها
بنفسهم
بنفسهن
بنفسنا
بنفسكما
بنفسهما
كنفسي
كنفسك
كنفسه
كنفسكم
كنفسكن
كنفسها
كنفسهم
كنفسهن
كنفسنا
كنفسكما
كنفسهما
لنفسي
لنفسك
لنفسه
لنفسكم
لنفسكن
لنفسها
لنفسهم
لنفسهن
لنفسنا
لنفسكما
لنفسهما
ونفسي
ونفسك
ونفسه
ونفسكم
ونفسكن
ونفسها
ونفسهم
ونفسهن
ونفسنا
ونفسكما
ونفسهما
فنفسي
فنفسك
فنفسه
فنفسكم
فنفسكن
فنفسها
فنفسهم
فنفسهن
فنفسنا
فنفسكما
فنفسهما
وبنفسي
وبنفسك
وبنفسه
وبنفسكم
وبنفسكن
وبنفسها
وبنفسهم
وبنفسهن
وبنفسنا
وبنفسكما
وبنفسهما
فبنفسي
فبنفسك
فبنفسه
فبنفسكم
فبنفسكن
فبنفسها
فبنفسهم
فبنفسهن
فبنفسنا
فبنفسكما
فبنفسهما
وكنفسي
وكنفسك
وكنفسه
وكنفسكم
وكنفسكن
وكنفسها
وكنفسهم
وكنفسهن
وكنفسنا
وكنفسكما
وكنفسهما
فكنفسي
فكنفسك
فكنفسه
فكنفسكم
فكنفسكن
فكنفسها
فكنفسهم
فكنفسهن
فكنفسنا
فكنفسكما
فكنفسهما
ولنفسي
ولنفسك
ولنفسه
ولنفسكم
ولنفسكن
ولنفسها
ولنفسهم
ولنفسهن
ولنفسنا
ولنفسكما
ولنفسهما
فلنفسي
فلنفسك
فلنفسه
فلنفسكم
فلنفسكن
فلنفسها
فلنفسهم
فلنفسهن
فلنفسنا
فلنفسكما
فلنفسهما
أنفسي
أنفسك
أنفسه
أنفسكم
أنفسكن
أنفسها
أنفسهم
أنفسهن
أنفسنا
أنفسكما
أنفسهما
أبنفسي
أبنفسك
أبنفسه
أبنفسكم
أبنفسكن
أبنفسها
أبنفسهم
أبنفسهن
أبنفسنا
أبنفسكما
أبنفسهما
أكنفسي
أكنفسك
أكنفسه
أكنفسكم
أكنفسكن
أكنفسها
أكنفسهم
أكنفسهن
أكنفسنا
أكنفسكما
أكنفسهما
ألنفسي
ألنفسك
ألنفسه
ألنفسكم
ألنفسكن
ألنفسها
ألنفسهم
ألنفسهن
ألنفسنا
ألنفسكما
ألنفسهما
أونفسي
أونفسك
أونفسه
أونفسكم
أونفسكن
أونفسها
أونفسهم
أونفسهن
أونفسنا
أونفسكما
أونفسهما
أفنفسي
أفنفسك
أفنفسه
أفنفسكم
أفنفسكن
أفنفسها
أفنفسهم
أفنفسهن
أفنفسنا
أفنفسكما
أفنفسهما
أوبنفسي
أوبنفسك
أوبنفسه
أوبنفسكم
أوبنفسكن
أوبنفسها
أوبنفسهم
أوبنفسهن
أوبنفسنا
أوبنفسكما
أوبنفسهما
أفبنفسي
أفبنفسك
أفبنفسه
أفبنفسكم
أفبنفسكن
أفبنفسها
أفبنفسهم
أفبنفسهن
أفبنفسنا
أفبنفسكما
أفبنفسهما
أوكنفسي
أوكنفسك
أوكنفسه
أوكنفسكم
أوكنفسكن
أوكنفسها
أوكنفسهم
أوكنفسهن
أوكنفسنا
أوكنفسكما
أوكنفسهما
أفكنفسي
أفكنفسك
أفكنفسه
أفكنفسكم
أفكنفسكن
أفكنفسها
أفكنفسهم
أفكنفسهن
أفكنفسنا
أفكنفسكما
أفكنفسهما
أولنفسي
أولنفسك
أولنفسه
أولنفسكم
أولنفسكن
أولنفسها
أولنفسهم
أولنفسهن
أولنفسنا
أولنفسكما
أولنفسهما
أفلنفسي
أفلنفسك
أفلنفسه
أفلنفسكم
أفلنفسكن
أفلنفسها
أفلنفسهم
أفلنفسهن
أفلنفسنا
أفلنفسكما
أفلنفسهما
ء
ؤ
ئ
آ
أ
ب
ت
ة
ث
ج
ح
خ
د
ذ
ر
ز
س
ش
ص
ض
ط
ظ
ع
غ
ف
ق
ك
ل
م
ن
ه
و
ى
ي
إلا
وإلا
فإلا
حاشا
وحاشا
فحاشا
حاشاي
حاشاك
حاشاه
حاشاكم
حاشاكن
حاشاها
حاشاهم
حاشاهن
حاشانا
حاشاكما
حاشاهما
وحاشاي
وحاشاك
وحاشاه
وحاشاكم
وحاشاكن
وحاشاها
وحاشاهم
وحاشاهن
وحاشانا
وحاشاكما
وحاشاهما
فحاشاي
فحاشاك
فحاشاه
فحاشاكم
فحاشاكن
فحاشاها
فحاشاهم
فحاشاهن
فحاشانا
فحاشاكما
فحاشاهما
خلا
وخلا
فخلا
خلاي
خلاك
خلاه
خلاكم
خلاكن
خلاها
خلاهم
خلاهن
خلانا
خلاكما
خلاهما
وخلاي
وخلاك
وخلاه
وخلاكم
وخلاكن
وخلاها
وخلاهم
وخلاهن
وخلانا
وخلاكما
وخلاهما
فخلاي
فخلاك
فخلاه
فخلاكم
فخلاكن
فخلاها
فخلاهم
فخلاهن
فخلانا
فخلاكما
فخلاهما
عدا
وعدا
فعدا
عداي
عداك
عداه
عداكم
عداكن
عداها
عداهم
عداهن
عدانا
عداكما
عداهما
وعداي
وعداك
وعداه
وعداكم
وعداكن
وعداها
وعداهم
وعداهن
وعدانا
وعداكما
وعداهما
فعداي
فعداك
فعداه
فعداكم
فعداكن
فعداها
فعداهم
فعداهن
فعدانا
فعداكما
فعداهما
فيم
وفيم
ففيم
فيما
وفيما
ففيما
هل
وهل
فهل
سوف
لسوف
وسوف
فسوف
ولسوف
فلسوف
هلا
وهلا
فهلا
قد
لقد
وقد
فقد
ولقد
فلقد
إما
وإما
فإما
كأنما
لكأنما
وكأنما
فكأنما
ولكأنما
فلكأنما
أكأنما
ألكأنما
أوكأنما
أفكأنما
أولكأنما
أفلكأنما
كما
وكما
فكما
لكي
ولكي
فلكي
لكيلا
ولكيلا
فلكيلا
إلى
لإلى
وإلى
فإلى
ولإلى
فلإلى
أإلى
ألإلى
أوإلى
أفإلى
أولإلى
أفلإلى
إلي
إليه
إليها
إليهم
إليهن
إلينا
إليهما
لإلي
لإليك
لإليه
لإليكم
لإليكن
لإليها
لإليهم
لإليهن
لإلينا
لإليكما
لإليهما
وإلي
وإليه
وإليها
وإليهم
وإليهن
وإلينا
وإليهما
فإلي
فإليه
فإليها
فإليهم
فإليهن
فإلينا
فإليهما
ولإلي
ولإليك
ولإليه
ولإليكم
ولإليكن
ولإليها
ولإليهم
ولإليهن
ولإلينا
ولإليكما
ولإليهما
فلإلي
فلإليك
فلإليه
فلإليكم
فلإليكن
فلإليها
فلإليهم
فلإليهن
فلإلينا
فلإليكما
فلإليهما
أإلي
أإليك
أإليه
أإليكم
أإليكن
أإليها
أإليهم
أإليهن
أإلينا
أإليكما
أإليهما
ألإلي
ألإليك
ألإليه
ألإليكم
ألإليكن
ألإليها
ألإليهم
ألإليهن
ألإلينا
ألإليكما
ألإليهما
أوإلي
أوإليك
أوإليه
أوإليكم
أوإليكن
أوإليها
أوإليهم
أوإليهن
أوإلينا
أوإليكما
أوإليهما
أفإلي
أفإليك
أفإليه
أفإليكم
أفإليكن
أفإليها
أفإليهم
أفإليهن
أفإلينا
أفإليكما
أفإليهما
أولإلي
أولإليك
أولإليه
أولإليكم
أولإليكن
أولإليها
أولإليهم
أولإليهن
أولإلينا
أولإليكما
أولإليهما
أفلإلي
أفلإليك
أفلإليه
أفلإليكم
أفلإليكن
أفلإليها
أفلإليهم
أفلإليهن
أفلإلينا
أفلإليكما
أفلإليهما
رب
ورب
فرب
على
لعلى
وعلى
فعلى
ولعلى
فلعلى
أعلى
ألعلى
أوعلى
أفعلى
أولعلى
أفلعلى
علي
عليه
عليكم
عليكن
عليها
عليهم
عليهن
علينا
عليكما
عليهما
لعليك
لعليه
لعليكم
لعليكن
لعليها
لعليهم
لعليهن
لعلينا
لعليكما
لعليهما
وعلي
وعليه
وعليكم
وعليكن
وعليها
وعليهم
وعليهن
وعلينا
وعليكما
وعليهما
فعلي
فعليه
فعليكم
فعليكن
فعليها
فعليهم
فعليهن
فعلينا
فعليكما
فعليهما
ولعليك
ولعليه
ولعليكم
ولعليكن
ولعليها
ولعليهم
ولعليهن
ولعلينا
ولعليكما
ولعليهما
فلعليك
فلعليه
فلعليكم
فلعليكن
فلعليها
فلعليهم
فلعليهن
فلعلينا
فلعليكما
فلعليهما
أعلي
أعليك
أعليه
أعليكم
أعليكن
أعليها
أعليهم
أعليهن
أعلينا
أعليكما
أعليهما
ألعليك
ألعليه
ألعليكم
ألعليكن
ألعليها
ألعليهم
ألعليهن
ألعلينا
ألعليكما
ألعليهما
أوعلي
أوعليك
أوعليه
أوعليكم
أوعليكن
أوعليها
أوعليهم
أوعليهن
أوعلينا
أوعليكما
أوعليهما
أفعلي
أفعليك
أفعليه
أفعليكم
أفعليكن
أفعليها
أفعليهم
أفعليهن
أفعلينا
أفعليكما
أفعليهما
أولعليك
أولعليه
أولعليكم
أولعليكن
أولعليها
أولعليهم
أولعليهن
أولعلينا
أولعليكما
أولعليهما
أفلعليك
أفلعليه
أفلعليكم
أفلعليكن
أفلعليها
أفلعليهم
أفلعليهن
أفلعلينا
أفلعليكما
أفلعليهما
عن
لعن
وعن
فعن
ولعن
فلعن
أعن
ألعن
أوعن
أفعن
أولعن
أفلعن
عني
عنك
عنه
عنكم
عنكن
عنها
عنهم
عنهن
عننا
عنكما
عنهما
لعني
لعنك
لعنه
لعنكم
لعنكن
لعنها
لعنهم
لعنهن
لعننا
لعنكما
لعنهما
وعني
وعنك
وعنه
وعنكم
وعنكن
وعنها
وعنهم
وعنهن
وعننا
وعنكما
وعنهما
فعني
فعنك
فعنه
فعنكم
فعنكن
فعنها
فعنهم
فعنهن
فعننا
فعنكما
فعنهما
ولعني
ولعنك
ولعنه
ولعنكم
ولعنكن
ولعنها
ولعنهم
ولعنهن
ولعننا
ولعنكما
ولعنهما
فلعني
فلعنك
فلعنه
فلعنكم
فلعنكن
فلعنها
فلعنهم
فلعنهن
فلعننا
فلعنكما
فلعنهما
أعني
أعنك
أعنه
أعنكم
أعنكن
أعنها
أعنهم
أعنهن
أعننا
أعنكما
أعنهما
ألعني
ألعنك
ألعنه
ألعنكم
ألعنكن
ألعنها
ألعنهم
ألعنهن
ألعننا
ألعنكما
ألعنهما
أوعني
أوعنك
أوعنه
أوعنكم
أوعنكن
أوعنها
أوعنهم
أوعنهن
أوعننا
أوعنكما
أوعنهما
أفعني
أفعنك
أفعنه
أفعنكم
أفعنكن
أفعنها
أفعنهم
أفعنهن
أفعننا
أفعنكما
أفعنهما
أولعني
أولعنك
أولعنه
أولعنكم
أولعنكن
أولعنها
أولعنهم
أولعنهن
أولعننا
أولعنكما
أولعنهما
أفلعني
أفلعنك
أفلعنه
أفلعنكم
أفلعنكن
أفلعنها
أفلعنهم
أفلعنهن
أفلعننا
أفلعنكما
أفلعنهما
في
لفي
وفي
ففي
ولفي
فلفي
أفي
ألفي
أوفي
أففي
أولفي
أفلفي
فيي
فيك
فيه
فيكم
فيكن
فيها
فيهم
فيهن
فينا
فيكما
فيهما
لفيي
لفيك
لفيه
لفيكم
لفيكن
لفيها
لفيهم
لفيهن
لفينا
لفيكما
لفيهما
وفيي
وفيك
وفيه
وفيكم
وفيكن
وفيها
وفيهم
وفيهن
وفينا
وفيكما
وفيهما
ففيي
ففيك
ففيه
ففيكم
ففيكن
ففيها
ففيهم
ففيهن
ففينا
ففيكما
ففيهما
ولفيي
ولفيك
ولفيه
ولفيكم
ولفيكن
ولفيها
ولفيهم
ولفيهن
ولفينا
ولفيكما
ولفيهما
فلفيي
فلفيك
فلفيه
فلفيكم
فلفيكن
فلفيها
فلفيهم
فلفيهن
فلفينا
فلفيكما
فلفيهما
أفيي
أفيك
أفيه
أفيكم
أفيكن
أفيها
أفيهم
أفيهن
أفينا
أفيكما
أفيهما
ألفيي
ألفيك
ألفيه
ألفيكم
ألفيكن
ألفيها
ألفيهم
ألفيهن
ألفينا
ألفيكما
ألفيهما
أوفيي
أوفيك
أوفيه
أوفيكم
أوفيكن
أوفيها
أوفيهم
أوفيهن
أوفينا
أوفيكما
أوفيهما
أففيي
أففيك
أففيه
أففيكم
أففيكن
أففيها
أففيهم
أففيهن
أففينا
أففيكما
أففيهما
أولفيي
أولفيك
أولفيه
أولفيكم
أولفيكن
أولفيها
أولفيهم
أولفيهن
أولفينا
أولفيكما
أولفيهما
أفلفيي
أفلفيك
أفلفيه
أفلفيكم
أفلفيكن
أفلفيها
أفلفيهم
أفلفيهن
أفلفينا
أفلفيكما
أفلفيهما
مني
منك
منه
منكم
منكن
منها
منهم
منهن
مننا
منكما
منهما
لمني
لمنك
لمنه
لمنكم
لمنكن
لمنها
لمنهم
لمنهن
لمننا
لمنكما
لمنهما
ومني
ومنك
ومنه
ومنكم
ومنكن
ومنها
ومنهم
ومنهن
ومننا
ومنكما
ومنهما
فمني
فمنك
فمنه
فمنكم
فمنكن
فمنها
فمنهم
فمنهن
فمننا
فمنكما
فمنهما
ولمني
ولمنك
ولمنه
ولمنكم
ولمنكن
ولمنها
ولمنهم
ولمنهن
ولمننا
ولمنكما
ولمنهما
فلمني
فلمنك
فلمنه
فلمنكم
فلمنكن
فلمنها
فلمنهم
فلمنهن
فلمننا
فلمنكما
فلمنهما
أمني
أمنك
أمنه
أمنكم
أمنكن
أمنها
أمنهم
أمنهن
أمننا
أمنكما
أمنهما
ألمني
ألمنك
ألمنه
ألمنكم
ألمنكن
ألمنها
ألمنهم
ألمنهن
ألمننا
ألمنكما
ألمنهما
أومني
أومنك
أومنه
أومنكم
أومنكن
أومنها
أومنهم
أومنهن
أومننا
أومنكما
أومنهما
أفمني
أفمنك
أفمنه
أفمنكم
أفمنكن
أفمنها
أفمنهم
أفمنهن
أفمننا
أفمنكما
أفمنهما
أولمني
أولمنك
أولمنه
أولمنكم
أولمنكن
أولمنها
أولمنهم
أولمنهن
أولمننا
أولمنكما
أولمنهما
أفلمني
أفلمنك
أفلمنه
أفلمنكم
أفلمنكن
أفلمنها
أفلمنهم
أفلمنهن
أفلمننا
أفلمنكما
أفلمنهما
عما
وعما
فعما
حتى
وحتى
فحتى
منذ
ومنذ
فمنذ
مذ
ومذ
فمذ
لم
ولم
فلم
ألم
أولم
أفلم
أجل
إذن
وإذن
فإذن
إي
وإي
فإي
بلى
جلل
جير
وجير
فجير
إذما
وإذما
فإذما
لئن
ولئن
فلئن
أما
وأما
فأما
وألا
فألا
أم
أو
بل
أيا
وأيا
فأيا
وهيا
فهيا
بك
وبك
فبك
أوبك
أفبك
أوبكم
أفبكم
بكما
وبكما
فبكما
أوبكما
أفبكما
بكن
وبكن
فبكن
أوبكن
أفبكن
بنا
وبنا
فبنا
أوبنا
أفبنا
به
وبه
فبه
أوبه
أفبه
بها
وبها
فبها
أوبها
أفبها
بي
وبي
فبي
أوبي
أفبي
لك
ولك
فلك
ألك
أولك
أفلك
لكم
ولكم
فلكم
ألكم
أولكم
أفلكم
لكما
ولكما
فلكما
ألكما
أولكما
أفلكما
ألكن
أولكن
أفلكن
لنا
ولنا
فلنا
ألنا
أولنا
أفلنا
له
وله
فله
أله
أوله
أفله
لها
ولها
فلها
ألها
أولها
أفلها
لي
ولي
فلي
ألي
أولي
أفلي
أنا
لأنا
وأنا
فأنا
ولأنا
فلأنا
أأنا
ألأنا
أوأنا
أفأنا
أولأنا
أفلأنا
أنت
لأنت
وأنت
فأنت
ولأنت
فلأنت
أأنت
ألأنت
أوأنت
أفأنت
أولأنت
أفلأنت
أنتم
لأنتم
وأنتم
فأنتم
ولأنتم
فلأنتم
أأنتم
ألأنتم
أوأنتم
أفأنتم
أولأنتم
أفلأنتم
أنتما
لأنتما
وأنتما
فأنتما
ولأنتما
فلأنتما
أأنتما
ألأنتما
أوأنتما
أفأنتما
أولأنتما
أفلأنتما
أنتن
لأنتن
وأنتن
فأنتن
ولأنتن
فلأنتن
أأنتن
ألأنتن
أوأنتن
أفأنتن
أولأنتن
أفلأنتن
نحن
لنحن
ونحن
فنحن
ولنحن
فلنحن
أنحن
ألنحن
أونحن
أفنحن
أولنحن
أفلنحن
هم
بهم
كهم
لهم
وهم
فهم
وبهم
فبهم
وكهم
فكهم
ولهم
فلهم
أهم
أكهم
ألهم
أوهم
أفهم
أوبهم
أفبهم
أوكهم
أفكهم
أولهم
أفلهم
هما
بهما
كهما
لهما
وهما
فهما
وبهما
فبهما
وكهما
فكهما
ولهما
فلهما
أهما
أكهما
ألهما
أوهما
أفهما
أوبهما
أفبهما
أوكهما
أفكهما
أولهما
أفلهما
هن
بهن
كهن
لهن
وهن
فهن
وبهن
فبهن
وكهن
فكهن
ولهن
فلهن
أهن
أكهن
ألهن
أوهن
أفهن
أوبهن
أفبهن
أوكهن
أفكهن
أولهن
أفلهن
هو
بهو
كهو
لهو
وهو
فهو
وبهو
فبهو
وكهو
فكهو
ولهو
فلهو
أهو
أبهو
أكهو
ألهو
أوهو
أفهو
أوبهو
أفبهو
أوكهو
أفكهو
أولهو
أفلهو
هي
بهي
كهي
لهي
وهي
فهي
وبهي
فبهي
وكهي
فكهي
ولهي
فلهي
أهي
أبهي
أكهي
ألهي
أوهي
أفهي
أوبهي
أفبهي
أوكهي
أفكهي
أولهي
أفلهي
إياك
بإياك
كإياك
لإياك
وإياك
فإياك
وبإياك
فبإياك
وكإياك
فكإياك
ولإياك
فلإياك
إياكم
بإياكم
كإياكم
لإياكم
وإياكم
فإياكم
وبإياكم
فبإياكم
وكإياكم
فكإياكم
ولإياكم
فلإياكم
إياكما
بإياكما
كإياكما
لإياكما
وإياكما
فإياكما
وبإياكما
فبإياكما
وكإياكما
فكإياكما
ولإياكما
فلإياكما
إياكن
بإياكن
كإياكن
لإياكن
وإياكن
فإياكن
وبإياكن
فبإياكن
وكإياكن
فكإياكن
ولإياكن
فلإياكن
إيانا
بإيانا
كإيانا
لإيانا
وإيانا
فإيانا
وبإيانا
فبإيانا
وكإيانا
فكإيانا
ولإيانا
فلإيانا
إياه
بإياه
كإياه
لإياه
وإياه
فإياه
وبإياه
فبإياه
وكإياه
فكإياه
ولإياه
فلإياه
إياها
بإياها
كإياها
لإياها
وإياها
فإياها
وبإياها
فبإياها
وكإياها
فكإياها
ولإياها
فلإياها
إياهم
بإياهم
كإياهم
لإياهم
وإياهم
فإياهم
وبإياهم
فبإياهم
وكإياهم
فكإياهم
ولإياهم
فلإياهم
إياهما
بإياهما
كإياهما
لإياهما
وإياهما
فإياهما
وبإياهما
فبإياهما
وكإياهما
فكإياهما
ولإياهما
فلإياهما
إياهن
بإياهن
كإياهن
لإياهن
وإياهن
فإياهن
وبإياهن
فبإياهن
وكإياهن
فكإياهن
ولإياهن
فلإياهن
إياي
بإياي
كإياي
لإياي
وإياي
فإياي
وبإياي
فبإياي
وكإياي
فكإياي
ولإياي
فلإياي
دون
ودون
فدون
ريث
وريث
فريث
عند
وعند
فعند
عندي
عندك
عنده
عندكم
عندكن
عندها
عندهم
عندهن
عندنا
عندكما
عندهما
وعندي
وعندك
وعنده
وعندكم
وعندكن
وعندها
وعندهم
وعندهن
وعندنا
وعندكما
وعندهما
فعندي
فعندك
فعنده
فعندكم
فعندكن
فعندها
فعندهم
فعندهن
فعندنا
فعندكما
فعندهما
عوض
وعوض
فعوض
قبل
وقبل
فقبل
قط
وقط
فقط
كلما
وكلما
فكلما
أكلما
أوكلما
أفكلما
لدن
ولدن
فلدن
لدني
لدنك
لدنه
لدنكم
لدنكن
لدنها
لدنهم
لدنهن
لدننا
لدنكما
لدنهما
ولدني
ولدنك
ولدنه
ولدنكم
ولدنكن
ولدنها
ولدنهم
ولدنهن
ولدننا
ولدنكما
ولدنهما
فلدني
فلدنك
فلدنه
فلدنكم
فلدنكن
فلدنها
فلدنهم
فلدنهن
فلدننا
فلدنكما
فلدنهما
لدى
ولدى
فلدى
ألدى
أولدى
أفلدى
لدي
لديك
لديه
لديكم
لديكن
لديها
لديهم
لديهن
لدينا
لديكما
لديهما
ولدي
ولديك
ولديه
ولديكم
ولديكن
ولديها
ولديهم
ولديهن
ولدينا
ولديكما
ولديهما
فلدي
فلديك
فلديه
فلديكم
فلديكن
فلديها
فلديهم
فلديهن
فلدينا
فلديكما
فلديهما
ألدي
ألديك
ألديه
ألديكم
ألديكن
ألديها
ألديهم
ألديهن
ألدينا
ألديكما
ألديهما
أولدي
أولديك
أولديه
أولديكم
أولديكن
أولديها
أولديهم
أولديهن
أولدينا
أولديكما
أولديهما
أفلدي
أفلديك
أفلديه
أفلديكم
أفلديكن
أفلديها
أفلديهم
أفلديهن
أفلدينا
أفلديكما
أفلديهما
الآن
والآن
فالآن
آناء
وآناء
فآناء
آنفا
وآنفا
فآنفا
أبدا
وأبدا
فأبدا
أصلا
وأصلا
فأصلا
أمد
وأمد
فأمد
أمس
وأمس
فأمس
أول
وأول
فأول
بعد
وبعد
فبعد
بعدي
بعدك
بعده
بعدكم
بعدكن
بعدها
بعدهم
بعدهن
بعدنا
بعدكما
بعدهما
وبعدي
وبعدك
وبعده
وبعدكم
وبعدكن
وبعدها
وبعدهم
وبعدهن
وبعدنا
وبعدكما
وبعدهما
فبعدي
فبعدك
فبعده
فبعدكم
فبعدكن
فبعدها
فبعدهم
فبعدهن
فبعدنا
فبعدكما
فبعدهما
تارة
وتارة
فتارة
حين
وحين
فحين
حيني
حينك
حينه
حينكم
حينكن
حينها
حينهم
حينهن
حيننا
حينكما
حينهما
وحيني
وحينك
وحينه
وحينكم
وحينكن
وحينها
وحينهم
وحينهن
وحيننا
وحينكما
وحينهما
فحيني
فحينك
فحينه
فحينكم
فحينكن
فحينها
فحينهم
فحينهن
فحيننا
فحينكما
فحينهما
صباح
وصباح
فصباح
ضحوة
وضحوة
فضحوة
غدا
وغدا
فغدا
غداة
وغداة
فغداة
مرة
ومرة
فمرة
مساء
ومساء
فمساء
يومئذ
ويومئذ
فيومئذ
خلال
وخلال
فخلال
خلالي
خلالك
خلاله
خلالكم
خلالكن
خلالها
خلالهم
خلالهن
خلالنا
خلالكما
خلالهما
وخلالي
وخلالك
وخلاله
وخلالكم
وخلالكن
وخلالها
وخلالهم
وخلالهن
وخلالنا
وخلالكما
وخلالهما
فخلالي
فخلالك
فخلاله
فخلالكم
فخلالكن
فخلالها
فخلالهم
فخلالهن
فخلالنا
فخلالكما
فخلالهما
أمام
لأمام
وأمام
فأمام
ولأمام
فلأمام
أأمام
ألأمام
أوأمام
أفأمام
أولأمام
أفلأمام
أمامي
أمامه
أمامكم
أمامكن
أمامها
أمامهم
أمامهن
أمامنا
أمامكما
أمامهما
لأمامي
لأمامك
لأمامه
لأمامكم
لأمامكن
لأمامها
لأمامهم
لأمامهن
لأمامنا
لأمامكما
لأمامهما
وأمامي
وأمامه
وأمامكم
وأمامكن
وأمامها
وأمامهم
وأمامهن
وأمامنا
وأمامكما
وأمامهما
فأمامي
فأمامه
فأمامكم
فأمامكن
فأمامها
فأمامهم
فأمامهن
فأمامنا
فأمامكما
فأمامهما
ولأمامي
ولأمامك
ولأمامه
ولأمامكم
ولأمامكن
ولأمامها
ولأمامهم
ولأمامهن
ولأمامنا
ولأمامكما
ولأمامهما
فلأمامي
فلأمامك
فلأمامه
فلأمامكم
فلأمامكن
فلأمامها
فلأمامهم
فلأمامهن
فلأمامنا
فلأمامكما
فلأمامهما
أأمامي
أأمامك
أأمامه
أأمامكم
أأمامكن
أأمامها
أأمامهم
أأمامهن
أأمامنا
أأمامكما
أأمامهما
ألأمامي
ألأمامك
ألأمامه
ألأمامكم
ألأمامكن
ألأمامها
ألأمامهم
ألأمامهن
ألأمامنا
ألأمامكما
ألأمامهما
أوأمامي
أوأمامك
أوأمامه
أوأمامكم
أوأمامكن
أوأمامها
أوأمامهم
أوأمامهن
أوأمامنا
أوأمامكما
أوأمامهما
أفأمامي
أفأمامك
أفأمامه
أفأمامكم
أفأمامكن
أفأمامها
أفأمامهم
أفأمامهن
أفأمامنا
أفأمامكما
أفأمامهما
أولأمامي
أولأمامك
أولأمامه
أولأمامكم
أولأمامكن
أولأمامها
أولأمامهم
أولأمامهن
أولأمامنا
أولأمامكما
أولأمامهما
أفلأمامي
أفلأمامك
أفلأمامه
أفلأمامكم
أفلأمامكن
أفلأمامها
أفلأمامهم
أفلأمامهن
أفلأمامنا
أفلأمامكما
أفلأمامهما
إزاء
وإزاء
فإزاء
بين
وبين
فبين
بيني
بينك
بينه
بينكم
بينكن
بينها
بينهم
بينهن
بيننا
بينكما
بينهما
وبيني
وبينك
وبينه
وبينكم
وبينكن
وبينها
وبينهم
وبينهن
وبيننا
وبينكما
وبينهما
فبيني
فبينك
فبينه
فبينكم
فبينكن
فبينها
فبينهم
فبينهن
فبيننا
فبينكما
فبينهما
تحت
لتحت
وتحت
فتحت
ولتحت
فلتحت
أتحت
ألتحت
أوتحت
أفتحت
أولتحت
أفلتحت
تحتي
تحتك
تحته
تحتكم
تحتكن
تحتها
تحتهم
تحتهن
تحتنا
تحتكما
تحتهما
لتحتي
لتحتك
لتحته
لتحتكم
لتحتكن
لتحتها
لتحتهم
لتحتهن
لتحتنا
لتحتكما
لتحتهما
وتحتي
وتحتك
وتحته
وتحتكم
وتحتكن
وتحتها
وتحتهم
وتحتهن
وتحتنا
وتحتكما
وتحتهما
فتحتي
فتحتك
فتحته
فتحتكم
فتحتكن
فتحتها
فتحتهم
فتحتهن
فتحتنا
فتحتكما
فتحتهما
ولتحتي
ولتحتك
ولتحته
ولتحتكم
ولتحتكن
ولتحتها
ولتحتهم
ولتحتهن
ولتحتنا
ولتحتكما
ولتحتهما
فلتحتي
فلتحتك
فلتحته
فلتحتكم
فلتحتكن
فلتحتها
فلتحتهم
فلتحتهن
فلتحتنا
فلتحتكما
فلتحتهما
أتحتي
أتحتك
أتحته
أتحتكم
أتحتكن
أتحتها
أتحتهم
أتحتهن
أتحتنا
أتحتكما
أتحتهما
ألتحتي
ألتحتك
ألتحته
ألتحتكم
ألتحتكن
ألتحتها
ألتحتهم
ألتحتهن
ألتحتنا
ألتحتكما
ألتحتهما
أوتحتي
أوتحتك
أوتحته
أوتحتكم
أوتحتكن
أوتحتها
أوتحتهم
أوتحتهن
أوتحتنا
أوتحتكما
أوتحتهما
أفتحتي
أفتحتك
أفتحته
أفتحتكم
أفتحتكن
أفتحتها
أفتحتهم
أفتحتهن
أفتحتنا
أفتحتكما
أفتحتهما
أولتحتي
أولتحتك
أولتحته
أولتحتكم
أولتحتكن
أولتحتها
أولتحتهم
أولتحتهن
أولتحتنا
أولتحتكما
أولتحتهما
أفلتحتي
أفلتحتك
أفلتحته
أفلتحتكم
أفلتحتكن
أفلتحتها
أفلتحتهم
أفلتحتهن
أفلتحتنا
أفلتحتكما
أفلتحتهما
خلف
لخلف
وخلف
فخلف
ولخلف
فلخلف
أخلف
ألخلف
أوخلف
أفخلف
أولخلف
أفلخلف
خلفي
خلفك
خلفه
خلفكم
خلفكن
خلفها
خلفهم
خلفهن
خلفنا
خلفكما
خلفهما
لخلفي
لخلفك
لخلفه
لخلفكم
لخلفكن
لخلفها
لخلفهم
لخلفهن
لخلفنا
لخلفكما
لخلفهما
وخلفي
وخلفك
وخلفه
وخلفكم
وخلفكن
وخلفها
وخلفهم
وخلفهن
وخلفنا
وخلفكما
وخلفهما
فخلفي
فخلفك
فخلفه
فخلفكم
فخلفكن
فخلفها
فخلفهم
فخلفهن
فخلفنا
فخلفكما
فخلفهما
ولخلفي
ولخلفك
ولخلفه
ولخلفكم
ولخلفكن
ولخلفها
ولخلفهم
ولخلفهن
ولخلفنا
ولخلفكما
ولخلفهما
فلخلفي
فلخلفك
فلخلفه
فلخلفكم
فلخلفكن
فلخلفها
فلخلفهم
فلخلفهن
فلخلفنا
فلخلفكما
فلخلفهما
أخلفي
أخلفك
أخلفه
أخلفكم
أخلفكن
أخلفها
أخلفهم
أخلفهن
أخلفنا
أخلفكما
أخلفهما
ألخلفي
ألخلفك
ألخلفه
ألخلفكم
ألخلفكن
ألخلفها
ألخلفهم
ألخلفهن
ألخلفنا
ألخلفكما
ألخلفهما
أوخلفي
أوخلفك
أوخلفه
أوخلفكم
أوخلفكن
أوخلفها
أوخلفهم
أوخلفهن
أوخلفنا
أوخلفكما
أوخلفهما
أفخلفي
أفخلفك
أفخلفه
أفخلفكم
أفخلفكن
أفخلفها
أفخلفهم
أفخلفهن
أفخلفنا
أفخلفكما
أفخلفهما
أولخلفي
أولخلفك
أولخلفه
أولخلفكم
أولخلفكن
أولخلفها
أولخلفهم
أولخلفهن
أولخلفنا
أولخلفكما
أولخلفهما
أفلخلفي
أفلخلفك
أفلخلفه
أفلخلفكم
أفلخلفكن
أفلخلفها
أفلخلفهم
أفلخلفهن
أفلخلفنا
أفلخلفكما
أفلخلفهما
شمال
لشمال
وشمال
فشمال
ولشمال
فلشمال
أشمال
ألشمال
أوشمال
أفشمال
أولشمال
أفلشمال
شمالي
شمالك
شماله
شمالكم
شمالكن
شمالها
شمالهم
شمالهن
شمالنا
شمالكما
شمالهما
لشمالي
لشمالك
لشماله
لشمالكم
لشمالكن
لشمالها
لشمالهم
لشمالهن
لشمالنا
لشمالكما
لشمالهما
وشمالي
وشمالك
وشماله
وشمالكم
وشمالكن
وشمالها
وشمالهم
وشمالهن
وشمالنا
وشمالكما
وشمالهما
فشمالي
فشمالك
فشماله
فشمالكم
فشمالكن
فشمالها
فشمالهم
فشمالهن
فشمالنا
فشمالكما
فشمالهما
ولشمالي
ولشمالك
ولشماله
ولشمالكم
ولشمالكن
ولشمالها
ولشمالهم
ولشمالهن
ولشمالنا
ولشمالكما
ولشمالهما
فلشمالي
فلشمالك
فلشماله
فلشمالكم
فلشمالكن
فلشمالها
فلشمالهم
فلشمالهن
فلشمالنا
فلشمالكما
فلشمالهما
أشمالي
أشمالك
أشماله
أشمالكم
أشمالكن
أشمالها
أشمالهم
أشمالهن
أشمالنا
أشمالكما
أشمالهما
ألشمالي
ألشمالك
ألشماله
ألشمالكم
ألشمالكن
ألشمالها
ألشمالهم
ألشمالهن
ألشمالنا
ألشمالكما
ألشمالهما
أوشمالي
أوشمالك
أوشماله
أوشمالكم
أوشمالكن
أوشمالها
أوشمالهم
أوشمالهن
أوشمالنا
أوشمالكما
أوشمالهما
أفشمالي
أفشمالك
أفشماله
أفشمالكم
أفشمالكن
أفشمالها
أفشمالهم
أفشمالهن
أفشمالنا
أفشمالكما
أفشمالهما
أولشمالي
أولشمالك
أولشماله
أولشمالكم
أولشمالكن
أولشمالها
أولشمالهم
أولشمالهن
أولشمالنا
أولشمالكما
أولشمالهما
أفلشمالي
أفلشمالك
أفلشماله
أفلشمالكم
أفلشمالكن
أفلشمالها
أفلشمالهم
أفلشمالهن
أفلشمالنا
أفلشمالكما
أفلشمالهما
ضمن
وضمن
فضمن
ضمني
ضمنك
ضمنه
ضمنكم
ضمنكن
ضمنها
ضمنهم
ضمنهن
ضمننا
ضمنكما
ضمنهما
وضمني
وضمنك
وضمنه
وضمنكم
وضمنكن
وضمنها
وضمنهم
وضمنهن
وضمننا
وضمنكما
وضمنهما
فضمني
فضمنك
فضمنه
فضمنكم
فضمنكن
فضمنها
فضمنهم
فضمنهن
فضمننا
فضمنكما
فضمنهما
فوق
لفوق
وفوق
ففوق
ولفوق
فلفوق
أفوق
ألفوق
أوفوق
أففوق
أولفوق
أفلفوق
فوقي
فوقك
فوقه
فوقكم
فوقكن
فوقها
فوقهم
فوقهن
فوقنا
فوقكما
فوقهما
لفوقي
لفوقك
لفوقه
لفوقكم
لفوقكن
لفوقها
لفوقهم
لفوقهن
لفوقنا
لفوقكما
لفوقهما
وفوقي
وفوقك
وفوقه
وفوقكم
وفوقكن
وفوقها
وفوقهم
وفوقهن
وفوقنا
وفوقكما
وفوقهما
ففوقي
ففوقك
ففوقه
ففوقكم
ففوقكن
ففوقها
ففوقهم
ففوقهن
ففوقنا
ففوقكما
ففوقهما
ولفوقي
ولفوقك
ولفوقه
ولفوقكم
ولفوقكن
ولفوقها
ولفوقهم
ولفوقهن
ولفوقنا
ولفوقكما
ولفوقهما
فلفوقي
فلفوقك
فلفوقه
فلفوقكم
فلفوقكن
فلفوقها
فلفوقهم
فلفوقهن
فلفوقنا
فلفوقكما
فلفوقهما
أفوقي
أفوقك
أفوقه
أفوقكم
أفوقكن
أفوقها
أفوقهم
أفوقهن
أفوقنا
أفوقكما
أفوقهما
ألفوقي
ألفوقك
ألفوقه
ألفوقكم
ألفوقكن
ألفوقها
ألفوقهم
ألفوقهن
ألفوقنا
ألفوقكما
ألفوقهما
أوفوقي
أوفوقك
أوفوقه
أوفوقكم
أوفوقكن
أوفوقها
أوفوقهم
أوفوقهن
أوفوقنا
أوفوقكما
أوفوقهما
أففوقي
أففوقك
أففوقه
أففوقكم
أففوقكن
أففوقها
أففوقهم
أففوقهن
أففوقنا
أففوقكما
أففوقهما
أولفوقي
أولفوقك
أولفوقه
أولفوقكم
أولفوقكن
أولفوقها
أولفوقهم
أولفوقهن
أولفوقنا
أولفوقكما
أولفوقهما
أفلفوقي
أفلفوقك
أفلفوقه
أفلفوقكم
أفلفوقكن
أفلفوقها
أفلفوقهم
أفلفوقهن
أفلفوقنا
أفلفوقكما
أفلفوقهما
يمين
ليمين
ويمين
فيمين
وليمين
فليمين
أيمين
أليمين
أويمين
أفيمين
أوليمين
أفليمين
يميني
يمينك
يمينه
يمينكم
يمينكن
يمينها
يمينهم
يمينهن
يميننا
يمينكما
يمينهما
ليميني
ليمينك
ليمينه
ليمينكم
ليمينكن
ليمينها
ليمينهم
ليمينهن
ليميننا
ليمينكما
ليمينهما
ويميني
ويمينك
ويمينه
ويمينكم
ويمينكن
ويمينها
ويمينهم
ويمينهن
ويميننا
ويمينكما
ويمينهما
فيميني
فيمينك
فيمينه
فيمينكم
فيمينكن
فيمينها
فيمينهم
فيمينهن
فيميننا
فيمينكما
فيمينهما
وليميني
وليمينك
وليمينه
وليمينكم
وليمينكن
وليمينها
وليمينهم
وليمينهن
وليميننا
وليمينكما
وليمينهما
فليميني
فليمينك
فليمينه
فليمينكم
فليمينكن
فليمينها
فليمينهم
فليمينهن
فليميننا
فليمينكما
فليمينهما
أيميني
أيمينك
أيمينه
أيمينكم
أيمينكن
أيمينها
أيمينهم
أيمينهن
أيميننا
أيمينكما
أيمينهما
أليميني
أليمينك
أليمينه
أليمينكم
أليمينكن
أليمينها
أليمينهم
أليمينهن
أليميننا
أليمينكما
أليمينهما
أويميني
أويمينك
أويمينه
أويمينكم
أويمينكن
أويمينها
أويمينهم
أويمينهن
أويميننا
أويمينكما
أويمينهما
أفيميني
أفيمينك
أفيمينه
أفيمينكم
أفيمينكن
أفيمينها
أفيمينهم
أفيمينهن
أفيميننا
أفيمينكما
أفيمينهما
أوليميني
أوليمينك
أوليمينه
أوليمينكم
أوليمينكن
أوليمينها
أوليمينهم
أوليمينهن
أوليميننا
أوليمينكما
أوليمينهما
أفليميني
أفليمينك
أفليمينه
أفليمينكم
أفليمينكن
أفليمينها
أفليمينهم
أفليمينهن
أفليميننا
أفليمينكما
أفليمينهما
حوالى
وحوالى
فحوالى
حوالي
حواليك
حواليه
حواليكم
حواليكن
حواليها
حواليهم
حواليهن
حوالينا
حواليكما
حواليهما
وحوالي
وحواليك
وحواليه
وحواليكم
وحواليكن
وحواليها
وحواليهم
وحواليهن
وحوالينا
وحواليكما
وحواليهما
فحوالي
فحواليك
فحواليه
فحواليكم
فحواليكن
فحواليها
فحواليهم
فحواليهن
فحوالينا
فحواليكما
فحواليهما
حول
وحول
فحول
حولي
حولك
حوله
حولكم
حولكن
حولها
حولهم
حولهن
حولنا
حولكما
حولهما
وحولي
وحولك
وحوله
وحولكم
وحولكن
وحولها
وحولهم
وحولهن
وحولنا
وحولكما
وحولهما
فحولي
فحولك
فحوله
فحولكم
فحولكن
فحولها
فحولهم
فحولهن
فحولنا
فحولكما
فحولهما
طالما
لطالما
وطالما
فطالما
ولطالما
فلطالما
أطالما
ألطالما
أوطالما
أفطالما
أولطالما
أفلطالما
قلما
لقلما
وقلما
فقلما
ولقلما
فلقلما
أقلما
ألقلما
أوقلما
أفقلما
أولقلما
أفلقلما
ابتدأ
لابتدأ
وابتدأ
فابتدأ
ولابتدأ
فلابتدأ
اخلولق
لاخلولق
واخلولق
فاخلولق
ولاخلولق
فلاخلولق
انبرى
لانبرى
وانبرى
فانبرى
ولانبرى
فلانبرى
أخذ
لأخذ
وأخذ
فأخذ
ولأخذ
فلأخذ
أقبل
لأقبل
وأقبل
فأقبل
ولأقبل
فلأقبل
أنشأ
لأنشأ
وأنشأ
فأنشأ
ولأنشأ
فلأنشأ
أوشك
لأوشك
وأوشك
فأوشك
ولأوشك
فلأوشك
جعل
لجعل
وجعل
فجعل
ولجعل
فلجعل
حرى
لحرى
وحرى
فحرى
ولحرى
فلحرى
شرع
لشرع
وشرع
فشرع
ولشرع
فلشرع
طفق
لطفق
وطفق
فطفق
ولطفق
فلطفق
عسى
لعسى
وعسى
فعسى
ولعسى
فلعسى
علق
لعلق
وعلق
فعلق
ولعلق
فلعلق
قام
لقام
وقام
فقام
ولقام
فلقام
كاد
لكاد
وكاد
فكاد
ولكاد
فلكاد
كرب
لكرب
وكرب
فكرب
ولكرب
فلكرب
هب
لهب
وهب
فهب
ولهب
فلهب
إنما
وإنما
فإنما
لكنما
ولكنما
فلكنما
ارتد
لارتد
وارتد
فارتد
ولارتد
فلارتد
استحال
لاستحال
واستحال
فاستحال
ولاستحال
فلاستحال
انقلب
لانقلب
وانقلب
فانقلب
ولانقلب
فلانقلب
آض
لآض
وآض
فآض
ولآض
فلآض
أصبح
لأصبح
وأصبح
فأصبح
ولأصبح
فلأصبح
أضحى
لأضحى
وأضحى
فأضحى
ولأضحى
فلأضحى
أمسى
لأمسى
وأمسى
فأمسى
ولأمسى
فلأمسى
بات
لبات
وبات
فبات
ولبات
فلبات
تبدل
لتبدل
وتبدل
فتبدل
ولتبدل
فلتبدل
تحول
لتحول
وتحول
فتحول
ولتحول
فلتحول
حار
لحار
وحار
فحار
ولحار
فلحار
راح
لراح
وراح
فراح
ولراح
فلراح
رجع
لرجع
ورجع
فرجع
ولرجع
فلرجع
صار
لصار
وصار
فصار
ولصار
فلصار
ظل
لظل
وظل
فظل
ولظل
فلظل
عاد
لعاد
وعاد
فعاد
ولعاد
فلعاد
لغدا
ولغدا
فلغدا
كان
لكان
وكان
فكان
ولكان
فلكان
ماانفك
وماانفك
فماانفك
مابرح
ومابرح
فمابرح
مادام
ومادام
فمادام
مازال
ومازال
فمازال
مافتئ
ومافتئ
فمافتئ
ليس
وليس
فليس
أليس
أوليس
أفليس
لست
ولست
فلست
ألست
أولست
أفلست
لسنا
ولسنا
فلسنا
ألسنا
أولسنا
أفلسنا
لستما
ولستما
فلستما
ألستما
أولستما
أفلستما
لستم
ولستم
فلستم
ألستم
أولستم
أفلستم
لستن
ولستن
فلستن
ألستن
أولستن
أفلستن
ليست
وليست
فليست
أليست
أوليست
أفليست
ليسا
وليسا
فليسا
أليسا
أوليسا
أفليسا
ليستا
وليستا
فليستا
أليستا
أوليستا
أفليستا
ليسوا
وليسوا
فليسوا
أليسوا
أوليسوا
أفليسوا
لسن
ولسن
فلسن
ألسن
أولسن
أفلسن
بضع
وبضع
فبضع
أبضع
أوبضع
أفبضع
بضعي
بضعك
بضعه
بضعكم
بضعكن
بضعها
بضعهم
بضعهن
بضعنا
بضعكما
بضعهما
وبضعي
وبضعك
وبضعه
وبضعكم
وبضعكن
وبضعها
وبضعهم
وبضعهن
وبضعنا
وبضعكما
وبضعهما
فبضعي
فبضعك
فبضعه
فبضعكم
فبضعكن
فبضعها
فبضعهم
فبضعهن
فبضعنا
فبضعكما
فبضعهما
أبضعي
أبضعك
أبضعه
أبضعكم
أبضعكن
أبضعها
أبضعهم
أبضعهن
أبضعنا
أبضعكما
أبضعهما
أوبضعي
أوبضعك
أوبضعه
أوبضعكم
أوبضعكن
أوبضعها
أوبضعهم
أوبضعهن
أوبضعنا
أوبضعكما
أوبضعهما
أفبضعي
أفبضعك
أفبضعه
أفبضعكم
أفبضعكن
أفبضعها
أفبضعهم
أفبضعهن
أفبضعنا
أفبضعكما
أفبضعهما
ذيت
وذيت
فذيت
فلان
وفلان
ففلان
أفلان
أوفلان
أففلان
كأين
وكأين
فكأين
أكذا
أوكذا
أفكذا
كيت
وكيت
فكيت
{
  "@file": "ArStrToTime",
  "str_replace": {
    "@function": "strtotime",
    "pair": [
      {
        "search": "أ",
        "replace": "ا"
      },
      {
        "search": "إ",
        "replace": "ا"
      },
      {
        "search": "آ",
        "replace": "ا"
      },
      {
        "search": "ة",
        "replace": "ه"
      },
      {
        "search": "بعد",
        "replace": "+"
      },
      {
        "search": "تالي",
        "replace": "next"
      },
      {
        "search": "لاحق",
        "replace": "next"
      },
      {
        "search": "قادم",
        "replace": "next"
      },
      {
        "search": "سابق",
        "replace": "last"
      },
      {
        "search": "فائت",
        "replace": "last"
      },
      {
        "search": "ماضي",
        "replace": "last"
      },
      {
        "search": "منذ",
        "replace": "-"
      },
      {
        "search": "قبل",
        "replace": "-"
      },
      {
        "search": "يوم",
        "replace": "1 day"
      },
      {
        "search": "ايام",
        "replace": "days"
      },
      {
        "search": "ساعه",
        "replace": "1 hour"
      },
      {
        "search": "ساعتان",
        "replace": "2 hours"
      },
      {
        "search": "ساعتين",
        "replace": "2 hours"
      },
      {
        "search": "ساعات",
        "replace": "hours"
      },
      {
        "search": "دقيقه",
        "replace": "1 minute"
      },
      {
        "search": "يومين",
        "replace": "2 days"
      },
      {
        "search": "دقيقتان",
        "replace": "2 minutes"
      },
      {
        "search": "دقيقتين",
        "replace": "2 minutes"
      },
      {
        "search": "دقائق",
        "replace": "minutes"
      },
      {
        "search": "ثانيه",
        "replace": "1 second"
      },
      {
        "search": "ثانيتين",
        "replace": "2 seconds"
      },
      {
        "search": "ثانيتان",
        "replace": "2 seconds"
      },
      {
        "search": "ثواني",
        "replace": "seconds"
      },
      {
        "search": "اسبوعين",
        "replace": "2 weeks"
      },
      {
        "search": "اسبوعان",
        "replace": "2 weeks"
      },
      {
        "search": "اسابيع",
        "replace": "weeks"
      },
      {
        "search": "اسبوع",
        "replace": "1 week"
      },
      {
        "search": "شهرين",
        "replace": "2 months"
      },
      {
        "search": "شهران",
        "replace": "2 months"
      },
      {
        "search": "اشهر",
        "replace": "months"
      },
      {
        "search": "شهور",
        "replace": "months"
      },
      {
        "search": "شهر",
        "replace": "1 month"
      },
      {
        "search": "سنه",
        "replace": "1 year"
      },
      {
        "search": "سنتين",
        "replace": "2 years"
      },
      {
        "search": "سنتان",
        "replace": "2 years"
      },
      {
        "search": "سنوات",
        "replace": "years"
      },
      {
        "search": "سنين",
        "replace": "years"
      },
      {
        "search": "صباحا",
        "replace": "am"
      },
      {
        "search": "فجرا",
        "replace": "am"
      },
      {
        "search": "قبل الظهر",
        "replace": "am"
      },
      {
        "search": "مساء",
        "replace": "pm"
      },
      {
        "search": "عصرا",
        "replace": "pm"
      },
      {
        "search": "بعد الظهر",
        "replace": "pm"
      },
      {
        "search": "ليلا",
        "replace": "pm"
      },
      {
        "search": "غد",
        "replace": "tomorrow"
      },
      {
        "search": "بارحة",
        "replace": "yesterday"
      },
      {
        "search": "أمس",
        "replace": "yesterday"
      },
      {
        "search": "مضت",
        "replace": "ago"
      },
      {
        "search": "مضى",
        "replace": "ago"
      },
      {
        "search": "هذا",
        "replace": "this"
      },
      {
        "search": "هذه",
        "replace": "this"
      },
      {
        "search": "الآن",
        "replace": "now"
      },
      {
        "search": "لحظه",
        "replace": "now"
      },
      {
        "search": "اول",
        "replace": "first"
      },
      {
        "search": "ثالث",
        "replace": "third"
      },
      {
        "search": "رابع",
        "replace": "fourth"
      },
      {
        "search": "خامس",
        "replace": "fifth"
      },
      {
        "search": "سادس",
        "replace": "sixth"
      },
      {
        "search": "سابع",
        "replace": "seventh"
      },
      {
        "search": "ثامن",
        "replace": "eighth"
      },
      {
        "search": "تاسع",
        "replace": "ninth"
      },
      {
        "search": "عاشر",
        "replace": "tenth"
      },
      {
        "search": "حادي عشر",
        "replace": "eleventh"
      },
      {
        "search": "حاديه عشر",
        "replace": "eleventh"
      },
      {
        "search": "ثاني عشر",
        "replace": "twelfth"
      },
      {
        "search": "ثانيه عشر",
        "replace": "twelfth"
      },
      {
        "search": "سبت",
        "replace": "saturday"
      },
      {
        "search": "احد",
        "replace": "sunday"
      },
      {
        "search": "اثنين",
        "replace": "monday"
      },
      {
        "search": "ثلاثاء",
        "replace": "tuesday"
      },
      {
        "search": "اربعاء",
        "replace": "wednesday"
      },
      {
        "search": "خميس",
        "replace": "thursday"
      },
      {
        "search": "جمعه",
        "replace": "friday"
      },
      {
        "search": "ثلاث",
        "replace": "3"
      },
      {
        "search": "اربع",
        "replace": "4"
      },
      {
        "search": "خمس",
        "replace": "5"
      },
      {
        "search": "ست",
        "replace": "6"
      },
      {
        "search": "سبع",
        "replace": "7"
      },
      {
        "search": "ثمان",
        "replace": "8"
      },
      {
        "search": "تسع",
        "replace": "9"
      },
      {
        "search": "عشر",
        "replace": "10"
      },
      {
        "search": "كانون ثاني",
        "replace": "january"
      },
      {
        "search": "شباط",
        "replace": "february"
      },
      {
        "search": "اذار",
        "replace": "march"
      },
      {
        "search": "نيسان",
        "replace": "april"
      },
      {
        "search": "ايار",
        "replace": "may"
      },
      {
        "search": "حزيران",
        "replace": "june"
      },
      {
        "search": "تموز",
        "replace": "july"
      },
      {
        "search": "اب",
        "replace": "august"
      },
      {
        "search": "ايلول",
        "replace": "september"
      },
      {
        "search": "تشرين اول",
        "replace": "october"
      },
      {
        "search": "تشرين ثاني",
        "replace": "november"
      },
      {
        "search": "كانون اول",
        "replace": "december"
      },
      {
        "search": "يناير",
        "replace": "january"
      },
      {
        "search": "فبراير",
        "replace": "february"
      },
      {
        "search": "مارس",
        "replace": "march"
      },
      {
        "search": "ابريل",
        "replace": "april"
      },
      {
        "search": "مايو",
        "replace": "may"
      },
      {
        "search": "يونيو",
        "replace": "june"
      },
      {
        "search": "يوليو",
        "replace": "july"
      },
      {
        "search": "اغسطس",
        "replace": "august"
      },
      {
        "search": "سبتمبر",
        "replace": "september"
      },
      {
        "search": "اكتوبر",
        "replace": "october"
      },
      {
        "search": "نوفمبر",
        "replace": "november"
      },
      {
        "search": "ديسمبر",
        "replace": "december"
      },
      {
        "search": "و",
        "replace": "+"
      }
    ]
  }
}{
  "@file": "Transliteration",
  "preg_replace_en2ar": [
    {
      "search": "/^au/",
      "replace": "ا"
    },
    {
      "search": "/^a/",
      "replace": "ا"
    },
    {
      "search": "/^e/",
      "replace": "ا"
    },
    {
      "search": "/^i/",
      "replace": "ا"
    },
    {
      "search": "/^mc/",
      "replace": "ماك"
    },
    {
      "search": "/^o/",
      "replace": "او"
    },
    {
      "search": "/^u/",
      "replace": "ا"
    },
    {
      "search": "/^wr/",
      "replace": "ر"
    },
    {
      "search": "/ough$/",
      "replace": "ه"
    },
    {
      "search": "/ue$/",
      "replace": ""
    },
    {
      "search": "/a$/",
      "replace": "ه"
    },
    {
      "search": "/s$/",
      "replace": "س"
    }
  ],
  "str_replace_en2ar": [
    {
      "search": "ough",
      "replace": "او"
    },
    {
      "search": "alk",
      "replace": "وك"
    },
    {
      "search": "ois",
      "replace": "وا"
    },
    {
      "search": "sch",
      "replace": "ش"
    },
    {
      "search": "tio",
      "replace": "ش"
    },
    {
      "search": "ai",
      "replace": "اي"
    },
    {
      "search": "au",
      "replace": "او"
    },
    {
      "search": "bb",
      "replace": "ب"
    },
    {
      "search": "cc",
      "replace": "ك"
    },
    {
      "search": "ce",
      "replace": "س"
    },
    {
      "search": "ci",
      "replace": "سي"
    },
    {
      "search": "cy",
      "replace": "سي"
    },
    {
      "search": "ch",
      "replace": "تش"
    },
    {
      "search": "ck",
      "replace": "ك"
    },
    {
      "search": "dd",
      "replace": "د"
    },
    {
      "search": "ea",
      "replace": "ي"
    },
    {
      "search": "ee",
      "replace": "ي"
    },
    {
      "search": "ey",
      "replace": "اي"
    },
    {
      "search": "ff",
      "replace": "ف"
    },
    {
      "search": "ge",
      "replace": "ج"
    },
    {
      "search": "gi",
      "replace": "جي"
    },
    {
      "search": "gg",
      "replace": "غ"
    },
    {
      "search": "gh",
      "replace": "ف"
    },
    {
      "search": "gn",
      "replace": "جن"
    },
    {
      "search": "ie",
      "replace": "ي"
    },
    {
      "search": "kk",
      "replace": "ك"
    },
    {
      "search": "kh",
      "replace": "خ"
    },
    {
      "search": "ll",
      "replace": "ل"
    },
    {
      "search": "mm",
      "replace": "م"
    },
    {
      "search": "nn",
      "replace": "ن"
    },
    {
      "search": "oo",
      "replace": "و"
    },
    {
      "search": "ou",
      "replace": "و"
    },
    {
      "search": "ph",
      "replace": "ف"
    },
    {
      "search": "pp",
      "replace": "ب"
    },
    {
      "search": "qu",
      "replace": "كو"
    },
    {
      "search": "rr",
      "replace": "ر"
    },
    {
      "search": "sh",
      "replace": "ش"
    },
    {
      "search": "ss",
      "replace": "س"
    },
    {
      "search": "th",
      "replace": "ذ"
    },
    {
      "search": "tt",
      "replace": "ت"
    },
    {
      "search": "wr",
      "replace": "ر"
    },
    {
      "search": "a",
      "replace": "ا"
    },
    {
      "search": "b",
      "replace": "ب"
    },
    {
      "search": "c",
      "replace": "ك"
    },
    {
      "search": "d",
      "replace": "د"
    },
    {
      "search": "e",
      "replace": ""
    },
    {
      "search": "f",
      "replace": "ف"
    },
    {
      "search": "g",
      "replace": "غ"
    },
    {
      "search": "h",
      "replace": "ه"
    },
    {
      "search": "i",
      "replace": "ي"
    },
    {
      "search": "j",
      "replace": "ج"
    },
    {
      "search": "k",
      "replace": "ك"
    },
    {
      "search": "l",
      "replace": "ل"
    },
    {
      "search": "m",
      "replace": "م"
    },
    {
      "search": "n",
      "replace": "ن"
    },
    {
      "search": "o",
      "replace": "و"
    },
    {
      "search": "p",
      "replace": "ب"
    },
    {
      "search": "q",
      "replace": "ك"
    },
    {
      "search": "r",
      "replace": "ر"
    },
    {
      "search": "s",
      "replace": "س"
    },
    {
      "search": "t",
      "replace": "ت"
    },
    {
      "search": "u",
      "replace": "و"
    },
    {
      "search": "v",
      "replace": "ف"
    },
    {
      "search": "w",
      "replace": "و"
    },
    {
      "search": "x",
      "replace": "كس"
    },
    {
      "search": "y",
      "replace": "ي"
    },
    {
      "search": "z",
      "replace": "ز"
    },
    {
      "search": ",",
      "replace": "،"
    },
    {
      "search": "?",
      "replace": "؟"
    },
    {
      "search": "/2/",
      "replace": "ء"
    },
    {
      "search": "7'",
      "replace": "خ"
    },
    {
      "search": "7",
      "replace": "ح"
    },
    {
      "search": "9'",
      "replace": "ض"
    },
    {
      "search": "9",
      "replace": "ص"
    },
    {
      "search": "6'",
      "replace": "ظ"
    },
    {
      "search": "6",
      "replace": "ط"
    },
    {
      "search": "3'",
      "replace": "غ"
    },
    {
      "search": "3",
      "replace": "ع"
    },
    {
      "search": "8",
      "replace": "ق"
    }
  ],
  "preg_replace_ar2en": [
    {
      "search": "/^ال/",
      "replace": "Al-"
    },
    {
      "search": "/^إِي/",
      "replace": "ei"
    },
    {
      "search": "/^عِي/",
      "replace": "ei"
    },
    {
      "search": "/^عُو/",
      "replace": "ou"
    },
    {
      "search": "/ْع$/",
      "replace": "a"
    },
    {
      "search": "/ِي$/",
      "replace": "i"
    },
    {
      "search": "/َو$/",
      "replace": "aw"
    },
    {
      "search": "/َي$/",
      "replace": "ay"
    },
    {
      "search": "/^ع/",
      "replace": "a"
    },
    {
      "search": "/^أ/",
      "replace": "a"
    },
    {
      "search": "/^آ/",
      "replace": "aa"
    }
  ],
  "str_replace_ar2en": [
    {
      "search": "Al-ت",
      "replace": "At-t"
    },
    {
      "search": "Al-ث",
      "replace": "Ath-th"
    },
    {
      "search": "Al-د",
      "replace": "Ad-d"
    },
    {
      "search": "Al-ذ",
      "replace": "Adh-dh"
    },
    {
      "search": "Al-ر",
      "replace": "Ar-r"
    },
    {
      "search": "Al-ز",
      "replace": "Az-z"
    },
    {
      "search": "Al-س",
      "replace": "As-s"
    },
    {
      "search": "Al-ش",
      "replace": "Ash-sh"
    },
    {
      "search": "Al-ص",
      "replace": "As-s"
    },
    {
      "search": "Al-ض",
      "replace": "Ad-d"
    },
    {
      "search": "Al-ط",
      "replace": "At-t"
    },
    {
      "search": "Al-ظ",
      "replace": "Az-z"
    },
    {
      "search": "Al-ن",
      "replace": "An-n"
    },
    {
      "search": "ّ",
      "replace": "#"
    },
    {
      "search": "تة",
      "replace": "t'h"
    },
    {
      "search": "ته",
      "replace": "t'h"
    },
    {
      "search": "كة",
      "replace": "k'h"
    },
    {
      "search": "كه",
      "replace": "k'h"
    },
    {
      "search": "ده",
      "replace": "d'h"
    },
    {
      "search": "دة",
      "replace": "d'h"
    },
    {
      "search": "ضه",
      "replace": "d'h"
    },
    {
      "search": "ضة",
      "replace": "d'h"
    },
    {
      "search": "سه",
      "replace": "s'h"
    },
    {
      "search": "سة",
      "replace": "s'h"
    },
    {
      "search": "صه",
      "replace": "s'h"
    },
    {
      "search": "صة",
      "replace": "s'h"
    },
    {
      "search": "َا",
      "replace": "a"
    },
    {
      "search": "َي",
      "replace": "a"
    },
    {
      "search": "ُو",
      "replace": "ou"
    },
    {
      "search": "ِي",
      "replace": "ei"
    },
    {
      "search": "ً",
      "replace": "an"
    },
    {
      "search": "ٌ",
      "replace": "un"
    },
    {
      "search": "ٍ",
      "replace": "in"
    },
    {
      "search": "َ",
      "replace": "a"
    },
    {
      "search": "ِ",
      "replace": "i"
    },
    {
      "search": "ُ",
      "replace": "u"
    },
    {
      "search": "ْ",
      "replace": ""
    },
    {
      "search": "ا",
      "replace": "a"
    },
    {
      "search": "ب",
      "replace": "b"
    },
    {
      "search": "ت",
      "replace": "t"
    },
    {
      "search": "ث",
      "replace": "th"
    },
    {
      "search": "ج",
      "replace": "j"
    },
    {
      "search": "ح",
      "replace": "h"
    },
    {
      "search": "خ",
      "replace": "kh"
    },
    {
      "search": "د",
      "replace": "d"
    },
    {
      "search": "ذ",
      "replace": "dh"
    },
    {
      "search": "ر",
      "replace": "r"
    },
    {
      "search": "ز",
      "replace": "z"
    },
    {
      "search": "س",
      "replace": "s"
    },
    {
      "search": "ش",
      "replace": "sh"
    },
    {
      "search": "ص",
      "replace": "s"
    },
    {
      "search": "ض",
      "replace": "d"
    },
    {
      "search": "ط",
      "replace": "t"
    },
    {
      "search": "ظ",
      "replace": "z"
    },
    {
      "search": "ع",
      "replace": "'"
    },
    {
      "search": "غ",
      "replace": "gh"
    },
    {
      "search": "ف",
      "replace": "f"
    },
    {
      "search": "ق",
      "replace": "q"
    },
    {
      "search": "ك",
      "replace": "k"
    },
    {
      "search": "ل",
      "replace": "l"
    },
    {
      "search": "م",
      "replace": "m"
    },
    {
      "search": "ن",
      "replace": "n"
    },
    {
      "search": "ه",
      "replace": "h"
    },
    {
      "search": "ة",
      "replace": "h"
    },
    {
      "search": "و",
      "replace": "w"
    },
    {
      "search": "ي",
      "replace": "y"
    },
    {
      "search": "ى",
      "replace": "a"
    },
    {
      "search": "أ",
      "replace": "'a"
    },
    {
      "search": "إ",
      "replace": "i"
    },
    {
      "search": "ء",
      "replace": "'a"
    },
    {
      "search": "ؤ",
      "replace": "u'"
    },
    {
      "search": "ئ",
      "replace": "'i"
    },
    {
      "search": "آ",
      "replace": "'aa"
    },
    {
      "search": "ْ",
      "replace": ""
    },
    {
      "search": "،",
      "replace": ","
    },
    {
      "search": "؟",
      "replace": "?"
    }
  ],
  "str_replace_diaritical": [
    {
      "search": "Al-ص",
      "replace": "Aș-ș"
    },
    {
      "search": "Al-ض",
      "replace": "Aḑ-ḑ"
    },
    {
      "search": "Al-ط",
      "replace": "Aț-ț"
    },
    {
      "search": "Al-ظ",
      "replace": "Az̧-z̧"
    },
    {
      "search": "َا",
      "replace": "ā"
    },
    {
      "search": "ُو",
      "replace": "ū"
    },
    {
      "search": "َى",
      "replace": "á"
    },
    {
      "search": "ص",
      "replace": "ș"
    },
    {
      "search": "ض",
      "replace": "ḑ"
    },
    {
      "search": "ط",
      "replace": "ț"
    },
    {
      "search": "آ",
      "replace": "ā"
    },
    {
      "search": "ظ",
      "replace": "z̧"
    },
    {
      "search": "ح",
      "replace": "ḩ"
    },
    {
      "search": "ِي",
      "replace": "ī"
    }
  ],
  "str_replace_RJGC": [
    {
      "search": "ḑ",
      "replace": "ḏ"
    },
    {
      "search": "ș",
      "replace": "s̱"
    },
    {
      "search": "ț",
      "replace": "ṯ"
    },
    {
      "search": "z̧",
      "replace": "ḏh"
    },
    {
      "search": "ḩ",
      "replace": "ẖ"
    }
  ],
  "str_replace_SES": [
    {
      "search": "á",
      "replace": "a"
    },
    {
      "search": "ā",
      "replace": "â"
    },
    {
      "search": "aw",
      "replace": "ô"
    },
    {
      "search": "ay",
      "replace": "ei"
    },
    {
      "search": "ḑ",
      "replace": "ḍ"
    },
    {
      "search": "ḩ",
      "replace": "ḥ"
    },
    {
      "search": "ī",
      "replace": "î"
    },
    {
      "search": "j",
      "replace": "g"
    },
    {
      "search": "ș",
      "replace": "ṣ"
    },
    {
      "search": "ț",
      "replace": "ṭ"
    },
    {
      "search": "ū",
      "replace": "û"
    },
    {
      "search": "z̧",
      "replace": "ẓ"
    }
  ],
  "str_replace_ISO233": [
    {
      "search": "َا",
      "replace": "a'"
    },
    {
      "search": "ِي",
      "replace": "iy"
    },
    {
      "search": "ُو",
      "replace": "uw"
    },
    {
      "search": "ً",
      "replace": "á"
    },
    {
      "search": "ٌ",
      "replace": "ú"
    },
    {
      "search": "ٍ",
      "replace": "í"
    },
    {
      "search": "ذ",
      "replace": "ḏ"
    },
    {
      "search": "غ",
      "replace": "ġ"
    },
    {
      "search": "خ",
      "replace": "ẖ"
    },
    {
      "search": "ش",
      "replace": "š"
    },
    {
      "search": "ث",
      "replace": "ṯ"
    },
    {
      "search": "ض",
      "replace": "ḍ"
    },
    {
      "search": "ة",
      "replace": "ṫ"
    },
    {
      "search": "ح",
      "replace": "ḥ"
    },
    {
      "search": "ج",
      "replace": "ǧ"
    },
    {
      "search": "ص",
      "replace": "ṣ"
    },
    {
      "search": "ط",
      "replace": "ṭ"
    },
    {
      "search": "ظ",
      "replace": "ẓ"
    }
  ]
}﻿a's
able
about
above
according
accordingly
across
actually
after
afterwards
again
against
ain't
all
allow
allows
almost
alone
along
already
also
although
always
am
among
amongst
an
and
another
any
anybody
anyhow
anyone
anything
anyway
anyways
anywhere
apart
appear
appreciate
appropriate
are
aren't
around
as
aside
ask
asking
associated
at
available
away
awfully
be
became
because
become
becomes
becoming
been
before
beforehand
behind
being
believe
below
beside
besides
best
better
between
beyond
both
brief
but
by
c'mon
c's
came
can
can't
cannot
cant
cause
causes
certain
certainly
changes
clearly
co
com
come
comes
concerning
consequently
consider
considering
contain
containing
contains
corresponding
could
couldn't
course
currently
definitely
described
despite
did
didn't
different
do
does
doesn't
doing
don't
done
down
downwards
during
each
edu
eg
eight
either
else
elsewhere
enough
entirely
especially
et
etc
even
ever
every
everybody
everyone
everything
everywhere
ex
exactly
example
except
far
few
fifth
first
five
followed
following
follows
for
former
formerly
forth
four
from
further
furthermore
get
gets
getting
given
gives
go
goes
going
gone
got
gotten
greetings
had
hadn't
happens
hardly
has
hasn't
have
haven't
having
he
he's
hello
help
hence
her
here
here's
hereafter
hereby
herein
hereupon
hers
herself
hi
him
himself
his
hither
hopefully
how
howbeit
however
i'd
i'll
i'm
i've
ie
if
ignored
immediate
in
inasmuch
inc
indeed
indicate
indicated
indicates
inner
insofar
instead
into
inward
is
isn't
it
it'd
it'll
it's
its
itself
just
keep
keeps
kept
know
knows
known
last
lately
later
latter
latterly
least
less
lest
let
let's
like
liked
likely
little
look
looking
looks
ltd
mainly
many
may
maybe
me
mean
meanwhile
merely
might
more
moreover
most
mostly
much
must
my
myself
name
namely
nd
near
nearly
necessary
need
needs
neither
never
nevertheless
new
next
nine
no
nobody
non
none
noone
nor
normally
not
nothing
novel
now
nowhere
obviously
of
off
often
oh
ok
okay
old
on
once
one
ones
only
onto
or
other
others
otherwise
ought
our
ours
ourselves
out
outside
over
overall
own
particular
particularly
per
perhaps
placed
please
plus
possible
presumably
probably
provides
que
quite
qv
rather
rd
re
really
reasonably
regarding
regardless
regards
relatively
respectively
right
said
same
saw
say
saying
says
second
secondly
see
seeing
seem
seemed
seeming
seems
seen
self
selves
sensible
sent
serious
seriously
seven
several
shall
she
should
shouldn't
since
six
so
some
somebody
somehow
someone
something
sometime
sometimes
somewhat
somewhere
soon
sorry
specified
specify
specifying
still
sub
such
sup
sure
t's
take
taken
tell
tends
th
than
thank
thanks
thanx
that
that's
thats
the
their
theirs
them
themselves
then
thence
there
there's
thereafter
thereby
therefore
therein
theres
thereupon
these
they
they'd
they'll
they're
they've
think
third
this
thorough
thoroughly
those
though
three
through
throughout
thru
thus
to
together
too
took
toward
towards
tried
tries
truly
try
trying
twice
two
un
under
unfortunately
unless
unlikely
until
unto
up
upon
us
use
used
useful
uses
using
usually
value
various
very
via
viz
vs
want
wants
was
wasn't
way
we
we'd
we'll
we're
we've
welcome
well
went
were
weren't
what
what's
whatever
when
whence
whenever
where
where's
whereafter
whereas
whereby
wherein
whereupon
wherever
whether
which
while
whither
who
who's
whoever
whole
whom
whose
why
will
willing
wish
with
within
without
won't
wonder
would
would
wouldn't
yes
yet
you
you'd
you'll
you're
you've
your
yours
yourself
yourselves
zero﻿one
every
least
less
many
now
ever
never
say
says
said
also
get
go
goes
just
made
make
put
see
seen
whether
like
well
back
even
still
way
take
since
another
however
two
three
four
five
first
second
new
old
high
long
هام
ذات
كل
نفس
عين
جميع
خلاص
عامa:37:{s:1:" ";a:37:{s:1:" ";s:5:"-4.74";s:2:"ء";s:6:"-11.09";s:2:"آ";s:5:"-2.58";s:2:"أ";s:4:"0.79";s:2:"ؤ";s:6:"-11.09";s:2:"إ";s:4:"0.13";s:2:"ئ";s:5:"-10.4";s:2:"ا";s:4:"2.34";s:2:"ب";s:4:"0.83";s:2:"ة";s:5:"-10.4";s:2:"ت";s:4:"0.39";s:2:"ث";s:5:"-2.18";s:2:"ج";s:5:"-0.71";s:2:"ح";s:4:"-0.5";s:2:"خ";s:4:"-1.1";s:2:"د";s:5:"-0.79";s:2:"ذ";s:5:"-2.39";s:2:"ر";s:5:"-0.86";s:2:"ز";s:5:"-2.34";s:2:"س";s:5:"-0.39";s:2:"ش";s:5:"-0.96";s:2:"ص";s:5:"-1.48";s:2:"ض";s:5:"-2.09";s:2:"ط";s:5:"-1.79";s:2:"ظ";s:5:"-3.69";s:2:"ع";s:4:"0.49";s:2:"غ";s:5:"-1.61";s:2:"ف";s:4:"0.71";s:2:"ق";s:5:"-0.28";s:2:"ك";s:5:"-0.47";s:2:"ل";s:4:"0.54";s:2:"م";s:4:"1.23";s:2:"ن";s:4:"-0.6";s:2:"ه";s:5:"-0.84";s:2:"و";s:4:"1.11";s:2:"ى";s:5:"-9.99";s:2:"ي";s:5:"-0.11";}s:2:"ء";a:37:{s:1:" ";s:4:"3.54";s:2:"ء";s:5:"-7.09";s:2:"آ";s:5:"-7.09";s:2:"أ";s:5:"-7.09";s:2:"ؤ";s:5:"-7.09";s:2:"إ";s:5:"-7.09";s:2:"ئ";s:5:"-7.09";s:2:"ا";s:4:"0.33";s:2:"ب";s:5:"-7.09";s:2:"ة";s:5:"-1.22";s:2:"ت";s:5:"-0.59";s:2:"ث";s:5:"-7.09";s:2:"ج";s:5:"-7.09";s:2:"ح";s:5:"-7.09";s:2:"خ";s:5:"-7.09";s:2:"د";s:5:"-7.09";s:2:"ذ";s:5:"-7.09";s:2:"ر";s:5:"-7.09";s:2:"ز";s:5:"-7.09";s:2:"س";s:5:"-7.09";s:2:"ش";s:5:"-7.09";s:2:"ص";s:5:"-7.09";s:2:"ض";s:5:"-7.09";s:2:"ط";s:5:"-7.09";s:2:"ظ";s:5:"-7.09";s:2:"ع";s:5:"-7.09";s:2:"غ";s:5:"-7.09";s:2:"ف";s:5:"-7.09";s:2:"ق";s:5:"-7.09";s:2:"ك";s:5:"-5.14";s:2:"ل";s:5:"-2.35";s:2:"م";s:5:"-4.32";s:2:"ن";s:5:"-4.52";s:2:"ه";s:5:"-1.48";s:2:"و";s:5:"-4.15";s:2:"ى";s:5:"-7.09";s:2:"ي";s:5:"-4.09";}s:2:"آ";a:37:{s:1:" ";s:5:"-3.96";s:2:"ء";s:5:"-5.35";s:2:"آ";s:5:"-5.35";s:2:"أ";s:5:"-5.35";s:2:"ؤ";s:5:"-5.35";s:2:"إ";s:5:"-5.35";s:2:"ئ";s:5:"-5.35";s:2:"ا";s:5:"-5.35";s:2:"ب";s:4:"0.37";s:2:"ة";s:5:"-2.95";s:2:"ت";s:4:"0.02";s:2:"ث";s:4:"0.03";s:2:"ج";s:5:"-0.34";s:2:"ح";s:5:"-4.65";s:2:"خ";s:4:"2.51";s:2:"د";s:5:"-1.63";s:2:"ذ";s:3:"0.6";s:2:"ر";s:5:"-0.04";s:2:"ز";s:5:"-4.25";s:2:"س";s:4:"1.27";s:2:"ش";s:5:"-3.27";s:2:"ص";s:5:"-1.98";s:2:"ض";s:5:"-5.35";s:2:"ط";s:5:"-5.35";s:2:"ظ";s:5:"-5.35";s:2:"ع";s:5:"-5.35";s:2:"غ";s:5:"-2.86";s:2:"ف";s:5:"-1.34";s:2:"ق";s:5:"-5.35";s:2:"ك";s:5:"-2.51";s:2:"ل";s:4:"1.73";s:2:"م";s:4:"0.46";s:2:"ن";s:4:"1.54";s:2:"ه";s:5:"-2.95";s:2:"و";s:5:"-0.68";s:2:"ى";s:5:"-5.35";s:2:"ي";s:5:"-0.21";}s:2:"أ";a:37:{s:1:" ";s:5:"-0.84";s:2:"ء";s:5:"-8.87";s:2:"آ";s:5:"-8.87";s:2:"أ";s:5:"-6.92";s:2:"ؤ";s:5:"-5.73";s:2:"إ";s:5:"-8.87";s:2:"ئ";s:5:"-5.61";s:2:"ا";s:5:"-6.47";s:2:"ب";s:5:"-0.19";s:2:"ة";s:5:"-1.83";s:2:"ت";s:5:"-0.65";s:2:"ث";s:5:"-0.67";s:2:"ج";s:5:"-0.33";s:2:"ح";s:4:"0.21";s:2:"خ";s:4:"0.06";s:2:"د";s:5:"-0.57";s:2:"ذ";s:5:"-2.97";s:2:"ر";s:4:"0.55";s:2:"ز";s:5:"-1.98";s:2:"س";s:4:"1.01";s:2:"ش";s:5:"-0.29";s:2:"ص";s:4:"-0.5";s:2:"ض";s:5:"-0.74";s:2:"ط";s:5:"-0.64";s:2:"ظ";s:5:"-2.26";s:2:"ع";s:4:"0.56";s:2:"غ";s:5:"-1.22";s:2:"ف";s:5:"-0.17";s:2:"ق";s:5:"-0.66";s:2:"ك";s:4:"0.34";s:2:"ل";s:1:"0";s:2:"م";s:3:"1.7";s:2:"ن";s:4:"2.01";s:2:"ه";s:5:"-0.54";s:2:"و";s:4:"1.15";s:2:"ى";s:5:"-3.95";s:2:"ي";s:4:"0.25";}s:2:"ؤ";a:37:{s:1:" ";s:5:"-1.55";s:2:"ء";s:5:"-6.14";s:2:"آ";s:5:"-6.14";s:2:"أ";s:5:"-6.14";s:2:"ؤ";s:5:"-6.14";s:2:"إ";s:5:"-6.14";s:2:"ئ";s:5:"-6.14";s:2:"ا";s:5:"-0.06";s:2:"ب";s:5:"-3.09";s:2:"ة";s:5:"-4.53";s:2:"ت";s:4:"1.13";s:2:"ث";s:5:"-0.38";s:2:"ج";s:5:"-1.56";s:2:"ح";s:5:"-6.14";s:2:"خ";s:4:"0.63";s:2:"د";s:4:"0.64";s:2:"ذ";s:5:"-2.42";s:2:"ر";s:5:"-1.68";s:2:"ز";s:5:"-6.14";s:2:"س";s:4:"0.89";s:2:"ش";s:4:"0.04";s:2:"ص";s:5:"-6.14";s:2:"ض";s:5:"-6.14";s:2:"ط";s:5:"-6.14";s:2:"ظ";s:5:"-6.14";s:2:"ع";s:5:"-6.14";s:2:"غ";s:5:"-6.14";s:2:"ف";s:5:"-6.14";s:2:"ق";s:4:"0.31";s:2:"ك";s:4:"0.82";s:2:"ل";s:4:"0.66";s:2:"م";s:5:"-0.94";s:2:"ن";s:4:"-1.9";s:2:"ه";s:5:"-0.26";s:2:"و";s:4:"2.75";s:2:"ى";s:5:"-3.43";s:2:"ي";s:4:"0.52";}s:2:"إ";a:37:{s:1:" ";s:4:"-4.6";s:2:"ء";s:2:"-8";s:2:"آ";s:2:"-8";s:2:"أ";s:2:"-8";s:2:"ؤ";s:2:"-8";s:2:"إ";s:2:"-8";s:2:"ئ";s:5:"-4.91";s:2:"ا";s:5:"-6.61";s:2:"ب";s:5:"-0.88";s:2:"ة";s:2:"-8";s:2:"ت";s:5:"-2.01";s:2:"ث";s:5:"-0.88";s:2:"ج";s:3:"0.1";s:2:"ح";s:5:"-0.41";s:2:"خ";s:5:"-1.14";s:2:"د";s:5:"-0.13";s:2:"ذ";s:5:"-0.26";s:2:"ر";s:3:"0.1";s:2:"ز";s:5:"-1.88";s:2:"س";s:4:"1.14";s:2:"ش";s:5:"-1.19";s:2:"ص";s:3:"0.2";s:2:"ض";s:5:"-0.43";s:2:"ط";s:4:"0.02";s:2:"ظ";s:5:"-3.92";s:2:"ع";s:4:"0.53";s:2:"غ";s:5:"-1.36";s:2:"ف";s:5:"-0.99";s:2:"ق";s:5:"-0.29";s:2:"ك";s:4:"-2.7";s:2:"ل";s:4:"2.28";s:2:"م";s:4:"0.17";s:2:"ن";s:3:"2.1";s:2:"ه";s:5:"-3.41";s:2:"و";s:5:"-5.92";s:2:"ى";s:2:"-8";s:2:"ي";s:4:"0.86";}s:2:"ئ";a:37:{s:1:" ";s:5:"-0.39";s:2:"ء";s:4:"-6.2";s:2:"آ";s:4:"-7.3";s:2:"أ";s:4:"-7.3";s:2:"ؤ";s:4:"-7.3";s:2:"إ";s:4:"-7.3";s:2:"ئ";s:4:"-7.3";s:2:"ا";s:4:"0.43";s:2:"ب";s:4:"0.02";s:2:"ة";s:4:"1.42";s:2:"ت";s:5:"-1.03";s:2:"ث";s:5:"-6.61";s:2:"ج";s:5:"-0.17";s:2:"ح";s:5:"-0.85";s:2:"خ";s:4:"-7.3";s:2:"د";s:4:"0.23";s:2:"ذ";s:5:"-3.37";s:2:"ر";s:1:"1";s:2:"ز";s:5:"-0.13";s:2:"س";s:4:"-2.8";s:2:"ش";s:5:"-5.51";s:2:"ص";s:5:"-2.73";s:2:"ض";s:5:"-2.97";s:2:"ط";s:5:"-2.62";s:2:"ظ";s:4:"-7.3";s:2:"ع";s:5:"-0.83";s:2:"غ";s:5:"-5.51";s:2:"ف";s:5:"-0.65";s:2:"ق";s:1:"0";s:2:"ك";s:5:"-2.09";s:2:"ل";s:4:"1.09";s:2:"م";s:4:"0.32";s:2:"ن";s:5:"-0.34";s:2:"ه";s:5:"-0.15";s:2:"و";s:5:"-1.99";s:2:"ى";s:4:"-6.2";s:2:"ي";s:4:"2.69";}s:2:"ا";a:37:{s:1:" ";s:4:"1.37";s:2:"ء";s:5:"-0.19";s:2:"آ";s:5:"-10.8";s:2:"أ";s:4:"-8.6";s:2:"ؤ";s:5:"-4.54";s:2:"إ";s:4:"-8.6";s:2:"ئ";s:5:"-0.29";s:2:"ا";s:4:"-6.7";s:2:"ب";s:5:"-0.12";s:2:"ة";s:5:"-2.01";s:2:"ت";s:4:"0.75";s:2:"ث";s:5:"-1.28";s:2:"ج";s:4:"-1.1";s:2:"ح";s:4:"-0.9";s:2:"خ";s:4:"-1.9";s:2:"د";s:5:"-0.03";s:2:"ذ";s:5:"-3.32";s:2:"ر";s:4:"0.65";s:2:"ز";s:5:"-1.67";s:2:"س";s:4:"-0.3";s:2:"ش";s:5:"-2.02";s:2:"ص";s:4:"-1.4";s:2:"ض";s:4:"-1.5";s:2:"ط";s:5:"-1.59";s:2:"ظ";s:5:"-4.43";s:2:"ع";s:5:"-0.38";s:2:"غ";s:5:"-2.79";s:2:"ف";s:5:"-0.73";s:2:"ق";s:5:"-0.25";s:2:"ك";s:5:"-1.26";s:2:"ل";s:4:"2.75";s:2:"م";s:4:"0.16";s:2:"ن";s:4:"0.87";s:2:"ه";s:5:"-1.58";s:2:"و";s:5:"-1.18";s:2:"ى";s:4:"-9.7";s:2:"ي";s:5:"-0.63";}s:2:"ب";a:37:{s:1:" ";s:4:"1.62";s:2:"ء";s:4:"-5.4";s:2:"آ";s:5:"-5.29";s:2:"أ";s:4:"-0.7";s:2:"ؤ";s:5:"-5.15";s:2:"إ";s:5:"-1.62";s:2:"ئ";s:5:"-3.81";s:2:"ا";s:4:"1.86";s:2:"ب";s:5:"-0.94";s:2:"ة";s:4:"0.14";s:2:"ت";s:4:"0.06";s:2:"ث";s:5:"-2.16";s:2:"ج";s:5:"-1.62";s:2:"ح";s:5:"-0.15";s:2:"خ";s:5:"-2.38";s:2:"د";s:5:"-0.03";s:2:"ذ";s:5:"-2.34";s:2:"ر";s:4:"1.05";s:2:"ز";s:5:"-3.03";s:2:"س";s:5:"-1.06";s:2:"ش";s:5:"-0.88";s:2:"ص";s:5:"-1.86";s:2:"ض";s:5:"-2.36";s:2:"ط";s:5:"-0.49";s:2:"ظ";s:5:"-5.19";s:2:"ع";s:4:"0.91";s:2:"غ";s:5:"-1.01";s:2:"ف";s:5:"-1.97";s:2:"ق";s:5:"-0.21";s:2:"ك";s:5:"-1.02";s:2:"ل";s:4:"0.75";s:2:"م";s:5:"-0.44";s:2:"ن";s:4:"0.09";s:2:"ه";s:4:"0.06";s:2:"و";s:4:"0.53";s:2:"ى";s:5:"-5.57";s:2:"ي";s:4:"1.52";}s:2:"ة";a:37:{s:1:" ";s:4:"3.61";s:2:"ء";s:5:"-9.25";s:2:"آ";s:5:"-9.25";s:2:"أ";s:5:"-7.46";s:2:"ؤ";s:5:"-9.25";s:2:"إ";s:5:"-9.25";s:2:"ئ";s:5:"-9.25";s:2:"ا";s:5:"-6.41";s:2:"ب";s:5:"-9.25";s:2:"ة";s:5:"-8.15";s:2:"ت";s:5:"-9.25";s:2:"ث";s:5:"-9.25";s:2:"ج";s:5:"-9.25";s:2:"ح";s:5:"-9.25";s:2:"خ";s:5:"-9.25";s:2:"د";s:5:"-9.25";s:2:"ذ";s:5:"-9.25";s:2:"ر";s:5:"-9.25";s:2:"ز";s:5:"-8.56";s:2:"س";s:5:"-9.25";s:2:"ش";s:5:"-9.25";s:2:"ص";s:5:"-8.56";s:2:"ض";s:5:"-9.25";s:2:"ط";s:5:"-6.68";s:2:"ظ";s:5:"-9.25";s:2:"ع";s:5:"-6.85";s:2:"غ";s:5:"-9.25";s:2:"ف";s:5:"-8.56";s:2:"ق";s:5:"-9.25";s:2:"ك";s:5:"-9.25";s:2:"ل";s:5:"-8.56";s:2:"م";s:5:"-7.64";s:2:"ن";s:5:"-7.86";s:2:"ه";s:5:"-9.25";s:2:"و";s:4:"-7.3";s:2:"ى";s:5:"-9.25";s:2:"ي";s:5:"-9.25";}s:2:"ت";a:37:{s:1:" ";s:4:"2.32";s:2:"ء";s:5:"-9.62";s:2:"آ";s:5:"-5.71";s:2:"أ";s:5:"-0.89";s:2:"ؤ";s:5:"-2.87";s:2:"إ";s:5:"-9.62";s:2:"ئ";s:5:"-3.22";s:2:"ا";s:4:"0.37";s:2:"ب";s:5:"-0.21";s:2:"ة";s:5:"-2.08";s:2:"ت";s:5:"-0.75";s:2:"ث";s:5:"-2.16";s:2:"ج";s:4:"-0.4";s:2:"ح";s:4:"0.67";s:2:"خ";s:5:"-0.07";s:2:"د";s:4:"-0.5";s:2:"ذ";s:5:"-2.75";s:2:"ر";s:4:"0.49";s:2:"ز";s:5:"-1.38";s:2:"س";s:5:"-0.35";s:2:"ش";s:4:"-0.1";s:2:"ص";s:5:"-0.32";s:2:"ض";s:5:"-1.56";s:2:"ط";s:5:"-0.94";s:2:"ظ";s:5:"-2.08";s:2:"ع";s:4:"0.24";s:2:"غ";s:5:"-1.66";s:2:"ف";s:5:"-0.08";s:2:"ق";s:4:"0.48";s:2:"ك";s:5:"-0.94";s:2:"ل";s:4:"0.09";s:2:"م";s:4:"0.39";s:2:"ن";s:5:"-0.22";s:2:"ه";s:4:"0.71";s:2:"و";s:4:"0.53";s:2:"ى";s:5:"-1.83";s:2:"ي";s:4:"0.91";}s:2:"ث";a:37:{s:1:" ";s:4:"2.11";s:2:"ء";s:5:"-7.62";s:2:"آ";s:5:"-7.62";s:2:"أ";s:4:"-3.9";s:2:"ؤ";s:5:"-7.62";s:2:"إ";s:5:"-7.62";s:2:"ئ";s:5:"-7.62";s:2:"ا";s:4:"1.91";s:2:"ب";s:5:"-0.93";s:2:"ة";s:4:"1.12";s:2:"ت";s:5:"-1.11";s:2:"ث";s:5:"-1.73";s:2:"ج";s:5:"-7.62";s:2:"ح";s:5:"-7.62";s:2:"خ";s:5:"-6.01";s:2:"د";s:5:"-2.32";s:2:"ذ";s:5:"-7.62";s:2:"ر";s:4:"1.17";s:2:"ز";s:5:"-7.62";s:2:"س";s:5:"-6.23";s:2:"ش";s:5:"-7.62";s:2:"ص";s:5:"-7.62";s:2:"ض";s:5:"-7.62";s:2:"ط";s:5:"-7.62";s:2:"ظ";s:5:"-7.62";s:2:"ع";s:5:"-5.13";s:2:"غ";s:5:"-3.93";s:2:"ف";s:5:"-2.29";s:2:"ق";s:4:"-0.6";s:2:"ك";s:5:"-4.48";s:2:"ل";s:4:"1.77";s:2:"م";s:4:"0.67";s:2:"ن";s:4:"1.04";s:2:"ه";s:4:"-1.4";s:2:"و";s:5:"-0.14";s:2:"ى";s:5:"-3.77";s:2:"ي";s:4:"0.78";}s:2:"ج";a:37:{s:1:" ";s:4:"1.12";s:2:"ء";s:5:"-8.42";s:2:"آ";s:5:"-4.92";s:2:"أ";s:5:"-2.44";s:2:"ؤ";s:5:"-7.03";s:2:"إ";s:5:"-8.42";s:2:"ئ";s:5:"-2.17";s:2:"ا";s:4:"1.74";s:2:"ب";s:5:"-0.54";s:2:"ة";s:4:"0.05";s:2:"ت";s:5:"-0.21";s:2:"ث";s:5:"-1.72";s:2:"ج";s:5:"-5.01";s:2:"ح";s:5:"-1.29";s:2:"خ";s:5:"-8.42";s:2:"د";s:4:"0.71";s:2:"ذ";s:5:"-2.65";s:2:"ر";s:4:"0.99";s:2:"ز";s:5:"-0.11";s:2:"س";s:5:"-1.46";s:2:"ش";s:5:"-6.34";s:2:"ص";s:5:"-8.42";s:2:"ض";s:5:"-8.42";s:2:"ط";s:5:"-8.42";s:2:"ظ";s:5:"-8.42";s:2:"ع";s:4:"-0.4";s:2:"غ";s:5:"-4.78";s:2:"ف";s:5:"-2.47";s:2:"ق";s:5:"-8.42";s:2:"ك";s:5:"-5.47";s:2:"ل";s:4:"0.88";s:2:"م";s:4:"1.44";s:2:"ن";s:4:"1.13";s:2:"ه";s:4:"0.87";s:2:"و";s:4:"1.01";s:2:"ى";s:5:"-6.22";s:2:"ي";s:4:"1.27";}s:2:"ح";a:37:{s:1:" ";s:4:"1.21";s:2:"ء";s:5:"-8.61";s:2:"آ";s:5:"-8.61";s:2:"أ";s:5:"-8.61";s:2:"ؤ";s:5:"-7.91";s:2:"إ";s:5:"-8.61";s:2:"ئ";s:5:"-8.61";s:2:"ا";s:4:"1.71";s:2:"ب";s:5:"-0.76";s:2:"ة";s:4:"0.37";s:2:"ت";s:4:"0.77";s:2:"ث";s:5:"-0.67";s:2:"ج";s:5:"-1.33";s:2:"ح";s:4:"-6.3";s:2:"خ";s:5:"-8.61";s:2:"د";s:4:"1.64";s:2:"ذ";s:5:"-1.25";s:2:"ر";s:4:"0.79";s:2:"ز";s:5:"-0.71";s:2:"س";s:4:"0.11";s:2:"ش";s:5:"-2.53";s:2:"ص";s:5:"-0.44";s:2:"ض";s:5:"-1.58";s:2:"ط";s:5:"-1.16";s:2:"ظ";s:5:"-1.15";s:2:"ع";s:5:"-8.61";s:2:"غ";s:5:"-8.61";s:2:"ف";s:5:"-0.32";s:2:"ق";s:4:"0.48";s:2:"ك";s:4:"0.57";s:2:"ل";s:4:"0.34";s:2:"م";s:4:"0.45";s:2:"ن";s:5:"-1.35";s:2:"ه";s:5:"-1.67";s:2:"و";s:4:"0.62";s:2:"ى";s:5:"-2.72";s:2:"ي";s:3:"1.1";}s:2:"خ";a:37:{s:1:" ";s:4:"0.29";s:2:"ء";s:5:"-7.86";s:2:"آ";s:5:"-7.86";s:2:"أ";s:5:"-7.86";s:2:"ؤ";s:5:"-7.86";s:2:"إ";s:5:"-7.86";s:2:"ئ";s:5:"-7.86";s:2:"ا";s:3:"1.9";s:2:"ب";s:4:"0.67";s:2:"ة";s:5:"-1.26";s:2:"ت";s:3:"0.7";s:2:"ث";s:5:"-5.46";s:2:"ج";s:5:"-4.42";s:2:"ح";s:5:"-7.86";s:2:"خ";s:5:"-1.89";s:2:"د";s:4:"0.33";s:2:"ذ";s:5:"-0.76";s:2:"ر";s:4:"1.48";s:2:"ز";s:5:"-1.28";s:2:"س";s:5:"-0.63";s:2:"ش";s:5:"-2.17";s:2:"ص";s:4:"0.81";s:2:"ض";s:2:"-1";s:2:"ط";s:4:"0.91";s:2:"ظ";s:5:"-7.86";s:2:"ع";s:5:"-7.86";s:2:"غ";s:5:"-7.86";s:2:"ف";s:4:"0.03";s:2:"ق";s:5:"-7.86";s:2:"ك";s:5:"-7.86";s:2:"ل";s:4:"1.77";s:2:"م";s:4:"0.84";s:2:"ن";s:5:"-1.86";s:2:"ه";s:5:"-2.76";s:2:"و";s:4:"-0.2";s:2:"ى";s:5:"-6.47";s:2:"ي";s:4:"0.71";}s:2:"د";a:37:{s:1:" ";s:4:"2.38";s:2:"ء";s:5:"-2.22";s:2:"آ";s:5:"-9.22";s:2:"أ";s:5:"-1.33";s:2:"ؤ";s:5:"-6.09";s:2:"إ";s:5:"-9.22";s:2:"ئ";s:5:"-3.01";s:2:"ا";s:4:"1.35";s:2:"ب";s:5:"-1.13";s:2:"ة";s:4:"1.09";s:2:"ت";s:5:"-0.39";s:2:"ث";s:5:"-0.44";s:2:"ج";s:5:"-3.18";s:2:"ح";s:5:"-3.73";s:2:"خ";s:5:"-1.36";s:2:"د";s:5:"-0.07";s:2:"ذ";s:5:"-7.61";s:2:"ر";s:4:"0.68";s:2:"ز";s:5:"-2.74";s:2:"س";s:5:"-1.34";s:2:"ش";s:5:"-4.58";s:2:"ص";s:5:"-9.22";s:2:"ض";s:5:"-9.22";s:2:"ط";s:5:"-9.22";s:2:"ظ";s:5:"-9.22";s:2:"ع";s:5:"-0.58";s:2:"غ";s:4:"-5.4";s:2:"ف";s:5:"-0.14";s:2:"ق";s:5:"-1.09";s:2:"ك";s:5:"-2.85";s:2:"ل";s:4:"-0.5";s:2:"م";s:4:"0.45";s:2:"ن";s:5:"-0.39";s:2:"ه";s:5:"-0.39";s:2:"و";s:3:"1.2";s:2:"ى";s:5:"-0.78";s:2:"ي";s:4:"1.53";}s:2:"ذ";a:37:{s:1:" ";s:4:"1.48";s:2:"ء";s:5:"-7.39";s:2:"آ";s:5:"-7.39";s:2:"أ";s:5:"-7.39";s:2:"ؤ";s:5:"-7.39";s:2:"إ";s:5:"-7.39";s:2:"ئ";s:5:"-5.08";s:2:"ا";s:4:"2.11";s:2:"ب";s:5:"-0.99";s:2:"ة";s:5:"-1.85";s:2:"ت";s:5:"-1.33";s:2:"ث";s:5:"-7.39";s:2:"ج";s:5:"-3.29";s:2:"ح";s:5:"-7.39";s:2:"خ";s:5:"-2.56";s:2:"د";s:5:"-7.39";s:2:"ذ";s:5:"-7.39";s:2:"ر";s:4:"0.11";s:2:"ز";s:5:"-7.39";s:2:"س";s:5:"-7.39";s:2:"ش";s:5:"-7.39";s:2:"ص";s:5:"-7.39";s:2:"ض";s:5:"-7.39";s:2:"ط";s:5:"-7.39";s:2:"ظ";s:5:"-7.39";s:2:"ع";s:5:"-2.17";s:2:"غ";s:5:"-7.39";s:2:"ف";s:5:"-2.94";s:2:"ق";s:5:"-4.25";s:2:"ك";s:4:"1.03";s:2:"ل";s:4:"1.48";s:2:"م";s:5:"-3.49";s:2:"ن";s:5:"-1.75";s:2:"ه";s:3:"1.4";s:2:"و";s:5:"-1.06";s:2:"ى";s:5:"-2.67";s:2:"ي";s:4:"2.33";}s:2:"ر";a:37:{s:1:" ";s:3:"2.1";s:2:"ء";s:5:"-5.99";s:2:"آ";s:4:"-4.9";s:2:"أ";s:5:"-1.86";s:2:"ؤ";s:5:"-3.32";s:2:"إ";s:4:"-9.7";s:2:"ئ";s:5:"-0.31";s:2:"ا";s:4:"1.85";s:2:"ب";s:4:"0.49";s:2:"ة";s:4:"0.78";s:2:"ت";s:3:"0.1";s:2:"ث";s:5:"-2.78";s:2:"ج";s:5:"-0.31";s:2:"ح";s:5:"-1.13";s:2:"خ";s:5:"-3.68";s:2:"د";s:5:"-0.71";s:2:"ذ";s:4:"-7.4";s:2:"ر";s:5:"-1.72";s:2:"ز";s:5:"-1.31";s:2:"س";s:5:"-0.41";s:2:"ش";s:5:"-1.79";s:2:"ص";s:5:"-1.65";s:2:"ض";s:5:"-0.46";s:2:"ط";s:5:"-1.14";s:2:"ظ";s:4:"-9.7";s:2:"ع";s:5:"-0.99";s:2:"غ";s:5:"-1.53";s:2:"ف";s:5:"-0.43";s:2:"ق";s:5:"-0.67";s:2:"ك";s:4:"0.25";s:2:"ل";s:5:"-1.82";s:2:"م";s:5:"-1.21";s:2:"ن";s:5:"-0.59";s:2:"ه";s:5:"-0.46";s:2:"و";s:4:"0.59";s:2:"ى";s:4:"-1.1";s:2:"ي";s:4:"1.75";}s:2:"ز";a:37:{s:1:" ";s:4:"2.24";s:2:"ء";s:5:"-1.25";s:2:"آ";s:5:"-7.02";s:2:"أ";s:5:"-4.88";s:2:"ؤ";s:5:"-7.71";s:2:"إ";s:5:"-7.71";s:2:"ئ";s:5:"-2.45";s:2:"ا";s:4:"1.83";s:2:"ب";s:5:"-0.04";s:2:"ة";s:4:"0.67";s:2:"ت";s:4:"-0.6";s:2:"ث";s:5:"-7.71";s:2:"ج";s:5:"-2.38";s:2:"ح";s:5:"-2.39";s:2:"خ";s:4:"-3.8";s:2:"د";s:5:"-1.48";s:2:"ذ";s:5:"-7.71";s:2:"ر";s:4:"0.75";s:2:"ز";s:5:"-1.75";s:2:"س";s:5:"-3.54";s:2:"ش";s:5:"-7.71";s:2:"ص";s:5:"-7.71";s:2:"ض";s:5:"-7.71";s:2:"ط";s:5:"-7.71";s:2:"ظ";s:5:"-7.71";s:2:"ع";s:4:"0.35";s:2:"غ";s:5:"-3.76";s:2:"ف";s:5:"-2.14";s:2:"ق";s:5:"-2.56";s:2:"ك";s:5:"-2.37";s:2:"ل";s:4:"0.15";s:2:"م";s:4:"0.43";s:2:"ن";s:5:"-0.54";s:2:"ه";s:4:"-0.3";s:2:"و";s:4:"0.63";s:2:"ى";s:5:"-4.07";s:2:"ي";s:4:"1.97";}s:2:"س";a:37:{s:1:" ";s:4:"1.62";s:2:"ء";s:5:"-4.87";s:2:"آ";s:5:"-9.01";s:2:"أ";s:5:"-2.39";s:2:"ؤ";s:5:"-0.21";s:2:"إ";s:5:"-9.01";s:2:"ئ";s:4:"-3.6";s:2:"ا";s:4:"1.35";s:2:"ب";s:4:"0.92";s:2:"ة";s:5:"-0.07";s:2:"ت";s:4:"1.64";s:2:"ث";s:5:"-7.06";s:2:"ج";s:5:"-0.57";s:2:"ح";s:5:"-1.06";s:2:"خ";s:5:"-2.58";s:2:"د";s:4:"-1.8";s:2:"ذ";s:5:"-9.01";s:2:"ر";s:3:"0.4";s:2:"ز";s:5:"-7.62";s:2:"س";s:5:"-1.86";s:2:"ش";s:5:"-7.06";s:2:"ص";s:5:"-9.01";s:2:"ض";s:5:"-9.01";s:2:"ط";s:4:"0.01";s:2:"ظ";s:5:"-9.01";s:2:"ع";s:4:"0.17";s:2:"غ";s:5:"-4.68";s:2:"ف";s:5:"-0.68";s:2:"ق";s:4:"-1.8";s:2:"ك";s:5:"-0.02";s:2:"ل";s:3:"0.8";s:2:"م";s:3:"0.3";s:2:"ن";s:5:"-0.04";s:2:"ه";s:4:"-0.8";s:2:"و";s:4:"0.71";s:2:"ى";s:5:"-3.39";s:2:"ي";s:4:"1.57";}s:2:"ش";a:37:{s:1:" ";s:3:"1.1";s:2:"ء";s:5:"-8.09";s:2:"آ";s:5:"-3.12";s:2:"أ";s:5:"-0.31";s:2:"ؤ";s:5:"-1.89";s:2:"إ";s:5:"-8.09";s:2:"ئ";s:5:"-3.43";s:2:"ا";s:4:"1.69";s:2:"ب";s:5:"-0.02";s:2:"ة";s:5:"-1.73";s:2:"ت";s:4:"0.26";s:2:"ث";s:5:"-8.09";s:2:"ج";s:5:"-1.65";s:2:"ح";s:5:"-0.85";s:2:"خ";s:3:"0.4";s:2:"د";s:5:"-0.05";s:2:"ذ";s:5:"-4.79";s:2:"ر";s:4:"2.05";s:2:"ز";s:5:"-6.99";s:2:"س";s:5:"-2.07";s:2:"ش";s:5:"-8.09";s:2:"ص";s:5:"-8.09";s:2:"ض";s:5:"-8.09";s:2:"ط";s:5:"-1.22";s:2:"ظ";s:4:"-4.3";s:2:"ع";s:5:"-0.13";s:2:"غ";s:5:"-1.53";s:2:"ف";s:4:"0.12";s:2:"ق";s:5:"-0.86";s:2:"ك";s:4:"0.33";s:2:"ل";s:5:"-0.65";s:2:"م";s:4:"0.34";s:2:"ن";s:4:"0.02";s:2:"ه";s:4:"0.77";s:2:"و";s:5:"-0.16";s:2:"ى";s:5:"-2.94";s:2:"ي";s:4:"1.35";}s:2:"ص";a:37:{s:1:" ";s:4:"0.88";s:2:"ء";s:5:"-7.97";s:2:"آ";s:5:"-7.97";s:2:"أ";s:5:"-7.97";s:2:"ؤ";s:5:"-7.97";s:2:"إ";s:5:"-7.97";s:2:"ئ";s:5:"-7.97";s:2:"ا";s:4:"1.88";s:2:"ب";s:4:"0.32";s:2:"ة";s:4:"0.32";s:2:"ت";s:5:"-2.04";s:2:"ث";s:5:"-7.97";s:2:"ج";s:5:"-7.97";s:2:"ح";s:3:"0.8";s:2:"خ";s:5:"-2.92";s:2:"د";s:4:"1.03";s:2:"ذ";s:5:"-7.97";s:2:"ر";s:4:"1.46";s:2:"ز";s:5:"-7.97";s:2:"س";s:5:"-7.97";s:2:"ش";s:5:"-7.97";s:2:"ص";s:4:"-1.2";s:2:"ض";s:5:"-7.97";s:2:"ط";s:5:"-1.85";s:2:"ظ";s:5:"-7.97";s:2:"ع";s:5:"-0.13";s:2:"غ";s:5:"-1.13";s:2:"ف";s:4:"0.71";s:2:"ق";s:5:"-2.82";s:2:"ك";s:5:"-6.87";s:2:"ل";s:4:"1.25";s:2:"م";s:1:"0";s:2:"ن";s:4:"0.18";s:2:"ه";s:5:"-2.37";s:2:"و";s:4:"1.03";s:2:"ى";s:5:"-2.12";s:2:"ي";s:4:"1.21";}s:2:"ض";a:37:{s:1:" ";s:4:"2.07";s:2:"ء";s:5:"-7.62";s:2:"آ";s:5:"-5.42";s:2:"أ";s:5:"-6.92";s:2:"ؤ";s:5:"-6.92";s:2:"إ";s:5:"-7.62";s:2:"ئ";s:5:"-2.94";s:2:"ا";s:4:"2.01";s:2:"ب";s:5:"-0.83";s:2:"ة";s:4:"0.06";s:2:"ت";s:5:"-0.25";s:2:"ث";s:5:"-7.62";s:2:"ج";s:5:"-3.72";s:2:"ح";s:4:"0.73";s:2:"خ";s:5:"-1.06";s:2:"د";s:5:"-0.07";s:2:"ذ";s:5:"-7.62";s:2:"ر";s:4:"0.73";s:2:"ز";s:5:"-7.62";s:2:"س";s:5:"-7.62";s:2:"ش";s:5:"-7.62";s:2:"ص";s:5:"-7.62";s:2:"ض";s:5:"-6.52";s:2:"ط";s:5:"-1.32";s:2:"ظ";s:5:"-7.62";s:2:"ع";s:4:"0.44";s:2:"غ";s:5:"-0.91";s:2:"ف";s:5:"-1.13";s:2:"ق";s:5:"-7.62";s:2:"ك";s:5:"-6.01";s:2:"ل";s:5:"-0.01";s:2:"م";s:4:"0.79";s:2:"ن";s:5:"-2.62";s:2:"ه";s:4:"-0.5";s:2:"و";s:4:"0.49";s:2:"ى";s:4:"-0.7";s:2:"ي";s:4:"1.67";}s:2:"ط";a:37:{s:1:" ";s:4:"1.63";s:2:"ء";s:5:"-4.13";s:2:"آ";s:5:"-8.04";s:2:"أ";s:5:"-2.11";s:2:"ؤ";s:5:"-4.19";s:2:"إ";s:5:"-8.04";s:2:"ئ";s:5:"-2.28";s:2:"ا";s:4:"2.07";s:2:"ب";s:4:"0.26";s:2:"ة";s:4:"1.05";s:2:"ت";s:5:"-1.33";s:2:"ث";s:5:"-8.04";s:2:"ج";s:5:"-6.66";s:2:"ح";s:5:"-2.46";s:2:"خ";s:5:"-4.75";s:2:"د";s:5:"-2.99";s:2:"ذ";s:5:"-8.04";s:2:"ر";s:4:"0.94";s:2:"ز";s:5:"-8.04";s:2:"س";s:5:"-1.95";s:2:"ش";s:5:"-4.41";s:2:"ص";s:5:"-8.04";s:2:"ض";s:5:"-8.04";s:2:"ط";s:5:"-1.24";s:2:"ظ";s:5:"-8.04";s:2:"ع";s:5:"-0.49";s:2:"غ";s:5:"-4.09";s:2:"ف";s:4:"0.34";s:2:"ق";s:4:"0.53";s:2:"ك";s:5:"-8.04";s:2:"ل";s:4:"1.17";s:2:"م";s:5:"-1.27";s:2:"ن";s:4:"0.67";s:2:"ه";s:5:"-0.95";s:2:"و";s:4:"1.07";s:2:"ى";s:5:"-2.41";s:2:"ي";s:4:"1.25";}s:2:"ظ";a:37:{s:1:" ";s:4:"0.91";s:2:"ء";s:5:"-6.36";s:2:"آ";s:5:"-6.36";s:2:"أ";s:5:"-6.36";s:2:"ؤ";s:5:"-6.36";s:2:"إ";s:5:"-6.36";s:2:"ئ";s:5:"-6.36";s:2:"ا";s:3:"1.9";s:2:"ب";s:5:"-0.92";s:2:"ة";s:4:"0.56";s:2:"ت";s:5:"-1.75";s:2:"ث";s:5:"-6.36";s:2:"ج";s:5:"-6.36";s:2:"ح";s:5:"-6.36";s:2:"خ";s:5:"-6.36";s:2:"د";s:5:"-6.36";s:2:"ذ";s:5:"-6.36";s:2:"ر";s:4:"1.66";s:2:"ز";s:5:"-6.36";s:2:"س";s:5:"-6.36";s:2:"ش";s:5:"-6.36";s:2:"ص";s:5:"-6.36";s:2:"ض";s:5:"-6.36";s:2:"ط";s:5:"-6.36";s:2:"ظ";s:5:"-6.36";s:2:"ع";s:5:"-5.26";s:2:"غ";s:5:"-6.36";s:2:"ف";s:4:"0.24";s:2:"ق";s:5:"-6.36";s:2:"ك";s:5:"-6.36";s:2:"ل";s:4:"0.32";s:2:"م";s:4:"1.92";s:2:"ن";s:5:"-2.05";s:2:"ه";s:4:"1.51";s:2:"و";s:5:"-0.11";s:2:"ى";s:5:"-1.31";s:2:"ي";s:4:"1.59";}s:2:"ع";a:37:{s:1:" ";s:4:"1.63";s:2:"ء";s:5:"-9.24";s:2:"آ";s:5:"-9.24";s:2:"أ";s:5:"-9.24";s:2:"ؤ";s:5:"-9.24";s:2:"إ";s:5:"-9.24";s:2:"ئ";s:5:"-9.24";s:2:"ا";s:3:"1.6";s:2:"ب";s:4:"0.08";s:2:"ة";s:4:"0.67";s:2:"ت";s:3:"0.3";s:2:"ث";s:5:"-1.74";s:2:"ج";s:5:"-2.55";s:2:"ح";s:5:"-9.24";s:2:"خ";s:5:"-9.24";s:2:"د";s:4:"1.18";s:2:"ذ";s:5:"-3.31";s:2:"ر";s:4:"1.14";s:2:"ز";s:5:"-1.65";s:2:"س";s:5:"-0.98";s:2:"ش";s:5:"-0.93";s:2:"ص";s:5:"-2.17";s:2:"ض";s:5:"-0.72";s:2:"ط";s:5:"-2.23";s:2:"ظ";s:5:"-2.32";s:2:"ع";s:5:"-9.24";s:2:"غ";s:5:"-9.24";s:2:"ف";s:5:"-2.16";s:2:"ق";s:5:"-0.51";s:2:"ك";s:5:"-2.94";s:2:"ل";s:4:"1.77";s:2:"م";s:4:"0.67";s:2:"ن";s:4:"0.92";s:2:"ه";s:5:"-0.66";s:2:"و";s:4:"0.02";s:2:"ى";s:5:"-2.33";s:2:"ي";s:4:"0.31";}s:2:"غ";a:37:{s:1:" ";s:4:"1.51";s:2:"ء";s:4:"-6.2";s:2:"آ";s:4:"-7.3";s:2:"أ";s:4:"-7.3";s:2:"ؤ";s:4:"-7.3";s:2:"إ";s:4:"-7.3";s:2:"ئ";s:4:"-7.3";s:2:"ا";s:4:"1.76";s:2:"ب";s:5:"-0.56";s:2:"ة";s:5:"-0.61";s:2:"ت";s:4:"0.47";s:2:"ث";s:5:"-4.59";s:2:"ج";s:2:"-5";s:2:"ح";s:4:"-7.3";s:2:"خ";s:4:"-7.3";s:2:"د";s:4:"0.91";s:2:"ذ";s:5:"-0.33";s:2:"ر";s:4:"1.49";s:2:"ز";s:4:"0.41";s:2:"س";s:5:"-0.89";s:2:"ش";s:5:"-3.19";s:2:"ص";s:5:"-4.04";s:2:"ض";s:4:"-0.8";s:2:"ط";s:5:"-0.51";s:2:"ظ";s:4:"-7.3";s:2:"ع";s:5:"-6.61";s:2:"غ";s:4:"-7.3";s:2:"ف";s:5:"-3.01";s:2:"ق";s:4:"-7.3";s:2:"ك";s:5:"-4.66";s:2:"ل";s:3:"0.8";s:2:"م";s:4:"0.48";s:2:"ن";s:4:"0.12";s:2:"ه";s:5:"-1.52";s:2:"و";s:4:"0.83";s:2:"ى";s:5:"-3.13";s:2:"ي";s:4:"1.73";}s:2:"ف";a:37:{s:1:" ";s:4:"1.45";s:2:"ء";s:5:"-4.74";s:2:"آ";s:5:"-6.85";s:2:"أ";s:5:"-3.09";s:2:"ؤ";s:5:"-5.33";s:2:"إ";s:5:"-1.75";s:2:"ئ";s:5:"-2.58";s:2:"ا";s:4:"1.12";s:2:"ب";s:5:"-2.56";s:2:"ة";s:4:"0.03";s:2:"ت";s:4:"0.15";s:2:"ث";s:5:"-5.61";s:2:"ج";s:5:"-0.46";s:2:"ح";s:5:"-1.56";s:2:"خ";s:5:"-2.66";s:2:"د";s:5:"-2.45";s:2:"ذ";s:5:"-2.18";s:2:"ر";s:4:"0.78";s:2:"ز";s:5:"-1.73";s:2:"س";s:5:"-0.58";s:2:"ش";s:5:"-2.31";s:2:"ص";s:5:"-1.74";s:2:"ض";s:5:"-0.27";s:2:"ط";s:5:"-0.94";s:2:"ظ";s:5:"-1.48";s:2:"ع";s:5:"-0.24";s:2:"غ";s:5:"-1.44";s:2:"ف";s:5:"-3.27";s:2:"ق";s:4:"0.25";s:2:"ك";s:5:"-2.26";s:2:"ل";s:3:"0.2";s:2:"م";s:5:"-2.35";s:2:"ن";s:5:"-0.99";s:2:"ه";s:5:"-0.85";s:2:"و";s:3:"0.1";s:2:"ى";s:5:"-1.44";s:2:"ي";s:4:"2.74";}s:2:"ق";a:37:{s:1:" ";s:4:"1.77";s:2:"ء";s:5:"-4.72";s:2:"آ";s:5:"-8.98";s:2:"أ";s:5:"-8.98";s:2:"ؤ";s:5:"-8.98";s:2:"إ";s:5:"-8.98";s:2:"ئ";s:5:"-8.98";s:2:"ا";s:4:"1.99";s:2:"ب";s:4:"0.51";s:2:"ة";s:3:"0.5";s:2:"ت";s:4:"0.88";s:2:"ث";s:5:"-8.98";s:2:"ج";s:5:"-8.98";s:2:"ح";s:5:"-5.55";s:2:"خ";s:5:"-8.98";s:2:"د";s:4:"1.16";s:2:"ذ";s:5:"-2.44";s:2:"ر";s:4:"0.76";s:2:"ز";s:5:"-6.21";s:2:"س";s:4:"-2.1";s:2:"ش";s:5:"-2.49";s:2:"ص";s:5:"-1.05";s:2:"ض";s:5:"-0.93";s:2:"ط";s:5:"-0.13";s:2:"ظ";s:4:"-5.8";s:2:"ع";s:4:"0.03";s:2:"غ";s:5:"-8.98";s:2:"ف";s:5:"-0.95";s:2:"ق";s:5:"-1.49";s:2:"ك";s:4:"-6.9";s:2:"ل";s:4:"0.56";s:2:"م";s:5:"-1.02";s:2:"ن";s:5:"-1.07";s:2:"ه";s:4:"-1.3";s:2:"و";s:4:"0.94";s:2:"ى";s:5:"-1.75";s:2:"ي";s:4:"1.31";}s:2:"ك";a:37:{s:1:" ";s:4:"1.42";s:2:"ء";s:5:"-8.67";s:2:"آ";s:5:"-6.88";s:2:"أ";s:5:"-1.41";s:2:"ؤ";s:5:"-5.49";s:2:"إ";s:5:"-4.98";s:2:"ئ";s:5:"-7.57";s:2:"ا";s:3:"1.9";s:2:"ب";s:4:"0.15";s:2:"ة";s:4:"0.44";s:2:"ت";s:4:"0.22";s:2:"ث";s:4:"-0.1";s:2:"ج";s:5:"-4.39";s:2:"ح";s:5:"-3.16";s:2:"خ";s:5:"-5.45";s:2:"د";s:4:"-0.3";s:2:"ذ";s:5:"-1.43";s:2:"ر";s:4:"1.04";s:2:"ز";s:5:"-0.41";s:2:"س";s:4:"0.01";s:2:"ش";s:5:"-0.95";s:2:"ص";s:5:"-6.59";s:2:"ض";s:5:"-5.37";s:2:"ط";s:4:"-6.1";s:2:"ظ";s:5:"-7.98";s:2:"ع";s:5:"-3.62";s:2:"غ";s:5:"-5.03";s:2:"ف";s:5:"-1.88";s:2:"ق";s:5:"-4.66";s:2:"ك";s:5:"-3.07";s:2:"ل";s:4:"0.66";s:2:"م";s:4:"0.62";s:2:"ن";s:4:"0.51";s:2:"ه";s:5:"-1.61";s:2:"و";s:4:"1.39";s:2:"ى";s:5:"-4.61";s:2:"ي";s:4:"1.63";}s:2:"ل";a:37:{s:1:" ";s:4:"1.35";s:2:"ء";s:5:"-7.46";s:2:"آ";s:5:"-2.84";s:2:"أ";s:4:"0.74";s:2:"ؤ";s:5:"-7.51";s:2:"إ";s:5:"-0.25";s:2:"ئ";s:5:"-5.52";s:2:"ا";s:4:"1.18";s:2:"ب";s:4:"0.01";s:2:"ة";s:5:"-0.21";s:2:"ت";s:4:"0.68";s:2:"ث";s:5:"-0.95";s:2:"ج";s:5:"-0.29";s:2:"ح";s:5:"-0.14";s:2:"خ";s:5:"-0.88";s:2:"د";s:5:"-0.23";s:2:"ذ";s:5:"-0.79";s:2:"ر";s:5:"-0.62";s:2:"ز";s:4:"-1.8";s:2:"س";s:4:"0.19";s:2:"ش";s:5:"-0.61";s:2:"ص";s:5:"-0.96";s:2:"ض";s:5:"-2.35";s:2:"ط";s:5:"-1.12";s:2:"ظ";s:5:"-3.86";s:2:"ع";s:4:"0.46";s:2:"غ";s:5:"-1.19";s:2:"ف";s:5:"-0.25";s:2:"ق";s:4:"0.04";s:2:"ك";s:5:"-0.23";s:2:"ل";s:4:"0.11";s:2:"م";s:3:"1.4";s:2:"ن";s:5:"-0.15";s:2:"ه";s:5:"-0.56";s:2:"و";s:4:"0.08";s:2:"ى";s:4:"0.62";s:2:"ي";s:4:"0.78";}s:2:"م";a:37:{s:1:" ";s:4:"1.72";s:2:"ء";s:5:"-9.88";s:2:"آ";s:5:"-7.31";s:2:"أ";s:5:"-3.77";s:2:"ؤ";s:5:"-1.17";s:2:"إ";s:5:"-9.88";s:2:"ئ";s:5:"-3.33";s:2:"ا";s:4:"1.57";s:2:"ب";s:5:"-0.44";s:2:"ة";s:4:"0.23";s:2:"ت";s:4:"0.43";s:2:"ث";s:5:"-1.36";s:2:"ج";s:5:"-0.42";s:2:"ح";s:5:"-0.15";s:2:"خ";s:4:"-1.2";s:2:"د";s:5:"-0.18";s:2:"ذ";s:5:"-3.33";s:2:"ر";s:3:"0.9";s:2:"ز";s:5:"-1.67";s:2:"س";s:4:"0.46";s:2:"ش";s:5:"-0.64";s:2:"ص";s:5:"-0.34";s:2:"ض";s:5:"-2.29";s:2:"ط";s:5:"-1.67";s:2:"ظ";s:5:"-3.66";s:2:"ع";s:4:"0.39";s:2:"غ";s:5:"-2.17";s:2:"ف";s:5:"-1.63";s:2:"ق";s:5:"-0.28";s:2:"ك";s:5:"-0.75";s:2:"ل";s:3:"0.5";s:2:"م";s:4:"-0.9";s:2:"ن";s:4:"1.67";s:2:"ه";s:4:"-0.7";s:2:"و";s:4:"0.33";s:2:"ى";s:5:"-3.38";s:2:"ي";s:4:"0.56";}s:2:"ن";a:37:{s:1:" ";s:4:"2.87";s:2:"ء";s:5:"-9.77";s:2:"آ";s:5:"-9.77";s:2:"أ";s:4:"-4.8";s:2:"ؤ";s:5:"-6.25";s:2:"إ";s:5:"-8.68";s:2:"ئ";s:5:"-4.52";s:2:"ا";s:4:"1.05";s:2:"ب";s:5:"-0.79";s:2:"ة";s:5:"-0.35";s:2:"ت";s:4:"0.71";s:2:"ث";s:5:"-4.67";s:2:"ج";s:5:"-1.05";s:2:"ح";s:4:"-1.4";s:2:"خ";s:4:"-2.6";s:2:"د";s:5:"-0.03";s:2:"ذ";s:5:"-1.49";s:2:"ر";s:5:"-2.59";s:2:"ز";s:5:"-1.37";s:2:"س";s:5:"-0.04";s:2:"ش";s:5:"-1.28";s:2:"ص";s:4:"-1.4";s:2:"ض";s:5:"-3.14";s:2:"ط";s:5:"-0.98";s:2:"ظ";s:5:"-0.75";s:2:"ع";s:4:"-1.7";s:2:"غ";s:5:"-1.53";s:2:"ف";s:5:"-0.04";s:2:"ق";s:5:"-0.65";s:2:"ك";s:5:"-1.83";s:2:"ل";s:5:"-4.54";s:2:"م";s:5:"-1.56";s:2:"ن";s:4:"-2.5";s:2:"ه";s:4:"0.49";s:2:"و";s:4:"0.13";s:2:"ى";s:5:"-2.56";s:2:"ي";s:4:"1.28";}s:2:"ه";a:37:{s:1:" ";s:4:"2.38";s:2:"ء";s:5:"-8.75";s:2:"آ";s:5:"-6.96";s:2:"أ";s:5:"-8.06";s:2:"ؤ";s:5:"-2.77";s:2:"إ";s:5:"-8.75";s:2:"ئ";s:5:"-7.37";s:2:"ا";s:4:"2.25";s:2:"ب";s:5:"-1.42";s:2:"ة";s:4:"-0.9";s:2:"ت";s:5:"-1.24";s:2:"ث";s:5:"-6.68";s:2:"ج";s:5:"-0.26";s:2:"ح";s:5:"-8.75";s:2:"خ";s:5:"-8.75";s:2:"د";s:4:"0.81";s:2:"ذ";s:4:"0.65";s:2:"ر";s:4:"0.46";s:2:"ز";s:5:"-1.05";s:2:"س";s:5:"-5.32";s:2:"ش";s:5:"-3.53";s:2:"ص";s:5:"-8.75";s:2:"ض";s:5:"-3.47";s:2:"ط";s:5:"-5.23";s:2:"ظ";s:5:"-5.62";s:2:"ع";s:5:"-7.66";s:2:"غ";s:5:"-8.06";s:2:"ف";s:5:"-5.62";s:2:"ق";s:5:"-3.39";s:2:"ك";s:5:"-3.64";s:2:"ل";s:5:"-0.49";s:2:"م";s:4:"1.31";s:2:"ن";s:5:"-0.25";s:2:"ه";s:5:"-2.72";s:2:"و";s:4:"0.66";s:2:"ى";s:4:"-2.8";s:2:"ي";s:3:"0.4";}s:2:"و";a:37:{s:1:" ";s:4:"0.61";s:2:"ء";s:4:"-2.9";s:2:"آ";s:5:"-4.53";s:2:"أ";s:5:"-0.06";s:2:"ؤ";s:5:"-7.65";s:2:"إ";s:5:"-1.58";s:2:"ئ";s:5:"-5.05";s:2:"ا";s:4:"1.59";s:2:"ب";s:4:"0.18";s:2:"ة";s:5:"-1.73";s:2:"ت";s:4:"0.08";s:2:"ث";s:5:"-1.97";s:2:"ج";s:5:"-0.27";s:2:"ح";s:5:"-0.81";s:2:"خ";s:5:"-1.93";s:2:"د";s:4:"0.22";s:2:"ذ";s:5:"-1.86";s:2:"ر";s:4:"0.94";s:2:"ز";s:5:"-0.13";s:2:"س";s:4:"0.01";s:2:"ش";s:5:"-0.71";s:2:"ص";s:5:"-1.07";s:2:"ض";s:4:"-0.8";s:2:"ط";s:5:"-1.03";s:2:"ظ";s:5:"-2.35";s:2:"ع";s:5:"-0.11";s:2:"غ";s:5:"-2.21";s:2:"ف";s:4:"0.17";s:2:"ق";s:4:"0.66";s:2:"ك";s:4:"0.09";s:2:"ل";s:4:"1.46";s:2:"م";s:4:"0.65";s:2:"ن";s:4:"1.18";s:2:"ه";s:5:"-0.84";s:2:"و";s:5:"-1.15";s:2:"ى";s:5:"-1.84";s:2:"ي";s:4:"0.64";}s:2:"ى";a:37:{s:1:" ";s:4:"3.61";s:2:"ء";s:5:"-5.05";s:2:"آ";s:5:"-7.83";s:2:"أ";s:5:"-7.83";s:2:"ؤ";s:5:"-7.83";s:2:"إ";s:5:"-7.83";s:2:"ئ";s:5:"-7.83";s:2:"ا";s:5:"-6.22";s:2:"ب";s:5:"-7.83";s:2:"ة";s:5:"-7.83";s:2:"ت";s:5:"-7.83";s:2:"ث";s:5:"-7.83";s:2:"ج";s:5:"-7.83";s:2:"ح";s:5:"-7.83";s:2:"خ";s:5:"-7.83";s:2:"د";s:5:"-7.83";s:2:"ذ";s:5:"-7.83";s:2:"ر";s:5:"-7.83";s:2:"ز";s:5:"-7.83";s:2:"س";s:5:"-7.83";s:2:"ش";s:5:"-7.83";s:2:"ص";s:5:"-7.83";s:2:"ض";s:5:"-7.83";s:2:"ط";s:5:"-6.73";s:2:"ظ";s:5:"-7.83";s:2:"ع";s:5:"-7.13";s:2:"غ";s:5:"-7.83";s:2:"ف";s:5:"-7.83";s:2:"ق";s:5:"-7.83";s:2:"ك";s:5:"-7.83";s:2:"ل";s:5:"-7.83";s:2:"م";s:5:"-7.83";s:2:"ن";s:5:"-7.83";s:2:"ه";s:5:"-7.83";s:2:"و";s:5:"-7.83";s:2:"ى";s:5:"-7.83";s:2:"ي";s:5:"-7.83";}s:2:"ي";a:37:{s:1:" ";s:4:"2.32";s:2:"ء";s:5:"-3.41";s:2:"آ";s:6:"-10.16";s:2:"أ";s:5:"-3.05";s:2:"ؤ";s:5:"-3.02";s:2:"إ";s:5:"-9.47";s:2:"ئ";s:5:"-2.71";s:2:"ا";s:4:"1.06";s:2:"ب";s:5:"-0.81";s:2:"ة";s:4:"1.51";s:2:"ت";s:2:"-0";s:2:"ث";s:5:"-1.55";s:2:"ج";s:5:"-1.36";s:2:"ح";s:5:"-1.25";s:2:"خ";s:5:"-1.89";s:2:"د";s:4:"0.12";s:2:"ذ";s:5:"-2.14";s:2:"ر";s:4:"0.77";s:2:"ز";s:5:"-1.42";s:2:"س";s:4:"0.06";s:2:"ش";s:5:"-1.06";s:2:"ص";s:5:"-2.24";s:2:"ض";s:5:"-1.71";s:2:"ط";s:5:"-0.99";s:2:"ظ";s:5:"-4.29";s:2:"ع";s:5:"-0.53";s:2:"غ";s:5:"-2.82";s:2:"ف";s:5:"-0.79";s:2:"ق";s:5:"-0.23";s:2:"ك";s:4:"0.12";s:2:"ل";s:4:"0.22";s:2:"م";s:5:"-0.09";s:2:"ن";s:4:"1.29";s:2:"ه";s:5:"-0.63";s:2:"و";s:4:"0.15";s:2:"ى";s:5:"-6.34";s:2:"ي";s:5:"-0.53";}}a:27:{s:1:" ";a:27:{s:1:" ";s:4:"0.42";s:1:"a";s:4:"1.05";s:1:"b";s:4:"0.18";s:1:"c";s:4:"0.06";s:1:"d";s:5:"-0.27";s:1:"e";s:5:"-0.55";s:1:"f";s:4:"0.08";s:1:"g";s:5:"-0.77";s:1:"h";s:4:"0.49";s:1:"i";s:3:"0.6";s:1:"j";s:5:"-2.37";s:1:"k";s:4:"-2.1";s:1:"l";s:5:"-0.42";s:1:"m";s:4:"0.09";s:1:"n";s:5:"-0.46";s:1:"o";s:4:"0.56";s:1:"p";s:4:"-0.3";s:1:"q";s:5:"-2.81";s:1:"r";s:5:"-0.55";s:1:"s";s:4:"0.64";s:1:"t";s:4:"1.37";s:1:"u";s:5:"-1.39";s:1:"v";s:5:"-1.54";s:1:"w";s:4:"0.49";s:1:"x";s:5:"-5.81";s:1:"y";s:5:"-1.17";s:1:"z";s:5:"-4.85";}s:1:"a";a:27:{s:1:" ";s:4:"0.66";s:1:"a";s:5:"-4.87";s:1:"b";s:5:"-0.48";s:1:"c";s:2:"-0";s:1:"d";s:4:"0.14";s:1:"e";s:5:"-3.41";s:1:"f";s:5:"-1.59";s:1:"g";s:5:"-0.77";s:1:"h";s:5:"-3.23";s:1:"i";s:4:"0.01";s:1:"j";s:5:"-5.06";s:1:"k";s:5:"-1.21";s:1:"l";s:4:"0.74";s:1:"m";s:5:"-0.35";s:1:"n";s:4:"1.76";s:1:"o";s:5:"-4.22";s:1:"p";s:5:"-0.64";s:1:"q";s:5:"-5.36";s:1:"r";s:4:"1.09";s:1:"s";s:4:"0.93";s:1:"t";s:4:"1.27";s:1:"u";s:5:"-1.33";s:1:"v";s:5:"-0.19";s:1:"w";s:5:"-1.43";s:1:"x";s:5:"-3.66";s:1:"y";s:5:"-0.46";s:1:"z";s:5:"-2.99";}s:1:"b";a:27:{s:1:" ";s:4:"-1.6";s:1:"a";s:4:"0.45";s:1:"b";s:5:"-1.84";s:1:"c";s:5:"-6.15";s:1:"d";s:4:"-2.8";s:1:"e";s:4:"2.22";s:1:"f";s:5:"-5.19";s:1:"g";s:5:"-4.46";s:1:"h";s:5:"-4.62";s:1:"i";s:4:"0.26";s:1:"j";s:5:"-1.57";s:1:"k";s:6:"-12.82";s:1:"l";s:4:"1.13";s:1:"m";s:5:"-3.36";s:1:"n";s:5:"-4.67";s:1:"o";s:4:"0.96";s:1:"p";s:5:"-7.76";s:1:"q";s:6:"-12.82";s:1:"r";s:4:"0.69";s:1:"s";s:5:"-0.49";s:1:"t";s:5:"-1.24";s:1:"u";s:4:"1.17";s:1:"v";s:5:"-3.47";s:1:"w";s:5:"-7.07";s:1:"x";s:6:"-12.82";s:1:"y";s:4:"0.85";s:1:"z";s:6:"-12.82";}s:1:"c";a:27:{s:1:" ";s:5:"-0.89";s:1:"a";s:3:"1.2";s:1:"b";s:5:"-7.22";s:1:"c";s:5:"-0.54";s:1:"d";s:5:"-4.26";s:1:"e";s:4:"1.53";s:1:"f";s:5:"-8.32";s:1:"g";s:6:"-12.82";s:1:"h";s:4:"1.52";s:1:"i";s:4:"0.42";s:1:"j";s:6:"-12.82";s:1:"k";s:3:"0.2";s:1:"l";s:4:"0.28";s:1:"m";s:6:"-12.82";s:1:"n";s:5:"-6.93";s:1:"o";s:4:"1.56";s:1:"p";s:5:"-8.32";s:1:"q";s:5:"-3.07";s:1:"r";s:4:"0.02";s:1:"s";s:5:"-3.56";s:1:"t";s:3:"0.9";s:1:"u";s:5:"-0.03";s:1:"v";s:6:"-12.82";s:1:"w";s:6:"-12.82";s:1:"x";s:6:"-12.82";s:1:"y";s:5:"-2.18";s:1:"z";s:5:"-8.32";}s:1:"d";a:27:{s:1:" ";s:4:"2.82";s:1:"a";s:5:"-0.28";s:1:"b";s:5:"-4.29";s:1:"c";s:5:"-4.96";s:1:"d";s:5:"-1.52";s:1:"e";s:3:"1.2";s:1:"f";s:2:"-4";s:1:"g";s:5:"-1.91";s:1:"h";s:5:"-3.96";s:1:"i";s:4:"0.76";s:1:"j";s:5:"-4.67";s:1:"k";s:5:"-5.27";s:1:"l";s:5:"-1.39";s:1:"m";s:5:"-2.85";s:1:"n";s:4:"-2.5";s:1:"o";s:4:"0.07";s:1:"p";s:5:"-5.43";s:1:"q";s:5:"-7.41";s:1:"r";s:5:"-0.73";s:1:"s";s:5:"-0.19";s:1:"t";s:4:"-3.7";s:1:"u";s:5:"-0.73";s:1:"v";s:5:"-2.59";s:1:"w";s:4:"-3.3";s:1:"x";s:6:"-12.82";s:1:"y";s:5:"-1.58";s:1:"z";s:6:"-12.82";}s:1:"e";a:27:{s:1:" ";s:4:"2.25";s:1:"a";s:4:"0.39";s:1:"b";s:5:"-3.37";s:1:"c";s:4:"-0.5";s:1:"d";s:4:"0.82";s:1:"e";s:5:"-0.16";s:1:"f";s:5:"-1.46";s:1:"g";s:5:"-1.95";s:1:"h";s:5:"-3.04";s:1:"i";s:5:"-1.07";s:1:"j";s:5:"-4.96";s:1:"k";s:5:"-3.52";s:1:"l";s:5:"-0.09";s:1:"m";s:5:"-0.69";s:1:"n";s:4:"0.79";s:1:"o";s:5:"-2.44";s:1:"p";s:4:"-1.3";s:1:"q";s:5:"-2.85";s:1:"r";s:4:"1.26";s:1:"s";s:4:"0.65";s:1:"t";s:5:"-0.43";s:1:"u";s:5:"-3.26";s:1:"v";s:5:"-0.86";s:1:"w";s:5:"-1.65";s:1:"x";s:5:"-1.26";s:1:"y";s:5:"-1.08";s:1:"z";s:5:"-5.31";}s:1:"f";a:27:{s:1:" ";s:4:"2.31";s:1:"a";s:4:"0.54";s:1:"b";s:5:"-5.05";s:1:"c";s:5:"-4.76";s:1:"d";s:5:"-5.18";s:1:"e";s:4:"0.92";s:1:"f";s:3:"0.3";s:1:"g";s:5:"-7.53";s:1:"h";s:5:"-5.92";s:1:"i";s:3:"0.8";s:1:"j";s:6:"-12.82";s:1:"k";s:5:"-7.53";s:1:"l";s:5:"-0.33";s:1:"m";s:5:"-6.84";s:1:"n";s:5:"-6.03";s:1:"o";s:4:"1.43";s:1:"p";s:5:"-5.39";s:1:"q";s:5:"-8.23";s:1:"r";s:3:"0.8";s:1:"s";s:5:"-2.24";s:1:"t";s:5:"-0.08";s:1:"u";s:4:"-0.1";s:1:"v";s:5:"-8.23";s:1:"w";s:5:"-5.23";s:1:"x";s:6:"-12.82";s:1:"y";s:5:"-3.19";s:1:"z";s:6:"-12.82";}s:1:"g";a:27:{s:1:" ";s:4:"2.17";s:1:"a";s:4:"0.47";s:1:"b";s:5:"-4.57";s:1:"c";s:5:"-4.26";s:1:"d";s:5:"-2.91";s:1:"e";s:4:"1.39";s:1:"f";s:5:"-4.91";s:1:"g";s:5:"-1.29";s:1:"h";s:4:"1.34";s:1:"i";s:3:"0.3";s:1:"j";s:5:"-7.31";s:1:"k";s:4:"-6.9";s:1:"l";s:4:"0.06";s:1:"m";s:5:"-2.52";s:1:"n";s:5:"-1.06";s:1:"o";s:4:"0.44";s:1:"p";s:4:"-4.2";s:1:"q";s:6:"-12.82";s:1:"r";s:4:"0.87";s:1:"s";s:5:"-0.35";s:1:"t";s:5:"-1.78";s:1:"u";s:5:"-0.37";s:1:"v";s:4:"-6.9";s:1:"w";s:5:"-4.67";s:1:"x";s:6:"-12.82";s:1:"y";s:5:"-2.43";s:1:"z";s:4:"-5.6";}s:1:"h";a:27:{s:1:" ";s:4:"1.12";s:1:"a";s:4:"1.45";s:1:"b";s:5:"-3.87";s:1:"c";s:4:"-2.9";s:1:"d";s:5:"-4.27";s:1:"e";s:4:"2.52";s:1:"f";s:5:"-4.38";s:1:"g";s:5:"-6.94";s:1:"h";s:5:"-6.25";s:1:"i";s:4:"1.22";s:1:"j";s:6:"-12.82";s:1:"k";s:5:"-9.13";s:1:"l";s:5:"-3.26";s:1:"m";s:5:"-3.35";s:1:"n";s:5:"-3.36";s:1:"o";s:4:"0.64";s:1:"p";s:5:"-6.74";s:1:"q";s:5:"-4.75";s:1:"r";s:5:"-1.15";s:1:"s";s:5:"-2.82";s:1:"t";s:5:"-0.22";s:1:"u";s:5:"-1.04";s:1:"v";s:6:"-12.82";s:1:"w";s:5:"-3.58";s:1:"x";s:6:"-12.82";s:1:"y";s:5:"-1.63";s:1:"z";s:6:"-12.82";}s:1:"i";a:27:{s:1:" ";s:4:"0.42";s:1:"a";s:4:"-0.8";s:1:"b";s:5:"-1.68";s:1:"c";s:4:"0.27";s:1:"d";s:5:"-0.02";s:1:"e";s:4:"0.26";s:1:"f";s:5:"-0.31";s:1:"g";s:5:"-0.18";s:1:"h";s:5:"-6.17";s:1:"i";s:5:"-4.34";s:1:"j";s:5:"-9.27";s:1:"k";s:5:"-1.62";s:1:"l";s:4:"0.35";s:1:"m";s:4:"0.11";s:1:"n";s:4:"1.96";s:1:"o";s:3:"0.3";s:1:"p";s:5:"-1.78";s:1:"q";s:5:"-4.45";s:1:"r";s:4:"0.17";s:1:"s";s:4:"1.14";s:1:"t";s:4:"1.17";s:1:"u";s:5:"-3.97";s:1:"v";s:5:"-0.45";s:1:"w";s:5:"-7.47";s:1:"x";s:5:"-2.98";s:1:"y";s:6:"-12.82";s:1:"z";s:5:"-2.44";}s:1:"j";a:27:{s:1:" ";s:4:"-2.3";s:1:"a";s:4:"1.74";s:1:"b";s:6:"-12.82";s:1:"c";s:6:"-12.82";s:1:"d";s:6:"-12.82";s:1:"e";s:4:"1.64";s:1:"f";s:6:"-12.82";s:1:"g";s:6:"-12.82";s:1:"h";s:6:"-12.82";s:1:"i";s:5:"-4.44";s:1:"j";s:6:"-12.82";s:1:"k";s:6:"-12.82";s:1:"l";s:6:"-12.82";s:1:"m";s:6:"-12.82";s:1:"n";s:6:"-12.82";s:1:"o";s:4:"1.97";s:1:"p";s:6:"-12.82";s:1:"q";s:6:"-12.82";s:1:"r";s:6:"-12.82";s:1:"s";s:6:"-12.82";s:1:"t";s:6:"-12.82";s:1:"u";s:4:"2.19";s:1:"v";s:6:"-12.82";s:1:"w";s:6:"-12.82";s:1:"x";s:6:"-12.82";s:1:"y";s:5:"-5.14";s:1:"z";s:6:"-12.82";}s:1:"k";a:27:{s:1:" ";s:4:"2.09";s:1:"a";s:5:"-0.78";s:1:"b";s:5:"-3.46";s:1:"c";s:5:"-4.29";s:1:"d";s:5:"-3.07";s:1:"e";s:4:"2.19";s:1:"f";s:5:"-2.19";s:1:"g";s:5:"-4.37";s:1:"h";s:5:"-3.86";s:1:"i";s:4:"1.26";s:1:"j";s:6:"-12.82";s:1:"k";s:5:"-4.78";s:1:"l";s:5:"-0.69";s:1:"m";s:5:"-3.64";s:1:"n";s:1:"1";s:1:"o";s:5:"-1.18";s:1:"p";s:5:"-3.42";s:1:"q";s:6:"-12.82";s:1:"r";s:5:"-4.46";s:1:"s";s:3:"0.5";s:1:"t";s:5:"-2.78";s:1:"u";s:5:"-2.51";s:1:"v";s:6:"-12.82";s:1:"w";s:4:"-2.2";s:1:"x";s:6:"-12.82";s:1:"y";s:5:"-1.45";s:1:"z";s:6:"-12.82";}s:1:"l";a:27:{s:1:" ";s:4:"1.36";s:1:"a";s:4:"1.01";s:1:"b";s:5:"-4.21";s:1:"c";s:4:"-2.9";s:1:"d";s:4:"0.59";s:1:"e";s:3:"1.5";s:1:"f";s:5:"-1.02";s:1:"g";s:5:"-3.45";s:1:"h";s:5:"-5.48";s:1:"i";s:3:"1.1";s:1:"j";s:5:"-8.04";s:1:"k";s:5:"-1.93";s:1:"l";s:3:"1.3";s:1:"m";s:4:"-1.9";s:1:"n";s:5:"-3.64";s:1:"o";s:3:"0.8";s:1:"p";s:5:"-2.79";s:1:"q";s:5:"-8.74";s:1:"r";s:2:"-3";s:1:"s";s:5:"-0.48";s:1:"t";s:5:"-0.72";s:1:"u";s:5:"-0.94";s:1:"v";s:5:"-2.19";s:1:"w";s:5:"-2.66";s:1:"x";s:6:"-12.82";s:1:"y";s:4:"0.96";s:1:"z";s:5:"-7.35";}s:1:"m";a:27:{s:1:" ";s:4:"1.45";s:1:"a";s:4:"1.42";s:1:"b";s:5:"-0.26";s:1:"c";s:5:"-5.41";s:1:"d";s:5:"-2.68";s:1:"e";s:4:"1.91";s:1:"f";s:5:"-3.04";s:1:"g";s:5:"-7.55";s:1:"h";s:5:"-4.81";s:1:"i";s:4:"0.86";s:1:"j";s:5:"-7.55";s:1:"k";s:6:"-12.82";s:1:"l";s:5:"-3.16";s:1:"m";s:5:"-0.51";s:1:"n";s:5:"-2.53";s:1:"o";s:4:"1.09";s:1:"p";s:4:"0.27";s:1:"q";s:6:"-12.82";s:1:"r";s:5:"-0.78";s:1:"s";s:5:"-0.39";s:1:"t";s:5:"-3.78";s:1:"u";s:4:"0.04";s:1:"v";s:5:"-7.15";s:1:"w";s:5:"-5.76";s:1:"x";s:6:"-12.82";s:1:"y";s:4:"0.31";s:1:"z";s:6:"-12.82";}s:1:"n";a:27:{s:1:" ";s:4:"1.94";s:1:"a";s:5:"-0.43";s:1:"b";s:5:"-4.22";s:1:"c";s:4:"0.13";s:1:"d";s:4:"1.56";s:1:"e";s:4:"0.83";s:1:"f";s:5:"-1.94";s:1:"g";s:4:"1.17";s:1:"h";s:5:"-2.55";s:1:"i";s:5:"-0.22";s:1:"j";s:5:"-3.41";s:1:"k";s:5:"-1.65";s:1:"l";s:5:"-1.44";s:1:"m";s:5:"-4.03";s:1:"n";s:5:"-1.47";s:1:"o";s:4:"0.57";s:1:"p";s:5:"-4.71";s:1:"q";s:5:"-3.64";s:1:"r";s:5:"-4.49";s:1:"s";s:4:"0.14";s:1:"t";s:4:"0.95";s:1:"u";s:5:"-1.37";s:1:"v";s:4:"-2.4";s:1:"w";s:5:"-3.77";s:1:"x";s:5:"-4.71";s:1:"y";s:5:"-1.12";s:1:"z";s:5:"-5.66";}s:1:"o";a:27:{s:1:" ";s:4:"1.23";s:1:"a";s:5:"-1.49";s:1:"b";s:5:"-1.63";s:1:"c";s:5:"-1.07";s:1:"d";s:4:"-0.6";s:1:"e";s:5:"-2.53";s:1:"f";s:4:"1.12";s:1:"g";s:5:"-1.81";s:1:"h";s:2:"-3";s:1:"i";s:5:"-1.44";s:1:"j";s:5:"-5.23";s:1:"k";s:5:"-1.24";s:1:"l";s:5:"-0.32";s:1:"m";s:4:"0.45";s:1:"n";s:4:"1.37";s:1:"o";s:5:"-0.11";s:1:"p";s:5:"-0.98";s:1:"q";s:5:"-5.08";s:1:"r";s:4:"1.15";s:1:"s";s:5:"-0.14";s:1:"t";s:4:"0.29";s:1:"u";s:4:"1.28";s:1:"v";s:5:"-0.81";s:1:"w";s:4:"0.28";s:1:"x";s:5:"-4.23";s:1:"y";s:5:"-2.39";s:1:"z";s:5:"-3.77";}s:1:"p";a:27:{s:1:" ";s:3:"0.5";s:1:"a";s:4:"1.24";s:1:"b";s:5:"-4.43";s:1:"c";s:5:"-4.43";s:1:"d";s:5:"-4.36";s:1:"e";s:4:"1.79";s:1:"f";s:5:"-5.56";s:1:"g";s:5:"-6.07";s:1:"h";s:5:"-0.52";s:1:"i";s:4:"0.37";s:1:"j";s:5:"-7.17";s:1:"k";s:5:"-5.78";s:1:"l";s:4:"0.95";s:1:"m";s:5:"-3.53";s:1:"n";s:5:"-4.15";s:1:"o";s:4:"1.19";s:1:"p";s:3:"0.5";s:1:"q";s:6:"-12.82";s:1:"r";s:3:"1.3";s:1:"s";s:5:"-0.51";s:1:"t";s:3:"0.1";s:1:"u";s:5:"-0.31";s:1:"v";s:6:"-12.82";s:1:"w";s:5:"-3.45";s:1:"x";s:6:"-12.82";s:1:"y";s:5:"-2.17";s:1:"z";s:6:"-12.82";}s:1:"q";a:27:{s:1:" ";s:5:"-2.09";s:1:"a";s:6:"-12.82";s:1:"b";s:6:"-12.82";s:1:"c";s:6:"-12.82";s:1:"d";s:6:"-12.82";s:1:"e";s:6:"-12.82";s:1:"f";s:6:"-12.82";s:1:"g";s:6:"-12.82";s:1:"h";s:6:"-12.82";s:1:"i";s:6:"-12.82";s:1:"j";s:6:"-12.82";s:1:"k";s:6:"-12.82";s:1:"l";s:6:"-12.82";s:1:"m";s:6:"-12.82";s:1:"n";s:6:"-12.82";s:1:"o";s:6:"-12.82";s:1:"p";s:6:"-12.82";s:1:"q";s:6:"-12.82";s:1:"r";s:5:"-5.09";s:1:"s";s:5:"-5.09";s:1:"t";s:6:"-12.82";s:1:"u";s:4:"3.29";s:1:"v";s:6:"-12.82";s:1:"w";s:6:"-12.82";s:1:"x";s:6:"-12.82";s:1:"y";s:6:"-12.82";s:1:"z";s:6:"-12.82";}s:1:"r";a:27:{s:1:" ";s:3:"1.8";s:1:"a";s:4:"0.68";s:1:"b";s:5:"-2.45";s:1:"c";s:5:"-1.15";s:1:"d";s:5:"-0.27";s:1:"e";s:4:"1.85";s:1:"f";s:5:"-1.88";s:1:"g";s:5:"-1.24";s:1:"h";s:5:"-2.64";s:1:"i";s:4:"0.76";s:1:"j";s:5:"-6.75";s:1:"k";s:5:"-1.45";s:1:"l";s:5:"-1.15";s:1:"m";s:5:"-0.65";s:1:"n";s:5:"-0.51";s:1:"o";s:4:"0.91";s:1:"p";s:5:"-2.04";s:1:"q";s:5:"-7.07";s:1:"r";s:5:"-0.67";s:1:"s";s:4:"0.22";s:1:"t";s:4:"0.13";s:1:"u";s:5:"-0.76";s:1:"v";s:5:"-1.66";s:1:"w";s:5:"-2.93";s:1:"x";s:5:"-9.13";s:1:"y";s:5:"-0.26";s:1:"z";s:5:"-1.89";}s:1:"s";a:27:{s:1:" ";s:4:"2.38";s:1:"a";s:5:"-0.06";s:1:"b";s:5:"-4.05";s:1:"c";s:5:"-0.85";s:1:"d";s:5:"-3.19";s:1:"e";s:4:"1.16";s:1:"f";s:5:"-3.56";s:1:"g";s:5:"-4.37";s:1:"h";s:4:"0.43";s:1:"i";s:4:"0.31";s:1:"j";s:5:"-6.62";s:1:"k";s:5:"-1.93";s:1:"l";s:5:"-1.17";s:1:"m";s:5:"-1.73";s:1:"n";s:4:"-2.8";s:1:"o";s:4:"0.28";s:1:"p";s:5:"-0.25";s:1:"q";s:4:"-3.8";s:1:"r";s:5:"-5.27";s:1:"s";s:4:"0.23";s:1:"t";s:4:"1.23";s:1:"u";s:5:"-0.11";s:1:"v";s:5:"-6.14";s:1:"w";s:5:"-1.93";s:1:"x";s:6:"-12.82";s:1:"y";s:5:"-2.87";s:1:"z";s:6:"-12.82";}s:1:"t";a:27:{s:1:" ";s:4:"1.89";s:1:"a";s:4:"0.06";s:1:"b";s:5:"-5.45";s:1:"c";s:4:"-2.3";s:1:"d";s:5:"-6.31";s:1:"e";s:4:"0.88";s:1:"f";s:5:"-3.69";s:1:"g";s:5:"-6.82";s:1:"h";s:3:"2.2";s:1:"i";s:4:"0.59";s:1:"j";s:5:"-8.83";s:1:"k";s:5:"-7.73";s:1:"l";s:5:"-0.93";s:1:"m";s:5:"-3.71";s:1:"n";s:5:"-3.51";s:1:"o";s:3:"0.9";s:1:"p";s:5:"-6.13";s:1:"q";s:5:"-9.53";s:1:"r";s:5:"-0.18";s:1:"s";s:5:"-0.41";s:1:"t";s:5:"-0.77";s:1:"u";s:5:"-0.69";s:1:"v";s:5:"-9.53";s:1:"w";s:5:"-1.55";s:1:"x";s:6:"-12.82";s:1:"y";s:5:"-1.12";s:1:"z";s:5:"-4.89";}s:1:"u";a:27:{s:1:" ";s:4:"0.45";s:1:"a";s:5:"-0.35";s:1:"b";s:5:"-0.71";s:1:"c";s:4:"0.22";s:1:"d";s:5:"-0.51";s:1:"e";s:5:"-0.18";s:1:"f";s:5:"-1.84";s:1:"g";s:4:"0.31";s:1:"h";s:5:"-6.27";s:1:"i";s:5:"-0.22";s:1:"j";s:5:"-8.36";s:1:"k";s:5:"-5.46";s:1:"l";s:4:"1.06";s:1:"m";s:5:"-0.18";s:1:"n";s:4:"1.14";s:1:"o";s:5:"-2.91";s:1:"p";s:4:"0.19";s:1:"q";s:5:"-5.36";s:1:"r";s:4:"1.48";s:1:"s";s:4:"1.18";s:1:"t";s:4:"1.22";s:1:"u";s:5:"-6.97";s:1:"v";s:5:"-3.68";s:1:"w";s:5:"-5.22";s:1:"x";s:5:"-3.74";s:1:"y";s:5:"-4.83";s:1:"z";s:5:"-3.61";}s:1:"v";a:27:{s:1:" ";s:4:"-2.9";s:1:"a";s:4:"1.02";s:1:"b";s:6:"-12.82";s:1:"c";s:6:"-12.82";s:1:"d";s:5:"-2.01";s:1:"e";s:4:"2.91";s:1:"f";s:6:"-12.82";s:1:"g";s:6:"-12.82";s:1:"h";s:5:"-5.05";s:1:"i";s:4:"1.38";s:1:"j";s:6:"-12.82";s:1:"k";s:6:"-12.82";s:1:"l";s:5:"-5.56";s:1:"m";s:6:"-12.82";s:1:"n";s:5:"-1.06";s:1:"o";s:4:"0.15";s:1:"p";s:6:"-12.82";s:1:"q";s:6:"-12.82";s:1:"r";s:4:"-3.8";s:1:"s";s:5:"-4.79";s:1:"t";s:6:"-12.82";s:1:"u";s:5:"-2.66";s:1:"v";s:5:"-4.13";s:1:"w";s:6:"-12.82";s:1:"x";s:6:"-12.82";s:1:"y";s:5:"-2.25";s:1:"z";s:6:"-12.82";}s:1:"w";a:27:{s:1:" ";s:4:"1.17";s:1:"a";s:3:"1.6";s:1:"b";s:5:"-4.71";s:1:"c";s:5:"-4.82";s:1:"d";s:5:"-2.97";s:1:"e";s:4:"1.47";s:1:"f";s:5:"-3.34";s:1:"g";s:5:"-6.98";s:1:"h";s:4:"1.63";s:1:"i";s:4:"1.57";s:1:"j";s:6:"-12.82";s:1:"k";s:4:"-4.3";s:1:"l";s:5:"-1.42";s:1:"m";s:4:"-4.9";s:1:"n";s:4:"0.01";s:1:"o";s:4:"0.92";s:1:"p";s:5:"-6.13";s:1:"q";s:5:"-8.08";s:1:"r";s:5:"-1.51";s:1:"s";s:5:"-1.07";s:1:"t";s:5:"-3.02";s:1:"u";s:5:"-3.45";s:1:"v";s:6:"-12.82";s:1:"w";s:5:"-4.75";s:1:"x";s:6:"-12.82";s:1:"y";s:5:"-3.98";s:1:"z";s:6:"-12.82";}s:1:"x";a:27:{s:1:" ";s:4:"0.99";s:1:"a";s:4:"0.48";s:1:"b";s:6:"-12.82";s:1:"c";s:4:"1.45";s:1:"d";s:5:"-3.61";s:1:"e";s:3:"0.4";s:1:"f";s:6:"-12.82";s:1:"g";s:5:"-5.55";s:1:"h";s:5:"-1.03";s:1:"i";s:4:"1.11";s:1:"j";s:6:"-12.82";s:1:"k";s:6:"-12.82";s:1:"l";s:5:"-3.25";s:1:"m";s:5:"-5.55";s:1:"n";s:6:"-12.82";s:1:"o";s:5:"-1.89";s:1:"p";s:4:"1.76";s:1:"q";s:5:"-2.46";s:1:"r";s:6:"-12.82";s:1:"s";s:5:"-2.61";s:1:"t";s:4:"1.85";s:1:"u";s:5:"-0.64";s:1:"v";s:5:"-1.97";s:1:"w";s:5:"-5.55";s:1:"x";s:5:"-1.38";s:1:"y";s:5:"-2.91";s:1:"z";s:6:"-12.82";}s:1:"y";a:27:{s:1:" ";s:4:"2.98";s:1:"a";s:5:"-1.64";s:1:"b";s:5:"-2.24";s:1:"c";s:5:"-3.43";s:1:"d";s:5:"-2.88";s:1:"e";s:4:"0.42";s:1:"f";s:5:"-3.19";s:1:"g";s:5:"-4.78";s:1:"h";s:5:"-3.95";s:1:"i";s:5:"-0.96";s:1:"j";s:5:"-7.92";s:1:"k";s:5:"-7.23";s:1:"l";s:5:"-2.45";s:1:"m";s:5:"-2.25";s:1:"n";s:4:"-2.8";s:1:"o";s:4:"1.18";s:1:"p";s:5:"-2.59";s:1:"q";s:6:"-12.82";s:1:"r";s:5:"-2.03";s:1:"s";s:5:"-0.13";s:1:"t";s:5:"-1.22";s:1:"u";s:5:"-4.14";s:1:"v";s:5:"-6.31";s:1:"w";s:5:"-3.14";s:1:"x";s:5:"-5.62";s:1:"y";s:5:"-5.97";s:1:"z";s:5:"-5.21";}s:1:"z";a:27:{s:1:" ";s:4:"0.34";s:1:"a";s:4:"2.43";s:1:"b";s:4:"-3.9";s:1:"c";s:5:"-1.82";s:1:"d";s:4:"-1.7";s:1:"e";s:3:"2.1";s:1:"f";s:6:"-12.82";s:1:"g";s:6:"-12.82";s:1:"h";s:4:"-4.3";s:1:"i";s:4:"0.65";s:1:"j";s:5:"-4.99";s:1:"k";s:6:"-12.82";s:1:"l";s:5:"-0.63";s:1:"m";s:6:"-12.82";s:1:"n";s:6:"-12.82";s:1:"o";s:4:"0.68";s:1:"p";s:6:"-12.82";s:1:"q";s:6:"-12.82";s:1:"r";s:5:"-4.99";s:1:"s";s:5:"-3.05";s:1:"t";s:4:"-4.3";s:1:"u";s:5:"-2.29";s:1:"v";s:5:"-3.61";s:1:"w";s:6:"-12.82";s:1:"x";s:6:"-12.82";s:1:"y";s:5:"-1.31";s:1:"z";s:5:"-0.32";}}-0.014
-0.003
0.033
0.019
-0.251
-0.003
0.349
-0.025
-0.003
-0.192
-0.129
0.292
-0.044
-0.234
-0.047
0.16
0.143
-0.017
-1.038
0.346
0.1
-0.242
-0.119
-0.102
-0.153
-0.04
0.059
-0.049
-0.029
-0.074
-0.013
0.028
0.007
0.006
0.007
-0.021
-0.013
0.001
-0.058
-0.02
-0.041
-0.011
0.014
0.005
0.018
-0.015
-0.021
-0.085
0.092
0.037
-0.009
0.019
-0.001
-0.029
-0.027
0.003
0.011
-0.004
-0.024
0.037
0.013
0.022
-0.021
0.053
-0.018
0.033
0.019
0.092
-0.006
0.048
0.042
0.013
0.046
0.011
0.048
0.058
0.023
0.139
0.038
0.027
0.04
0.046
0.01
-0.008
0.011
-0.004
-0.001
-0.233
-0.015
0.05
-0.035
-0.221
-0.021
-0.074
-0.024
-0.014
-0.01
-0.013
-0.256
-0.065
0.067
0.004
0.038
-0.035
0.02
-0.074
0.036
0.001
0.002
-0.021
-0.002
-0.026
-0.05
-0.003
0.015
-0.006
-0.484
-0.027
-0.16
-0.108
0.101
0.157
-0.586
-1.049
-0.081
-0.341
-0.046
-0.148
-0.093
0.107
-0.045
0.863
-0.091
-0.341
-0.064
-0.072
-0.176
-0.126
-0.366
0.035
-0.046
-0.119
-0.054
-0.199
-0.058
-0.035
0.006
-0.051
0.037
-0.176
0.097
-0.122
0.038
-0.001
-0.084
-0.009
0.089
0.062
0.176
0.219
0.018
0.086
0.027
-0.115
-0.234
-0.093
0.003
-0.174
-0.169
-0.084
-0.066
0.022
-0.037
-0.166
-0.051
-0.014
-0.079
0.024
-0.015
0.152
0.018
0.049
0.038
0.029
-0.05
0.129
-0.041
0.389
0.023
0.005
0.076
-0.166
-0.177
-0.312
-0.13
-0.101
-0.116
-0.051
-0.031
-0.198
-0.025
0.027
-0.076
0.052
0.014
0.457
-0.001
-0.046
0.031
-0.223
0.724
0.107
-0.033
0.034
0.019
0.124
0.103
0.026
-0.134
0.044
-0.536
0.137
0.191
-0.082
-0.109
-0.292
-0.036
0.045
0.038
0.026
-0.046
0.087
-0.257
0.031
0.067
0.024
0.013
0.149
0.1
0.003
-0.046
-0.109
-0.014
0.2
-0.129
0.091
0.102
0.246
-0.155
-0.115
-0.008
0.013
0.086
0.043
-0.175
-0.021
0.059
-0.015
0.026
-0.042
0.039
0.004
0.02
-0.076
-0.078
-0.225
0.392
-0.509
-0.425
-0.404
-0.475
0.074
0.519
0.285
0.352
0.432
0.027
-0.379
0.519
0.037
0.187
0.071
0.087
-0.034
-0.002
-0.002
-0.14
-0.015
0.001
-0.115
-0.129
-0.01
-0.04
0.038
-0.106
0.067
-0.116
-0.17
0.038
-0.038
0.144
-0.046
-0.052
0.142
0.08
-0.122
-0.089
-0.01
-0.337
-0.019
0.062
-0.086
-0.083
-0.015
-0.034
-0.053
0.02
0.026
-0.006
0.004
0.023
0.241
0.082
0.068
0.115
-0.047
-0.04
-0.005
-0.261
-0.093
0.316
-0.173
0.173
0.233
0.122
0.087
-0.206
0.129
-0.121
0.042
0.039
-0.19
0.069
-0.046
-0.098
-0.004
0.066
0.038
0.332
-0.01
0.045
-0.02
-0.908
-0.041
-0.061
0.256
-0.037
0.099
-0.04
0.297
-0.021
0.091
-0.073
0.074
-0.108
-0.073
-0.101
0.122
0.201
-0.033
-0.003
0.011
-0.043
-0.144
0.02
0.099
0.047
0.076
-0.005
-0.005
0.047
-0.286
-0.22
0.008
0.044
-0.038
0.234
-0.053
0.316
-0.142
0.063
0.065
-0.166
-0.04
-0.133
-0.112
0.065
-0.004
-0.119
-0.041
0.111
-0.046
0.07
0.023
0.029
0.062
-0.137
0.019
-0.049
0.057
-0.012
0.02
0.061
0.081
-0.032
0.187
0.005
0.027
0.128
0.323
0.13
-0.098
0.032
-0.288
-0.133
-0.077
0.122
-0.09
-0.206
0.021
-0.128
0.068
0.043
-0.009
0.011
-0.046
-0.029
-0.013
0.103
0.232
0.29
-0.023
-0.14
-0.022
0.655
-0.006
0.181
0.041
0.483
-0.127
0.329
0.062
0.958
0.391
0.113
0.445
0.049
-0.05
0.034
0.013
0.004
0.08
0.079
0.147
0.012
-0.027
0.093
0.056
-0.058
0.019
0.222
0.071
-0.008
-0.127
-0.073
-0.186
0.055
0.004
0.163
0.055
-0.109
-0.169
0.049
-0.315
-0.081
0.008
-0.071
0.101
-0.069
-0.186
-0.026
-0.041
-0.006
-0.294
-0.044
0.099
-0.04
0.232
-0.415
-0.304
-0.322
-0.243
-0.484
0.004
-0.261
0.037
0.209
-0.666
0.337
0.268
-0.359
0.189
0.314
-0.04
-0.077
-0.1
-0.276
-0.196
0.052
-0.07
-0.133
-0.092
-0.062
-0.001
-0.084
-0.01
-0.04
-0.041
-0.049
-0.283
0.024
-0.102
-0.042
0.054
-0.034
-0.001
-0.061
0.006
0.059
-0.124
0.334
0.168
0.228
-0.097
-0.108
0.008
-0.009
-0.001
-0.038
-0.049
0.04
0.096
0.044
0.021
-0.015
0.073
0.086
0.4
0.402
-0.051
-0.055
0.15
-0.101
-0.146
0.132
-0.253
0.029
0.15
-0.073
0.131
-0.422
0.149
0.02
0.026
0.108
-0.067
-0.042
0.1
-0.051
0.179
-0.033
0.014
-0.002
0.062
0.089
0.077
0.083
-0.15
0.055
0.002
0.025
0.216
-0.062
0.373
-0.054
0.117
0.055
0.03
0.03
0.213
-0.078
0.088
0.046
0.03
0.027
0.024
0.049
0.028
0.002
-0.016
0.014
-0.112
0.058
-0.193
-0.22
0.22
-0.006
0.014
0.411
0.003
0.456
-0.073
0.108
0.081
-0.074
0.165
-0.298
-0.011
-0.244
0.006
-0.257
-0.154
-0.004
0.044
-0.035
-0.016
-0.048
-0.007
-0.013
0.041
0.091
0.075
-0.02
0.167
-0.075
0.057
0.099
0.071
0.033
-0.109
0.051
0.069
-0.018
-0.156
-0.056
-0.142
-0.17
0.156
-0.034
0.186
0.042
0.007
0.012
0.053
0.11
0.018
0.008
-0.037
0.029
0.033
0.021
0.003
0.017
-0.045
-0.02
-0.01
-0.089
-0.01
0.045
0.02
0.023
0.031
0.025
-0.035
-0.053
-0.056
0.139
0.054
-0.008
0.036
0.064
0.016
-0.019
0.006
0.01
0.004
-0.005
-0.031
0.075
-0.067
0.015
0.015
-0.055
0.241
0.012
-0.011
-0.018
-0.434
0.027
0.22
-0.009
-0.023
-0.006
0.006
-0.052
0.179
0.049
-0.042
0.05
0.055
-0.04
-0.024
0.004
0.001
-0.04
-0.103
-0.04
-0.081
0.011
0.008
0.168
-0.137
-0.027
0.088
-0.116
-0.038
0.026
-0.061
0.081
0.03
-0.071
-0.039
-0.068
-0.052
-0.014
-0.032
0.05
-0.055
-0.02
0.074
-0.07
0.017
-0.032
-0.012
-0.192
0.022
0
0.044
0.009
-0.027
0.026
0.115
-0.107
0.053
0.027
-0.011
0.051
0.169
0.048
-0.053
0.105
-0.03
0.12
0.121
0.14
0.203
0.111
0.027
-0.061
0.017
0.032
-0.029
-0.013
-0.038
-0.011
-0.031
-0.037
-0.106
-0.011
-0.059
-0.02
-0.021
-0.031
-0.062
0
-0.064
0.02
0.002
0.082
-0.104
-0.048
-0.062
0.09
-0.055
-0.051
-0.021
0
-0.059
-0.041
-0.022
-0.056
0.006
0.314
-0.037
0.03
-0.012
-0.069
-0.014
-0.08
-0.102
-0.065
-0.072
0.03
-0.036
0.014
-0.017
-0.011
-0.05
-0.096
-0.037
-0.024
-0.008
0.088
-0.022
-0.012
-0.039
0.004
-0.036
0.027
0.017
-0.04
0.09
0.026
-0.082
0.036
0.188
-0.024
-0.485
-0.139
-0.028
-0.042
0.051
-0.291
0.01
0.055
0.08
-0.132
-0.165
0.105
0.673
-0.577
-0.092
0.213
0.171
0.089
0.102
0.065
-0.01
0.04
0.113
0.102
0.001
-0.043
-0.022
-0.059
-0.03
0.023
-0.001
0.011
0.064
-0.002
0.057
0.013
0.001
-0.022
-0.053
0.009
0.026
0.081
-0.148
-0.046
0.01
-0.015
-0.004
0.035
0.031
-0.001
-0.022
0
0.02
-0.034
-0.003
0.003
-0.002
0.003
0.027
-0.003
0.012
-0.022
0.008
0.011
-0.089
-0.019
-0.025
-0.156
-0.067
-0.086
-0.044
-0.174
-0.031
0.015
-0.025
-0.071
-0.011
0.005
-0.014
-0.081
0
0.302
0.027
-0.043
0.048
0.254
-0.008
0.082
0.007
-0.003
0.062
0
0.308
0.036
-0.039
-0.015
-0.057
-0.001
-0.11
0.074
-0.038
-0.006
0.005
0.039
0.01
0.034
0.071
0.007
-0.024
0.015
0.565
0.01
0.227
0.135
-0.308
-0.131
0.554
0.445
0.279
0.543
-0.002
0.177
0.089
-0.092
0.124
-0.778
-0.123
-0.373
0.26
0.127
-0.025
0.014
0.195
-0.101
0.039
0.192
-0.018
0.105
0.067
-0.093
0.023
0.049
-0.072
0.116
-0.026
0.104
0.039
0.031
0.059
0.03
-0.146
-0.052
-0.166
-0.117
-0.218
-0.092
-0.094
0.107
0.207
0.096
0.105
0.196
0.134
0.07
0.052
-0.047
-0.013
0.115
0.047
-0.005
0.064
-0.022
-0.041
-0.201
0.028
-0.005
-0.042
-0.093
0.039
-0.086
-0.041
-0.457
-0.012
-0.012
-0.022
0.13
0.016
0.295
0.173
0.144
0.066
0.03
0.016
0.108
0.037
-0.03
0.061
-0.057
-0.014
-0.34
0
0.157
-0.05
0.225
-0.879
-0.05
-0.013
-0.049
0.086
0.001
-0.064
-0.041
0.112
-0.056
0.361
-0.106
-0.224
-0.036
0.141
0.301
0.028
0.021
-0.025
-0.031
0.03
-0.072
0.219
0.005
0.009
-0.004
-0.073
-0.096
-0.078
-0.03
0.037
0.002
0.025
-0.208
0.127
-0.019
-0.228
-0.385
-0.074
0.079
0.082
0.034
-0.055
0.049
0.159
0.012
-0.054
0.037
-0.026
0.035
-0.025
0.036
0.008
0.083
0.056
0.362
-0.376
0.159
0.254
0.113
0.418
-0.068
-0.206
-0.199
-0.46
-0.411
0.282
0.195
0.082
-0.064
0.155
-0.005
0.181
-0.012
-0.029
-0.018
0.1
0.044
0.03
0.083
0.196
0.045
0.013
-0.012
0.12
-0.054
0.15
0.107
-0.039
0.021
-0.105
-0.001
0.04
-0.119
-0.038
-0.164
0.04
-0.093
0.353
-0.027
-0.104
0.093
0.088
0.039
-0.001
0.055
-0.007
0.004
-0.013
-0.172
-0.037
-0.149
-0.047
-0.005
-0.234
-0.07
0.037
-0.021
0.113
0.124
-0.223
0.164
-0.253
-0.073
-0.228
0.055
0.259
-0.274
0.05
0.036
-0.035
0.202
-0.036
0.022
0.088
0.041
-0.06
-0.037
-0.24
0.019
-0.082
0.04
0.788
0.041
0.106
-0.254
0.026
0.058
0.005
-0.336
0.004
-0.02
0.018
-0.11
0.065
0.085
0.074
-0.118
-0.14
0.056
0.077
-0.017
0.054
0.144
-0.029
-0.069
-0.018
0.069
0.024
-0.034
-0.037
0.168
0.17
0.019
-0.075
0.063
-0.129
0.09
-0.419
0.093
0.136
-0.103
0.044
0.026
0.177
0.083
-0.027
0.035
0.089
0.029
-0.151
0.073
-0.044
-0.028
-0.019
0.01
0.06
-0.023
0.046
-0.062
0.168
0.03
0.049
0.002
0.029
0.26
-0.029
-0.354
-0.127
-0.19
-0.19
-0.035
-0.05
0.121
0.027
-0.037
-0.109
0.115
0.226
-0.053
0.092
-0.064
-0.065
-0.026
-0.049
0.029
-0.005
0.008
-0.08
-0.899
-0.36
-0.048
0.069
0.111
-0.655
-0.017
-0.698
-0.078
-0.38
-0.064
-0.329
-0.006
-1.472
-0.42
-0.071
-0.398
-0.229
0.039
-0.003
-0.003
-0.022
-0.12
-0.122
-0.317
-0.083
0.036
-0.107
-0.054
-0.401
0.024
-0.27
-0.153
0.047
0.159
0.042
0.265
0.037
0.083
-0.141
-0.853
0.093
0.298
0.007
0.059
0.002
0.039
0.116
-0.099
0.175
0.168
0.007
0.014
0.003
0.127
0.034
0.039
0.016
-0.052
0.203
0.232
0.42
0.25
-1.209
-0.058
-0.111
0.246
-0.153
0.4
-0.052
-0.172
0.287
-0.032
-0.092
0.061
0.322
0.241
0.223
0.181
0.021
0.06
0.098
0.115
0.103
0.001
0.051
-0.016
0.026
-0.019
0.15
0.137
-0.044
0.126
0.026
-0.045
0.045
0.064
0.031
-0.178
-0.065
0.154
-0.52
-0.223
-0.164
0.08
0.115
0.025
0.032
0.002
0.046
0.04
-0.007
-0.041
-0.041
-0.063
0.002
-0.015
-0.154
-0.342
-0.236
0.046
0.099
-0.22
0.064
0.101
-0.332
0.26
-0.088
-0.059
0.093
-0.254
0.086
-0.267
-0.038
-0.033
-0.138
-0.026
0.074
-0.149
0.06
-0.211
0.019
-0.011
0.008
-0.059
-0.115
-0.062
-0.015
0.276
0.011
0.03
-0.027
-0.232
0.066
-0.402
-0.047
-0.117
-0.04
0.019
0.003
-0.203
0.067
-0.029
-0.06
-0.012
0.031
0.046
-0.055
-0.024
-0.014
0.04
0.004
0.004
-0.032
0.215
0.172
-0.269
0.128
-0.023
-0.315
0.028
-0.543
0.041
-0.104
-0.175
0.057
-0.184
0.259
-0.04
0.33
-0.078
0.336
0.232
-0.046
0.008
0.038
0.017
-0.054
0.007
-0.028
-0.054
-0.101
-0.078
-0.031
-0.252
0.041
-0.132
-0.092
-0.109
-0.043
0.05
-0.096
-0.044
-0.122
0.167
0.009
0.052
0.063
-0.138
0.037
-0.315
-0.001
0.064
0.037
-0.087
-0.112
-0.024
-0.029
0.02
-0.03
-0.056
-0.041
-0.097
-0.031
0.026
0.002
0.018
0.083
-0.02
-0.072
-0.028
-0.017
-0.072
-0.06
0.019
0.055
0.04
-0.192
-0.066
0
-0.066
-0.039
-0.004
0.009
-0.007
-0.031
-0.014
0.006
0.028
-0.159
0.083
-0.04
-0.05
0.045
-0.177
-0.008
0.035
-0.015
0.412
-0.032
-0.219
-0.037
-0.317
-0.007
-0.003
0.027
-0.23
-0.068
-0.086
-0.039
-0.066
0.054
0.041
0.006
-0.068
0.03
0.201
0.046
0.064
0.002
0.041
-0.146
0.212
0.065
-0.005
0.057
0.025
0.006
0.064
-0.082
0.047
0.065
0.024
0.046
0.038
0.054
0.031
0.003
0.083
0.018
-0.04
0.088
-0.023
0.013
0.029
0.194
-0.02
0.066
-0.016
0.107
0.047
0.033
-0.02
0.127
0.009
0.005
-0.019
-0.006
-0.099
0.02
-0.03
0.034
0.018
-0.041
0.096
-0.087
-0.103
-0.105
-0.015
0.059
0.004
-0.014
0.025
0.007
0.084
0.022
0.022
0.033
0.05
-0.009
0.069
0.068
0.018
0.01
0.059
0
0.063
0.038
0.009
-0.152
0.079
0.109
0.043
-0.072
0.047
0.032
0.017
-0.008
0.067
0.046
0.01
0.039
-0.004
-0.261
0.02
0.02
0.025
0.019
0.028
0.083
0.061
0.044
0.064
-0.052
0.068
-0.01
0.049
0.012
0.008
0.032
0.036
0.057
0.001
-0.053
0.04
0.025
0.034
0.013
0.043
-0.039
0.005
0.045
-3.965499
-0.854655
-3.03215
-0.153276
-4.023607
-4.673087
-2.630215
-4.403754
-2.524289
-4.655068
-2.4791
-3.843345
-2.684714
-4.802988
-5.56247
-6.146723
-5.080372
-6.43971
-1.396834
-6.540354
-2.785466
-3.065833
-3.252391
-1.212763
-1.811198
-2.067438
-1.119142
-3.107886
-1.018752
1.366219
3.174063
2.036215
3.064259
0.640191
1.015237
1.38322
0.827704
1.749644
-0.006821
2.384875
0.298593
1.690875
0.532707
0.741226
0.221505
0.639107
-0.490815
1.651321
0.07928
1.547435
1.606808
1.349656
3.40534
2.338017
2.48096
1.966708
1.865277
2.664035
-1.031632
1.78198
-0.073624
1.295881
-1.547044
-1.260081
-0.477828
-1.681728
0.412319
-1.851708
0.624403
-2.199456
-0.072509
-0.866586
-1.366133
-0.827398
-0.613334
-3.069118
0.364916
-1.251849
-0.966501
-0.271191
-0.21686
1.033685
-0.026635
0.615309
0.761667
0.087937
1.138669
-1.487937
1.851554
1.010913
0.984677
-1.324907
-0.236486
-0.010265
-0.696605
0.214182
-1.77607
1.021055
-0.849438
-0.3895
-0.771849
-0.659821
-1.526117
-0.905942
-1.787575
0.541809
-1.593431
0.213441
0.426558
0.154514
0.862542
0.741906
0.622317
0.97027
0.519072
1.385263
-2.442291
-0.268098
-2.970987
-0.781192
-1.888076
-4.307182
-6.706339
-7.198815
-5.865011
-7.724908
-0.226145
-7.764129
-6.720325
-6.706339
-7.615709
-8.418056
-6.994021
-8.418056
-6.238073
-5.441741
-3.015032
-2.445813
-4.746635
-1.12326
-1.865767
-1.265004
-1.941794
-2.225064
-0.454863
-1.402067
0.84297
-0.281042
0.485153
-4.353642
-3.31211
-2.317564
-5.818219
0.71669
-3.140726
-0.675286
-0.967753
-1.902318
-3.464398
-5.965725
-6.238073
-5.549283
-6.034245
-0.520931
-5.292646
-3.320067
-5.02458
-2.598201
-0.076811
0.076451
-0.192903
-0.196246
-0.358293
0.495257
-2.680491
1.084931
-0.33103
0.966723
-0.522906
-0.964538
-3.300523
-6.100203
0.180618
-3.552891
-0.316238
-1.711193
-0.860221
-2.428709
-2.353913
-2.333732
-2.676286
-1.896415
-4.935633
-6.600978
-1.725399
-0.448698
-0.879192
-0.059894
0.062809
-0.416546
-0.200638
-0.296301
0.578606
-2.722512
0.442498
-1.650536
0.234959
-5.441741
-1.81099
-6.107808
-4.628635
-0.969811
-2.320154
-0.085507
-3.696813
-2.504969
-3.649329
-0.324711
-3.521365
-1.546964
-6.613557
-3.394681
-6.957653
-0.961468
-2.417784
-3.214895
-0.066536
-0.796333
-1.375501
-1.026498
-1.016355
0.027889
-1.296182
1.211501
-1.729701
0.799894
-0.874212
-2.169864
-3.323834
-1.455885
-0.874742
-6.085165
-0.165309
-3.97089
-1.917922
-2.629272
-5.108074
-5.841341
-4.487739
-5.664885
-1.259302
-4.083282
-1.730372
-0.288835
-1.631426
-0.612769
0.173145
-0.225294
0.109499
-0.078261
0.664224
-3.710607
0.129443
-1.777979
-1.161324
-7.687168
-3.745227
-5.085851
-6.100203
-6.195513
-3.562426
-1.388968
-8.58511
-7.051179
-7.031761
-8.092633
-8.78578
-7.687168
-8.58511
-4.413524
-7.847511
-4.708243
-3.907196
-0.687881
-1.091641
-4.078254
-1.893477
-0.75223
-2.515002
-0.790811
-0.044342
1.76099
0.190496
1.687179
-3.274594
-0.635954
-0.414564
-1.841158
-0.481423
-5.275895
-0.812778
-2.117976
-0.947218
-1.893477
-2.596376
-0.85294
-2.105205
-5.465552
-0.308761
-1.548641
0.051013
-0.491814
-0.713036
-0.850909
-0.089029
-0.135398
0.732927
0.610338
1.39124
-1.712699
-0.346741
-3.136806
-0.778006
-6.706339
-1.569316
-3.309317
-4.949919
-1.936832
-7.804951
-2.005859
-2.71453
-5.162922
-5.575833
-6.872131
-7.581808
-6.013192
-7.012713
-2.020101
-5.064111
-3.542271
-3.280901
-3.525396
-1.426667
-1.427658
-1.204571
-1.548162
-1.586434
-0.675687
-0.565676
1.19454
0.231177
1.223118
-2.876491
-1.707907
-0.917739
-1.754727
-0.514289
-3.095421
0.252598
-3.375624
-1.163008
-2.848244
-4.035068
-3.83152
-0.756782
-4.128651
-0.169607
-2.808302
-0.952312
-0.577712
-1.099567
0.374389
-0.028032
0.110564
-0.022978
0.023004
0.874191
-0.924439
0.249161
-1.282368
-0.039983
-5.883139
-3.28543
-2.95032
-1.045213
-1.310252
-4.271265
-0.139237
-4.446313
-3.677817
-3.250198
-1.141925
-7.294126
-2.568066
-6.429128
-0.867071
-3.164575
-1.601741
-1.347201
-0.797652
-0.973132
-2.261077
-1.310441
-0.596289
-0.993248
0.17691
-2.961421
0.264965
-1.135718
0.27597
-7.012713
-6.048531
-0.478869
-3.822936
-1.113385
-7.847511
-0.190619
-7.051179
-6.178984
-5.074922
-1.60478
-7.456645
-3.098052
-7.372087
-1.773765
-2.068245
-0.726293
-2.038846
-3.576294
-0.371629
-1.967707
-1.436693
-0.78879
-0.725872
0.041101
-2.565632
-0.499539
-2.718899
-0.979401
-7.891963
-3.689307
-1.877026
-4.700738
-3.683208
-8.903563
-1.689243
-8.092633
-6.720325
-5.72551
-7.427657
-4.878212
-3.101445
-8.418056
-0.95761
-4.642646
-1.344656
-3.010194
-2.895672
-1.682641
-2.434701
-2.191063
-1.568092
-1.580723
-0.598296
-2.096734
0.148235
-0.819926
-0.215215
-7.091185
-4.979118
-2.450135
-3.66314
-3.812117
-7.650801
-0.459855
-4.7886
-4.054467
-4.459443
-6.975672
-7.427657
-3.429718
-6.855871
-0.768949
-4.295896
-1.373553
-0.787818
-3.784821
-0.383923
-1.296182
-1.291907
-1.161813
-1.020683
-0.217704
-5.157005
-0.897446
-4.41915
-1.05898
-7.891963
-6.957653
-6.720325
-7.051179
-5.87706
-8.68042
-1.086492
-7.987273
-6.398038
-6.600978
-7.804951
-7.891963
-4.700738
-5.97237
-4.704484
-7.399486
-0.834339
-5.829713
-4.921548
-2.256848
-1.673634
-1.971482
-1.437622
-2.74579
-1.216426
-1.790218
1.495592
0.038571
0.904438
-3.170222
-0.836454
-4.721513
-5.549283
0.580072
-2.926261
0.344482
-1.68814
-2.280162
-1.0633
-2.222082
-0.937237
-1.92372
-2.445029
-3.324305
-6.055751
-0.653988
-0.615375
-1.519574
1.071622
0.671186
0.802304
0.329235
-0.302581
0.913154
-3.154171
-0.504028
-1.265305
-0.44183
-4.710128
-5.784508
-6.146723
-6.763497
-2.831095
-5.217814
0.230058
-3.028984
-3.146635
-4.95232
-3.969989
-2.756699
-3.028282
-5.494067
-5.445671
-5.341098
-0.747412
-3.091674
-4.176176
-1.264162
-1.493443
-2.002329
-1.275167
-2.245392
-0.027176
-2.05463
1.075837
-2.328488
0.834157
-4.226073
-2.091493
-1.206158
-2.928482
-0.172934
-2.76854
0.229693
-2.4066
-0.502876
-1.826699
-1.433624
-1.255479
-1.063005
-2.338475
-0.846344
-3.223818
-1.631599
0.078338
-0.612989
0.162531
-0.777193
0.149845
0.509687
-0.516536
1.416597
-0.70331
1.110727
-0.025885
1.023048
-6.027178
-5.334031
-3.386613
-5.741258
-0.002078
-3.644767
0.395188
-3.68727
-1.942267
-2.664019
-0.556921
-2.299112
-1.110131
-4.276143
-0.496353
-6.21232
-1.208772
-1.179559
-2.758573
0.439416
-0.201843
-0.783309
0.067996
-0.36885
0.53113
-2.517737
1.415806
0.558518
1.249076
-0.584669
-3.187358
-3.602749
-4.553286
-1.403103
-2.074175
0.689101
-2.779154
-1.610631
-2.189241
-4.226073
-4.957139
-4.513755
-5.510734
-2.878302
-4.592764
-0.957388
-3.17957
-1.623555
0.45345
-0.00742
1.048601
-0.100872
-0.247957
0.516997
0.391646
3.064651
1.53801
2.559983
-0.098938
0.552552
0.956312
0.249413
1.266414
-0.275209
1.86709
-0.203278
1.29913
0.246549
0.422758
-0.475502
0.306252
-0.61017
1.152828
0.186303
1.296783
1.302719
1.360196
1.866428
2.169251
1.802013
1.540124
1.716806
2.391267
-0.900117
2.105876
-0.000819
1.874484
-1.203268
-0.305759
-0.138378
-1.094987
0.30233
-1.580393
0.918816
-0.318291
0.086052
-0.234852
-0.928343
-0.890676
-0.713868
-1.312648
0.96169
-1.582871
0.066519
0.248962
0.162488
1.234596
0.734949
1.639707
0.861244
0.996236
1.191071
-1.223561
1.641245
-0.583572
1.093261
-3.145451
-0.816461
-1.024603
-2.888932
0.078479
-2.246837
-0.329659
-1.763408
0.063271
-1.465253
-1.003066
-2.48949
-1.545051
-0.601484
-0.771518
-3.148613
-0.060255
-0.001618
-0.813046
-0.740513
-0.143287
-0.154287
0.89895
-0.154644
1.122164
-1.626575
1.532106
-1.617629
-0.042426
-4.930916
-2.520057
-4.165174
-4.647951
-0.931054
-0.172752
-0.96018
-2.788776
-3.291348
-2.538168
-4.685896
-4.024557
-4.261579
-5.494067
-3.709219
-5.292646
-2.529817
-2.779701
-1.454575
-0.870797
0.184976
-0.804427
-0.275612
-0.203986
-0.018313
-0.704168
2.252483
0.362129
1.658557
-1.626921
0.153669
0.059325
-1.077619
0.776546
-1.39869
0.87758
-1.308302
-0.008756
-0.981393
-0.431603
-0.741618
-0.838966
-1.233835
0.664679
-1.602163
0.41152
0.536211
0.118125
1.56121
0.836559
1.154944
0.937849
0.543281
1.495317
-0.483074
1.614633
0.197534
1.93649
-1.243685
-0.460528
-0.330912
-1.122738
0.627102
-2.200682
1.049399
-1.115248
-0.083344
-0.985708
-1.447976
-1.289313
-1.212306
-2.625746
0.075081
-2.202986
0.057354
0.200082
-0.018209
0.662342
0.549321
1.246496
1.146138
0.485488
0.830062
ءء
ءا
ءب
ءت
ءث
ءج
ءح
ءخ
ءد
ءذ
ءر
ءز
ءس
ءش
ءص
ءض
ءط
ءظ
ءع
ءغ
ءف
ءق
ءك
ءل
ءم
ءن
ءه
ءو
ءي
اء
اا
اب
ات
اث
اج
اح
اخ
اد
اذ
ار
از
اس
اش
اص
اض
اط
اظ
اع
اغ
اف
اق
اك
ال
ام
ان
اه
او
اي
بء
با
بب
بت
بث
بج
بح
بخ
بد
بذ
بر
بز
بس
بش
بص
بض
بط
بظ
بع
بغ
بف
بق
بك
بل
بم
بن
به
بو
بي
تء
تا
تب
تت
تث
تج
تح
تخ
تد
تذ
تر
تز
تس
تش
تص
تض
تط
تظ
تع
تغ
تف
تق
تك
تل
تم
تن
ته
تو
تي
ثء
ثا
ثب
ثت
ثث
ثج
ثح
ثخ
ثد
ثذ
ثر
ثز
ثس
ثش
ثص
ثض
ثط
ثظ
ثع
ثغ
ثف
ثق
ثك
ثل
ثم
ثن
ثه
ثو
ثي
جء
جا
جب
جت
جث
جج
جح
جخ
جد
جذ
جر
جز
جس
جش
جص
جض
جط
جظ
جع
جغ
جف
جق
جك
جل
جم
جن
جه
جو
جي
حء
حا
حب
حت
حث
حج
حح
حخ
حد
حذ
حر
حز
حس
حش
حص
حض
حط
حظ
حع
حغ
حف
حق
حك
حل
حم
حن
حه
حو
حي
خء
خا
خب
خت
خث
خج
خح
خخ
خد
خذ
خر
خز
خس
خش
خص
خض
خط
خظ
خع
خغ
خف
خق
خك
خل
خم
خن
خه
خو
خي
دء
دا
دب
دت
دث
دج
دح
دخ
دد
دذ
در
دز
دس
دش
دص
دض
دط
دظ
دع
دغ
دف
دق
دك
دل
دم
دن
ده
دو
دي
ذء
ذا
ذب
ذت
ذث
ذج
ذح
ذخ
ذد
ذذ
ذر
ذز
ذس
ذش
ذص
ذض
ذط
ذظ
ذع
ذغ
ذف
ذق
ذك
ذل
ذم
ذن
ذه
ذو
ذي
رء
را
رب
رت
رث
رج
رح
رخ
رد
رذ
رر
رز
رس
رش
رص
رض
رط
رظ
رع
رغ
رف
رق
رك
رل
رم
رن
ره
رو
ري
زء
زا
زب
زت
زث
زج
زح
زخ
زد
زذ
زر
زز
زس
زش
زص
زض
زط
زظ
زع
زغ
زف
زق
زك
زل
زم
زن
زه
زو
زي
سء
سا
سب
ست
سث
سج
سح
سخ
سد
سذ
سر
سز
سس
سش
سص
سض
سط
سظ
سع
سغ
سف
سق
سك
سل
سم
سن
سه
سو
سي
شء
شا
شب
شت
شث
شج
شح
شخ
شد
شذ
شر
شز
شس
شش
شص
شض
شط
شظ
شع
شغ
شف
شق
شك
شل
شم
شن
شه
شو
شي
صء
صا
صب
صت
صث
صج
صح
صخ
صد
صذ
صر
صز
صس
صش
صص
صض
صط
صظ
صع
صغ
صف
صق
صك
صل
صم
صن
صه
صو
صي
ضء
ضا
ضب
ضت
ضث
ضج
ضح
ضخ
ضد
ضذ
ضر
ضز
ضس
ضش
ضص
ضض
ضط
ضظ
ضع
ضغ
ضف
ضق
ضك
ضل
ضم
ضن
ضه
ضو
ضي
طء
طا
طب
طت
طث
طج
طح
طخ
طد
طذ
طر
طز
طس
طش
طص
طض
طط
طظ
طع
طغ
طف
طق
طك
طل
طم
طن
طه
طو
طي
ظء
ظا
ظب
ظت
ظث
ظج
ظح
ظخ
ظد
ظذ
ظر
ظز
ظس
ظش
ظص
ظض
ظط
ظظ
ظع
ظغ
ظف
ظق
ظك
ظل
ظم
ظن
ظه
ظو
ظي
عء
عا
عب
عت
عث
عج
عح
عخ
عد
عذ
عر
عز
عس
عش
عص
عض
عط
عظ
عع
عغ
عف
عق
عك
عل
عم
عن
عه
عو
عي
غء
غا
غب
غت
غث
غج
غح
غخ
غد
غذ
غر
غز
غس
غش
غص
غض
غط
غظ
غع
غغ
غف
غق
غك
غل
غم
غن
غه
غو
غي
فء
فا
فب
فت
فث
فج
فح
فخ
فد
فذ
فر
فز
فس
فش
فص
فض
فط
فظ
فع
فغ
فف
فق
فك
فل
فم
فن
فه
فو
في
قء
قا
قب
قت
قث
قج
قح
قخ
قد
قذ
قر
قز
قس
قش
قص
قض
قط
قظ
قع
قغ
قف
قق
قك
قل
قم
قن
قه
قو
قي
كء
كا
كب
كت
كث
كج
كح
كخ
كد
كذ
كر
كز
كس
كش
كص
كض
كط
كظ
كع
كغ
كف
كق
كك
كل
كم
كن
كه
كو
كي
لء
لا
لب
لت
لث
لج
لح
لخ
لد
لذ
لر
لز
لس
لش
لص
لض
لط
لظ
لع
لغ
لف
لق
لك
لل
لم
لن
له
لو
لي
مء
ما
مب
مت
مث
مج
مح
مخ
مد
مذ
مر
مز
مس
مش
مص
مض
مط
مظ
مع
مغ
مف
مق
مك
مل
مم
من
مه
مو
مي
نء
نا
نب
نت
نث
نج
نح
نخ
ند
نذ
نر
نز
نس
نش
نص
نض
نط
نظ
نع
نغ
نف
نق
نك
نل
نم
نن
نه
نو
ني
هء
ها
هب
هت
هث
هج
هح
هخ
هد
هذ
هر
هز
هس
هش
هص
هض
هط
هظ
هع
هغ
هف
هق
هك
هل
هم
هن
هه
هو
هي
وء
وا
وب
وت
وث
وج
وح
وخ
ود
وذ
ور
وز
وس
وش
وص
وض
وط
وظ
وع
وغ
وف
وق
وك
ول
وم
ون
وه
وو
وي
يء
يا
يب
يت
يث
يج
يح
يخ
يد
يذ
ير
يز
يس
يش
يص
يض
يط
يظ
يع
يغ
يف
يق
يك
يل
يم
ين
يه
يو
يي
ا
ا
ا
ه
+
next
next
next
last
last
last
-
-
1 day
days
1 hour
2 hours
2 hours
hours
1 minute
2 days
2 minutes
2 minutes
minutes
1 second
2 seconds
2 seconds
seconds
2 weeks
2 weeks
weeks
1 week
2 months
2 months
months
months
1 month
1 year
2 years
2 years
years
years
am
am
am
pm
pm
pm
pm
tomorrow
yesterday
yesterday
ago
ago
this
this
now
now
first
third
fourth
fifth
sixth
seventh
eighth
ninth
tenth
eleventh
eleventh
twelfth
twelfth
saturday
sunday
monday
tuesday
wednesday
thursday
friday
3
4
5
6
7
8
9
10
january
february
march
april
may
june
july
august
september
october
november
december
january
february
march
april
may
june
july
august
september
october
november
december
+أ
إ
آ
ة
بعد
تالي
لاحق
قادم
سابق
فائت
ماضي
منذ
قبل
يوم
ايام
ساعه
ساعتان
ساعتين
ساعات
دقيقه
يومين
دقيقتان
دقيقتين
دقائق
ثانيه
ثانيتين
ثانيتان
ثواني
اسبوعين
اسبوعان
اسابيع
اسبوع
شهرين
شهران
اشهر
شهور
شهر
سنه
سنتين
سنتان
سنوات
سنين
صباحا
فجرا
قبل الظهر
مساء
عصرا
بعد الظهر
ليلا
غد
بارحة
أمس
مضت
مضى
هذا
هذه
الآن
لحظه
اول
ثالث
رابع
خامس
سادس
سابع
ثامن
تاسع
عاشر
حادي عشر
حاديه عشر
ثاني عشر
ثانيه عشر
سبت
احد
اثنين
ثلاثاء
اربعاء
خميس
جمعه
ثلاث
اربع
خمس
ست
سبع
ثمان
تسع
عشر
كانون ثاني
شباط
اذار
نيسان
ايار
حزيران
تموز
اب
ايلول
تشرين اول
تشرين ثاني
كانون اول
يناير
فبراير
مارس
ابريل
مايو
يونيو
يوليو
اغسطس
سبتمبر
اكتوبر
نوفمبر
ديسمبر
و#1/1/1420#
17/04/1999
16/05/1999
15/06/1999
14/07/1999
12/08/1999
11/09/1999
10/10/1999
09/11/1999
09/12/1999
08/01/2000
07/02/2000
07/03/2000
06/04/2000
05/05/2000
03/06/2000
03/07/2000
01/08/2000
30/08/2000
28/09/2000
28/10/2000
27/11/2000
27/12/2000
26/01/2001
24/02/2001
26/03/2001
25/04/2001
24/05/2001
22/06/2001
22/07/2001
20/08/2001
18/09/2001
17/10/2001
16/11/2001
16/12/2001
15/01/2002
13/02/2002
15/03/2002
14/04/2002
13/05/2002
12/06/2002
11/07/2002
10/08/2002
08/09/2002
07/10/2002
06/11/2002
05/12/2002
04/01/2003
02/02/2003
04/03/2003
03/04/2003
02/05/2003
01/06/2003
01/07/2003
30/07/2003
29/08/2003
27/09/2003
26/10/2003
25/11/2003
24/12/2003
23/01/2004
21/02/2004
22/03/2004
20/04/2004
20/05/2004
19/06/2004
18/07/2004
17/08/2004
15/09/2004
15/10/2004
14/11/2004
13/12/2004
12/01/2005
10/02/2005
11/03/2005
10/04/2005
09/05/2005
08/06/2005
07/07/2005
06/08/2005
05/09/2005
04/10/2005
03/11/2005
03/12/2005
01/01/2006
31/01/2006
01/03/2006
30/03/2006
29/04/2006
28/05/2006
27/06/2006
26/07/2006
25/08/2006
24/09/2006
23/10/2006
22/11/2006
22/12/2006
20/01/2007
19/02/2007
20/03/2007
18/04/2007
18/05/2007
16/06/2007
15/07/2007
14/08/2007
13/09/2007
13/10/2007
11/11/2007
11/12/2007
10/01/2008
08/02/2008
09/03/2008
07/04/2008
06/05/2008
05/06/2008
04/07/2008
02/08/2008
01/09/2008
01/10/2008
30/10/2008
29/11/2008
29/12/2008
27/01/2009
26/02/2009
28/03/2009
26/04/2009
25/05/2009
24/06/2009
23/07/2009
22/08/2009
20/09/2009
20/10/2009
18/11/2009
18/12/2009
16/01/2010
15/02/2010
17/03/2010
15/04/2010
15/05/2010
13/06/2010
13/07/2010
11/08/2010
10/09/2010
09/10/2010
07/11/2010
07/12/2010
05/01/2011
04/02/2011
06/03/2011
05/04/2011
04/05/2011
03/06/2011
02/07/2011
01/08/2011
30/08/2011
29/09/2011
28/10/2011
26/11/2011
26/12/2011
24/01/2012
23/02/2012
24/03/2012
22/04/2012
22/05/2012
21/06/2012
20/07/2012
19/08/2012
17/09/2012
17/10/2012
15/11/2012
14/12/2012
13/01/2013
11/02/2013
13/03/2013
11/04/2013
11/05/2013
10/06/2013
09/07/2013
08/08/2013
07/09/2013
06/10/2013
04/11/2013
04/12/2013
02/01/2014
01/02/2014
02/03/2014
01/04/2014
30/04/2014
30/05/2014
28/06/2014
28/07/2014
27/08/2014
25/09/2014
25/10/2014
23/11/2014
23/12/2014
21/01/2015
20/02/2015
21/03/2015
20/04/2015
19/05/2015
18/06/2015
17/07/2015
16/08/2015
14/09/2015
14/10/2015
13/11/2015
12/12/2015
11/01/2016
10/02/2016
10/03/2016
08/04/2016
08/05/2016
06/06/2016
06/07/2016
04/08/2016
02/09/2016
02/10/2016
01/11/2016
30/11/2016
30/12/2016
29/01/2017
28/02/2017
29/03/2017
27/04/2017
27/05/2017
25/06/2017
24/07/2017
23/08/2017
21/09/2017
21/10/2017
19/11/2017
19/12/2017
18/01/2018
17/02/2018
18/03/2018
17/04/2018
16/05/2018
15/06/2018
14/07/2018
12/08/2018
11/09/2018
10/10/2018
09/11/2018
08/12/2018
07/01/2019
06/02/2019
08/03/2019
06/04/2019
06/05/2019
04/06/2019
04/07/2019
02/08/2019
31/08/2019
30/09/2019
29/10/2019
28/11/2019
27/12/2019
26/01/2020
25/02/2020
25/03/2020
24/04/2020
24/05/2020
22/06/2020
22/07/2020
20/08/2020
18/09/2020
18/10/2020
16/11/2020
16/12/2020
14/01/2021
13/02/2021
14/03/2021
13/04/2021
13/05/2021
11/06/2021
11/07/2021
09/08/2021
08/09/2021
07/10/2021
06/11/2021
05/12/2021
04/01/2022
02/02/2022
04/03/2022
02/04/2022
02/05/2022
31/05/2022
30/06/2022
30/07/2022
28/08/2022
27/09/2022
26/10/2022
25/11/2022
25/12/2022
23/01/2023
21/02/2023
23/03/2023
21/04/2023
21/05/2023
19/06/2023
19/07/2023
17/08/2023
16/09/2023
16/10/2023
15/11/2023
14/12/2023
13/01/2024
11/02/2024
11/03/2024
10/04/2024
09/05/2024
07/06/2024
07/07/2024
05/08/2024
04/09/2024
04/10/2024
03/11/2024
02/12/2024
01/01/2025
31/01/2025
01/03/2025
30/03/2025
29/04/2025
28/05/2025
26/06/2025
26/07/2025
24/08/2025
23/09/2025
23/10/2025
22/11/2025
21/12/2025
20/01/2026
18/02/2026
20/03/2026
18/04/2026
18/05/2026
16/06/2026
15/07/2026
14/08/2026
12/09/2026
12/10/2026
11/11/2026
10/12/2026
09/01/2027
08/02/2027
09/03/2027
08/04/2027
07/05/2027
06/06/2027
05/07/2027
03/08/2027
02/09/2027
01/10/2027
31/10/2027
29/11/2027
29/12/2027
28/01/2028
26/02/2028
27/03/2028
26/04/2028
25/05/2028
24/06/2028
23/07/2028
22/08/2028
20/09/2028
19/10/2028
18/11/2028
17/12/2028
16/01/2029
14/02/2029
16/03/2029
15/04/2029
14/05/2029
13/06/2029
13/07/2029
11/08/2029
10/09/2029
09/10/2029
07/11/2029
07/12/2029
05/01/2030
04/02/2030
05/03/2030
04/04/2030
03/05/2030
02/06/2030
02/07/2030
01/08/2030
30/08/2030
29/09/2030
28/10/2030
26/11/2030
26/12/2030
24/01/2031
23/02/2031
24/03/2031
23/04/2031
22/05/2031
21/06/2031
21/07/2031
20/08/2031
18/09/2031
17/10/2031
16/11/2031
15/12/2031
14/01/2032
12/02/2032
13/03/2032
11/04/2032
10/05/2032
09/06/2032
09/07/2032
08/08/2032
06/09/2032
06/10/2032
04/11/2032
04/12/2032
02/01/2033
01/02/2033
02/03/2033
01/04/2033
30/04/2033
29/05/2033
28/06/2033
28/07/2033
26/08/2033
25/09/2033
24/10/2033
23/11/2033
23/12/2033
21/01/2034
20/02/2034
21/03/2034
20/04/2034
19/05/2034
17/06/2034
17/07/2034
15/08/2034
14/09/2034
13/10/2034
12/11/2034
12/12/2034
11/01/2035
09/02/2035
11/03/2035
09/04/2035
09/05/2035
07/06/2035
06/07/2035
05/08/2035
03/09/2035
02/10/2035
01/11/2035
01/12/2035
30/12/2035
29/01/2036
28/02/2036
29/03/2036
27/04/2036
27/05/2036
25/06/2036
24/07/2036
23/08/2036
21/09/2036
20/10/2036
19/11/2036
19/12/2036
17/01/2037
16/02/2037
18/03/2037
17/04/2037
16/05/2037
15/06/2037
14/07/2037
12/08/2037
11/09/2037
10/10/2037
08/11/2037
08/12/2037
07/01/2038
05/02/2038
07/03/2038
06/04/2038
05/05/2038
04/06/2038
03/07/2038
02/08/2038
31/08/2038
30/09/2038
29/10/2038
27/11/2038
27/12/2038
26/01/2039
24/02/2039
26/03/2039
24/04/2039
24/05/2039
23/06/2039
22/07/2039
21/08/2039
19/09/2039
19/10/2039
17/11/2039
17/12/2039
15/01/2040
14/02/2040
14/03/2040
13/04/2040
12/05/2040
11/06/2040
10/07/2040
09/08/2040
07/09/2040
07/10/2040
06/11/2040
05/12/2040
04/01/2041
02/02/2041
04/03/2041
02/04/2041
01/05/2041
31/05/2041
29/06/2041
29/07/2041
28/08/2041
26/09/2041
26/10/2041
25/11/2041
24/12/2041
23/01/2042
21/02/2042
23/03/2042
21/04/2042
20/05/2042
19/06/2042
18/07/2042
17/08/2042
15/09/2042
15/10/2042
14/11/2042
14/12/2042
12/01/2043
11/02/2043
12/03/2043
11/04/2043
10/05/2043
08/06/2043
08/07/2043
06/08/2043
04/09/2043
04/10/2043
03/11/2043
03/12/2043
02/01/2044
31/01/2044
01/03/2044
30/03/2044
29/04/2044
28/05/2044
26/06/2044
26/07/2044
24/08/2044
23/09/2044
22/10/2044
21/11/2044
21/12/2044
19/01/2045
18/02/2045
20/03/2045
18/04/2045
18/05/2045
16/06/2045
15/07/2045
14/08/2045
12/09/2045
12/10/2045
10/11/2045
10/12/2045
08/01/2046
07/02/2046
09/03/2046
07/04/2046
07/05/2046
05/06/2046
05/07/2046
03/08/2046
02/09/2046
01/10/2046
31/10/2046
29/11/2046
28/12/2046
27/01/2047
26/02/2047
27/03/2047
26/04/2047
26/05/2047
24/06/2047
24/07/2047
23/08/2047
21/09/2047
20/10/2047
19/11/2047
18/12/2047
16/01/2048
15/02/2048
16/03/2048
14/04/2048
14/05/2048
12/06/2048
12/07/2048
11/08/2048
10/09/2048
09/10/2048
07/11/2048
07/12/2048
05/01/2049
03/02/2049
05/03/2049
03/04/2049
03/05/2049
02/06/2049
01/07/2049
31/07/2049
30/08/2049
28/09/2049
28/10/2049
26/11/2049
26/12/2049
24/01/2050
23/02/2050
24/03/2050
22/04/2050
22/05/2050
20/06/2050
20/07/2050
19/08/2050
17/09/2050
17/10/2050
15/11/2050
15/12/2050
14/01/2051
12/02/2051
14/03/2051
12/04/2051
11/05/2051
10/06/2051
09/07/2051
08/08/2051
06/09/2051
06/10/2051
05/11/2051
04/12/2051
03/01/2052
02/02/2052
02/03/2052
01/04/2052
30/04/2052
29/05/2052
28/06/2052
27/07/2052
26/08/2052
24/09/2052
24/10/2052
22/11/2052
22/12/2052
21/01/2053
20/02/2053
21/03/2053
20/04/2053
19/05/2053
17/06/2053
17/07/2053
15/08/2053
13/09/2053
13/10/2053
11/11/2053
11/12/2053
10/01/2054
09/02/2054
10/03/2054
09/04/2054
09/05/2054
07/06/2054
06/07/2054
05/08/2054
03/09/2054
02/10/2054
01/11/2054
30/11/2054
30/12/2054
29/01/2055
27/02/2055
29/03/2055
28/04/2055
28/05/2055
26/06/2055
25/07/2055
24/08/2055
22/09/2055
21/10/2055
20/11/2055
19/12/2055
18/01/2056
17/02/2056
17/03/2056
16/04/2056
16/05/2056
14/06/2056
14/07/2056
12/08/2056
11/09/2056
10/10/2056
08/11/2056
08/12/2056
06/01/2057
05/02/2057
06/03/2057
05/04/2057
05/05/2057
03/06/2057
03/07/2057
01/08/2057
31/08/2057
30/09/2057
29/10/2057
27/11/2057
27/12/2057
25/01/2058
24/02/2058
25/03/2058
24/04/2058
23/05/2058
22/06/2058
21/07/2058
20/08/2058
19/09/2058
18/10/2058
17/11/2058
17/12/2058
15/01/2059
14/02/2059
15/03/2059
13/04/2059
13/05/2059
11/06/2059
11/07/2059
09/08/2059
08/09/2059
08/10/2059
06/11/2059
06/12/2059
05/01/2060
03/02/2060
04/03/2060
02/04/2060
01/05/2060
31/05/2060
29/06/2060
28/07/2060
27/08/2060
26/09/2060
25/10/2060
24/11/2060
24/12/2060
23/01/2061
21/02/2061
23/03/2061
21/04/2061
20/05/2061
19/06/2061
18/07/2061
16/08/2061
15/09/2061
15/10/2061
13/11/2061
13/12/2061
12/01/2062
10/02/2062
12/03/2062
11/04/2062
10/05/2062
08/06/2062
08/07/2062
06/08/2062
04/09/2062
04/10/2062
03/11/2062
02/12/2062
01/01/2063
30/01/2063
01/03/2063
31/03/2063
30/04/2063
29/05/2063
27/06/2063
27/07/2063
25/08/2063
24/09/2063
23/10/2063
22/11/2063
21/12/2063
20/01/2064
18/02/2064
19/03/2064
18/04/2064
17/05/2064
16/06/2064
15/07/2064
14/08/2064
12/09/2064
12/10/2064
10/11/2064
09/12/2064
08/01/2065
06/02/2065
08/03/2065
07/04/2065
06/05/2065
05/06/2065
05/07/2065
03/08/2065
02/09/2065
01/10/2065
31/10/2065
29/11/2065
28/12/2065
27/01/2066
25/02/2066
27/03/2066
25/04/2066
25/05/2066
24/06/2066
24/07/2066
22/08/2066
21/09/2066
20/10/2066
19/11/2066
18/12/2066
16/01/2067
15/02/2067
16/03/2067
15/04/2067
14/05/2067
13/06/2067
13/07/2067
11/08/2067
10/09/2067
10/10/2067
08/11/2067
08/12/2067
06/01/2068
04/02/2068
05/03/2068
03/04/2068
03/05/2068
01/06/2068
01/07/2068
30/07/2068
29/08/2068
28/09/2068
27/10/2068
26/11/2068
25/12/2068
24/01/2069
23/02/2069
24/03/2069
22/04/2069
22/05/2069
20/06/2069
20/07/2069
18/08/2069
17/09/2069
16/10/2069
15/11/2069
15/12/2069
13/01/2070
12/02/2070
14/03/2070
12/04/2070
11/05/2070
10/06/2070
09/07/2070
08/08/2070
06/09/2070
05/10/2070
04/11/2070
04/12/2070
02/01/2071
01/02/2071
03/03/2071
02/04/2071
01/05/2071
30/05/2071
29/06/2071
28/07/2071
26/08/2071
25/09/2071
24/10/2071
23/11/2071
22/12/2071
21/01/2072
20/02/2072
21/03/2072
19/04/2072
19/05/2072
17/06/2072
17/07/2072
15/08/2072
13/09/2072
13/10/2072
11/11/2072
11/12/2072
09/01/2073
08/02/2073
10/03/2073
09/04/2073
08/05/2073
07/06/2073
06/07/2073
05/08/2073
03/09/2073
02/10/2073
01/11/2073
30/11/2073
30/12/2073
28/01/2074
27/02/2074
29/03/2074
27/04/2074
27/05/2074
26/06/2074
25/07/2074
23/08/2074
22/09/2074
21/10/2074
20/11/2074
19/12/2074
18/01/2075
16/02/2075
18/03/2075
16/04/2075
16/05/2075
15/06/2075
14/07/2075
13/08/2075
11/09/2075
11/10/2075
09/11/2075
09/12/2075
07/01/2076
06/02/2076
06/03/2076
05/04/2076
04/05/2076
03/06/2076
02/07/2076
01/08/2076
30/08/2076
29/09/2076
29/10/2076
27/11/2076
27/12/2076
26/01/2077
24/02/2077
25/03/2077
24/04/2077
23/05/2077
21/06/2077
21/07/2077
19/08/2077
18/09/2077
18/10/2077
�p�5��ӱ		
sW��q�f   GBMB