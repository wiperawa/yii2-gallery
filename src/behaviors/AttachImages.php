<?php
namespace wiperawa\gallery\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use wiperawa\gallery\models;
use yii\helpers\BaseFileHelper;
use wiperawa\gallery\ModuleTrait;
use wiperawa\gallery\models\Image;
use wiperawa\gallery\models\PlaceHolder;

class AttachImages extends Behavior
{
    use ModuleTrait;

    public $createAliasMethod = false;
    public $modelClass = null;
    public $uploadsPath = '';
    public $mode = 'gallery';
    public $webUploadsPath = '/uploads';
    public $allowExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    public $inputName = 'galleryFiles';
    private $doResetImages = true;
    public $quality = false;
    public $maxWidth = false;
    public $maxHeight = false;
    public $galleryId = false;

    const STATUS_SUCCESS = 0;

    public function init()
    {
        if (empty($this->uploadsPath)) {
            $this->uploadsPath = Yii::$app->getModule('gallery')->imagesStorePath;
        }

        if ($this->quality > 100) {
            $this->quality = 100;
        } elseif ($this->quality < 0) {
            $this->quality = 0;
        }
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_UPDATE => 'setImages',
            ActiveRecord::EVENT_AFTER_INSERT => 'setImages',
            ActiveRecord::EVENT_BEFORE_DELETE => 'removeImages',
        ];
    }

    private static function resizePhoto($source_filename, $new_filename, $quality, $max_width, $max_height)
    {

        if (Yii::$app->getModule('gallery')->graphicsLibrary == 'GD') {
            return static::resizePhotoGd($source_filename, $new_filename, $quality, $max_width, $max_height);
        }
        return static::resizePhotoImagick($source_filename, $new_filename, $quality, $max_width, $max_height);

    }

    private static function resizePhotoImagick ($source_filename, $new_filename, $quality = false, $max_width, $max_height) {
        $image = new \Imagick($source_filename);
        $width = $image->getImageWidth();
        $height = $image->getImageHeight();
        list($new_width, $new_height) = static::getNewWidthHeight($width, $height, $max_width, $max_height);

        $image->resizeImage($new_width, $new_height,\Imagick::FILTER_POINT,1);
        $ret = $image->writeImage($new_filename);
        $image->destroy();
        return $ret;
    }

    private static function resizePhotoGd($source_filename, $new_filename, $quality, $max_width = false, $max_height = false) {
        $type = pathinfo($source_filename, PATHINFO_EXTENSION);
        switch ($type) {
            case 'jpeg':
            case 'jpg':
            {
                $source = imagecreatefromjpeg($source_filename);
                break;
            }
            case 'png':
            {
                $source = imagecreatefrompng($source_filename);
                break;

            }
            case 'gif':
            {
                $source = imagecreatefromgif($source_filename);
                break;
            }
            default:
                return false;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        list($newwidth, $newheight) = static::getNewWidthHeight($width, $height, $max_width, $max_height);

        $tmpimg = imagecreatetruecolor( $newwidth, $newheight );

        imagealphablending($tmpimg, false);
        imagesavealpha($tmpimg, true);

        imagecopyresampled( $tmpimg, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

        if ( !$quality ) {
            $quality = 70;
        }

        switch($type){
            case'jpeg':
            case'jpg':
                $res = imagejpeg($tmpimg, $new_filename, $quality);
                break;
            case'png':
                $quality = (int)(100 - $quality) / 10 - 1;
                $res = imagepng($tmpimg, $new_filename, $quality);
                break;
            case'gif':
                $res = imagegif($tmpimg, $new_filename);
                break;

        }

        imagedestroy($source);
        imagedestroy($tmpimg);

        return $res;
    }

    private static function getNewWidthHeight($width, $height, $max_width, $max_height) {
        if ($width > $height) {
            if($width < $max_width)
                $newwidth = $width;
            else
                $newwidth = $max_width;
            $divisor = $width / $newwidth;
            $newheight = floor( $height / $divisor);
        } else {

            if($height < $max_height)
                $newheight = $height;
            else
                $newheight =  $max_height;

            $divisor = $height / $newheight;
            $newwidth = floor( $width / $divisor );
        }

        return [$newwidth,$newheight];
    }

    public function attachImage($absolutePath, $isMain = false)
    {
        if (!preg_match('#http#', $absolutePath)) {
            if (!file_exists($absolutePath)) {
                throw new \Exception('File not exist! :' . $absolutePath);
            }
        }

        if (!$this->owner->id) {
            throw new \Exception('Owner must have id when you attach image!');
        }

        $pictureFileName =
            substr(md5(microtime(true)
                . $absolutePath), 4, 6)
            . '.'
            . pathinfo($absolutePath, PATHINFO_EXTENSION);

        $pictureSubDir = $this->getModule()->getModelSubDir($this->owner);
        $storePath = $this->getModule()->getStorePath($this->owner);

        $newAbsolutePath = $storePath
            . DIRECTORY_SEPARATOR
            . $pictureSubDir
            . DIRECTORY_SEPARATOR
            . $pictureFileName;

        BaseFileHelper::createDirectory($storePath . DIRECTORY_SEPARATOR . $pictureSubDir, 0775, true);

        if ($this->quality !== false or $this->maxHeight != false or $this->maxWidth != false) {
            self::resizePhoto($absolutePath, $newAbsolutePath, $this->quality,$this->maxWidth, $this->maxHeight);
        } else {
            copy($absolutePath, $newAbsolutePath);
        }

        if (!file_exists($newAbsolutePath)) {
            throw new \Exception('Cant copy file! ' . $absolutePath . ' to ' . $newAbsolutePath);
        }

        unlink($absolutePath);

        if ($this->modelClass === null) {
            $image = new models\Image;
        } else {
            $image = new ${$this->modelClass}();
        }
        if($this->galleryId === false) $galleryId = null; else $galleryId = $this->galleryId;

        $image->itemId = $this->owner->id;
        $image->filePath = $pictureSubDir . '/' . $pictureFileName;
        $image->modelName = $this->getModule()->getShortClass($this->owner);
        $image->urlAlias = $this->getAlias($image);
        $image->gallery_id = $galleryId;

        if (!$image->save()) {
            return false;
        }

        if (count($image->getErrors()) > 0) {
            $ar = array_shift($image->getErrors());

            unlink($newAbsolutePath);
            throw new \Exception(array_shift($ar));
        }
        $img = $this->owner->getImage($this->galleryId);

        if (is_object($img) && get_class($img) == 'wiperawa\gallery\models\PlaceHolder' or $img == null or $isMain) {
            $this->setMainImage($image);
        }

        return $image;
    }

    public function setMainImage($img)
    {
        if ($this->owner->id != $img->itemId) {
            throw new \Exception('Image must belong to this model');
        }

        $counter = 1;
        $img->setMain(true);
        $img->urlAlias = $this->getAliasString() . '-' . $counter;
        $img->save();

        $images = $this->owner->getImages();

        foreach ($images as $allImg) {
            if ($allImg->id == $img->id) {
                continue;
            } else {
                $counter++;
            }

            $allImg->setMain(false);
            $allImg->urlAlias = $this->getAliasString() . '-' . $counter;
            $allImg->save();
        }

        $this->owner->clearImagesCache();
    }

    public function clearImagesCache()
    {
        $cachePath = $this->getModule()->getCachePath();
        $subdir = $this->getModule()->getModelSubDir($this->owner);
        $dirToRemove = $cachePath . '/' . $subdir;

        if (preg_match('/' . preg_quote($cachePath, '/') . '/', $dirToRemove)) {
            BaseFileHelper::removeDirectory($dirToRemove);

            return true;
        } else {
            return false;
        }
    }

    public function getImages($galleryId = false)
    {
        $this->galleryId = $galleryId;
        $finder = $this->getImagesFinder();

        $imageQuery = Image::find()->where($finder);
        $imageQuery->orderBy(['isMain' => SORT_DESC, 'sort' => SORT_DESC, 'id' => SORT_ASC]);
        $imageRecords = $imageQuery->all();
        if (!$imageRecords) {
            return [$this->getModule()->getPlaceHolder()];
        }

        return $imageRecords;
    }

    public function getImage($galleryId = false)
    {
        $this->galleryId = $galleryId;
        $finder = $this->getImagesFinder();
        $imageQuery = Image::find()->where($finder);
        $imageQuery->orderBy(['isMain' => SORT_DESC, 'sort' => SORT_DESC, 'id' => SORT_ASC]);
        $img = $imageQuery->one();

        if (!$img) {
            return $this->getModule()->getPlaceHolder();
        }

        return $img;
    }

    public function getImageByName($name)
    {
        if ($this->getModule()->className === null) {
            $imageQuery = Image::find();
        } else {
            $class = $this->getModule()->className;
            $imageQuery = $class::find();
        }

        $finder = $this->getImagesFinder(['name' => $name]);
        $imageQuery->where($finder);
        $imageQuery->orderBy(['isMain' => SORT_DESC, 'id' => SORT_ASC]);
        $img = $imageQuery->one();

        if (!$img) {
            return $this->getModule()->getPlaceHolder();
        }

        return $img;
    }

    public function removeImages()
    {
        $images = $this->owner->getImages();

        if (count($images) < 1) {
            return true;
        } else {
            foreach ($images as $image) {
                $this->owner->removeImage($image);
            }
        }
    }

    public function removeImage(Image $img)
    {
        $img->clearCache();

        if ( !$img->callRelatedModelEvent(Image::GALLERY_EVENT_BEFORE_DELETE) ) return false;
	
        $storePath = $this->getModule()->getStorePath();
        $fileToRemove = $storePath . DIRECTORY_SEPARATOR . $img->filePath;

        if (preg_match('@\.@', $fileToRemove) and is_file($fileToRemove)) {
            unlink($fileToRemove);
        }

        $img->delete();
    }

    private function getImagesFinder($additionWhere = false)
    {
        $base = [
            'itemId' => $this->owner->id,
            'modelName' => $this->getModule()->getShortClass($this->owner),
        ];

        if ($this->galleryId !== false) {
            $base = \yii\helpers\BaseArrayHelper::merge($base, ['gallery_id' => $this->galleryId]);
        }

        if ($additionWhere) {
            $base = \yii\helpers\BaseArrayHelper::merge($base, $additionWhere);
        }

        return $base;
    }

    private function getAliasString()
    {
        if ($this->createAliasMethod) {
            $string = $this->owner->{$this->createAliasMethod}();
            if (!is_string($string)) {
                throw new \Exception("Image's url must be string!");
            } else {
                return $string;
            }

        } else {
            return substr(md5(microtime()), 0, 10);
        }
    }

    private function getAlias()
    {
        $aliasWords = $this->getAliasString();
        $imagesCount = count($this->owner->getImages());

        return $aliasWords . '-' . intval($imagesCount + 1);
    }

    public function getGalleryMode()
    {
        return $this->mode;
    }

    public function getInputName()
    {
        return $this->inputName;
    }

    public function setImages($event)
    {

        $userImages = UploadedFile::getInstancesByName($this->getInputName());

        if ($userImages && $this->doResetImages) {
            foreach ($userImages as $file) {
                if (in_array(strtolower($file->extension), $this->allowExtensions)) {
                    if ($file->error) {
                        throw new \ErrorException('Unable to upload file, code is: '.$file->error,500);
                    }
                    if (!file_exists($this->uploadsPath)) {
                        mkdir($this->uploadsPath, 0777, true);
                    }

                    $file->saveAs("{$this->uploadsPath}/{$file->baseName}.{$file->extension}");

                    if ($this->owner->getGalleryMode() == 'single') {
                        foreach ($this->owner->getImages() as $image) {
                            $image->delete();
                        }
                    }

                    $this->attachImage("{$this->uploadsPath}/{$file->baseName}.{$file->extension}");
                } else {
                    throw new \ErrorException('File not allowed', 500);
                }
            }

            $this->doResetImages = false;
        }

        $this->setOtherFiles();


        return $this;
    }

    public function setOtherFiles()
    {
        $files = $_FILES;

        if ($files && $this->doResetImages) {
            if($ownerIid = (int)Yii::$app->request->post('owner_id')) {
                if($ownerIid !== $this->owner->id) {
                    return false;
                }
            }
            foreach ($files as $name => $file) {
                if($file['error'] === self::STATUS_SUCCESS) {
                    $file = UploadedFile::getInstanceByName($name);

                    if (in_array(strtolower($file->extension), $this->allowExtensions)) {

                        if (!file_exists($this->uploadsPath)) {
                            mkdir($this->uploadsPath, 0777, true);
                        }

                        $this->galleryId = str_replace('gallery-file-input-', "", $name);

                        if(is_numeric($this->galleryId)) {
                            $this->galleryId = null;
                        }

                        $file->saveAs("{$this->uploadsPath}/{$file->baseName}.{$file->extension}");
                        $this->attachImage("{$this->uploadsPath}/{$file->baseName}.{$file->extension}");
                    }
                }
            }
            $this->doResetImages = false;
        }
    }

    public function hasImage($gallaryId = false)
    {
        return ($this->getImage($gallaryId) instanceof PlaceHolder) ? false : true;
    }
}
