Yii2-gallery
==========
This module was done to allow you quickly upload pictures in admin panel or frontend member area,
add image props like title, alt, as well as set position, and main image of the gallery.
Features:
1. Ajax files upload
2. Server-side resizing
3. Client-side resizing
4. Resizing images by alias, with storing resied images in cache
5. related-model events calling on image insert, image delete, etc.
6. editing image: resize, crop, rotate.

Install
---------------------------------
Either run

```
php composer require wiperawa/yii2-gallery "@dev"
```

or add this line to  composer.json of your project

```
"wiperawa/yii2-gallery": "@dev",
```

And run

```
php composer update
```

After that run migration to create neccesary table

```
php yii migrate/up --migrationPath=@vendor/wiperawa/yii2-gallery/src/migrations
```

Setup and Usage
---------------------------------
Add module 'gallery' in your config file of app. (usually main.php)


```php
    'modules' => [
        'gallery' => [
            'class' => 'wiperawa\gallery\Module',
            'imagesStorePath' => dirname(dirname(__DIR__)).'/frontend/web/images/store', //path to origin images
            'imagesCachePath' => dirname(dirname(__DIR__)).'/frontend/web/images/cache', //path to resized copies
            'graphicsLibrary' => 'GD', //Can be GD or Шьфпшсл
            'placeHolderPath' => '@webroot/images/placeHolder.png',
            'access' => ['@'], //roles list who have access to module, remove if dont need it.  
        ],
        //...
    ]
```

Attach Behavior to the Model to which you want to attach the downloaded images:

```php
    function behaviors()
    {
        return [
            'images' => [
                'class' => 'wiperawa\gallery\behaviors\AttachImages',
                'mode' => 'gallery',
                'quality' => 60,
                'maxWidth' => 1920, //Set if need to resize image by height or width . NOTE that this take action only for server-size resizing, better to use client-side. see widget declaration below.
                'maxHeight' => 1080,
                'galleryId' => 'picture',	//here can be your model name for example
                'allowExtensions' => ['jpg', 'jpeg', 'png', 'gif'],
                'galleryBeforeDelete' => 'galleryBeforeDeleteEvt', // main model method name to fire before Image delete  
                'galleryBeforeInsert' => 'galleryBeforeInsertEvt', // main model method name to fire before Image  insert  
                'galleryBeforeSetMain' => 'galleryBeforeSetMain', // main model method name to fire before Image setMain event  
                'galleryCheckRightsCallback' => 'galleryCheckRightsCallback', // Main Model method that calls from defaultController before any image manipulation. if return false no action performed
            ],
        ];
    }
```

mode - upload type: gallery - mass upload, single - single image
 quality (0 - 100) - compression quality,  0 - max compression, 100 - max quality. 
 galleryId - identificatior of your gallery. can be model name for example
 galleryBeforeDelete - name of method of related model
 galleryBeforeInsert - name of method of related model
 galleryBeforeSetMain - name of method of related model
 galleryCheckRightsCallback - if set, will fire on each image operation, like crop, change, delete, etc. if you return false here, operation will cancelled
 It useful for frontend, for example if member should have ability to manupulate only theirs images, we can check owner id with logged member id 

Behavior usage
---------------------------------

```php
$images = $model->getImages();
foreach($images as $img) {
    //retun url to full image
    echo $img->getUrl();

    //return url to proportionally resized image by width
    echo $img->getUrl('300x');

    //return url to proportionally resized image by height
    echo $img->getUrl('x300');

    //return url to resized and cropped (center) image by width and height
    echo $img->getUrl('200x300');

    //return alt text to image
    $img->alt

    //return title to image
    $img->title
    
    //return description image
    $img->description
}
```

Widgets
---------------------------------

Images are downloaded through the widget. Add to the _form.php inside the CRUD form of your model.
The following parameters are passed to the widget:
model => The model to which the pictures will be attached, by default null;
previewSize => size of the preview of the downloaded images, default is '140x140';
label => label, default 'Image';
fileInputPluginLoading => whether it is necessary to show the progress loading indicator at the input location, by default true;

fileInputPluginOptions => properties of widget [kartik/file/fileInput](http://demos.krajee.com/widget-details/fileinput),defaults to [];


Dont forget to add multipart/form-data option for form, if you use regular upload
```php
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
```

fi you want to use ajax upload, set uploadUrl in fileInputPluginOptions
```php
...
    'fileInputPluginOptions' => [
        'uploadUrl' => Url::to('/your/upload/action')
    ]
...
```
If this case you need to create action in your controller, that will attach uploaded images to your model, like follow:

```php
public function actionUploadPhoto($id) {
//$model - Our model with behavior attached
        $model = $this->findModel($id);

        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            if ($model->setImages(ActiveRecord::EVENT_AFTER_INSERT)) {
                return true;
            }
        } catch (\ErrorException $e) {
            return ['error' => $e->getMessage()];
        }

        return false;
    }
```

Additionally, if you set ResizeImage, maxImageHeight or maxImageWidth (if set one of theese, image will be resized proportionally ),
image will be resized CLIENT side (in browser). Userful thing, so dont overload the server. 
 (For more details check our [kartik/file-input](https://plugins.krajee.com/file-input/plugin-options#resizeImage)

Finally, you can set your own icons if you use different icons framework (iconDelete, iconEdit, IconCrop). default is glyphicons.
```php

<?=\wiperawa\gallery\widgets\Gallery::widget(
    [
        'model' => $model,
        'previewSize' => '140x140',
        'fileInputPluginLoading' => true,
        'fileInputPluginOptions' => [
            'uploadUrl' => Url::to('/your/upload/action'),
            'maxFileCount' => 20,
          //'maxFileSize' => 5120,
          //'resizeImage' => true, //Set to true if need client-side resizing
          //'maxImageWidth' => 1760, //Max width
          //'maxImageHeight' => 1080 //Max height
          //Can set your own icons for buttons, for example if you use bootstrap4
          //'iconDelete' => '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>'
          //'iconCrop' => ...
          //'iconEdit' => ...  
        ],
   
    ]
); ?>

<?= \wiperawa\gallery\widgets\FrontendGallery::widget([
    'model' => $model,
    'countInput' => 2,
    'classBlockRight' => 'col-sm-12',
    'previewWidth' => '200px',
    'previewHeight' => '150px',
    'informationBlock' => false,
    'otherInputsSettings' => [
        'you-photo' => ['label' => 'Your Photo', 'required' => true],
        'our-work' => ['label' => 'Your Work', 'required' => true],
    ],
]) ?>

```
