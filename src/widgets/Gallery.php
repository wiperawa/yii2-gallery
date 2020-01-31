<?php
namespace wiperawa\gallery\widgets;

use yii;
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\file\FileInput;
use wiperawa\gallery\assets\GalleryAsset;
use wiperawa\gallery\assets\PicaAsset;

class Gallery extends \yii\base\Widget
{
    public $model = null;
    public $previewSize = '140x140';
    public $fileInputPluginLoading = true;
    public $fileInputPluginOptions = [];
    public $label = null;
    public $action_crop = 'gallery/default/crop-modal';
    public $action_delete = 'gallery/default/delete';
    public $action_edit = 'gallery/default/modal';
    public $disable_edit = false;

    public function init()
    {
        
        $view = $this->getView();
        $view->on($view::EVENT_END_BODY, function($event) {
            echo $this->render('modal');
        });
        GalleryAsset::register($view);
    }

    public function run()
    {
        $model = $this->model;
        $params = [];
        $img = '';
        $label = '';
        if ($this->label) {
            $label = '<label class="control-label">' . $this->label . '</label>';
        }
        $cart = '';
        
        if($model->getGalleryMode() == 'single') {
            if($model->hasImage()) {
                $image = $this->model->getImage();
                $img = $this->getImagePreview($image);
                $params = $this->getParams($image->id);

            }

            return $label . '<br style="clear: both;" />' . Html::tag('div', $img, $params) . '<br style="clear: both;" />' . $this->getFileInput();
        }

        if (  $this->model->hasImage() ){
            $elements = $this->model->getImages();
            $cart = Html::ul(
                $elements,
                [
                    'item' => function($item) {
                        return $this->row($item);
                    },
                    'class' => 'wiperawa-gallery'
                ]);
        }
        $modal = Html::tag('div', null, ['data-role' => 'modal-crop']);
        $blockCropLib = Html::tag('div', null, ['class' => 'block-crop-lib']);

        return Html::tag( 'div', $label . $cart . $blockCropLib . '<br style="clear: both;" />' . $this->getFileInput() . $modal);
    }

    private function row($image)
    {
        if($image instanceof \wiperawa\gallery\models\PlaceHolder) {
            return '';
        }

        $class = ' wiperawa-gallery-row';

        if($image->isMain) {
            $class .= ' main';
        }

        $liParams = $this->getParams($image->id);
        $liParams['class'] .=  $class;

        return Html::tag('li', $this->getImagePreview($image), $liParams);
    }

    private function getFileInput()
    {
        return FileInput::widget([
            'name' => $this->model->getInputName() . '[]',
            'options' => [
                'accept' => 'image/*',
                'multiple' => $this->model->getGalleryMode() == 'gallery',
            ],
            'resizeImages' => ( isset($this->fileInputPluginOptions['resizeImage'])
                and $this->fileInputPluginOptions['resizeImage'] === true ),
            
            'pluginOptions' => $this->fileInputPluginOptions,
            'pluginLoading' => $this->fileInputPluginLoading
        ]);
    }

    private function getParams($id)
    {
        $model = $this->model;

        return  [
            'class' => 'wiperawa-gallery-item',
            'data-model' => $model::className(),
            'data-id' => $model->id,
            'data-image' => $id
        ];
    }

    private function getImagePreview($image)
    {
        $size = (explode('x', $this->previewSize));

        $delete = Html::a("<span class='glyphicon glyphicon-trash' aria-hidden='true'></span>", '#', ['data-action' => Url::toRoute([$this->action_delete, 'id' => $image->id]), 'class' => 'delete']);
        $crop = Html::a($this->getParamsIconCrop($image->id), false, ['class' => 'crop']);
        $write = '';
        if (!$this->disable_edit) {
    	    $write = Html::a('<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>', '#', ['data-action' => Url::toRoute([$this->action_edit, 'id' => $image->id]), 'class' => 'write']);
        }
        $img = Html::img($image->getUrl($this->previewSize), ['data-action' => Url::toRoute(['/gallery/default/setmain', 'id' => $image->id]), 'width' => $size[0], 'height' => $size[1], 'class' => 'thumb']);
        if (!$image->isMain) {
    	    $visibility = "style='display: none;'";
        } else {
    	    $visibility = '';
        }
        $main_selected_div = "<div class='wiperawa-main-span' ".$visibility." ><div class='wiperawa-main-span-text'>MAIN</div></div>";
        
        $a = Html::a($img, $image->getUrl());
	
	$actions_div = "<div class='wiperawa-image-actions'>".((!$this->disable_edit)?"<div class='btn btn-default btn-xs'>".$write."</div>":'')."<div class='btn btn-info btn-xs'>".$crop."</div>"."<div class='btn btn-danger btn-xs'>".$delete."</div></div>";
	
        return $img.$main_selected_div.$actions_div;
    }

    private function getParamsIconCrop($id)
    {
        $params = [
            'class' => 'glyphicon glyphicon-retweet',
            'data-role' => 'show-modal-crop-icon',
            'data-action' => Url::to([$this->action_crop, 'id' => $id]),
        ];

        return Html::tag('span', null, $params);
    }
}
