<?php
namespace wiperawa\gallery\widgets;

use yii;
use yii\helpers\Url;
use yii\base\Widget;
use yii\helpers\Html;
use kartik\file\FileInput;
use wiperawa\gallery\assets\GalleryAsset;
use wiperawa\gallery\assets\PicaAsset;

class Gallery extends Widget
{
    public $model = null;
    public $previewSize = '140x140';
    public $options = [];
    public $fileInputPluginLoading = true;
    public $fileInputPluginOptions = [];
    public $label = null;
    public $disableEdit = false;
    public $selectedImgLabel = 'Default';

    public $iconDelete = '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>';
    public $iconCrop = '<span class="glyphicon glyphicon-retweet" aria-hidden="true"></span>';
    public $iconEdit = '<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>';

    protected $action_crop = 'default/crop-modal';
    protected $action_delete = 'default/delete';
    protected $action_edit = 'default/modal';
    protected $action_setmain = 'default/setmain';



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
        $module_name = Yii::$app->getModule('gallery')->id;
        $this->action_crop =   '/'. $module_name.'/'.$this->action_crop;
        $this->action_delete = '/'. $module_name.'/'.$this->action_delete;
        $this->action_edit =   '/'. $module_name.'/'.$this->action_edit;
        $this->action_setmain =   '/'. $module_name.'/'.$this->action_setmain;

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

            return (
                $label . '<br style="clear: both;" />' .
                Html::tag('div', $img, $params) .
                '<br style="clear: both;" />' .
                $this->getFileInput()
            );
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
	$_def_options = [
                'accept' => 'image/*',
                'multiple' => $this->model->getGalleryMode() == 'gallery',
	];
	
        return FileInput::widget([
            'name' => $this->model->getInputName() . '[]',
            'options' => array_merge($this->options,$_def_options),
	    'resizeImages' => ( !empty($this->fileInputPluginOptions['resizeImage']) ),

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

        $delete = Html::a(
            $this->iconDelete,
            '#',
            ['data-action' => Url::toRoute([$this->action_delete, 'id' => $image->id]), 'class' => 'btn btn-sm btn-danger wiperawa-gallery-delete']
        );
        $crop = Html::a(
            $this->iconCrop,
            Url::to([$this->action_crop, 'id' => $image->id]),
            [
                'class' => 'btn btn-sm btn-info wiperawa-gallery-crop',
                'data-role' => 'show-modal-crop-icon'
            ]
        );
        $write = '';
        if (!$this->disableEdit) {
    	    $write = Html::a(
    	        $this->iconEdit,
                '#',
                [
                    'data-action' => Url::toRoute([$this->action_edit, 'id' => $image->id]),
                    'class' => 'btn btn-sm btn-success wiperawa-gallery-write'
                ]
            );
        }
        $img = Html::img(
            $image->getUrl($this->previewSize),
            [
                'data-action' => Url::toRoute([$this->action_setmain, 'id' => $image->id]),
                'width' => $size[0],
                'height' => $size[1],
                'class' => 'thumb'
            ]
        );
        if (!$image->isMain) {
    	    $visibility = "style='display: none;'";
        } else {
    	    $visibility = '';
        }
        $main_selected_div = "<div class='wiperawa-main-span' ".$visibility." ><div class='wiperawa-main-span-text'>".Yii::t('gallery',$this->selectedImgLabel)."</div></div>";

	    $actions_div = "<div class='wiperawa-image-actions'>".
            $write. $crop . $delete.
        "</div>";
	
        return $img.$main_selected_div.$actions_div;
    }

}
