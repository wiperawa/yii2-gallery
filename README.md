Yii2-gallery
==========
Это модуль был создан, чтобы дать возможность быстро загружать в админке картинки, добавлять титульник, описание, альтернативный текст, а также задать положение (чем выше значение тем выше в списке будет изображение) и главное изображение для галереи.
Так же есть возможность вызывать ваши callback функции при добавлении, удалении, установке изображения в качестве главного.
 

Установка
---------------------------------
Выполнить команду

```
php composer require wiperawa/yii2-gallery "@dev"
```

Или добавить в composer.json

```
"wiperawa/yii2-gallery": "@dev",
```

И выполнить

```
php composer update
```

Миграция

```
php yii migrate/up --migrationPath=@vendor/wiperawa/yii2-gallery/src/migrations
```

Подключение и настройка
---------------------------------
В конфигурационный файл приложения добавить модуль gallery
Если нужно выполнять callback функции при удалении, дибовлении картинки, то Укажите namespaceOfRelatedModel 
Например ,если основная модель, для которой производится установка галереи, это абстрактный Object , находящийся в \common\models, то namespaceOfRelatedModel будет '\\common\\models\\'.
Имя класса будет связано с каждым обьектом Image, записывается в поле modelName.

Теперь достаточно всего-лишь определить у основной модели след. методы:
public function galeryBeforeDelete(Image $img);
Если ваш метод вернет false - картинка НЕ УДАЛИТСЯ!

public function galeryBeforeInsert(Image $img); (Выполнится только если это новая картинка)

public function galeryBeforeSetMain(Image $img);

В целом, не знаю правильно ли это. Но для меня это решение показалось самым простым из возможных.

```php
    'modules' => [
        'gallery' => [
            'class' => 'deadly299\gallery\Module',
            'imagesStorePath' => dirname(dirname(__DIR__)).'/frontend/web/images/store', //path to origin images
            'imagesCachePath' => dirname(dirname(__DIR__)).'/frontend/web/images/cache', //path to resized copies
            'graphicsLibrary' => 'GD',
            'placeHolderPath' => '@webroot/images/placeHolder.png',
            'adminRoles' => ['administrator', 'admin', 'superadmin'],
            'namespaceOfRelatedModel' => '' //Оставьте пустым, если callback-и не нужны
        ],
        //...
    ]
```

К модели, к которой необходимо аттачить загружаемые картинки, добавляем поведение:

```php
    function behaviors()
    {
        return [
            'images' => [
                'class' => 'wiperawa\gallery\behaviors\AttachImages',
                'mode' => 'gallery',
                'quality' => 60,
                'galleryId' => 'picture'	//here can be your model name for example
            ],
        ];
    }
```

*mode - тип загрузки. gallery - массовая загрузка, single - одиночное поле, если вам необходимо сжатие то установите quality (0 - 100) где  0 - максимальное сжатие, 100 - минимальное сжатие. galleryId - идентификатор галереи, если у вас возникает конфликт при одинаковых имён класса

Использование
---------------------------------
Использовать можно также, как напрямую:

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

Виджеты
---------------------------------
Загрузка картинок осуществляется через виджет. Добавьте в _form.php внутри формы CRUDа вашей модели.
Виджету передаются следующие параметры:
model => Модель к которой будут привязаны картинки, по умолчанию null;
previewSize => размер превью загруженных изображений, по умолчанию '140x140';
label => метка для виджета по умолчанию 'Изображение';
fileInputPluginLoading => нужно ли показывать индикатор загрузки прогресса в месте ввода, по умолчанию true;
fileInputPluginOptions => массив свойств виджета [kartik/file/fileInput](http://demos.krajee.com/widget-details/fileinput), по умолчанию [];


Не забудьте
```php<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>```
для формы.

```php
<?=\wiperawa\gallery\widgets\Gallery::widget(
    [
        'model' => $model,
        'previewSize' => '50x50',
        'fileInputPluginLoading' => true,
        'fileInputPluginOptions' => []
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
        'you-photo' => ['label' => 'Ваше фото', 'required' => true],
        'our-work' => ['label' => 'Наша работа', 'required' => true],
    ],
]) ?>

```
