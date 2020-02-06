<?php
namespace wiperawa\gallery\controllers;

use Yii;
use yii\web\Response;
use yii\web\Controller;
use yii\web\HttpException;
use wiperawa\gallery\ModuleTrait;

class ImagesController extends Controller
{
    use ModuleTrait;

    public function behaviors()
    {
        return [
            [
                'class' => 'yii\filters\HttpCache',
                // 'only' => ['index'],
                'sessionCacheLimiter' => 'public',
            ],
        ];
    }

    public function actionImageByItemAndAlias($item = '', $dirtyAlias)
    {

        $image = $this->getImage($item,$dirtyAlias);

        if($image){
            $this->prepareHeaders($image);
            return $image->getContent($this->getSizeFromAlias($dirtyAlias));
        }else{
            throw new HttpException(404, 'There is no images');
        }
    }

    public function actionImageByAliasOrigin($item = '', $dirtyAlias)
    {

        $image = $this->getImage($item,$dirtyAlias);

        if($image){
            $this->prepareHeaders($image);
            return $image->getContent($this->getSizeFromAlias($dirtyAlias));
        }else{
            throw new HttpException(404, 'There is no images');
        }
    }

    protected function getImage($item, $dirtyAlias) {

        $dotParts = explode('.', $dirtyAlias);

        if(!isset($dotParts[1])){
            throw new HttpException(406, 'Image must have extension');
        }

        $dirtyAlias = $dotParts[0];
        $alias = isset(explode('_', $dirtyAlias)[0]) ? explode('_', $dirtyAlias)[0] : false;
        $image = $this->getModule()->getImage($item, $alias);

        if($image->getExtension() != $dotParts[1]){
            throw new HttpException(404, 'Image not found (extenstion)');
        }
        return $image;
    }

    protected function getSizeFromAlias($alias) {
        return isset(explode('_', $alias)[1]) ? explode('_', $alias)[1] : false;
    }

    protected function prepareHeaders($image){
        // header('Content-Type: image/jpg');
        // header("Cache-Control: public, max-age=2592000");
        // header('ETag: ' . md5($image->getPath()));
        // header('Last-Modified:' . gmdate('D, d M Y H:i:s T', filemtime($image->getPath())));
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'image/jpg');
        Yii::$app->response->headers->set('Cache-Control', 'public, max-age=2592000');
        Yii::$app->response->headers->set('ETag', md5($image->getPath()));
        Yii::$app->response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s T', filemtime($image->getPathToOrigin())));
        Yii::$app->response->headers->set('Expires', gmdate('D, d M Y H:i:s T', strtotime('+1 month') ));
    }
}
