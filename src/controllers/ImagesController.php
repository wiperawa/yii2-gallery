<?php
namespace wiperawa\gallery\controllers;

use yii;
use yii\web\Controller;
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

    public function actionIndex()
    {
        echo "Hello, man. It's ok, dont worry.";
    }

    public function actionTestTest()
    {
        echo "Hello, man. It's ok, dont worry.";
    }

    public function actionImageByItemAndAlias($item = '', $dirtyAlias)
    {
        $dotParts = explode('.', $dirtyAlias);

        if(!isset($dotParts[1])){
            throw new yii\web\HttpException(404, 'Image must have extension');
        }

        $dirtyAlias = $dotParts[0];

        $size = isset(explode('_', $dirtyAlias)[1]) ? explode('_', $dirtyAlias)[1] : false;
        $alias = isset(explode('_', $dirtyAlias)[0]) ? explode('_', $dirtyAlias)[0] : false;
        $image = $this->getModule()->getImage($item, $alias);

        if($image->getExtension() != $dotParts[1]){
            throw new yii\web\HttpException(404, 'Image not found (extenstion)');
        }


        if($image){
            // header('Content-Type: image/jpg');
            // header("Cache-Control: public, max-age=2592000");
            // header('ETag: ' . md5($image->getPath()));
            // header('Last-Modified:' . gmdate('D, d M Y H:i:s T', filemtime($image->getPath())));
            \Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
            \Yii::$app->response->headers->set('Content-Type', 'image/jpg');
            \Yii::$app->response->headers->set('Cache-Control', 'public, max-age=2592000');
            \Yii::$app->response->headers->set('ETag', md5($image->getPath()));
            \Yii::$app->response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s T', filemtime($image->getPath())));
            \Yii::$app->response->headers->set('Expires', gmdate('D, d M Y H:i:s T', strtotime('+1 month') ));

            return $image->getContent($size);
            // echo $image->getContent($size);
        }else{
            throw new \yii\web\HttpException(404, 'There is no images');
        }
    }
}
