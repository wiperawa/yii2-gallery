<?php

use yii\helpers\Url;

?>

<div class="modal fade" id="wiperawa-gallery-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Кроп изображения</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body crop-modal-body">
                <img src="<?= $model->getUrl() ?>" width="100%" id="cropbox"/>

                <input type="hidden" class="cord-crop" id="heightImage" name="heightImage"/>
                <input type="hidden" class="cord-crop" id="widthImage" name="widthImage"/>
                <input type="hidden" class="cord-crop" id="marginLeft" name="marginLeft"/>
                <input type="hidden" class="cord-crop" id="marginTop" name="marginTop"/>
                <input type="hidden" class="cord-crop" id="widthPlane" name="widthPlane"/>
                <input type="hidden" class="cord-crop" id="heightPlane" name="heightPlane"/>
                <input type="hidden" value="<?= $model->id ?>" id="id-image" name="id"/>
                <div class="btn-group crop-tools" data-id="<?= $model->id ?>"
                     data-url="<?= Url::to(['default/rotate-image']) ?>">
                    <button type="button" class="btn btn-primary" data-degrees="90" data-role="rotate-image">
                        <span class="fa fa-level-up-alt fa-rotate-270"></span>
                    </button>
                    <button type="button" class="btn btn-primary" data-degrees="270" data-role="rotate-image">
                        <span class="fa fa-level-down-alt fa-rotate-270"></span>
                    </button>
                    <button type="button" class="btn btn-primary" data-role="send-crop-image"
                            data-url="<?= Url::to(['default/crop-image']) ?>">
                        <span class="fa fa-crop"></span>
                    </button>
                    <button type="button" class="btn btn-primary" data-dismiss="modal">
                        <?=Yii::t('gallery','Cancel')?>
                    </button>
                    <div class="preloadr-crop"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $('#wiperawa-gallery-modal').on('hide.bs.modal', function () {
        $('img#cropbox').imgAreaSelect({
            hide: true,
        });
        $('.block-crop-lib').html(null);
    });
</script>