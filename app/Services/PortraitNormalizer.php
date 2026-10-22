<?php
namespace App\Services;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
class PortraitNormalizer {
 public function jpeg(UploadedFile $file):string {
  if(!function_exists('imagecreatefromstring'))throw ValidationException::withMessages(['photo'=>'Profielfoto’s zijn tijdelijk niet beschikbaar: PHP GD moet op de server aan staan.']);
  $image=@imagecreatefromstring(file_get_contents($file->getRealPath()));
  if(!$image)throw ValidationException::withMessages(['photo'=>'Dit bestand kan niet als foto worden gelezen.']);
  try {
   if($file->getMimeType()==='image/jpeg'&&function_exists('exif_read_data')){
    $exif=@exif_read_data($file->getRealPath());$orientation=(int)($exif['Orientation']??1);
    if(in_array($orientation,[2,4,5,7],true))imageflip($image,IMG_FLIP_HORIZONTAL);
    $angle=match($orientation){3,4=>180,5,6=>-90,7,8=>90,default=>0};
    if($angle){$rotated=imagerotate($image,$angle,0);if($rotated){imagedestroy($image);$image=$rotated;}}
   }
   $w=imagesx($image);$h=imagesy($image);$scale=min(1,768/max($w,$h));$out=imagecreatetruecolor(max(1,(int)round($w*$scale)),max(1,(int)round($h*$scale)));
   try {imagefill($out,0,0,imagecolorallocate($out,255,255,255));imagecopyresampled($out,$image,0,0,0,0,imagesx($out),imagesy($out),$w,$h);ob_start();try{imagejpeg($out,null,88);$bytes=ob_get_contents();}finally{ob_end_clean();}if(!$bytes)throw new \RuntimeException('Foto verwerken mislukt.');return $bytes;}finally{imagedestroy($out);}
  }finally{imagedestroy($image);}
 }
}
