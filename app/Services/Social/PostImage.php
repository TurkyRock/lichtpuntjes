<?php
namespace App\Services\Social;
use App\Models\Article;
class PostImage {
 private function rgb($im,string $hex):int {return imagecolorallocate($im,hexdec(substr($hex,0,2)),hexdec(substr($hex,2,2)),hexdec(substr($hex,4,2)));}
 private function width(string $text,int $size,string $font):int {$b=imagettfbbox($size,0,$font,$text);return abs($b[2]-$b[0]);}
 private function lines(string $text,int $size,string $font,int $width):array {
  $out=[];$line='';foreach(preg_split('/\s+/u',trim($text)) as $word){
   if($this->width($word,$size,$font)>$width){foreach(mb_str_split($word) as $char){if($this->width($line.$char,$size,$font)>$width){$out[]=$line;$line='';}$line.=$char;}continue;}
   $next=$line===''?$word:$line.' '.$word;if($line!==''&&$this->width($next,$size,$font)>$width){$out[]=$line;$line=$word;}else $line=$next;
  }if($line!=='')$out[]=$line;return $out;
 }
 private function text($im,string $text,int $x,int $top,int $width,int $height,int $size,string $font,string $color):void {
  do{$lines=$this->lines(strip_tags($text),$size,$font,$width);$lineHeight=(int)ceil($size*1.5);if(count($lines)*$lineHeight<=$height)break;$size--;}while($size>12);
  $max=max(1,(int)floor($height/$lineHeight));$lines=array_slice($lines,0,$max);
  foreach($lines as $i=>$line)imagettftext($im,$size,0,$x,$top+$size+$i*$lineHeight,$this->rgb($im,$color),$font,$line);
 }
 private function sun($im,int $x,int $y,int $r):void {
  $color=$this->rgb($im,'EFCB53');imagefilledellipse($im,$x,$y,$r*2,$r*2,$color);imagesetthickness($im,max(4,(int)($r*.15)));
  for($i=0;$i<12;$i++){$a=$i*M_PI/6;imageline($im,(int)($x+cos($a)*$r*1.3),(int)($y+sin($a)*$r*1.3),(int)($x+cos($a)*$r*1.85),(int)($y+sin($a)*$r*1.85),$color);}imagesetthickness($im,1);
 }
 public function render(Article $a,string $channel,string $kind,?array $snapshot=null):string {
  abort_unless(app(Planner::class)->usable($a),404);$cfg=config('social-publishing.channels.'.$channel);abort_unless($cfg,404);
  if(!function_exists('imagettftext'))throw new \RuntimeException('PHP GD met FreeType is nodig voor sociale beeldtemplates.');
  $photo=$snapshot['photo']??$a->photo;$url=$photo['url']??'';
  abort_unless(preg_match('~^/news-images/[a-f0-9]{64}\.(jpg|png)$~D',$url),404);$path=public_path(ltrim($url,'/'));abort_unless(is_file($path)&&!is_link($path)&&hash_file('sha256',$path)===($photo['sha256']??''),404);
  $bytes=file_get_contents($path);$info=@getimagesizefromstring($bytes);abort_unless($info&&$info[0]<=4096&&$info[1]<=4096,422);$source=@imagecreatefromstring($bytes);abort_unless($source,422);
  $w=$cfg['width'];$h=$cfg['height'];$im=imagecreatetruecolor($w,$h);$serif=public_path('fonts/social/serif-bold.ttf');$sans=public_path('fonts/social/sans.ttf');
  try{
   imagefill($im,0,0,$this->rgb($im,'F8F3E6'));
   // Deterministic paper grain; no AI generation and no image files accumulating on disk.
   for($y=0;$y<$h;$y+=6)for($x=0;$x<$w;$x+=8)if((($x*7+$y*11)%31)<3)imagesetpixel($im,$x,$y,$this->rgb($im,'ECE8D9'));
   $wide=$w>$h;$isTikTok=$channel==='tiktok';
   if($wide){$px=555;$py=58;$pw=$w-580;$ph=$h-190;}else{$px=35;$py=$isTikTok?450:405;$pw=$w-70;$ph=$isTikTok?900:620;}
   $ratio=max($pw/imagesx($source),$ph/imagesy($source));$sw=$pw/$ratio;$sh=$ph/$ratio;
   imagecopyresampled($im,$source,$px,$py,(int)((imagesx($source)-$sw)/2),(int)((imagesy($source)-$sh)/2),$pw,$ph,(int)$sw,(int)$sh);
   // Jagged cream-paper edges around the actual illustration.
   for($x=$px;$x<$px+$pw;$x+=24){$t=intdiv($x,24)%3;imagefilledpolygon($im,[$x,$py,$x+12,$py+9+$t*3,$x+24,$py],$this->rgb($im,'F8F3E6'));imagefilledpolygon($im,[$x,$py+$ph,$x+12,$py+$ph-9-$t*3,$x+24,$py+$ph],$this->rgb($im,'F8F3E6'));}
   $intro=config('social-publishing.slots.'.$kind.'.intro');$title=$snapshot['title']??$a->nl['title'];
   if($wide){
    imagefilledrectangle($im,35,36,510,85,$this->rgb($im,'DBE4CD'));$this->text($im,$intro,48,44,450,40,16,$sans,'2A493D');
    $this->text($im,$title,42,120,480,340,42,$serif,'203E36');
    imagefilledrectangle($im,0,$h-130,$w,$h,$this->rgb($im,'D9E4CC'));
    $this->sun($im,65,$h-63,21);$this->text($im,'Jouw Lichtpuntjes',110,$h-85,350,48,25,$serif,'203E36');
    $this->text($im,'Benieuwd naar nog meer lichtpuntjes?',520,$h-102,$w-555,58,24,$serif,'203E36');
    $this->text($im,'jouwlichtpuntjes.nl',520,$h-48,$w-555,40,20,$sans,'203E36');
   }else{
    imagefilledrectangle($im,45,48,$w-45,110,$this->rgb($im,'DAE4CE'));$this->text($im,$intro,67,60,$w-135,42,22,$sans,'203E36');
    $this->text($im,$title,65,155,$w-135,$isTikTok?245:220,58,$serif,'203E36');
    $bottom=$py+$ph+22;imagefilledrectangle($im,0,$bottom-8,$w,$h,$this->rgb($im,'DAE4CE'));
    imagefilledrectangle($im,60,$bottom+48,$w-($isTikTok?150:60),$bottom+185,$this->rgb($im,'F1D56D'));
    $this->text($im,'Haal meer lichtpuntjes uit je dag',85,$bottom+60,$w-($isTikTok?270:170),110,35,$serif,'203E36');
    $brandY=$isTikTok?$bottom+245:$h-82;$this->sun($im,89,$brandY+20,20);$this->text($im,'Jouw Lichtpuntjes',145,$brandY-7,$w-230,60,28,$serif,'203E36');
    if($isTikTok)$this->text($im,'jouwlichtpuntjes.nl',145,$brandY+65,$w-270,45,24,$sans,'203E36');
   }
   ob_start();try{if(!imagejpeg($im,null,86))throw new \RuntimeException('Beeld exporteren mislukt.');return ob_get_contents();}finally{ob_end_clean();}
  }finally{imagedestroy($source);imagedestroy($im);}
 }
}
