<?php

namespace wiperawa\gallery\widgets;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use wiperawa\gallery\assets\GalleryAsset;

class FrontendGallery extends \yii\base\Widget
{
    public $countInput = 10;
    public $model = null;
    public $classBlockLeft = 'col-sm-6';
    public $classBlockRight = 'col-sm-6';
    public $previewWidth = '100px';
    public $previewHeight = '100px';
    public $preloader = '/images/preloader-2.gif';
    public $informationBlock = 'Максимальный размер файла 5 МБ, формат .jpg, .jpeg, .png, .gif';
    public $otherInputs = [];
    public $showFullSize = true;
    public $customAttributes = null;
    public $otherInputsSettings = [];

    const ROLE_FILE_DOWNLOADED = 'file-downloaded';
    const ROLE_FILE_EMPTY = 'file-empty';

    public function init()
    {
        foreach ($this->otherInputsSettings as $inputName => $settings) {
            array_push($this->otherInputs, $inputName);
        }
        $bundle = Yii::$app->getAssetManager()->getBundle(GalleryAsset::className())->baseUrl;
        $this->preloader = $bundle . $this->preloader;
        GalleryAsset::register($this->getView());
    }

    public function run()
    {
        $blockLeft = null;
        $row = $this->getPreviewAndInput($this->otherInputs);
        $row .= $this->getPreviewAndInput();


        $blockRight = Html::tag('div', $row, ['class' => $this->classBlockRight . ' block-preview']);
        $blockCropLib = Html::tag('div', null, ['class' => 'block-crop-lib']);

        if ($this->informationBlock) {
            $blockLeft = Html::tag('div', $this->getInformation(), ['class' => $this->classBlockLeft]);
        }

        return Html::tag('div', $blockLeft . $blockRight . $blockCropLib, [
            'class' => 'gallary-upload-images row',
            'data-preloader' => $this->preloader
        ]);

    }

    private function getPreviewAndInput($galleryId = null)
    {
        $row = null;
        $countDownloadedFile = 0;
        $otherInputsPreview = [];
        $otherInputs = $this->otherInputs;
        $modelsPreview = [];
        $images = null;

        if ($this->model->hasImage($galleryId)) {

            $images = $this->model->getImages($galleryId);

            foreach ($images as $serialNumber => $image) {

                if (!is_null($galleryId)) {

                    foreach ($otherInputs as $key => $input) {

                        if ($input === $image->gallery_id) {

                            ArrayHelper::remove($otherInputs, $key);
                            array_push($otherInputsPreview, $input);
                            $modelsPreview[$input] = $image;
                            break;
                        }
                    }
                } else {
                    $modelsPreview[$countDownloadedFile] = $image;
                    $countDownloadedFile++;
                }
            }
        }

        if (is_null($galleryId)) {

            for ($i = 0; $i <= $countDownloadedFile - 1; $i++) {

                $settings = $this->setSettings($i, $i);
                $row .= $this->row($modelsPreview[$i], self::ROLE_FILE_DOWNLOADED, $this->getSettingsArray($settings, $i));
            }

            for ($i = $countDownloadedFile + 1; $i <= $this->countInput; $i++) {
                $settings = $this->setSettings($i, $i);
                $row .= $this->getNewInput(self::ROLE_FILE_EMPTY, $this->getSettingsArray($settings, $i));
            }

        } else {

            foreach ($otherInputsPreview as $inputPrefix) {
                $settings = $this->otherInputsSettings[$inputPrefix];
                $row .= $this->row($modelsPreview[$inputPrefix], self::ROLE_FILE_DOWNLOADED, $this->getSettingsArray($settings, $inputPrefix));
            }

            foreach ($otherInputs as $inputPrefix) {
                $settings = $this->otherInputsSettings[$inputPrefix];
                $row .= $this->getNewInput(self::ROLE_FILE_EMPTY, $this->getSettingsArray($settings, $inputPrefix));
            }
        }

        return $row;
    }

    private function row($image, $role, $settings = null)
    {
        $label = null;
        $width = preg_replace('/[^0-9]/', '', $this->previewWidth);
        $height = preg_replace('/[^0-9]/', '', $this->previewHeight);
        $style = 'background-image: url(' . $image->getUrl($width . 'x' . $height) . ');' .
            'width: ' . $this->previewWidth . '; height: ' . $this->previewHeight . ';';

        $mainAttributes = [
            'style' => $style,
            'class' => 'col-sm-2 gallery-preview downloaded',
            'data-role' => $role,
            'data-serial-number' => $settings['inputPrefix'],
            'data-sort' => $settings['sort'],
        ];

        if ($this->customAttributes && is_array($this->customAttributes)) {
            $mainAttributes = array_merge($mainAttributes, $this->customAttributes);
        }

        if ($settings['label']) {
            $label = Html::tag('div', $settings['label'], ['class' => 'label-fg label-txt']);
        }

        $linkRemove = Html::a($this->getParamsIconRemove($image->id), false);
        $linkExpand = Html::a($this->getParamsIconExpand(), $image->getUrl(), ['data-fancybox' => 'group',]);
        $linkCrop = Html::a($this->getParamsIconCrop($image->id), false);
        $modal = Html::tag('div', null, ['data-role' => 'modal-crop']);

        $divControlPanel = Html::tag('div', $linkRemove . $linkExpand . $linkCrop, ['class' => 'col-sm-4 gallery-control-panel']);
        $row = Html::tag('div', $divControlPanel . $label . $modal, $mainAttributes);

        return $row;
    }

    private function getNewInput($role, $settings = null)
    {
        $label = null;
        if ($settings['label']) {
            $label = Html::tag('div', $settings['label'], ['class' => 'label-fg label-txt']);
        }
        $span = Html::tag('span', '', ['class' => 'glyphicon glyphicon-plus gallery-span-add', 'data-role' => 'file-add']);
        $fileInput = Html::fileInput('gallery-file-input-' . $settings['inputPrefix'], null, ['data-role' => 'gallery-file-input', 'data-serial-number' => $settings['inputPrefix']]);
        $error = Html::tag('div', Html::tag('p', 'Загрузите изображение &#8593;'), ['class' => 'suggest error hide']);

        $row = Html::tag('div', $span . $fileInput . $label . $error, [
            'class' => 'col-sm-2 gallery-preview empty',
            'data-role' => $role,
            'data-required' => $settings['required'],
            'data-sort' => '91' . $settings['sort'],
            'style' => 'width: ' . $this->previewWidth . '; height: ' . $this->previewHeight . ';',
        ]);


        return $row;
    }

    private function getSettingsArray($settings, $inputPrefix)
    {
        return $this->validateSettings($settings, $inputPrefix);

    }

    private function validateSettings($settings, $inputPrefix)
    {
        if (!isset($settings['inputPrefix'])) {
            $settings['inputPrefix'] = $inputPrefix;
        }

        if (!isset($settings['label'])) {
            $settings['label'] = null;
        }

        if (!isset($settings['sort'])) {
            $settings['sort'] = 1;
        }

        if (!isset($settings['required'])) {
            $settings['required'] = 0;
        }

        if (!$settings['required']) {
            $settings['required'] = 0;
        }

        if ($settings['required']) {
            $settings['required'] = 1;
        }

        return $settings;
    }

    private function setSettings($inputPrefix = 1, $sort = null, $label = null)
    {
        return [
            'inputPrefix' => $inputPrefix,
            'sort' => $sort,
            'label' => $label,
        ];
    }

    private function getInformation()
    {
        $info = Html::tag('p', $this->informationBlock);

        return $info;
    }

    private function getParamsIconRemove($id)
    {
        $params = [
            'class' => 'fa fa-times gallery-icon-remove',
            'data-image-id' => $id,
            'data-action' => Url::toRoute(['/gallery/default/delete', 'id' => $id]),
            'data-role' => 'file-remove',
        ];

        return Html::tag('i', null, $params);
    }

    private function getParamsIconCrop($id)
    {
        $params = [
            'class' => 'fa fa-crop gallery-icon-crop',
            'data-role' => 'show-modal-crop-icon',
            'data-action' => Url::to(['gallery/default/crop-modal', 'id' => $id]),
        ];

        return Html::tag('i', null, $params);
    }

    private function getParamsIconExpand()
    {
        $params = [
            'class' => 'fa fa-expand gallery-icon-expand',
        ];

        return Html::tag('i', null, $params);
    }
}