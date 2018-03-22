<?php
namespace dvizh\gallery\assets;

use yii\web\AssetBundle;

class GalleryAsset extends AssetBundle
{
    //public $baseUrl = '/images';

    public $depends = [
        'yii\web\JqueryAsset',
    ];
    
    public $js = [
        'js/scripts.js',
        'js/jquery.imgareaselect.js',
    ];

    public $css = [
        'css/styles.css',
        'css/imgareaselect-default.css',
    ];
    
    public function init()
    {
        $this->sourcePath = dirname(__DIR__).'/web';
        parent::init();
    }
}
