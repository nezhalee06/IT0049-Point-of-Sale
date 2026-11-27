<?php
namespace App\Libraries;
class ImageUpload {
 public function save($file): ?string {
  if (!$file || $file->getError()===UPLOAD_ERR_NO_FILE) return null;
  if (!$file->isValid() || $file->getSize()>2*1024*1024) throw new \RuntimeException('Choose an image smaller than 2 MB.');
  if (!in_array($file->getMimeType(),['image/jpeg','image/png','image/webp'],true)) throw new \RuntimeException('Only JPEG, PNG and WebP images are allowed.');
  $size=@getimagesize($file->getTempName());
  if (!$size || $size[0]>6000 || $size[1]>6000 || $size[0]*$size[1]>16000000) throw new \RuntimeException('Image dimensions must be at most 6000 pixels and 16 megapixels.');
  if (!extension_loaded('gd')) throw new \RuntimeException('Enable the PHP GD extension to prepare uploaded images.');
  $source=@imagecreatefromstring(file_get_contents($file->getTempName()));
  if (!$source) throw new \RuntimeException('This image could not be decoded.');
  $ratio=min(1,800/max($size[0],$size[1])); $w=max(1,(int)round($size[0]*$ratio)); $h=max(1,(int)round($size[1]*$ratio));
  $image=imagecreatetruecolor($w,$h); imagefill($image,0,0,imagecolorallocate($image,255,255,255));
  imagecopyresampled($image,$source,0,0,0,0,$w,$h,$size[0],$size[1]);
  $name=bin2hex(random_bytes(16)).'.jpg';
  if (!is_dir(WRITEPATH.'uploads/pos')) mkdir(WRITEPATH.'uploads/pos',0755,true);
  $ok=imagejpeg($image,WRITEPATH.'uploads/pos/'.$name,85); imagedestroy($source); imagedestroy($image);
  if (!$ok) throw new \RuntimeException('Could not store image. Check writable permissions.');
  return $name;
 }
 public function remove(?string $name): void { if ($name && preg_match('/^[a-f0-9]{32}\.jpg$/D',$name) && is_file(WRITEPATH.'uploads/pos/'.$name)) unlink(WRITEPATH.'uploads/pos/'.$name); }
}
