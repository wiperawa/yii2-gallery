if (!!!wiperawa) {
    var wiperawa = {};
}

wiperawa.gallery = {
    init: function () {
        $('.wiperawa-gallery-item a.delete').on('click', this.deleteProductImage);
        $('.wiperawa-gallery-item a.write').on('click', this.callModal);
        $('.wiperawa-gallery img').on('click', this.setMainProductImage);
        $('.noctua-gallery-form').on('submit', this.writeProductImage);
    },
    setMainProductImage: function () {
        wiperawa.gallery._sendData($(this).data('action'), $(this).parents('li').data());
        $('.wiperawa-gallery > li > .wiperawa-main-span').hide();
        $(this).parents('li').addClass('main');
        var our_li = $(this).parents('li');
        $(our_li).children('.wiperawa-main-span').show();
        $('.wiperawa-gallery > li>').removeClass('main');
        return false;
    },

    writeProductImage: function (event) {
        event.preventDefault();
        var modalContainer = $('#noctua-gallery-modal');
        var form = $(this).find('form');
        var data = form.serialize();
        var url = form.attr('action');
        $.ajax({
            url: url,
            type: "POST",
            data: data,
            success: function (result) {
                var json = $.parseJSON(result);
                if (json.result == 'success') {
                    modalContainer.modal('hide');
                }
                else {
                    alert(json.error);
                }
            }
        });
    },

    callModal: function (event) {
        event.preventDefault();
        var modalContainer = $('#noctua-gallery-modal');
        var url = $(this).data('action');
        modalContainer.modal({show: true});
        data = $(this).parents('.wiperawa-gallery-item').data();
        $.ajax({
            url: url,
            type: "POST",
            data: data,
            success: function (data) {
                $('.noctua-gallery-form').html(data);
            }
        });
    },
    deleteProductImage: function () {
        if (confirm('Are you sure You want to Delete Image?')) {
            wiperawa.gallery._sendData($(this).data('action'), $(this).parents('.wiperawa-gallery-item').data());
            $(this).parents('.wiperawa-gallery-item').hide('slow');
        }
        return false;
    },
    _sendData: function (action, data) {
        return $.post(
            action,
            {image: data.image, id: data.id, model: data.model},
            function (answer) {
                var json = $.parseJSON(answer);
                if (json.result == 'success') {

                }
                else {
                    alert(json.error);
                }
            }
        );
    }
};

wiperawa.gallery.init();


wiperawa.frontendGallery = {
    maxFileSize: 5 * 1024 * 1024,
    blockPreview: '.block-preview',
    galleryPreview: '.gallery-preview',
    addFile: '[data-role=file-empty]',
    removeFile: '[data-role=file-remove]',
    fileInput: '[data-role=gallery-file-input]',
    spanAdd: '[data-role=file-add]',
    statusDownloaded: 'file-downloaded',

    showModalCropIcon: '[data-role=show-modal-crop-icon]',
    sendCropImage: '[data-role=send-crop-image]',
    rotateImageBtn: '[data-role=rotate-image]',
    modalCrop: '[data-role=modal-crop]',


    init: function () {
        FrontendGallery = wiperawa.frontendGallery;

        $(document).on('click', FrontendGallery.addFile, this.openProvider);
        $(document).on('change', FrontendGallery.fileInput, this.parseFiles);
        $(document).on('click', FrontendGallery.removeFile, this.deleteFile);
        $('.gallary-upload-images').parents('form').on('beforeSubmit', this.validate);
        $(document).on('click', FrontendGallery.showModalCropIcon, this.showModalCrop);
        $(document).on('click', FrontendGallery.sendCropImage, this.cropImage);
        $(document).on('click', FrontendGallery.rotateImageBtn, this.rotateImage);
    },
    rotateImage: function () {
        var self = this,
            cropTools = $(self).parents('.crop-tools'),
            url = $(cropTools).data('url'),
            id = $(cropTools).data('id'),
            degrees = $(self).data('degrees');

        $.post({
            url: url,
            dataType: 'json',
            data: {id: id, degrees: degrees},
            beforeSend: function () {
                $('.crop-modal-body').css('opacity', 0.5);
                $('.preloader-crop').addClass('loading-crop');
            },
            success: function (response) {
                if (!response.error) {
                    $('#cropbox').attr('src', response.response+'&t=t'+ Math.random(5));
                }
                $('.crop-modal-body').css('opacity', 1);
                $('.preloader-crop').removeClass('loading-crop');
            }
        });


    },
    cropImage: function () {
        var self = this,
            block = $(self).parents('.modal-body'),
            cords = $(block).find('.cord-crop'),
            url = $(self).data('url'),
            id = $(block).find('#id-image').val(),
            dataCords = {id: id};

        $(cords).each(function (item, value) {
            switch ($(value).prop('id')) {
                case 'marginLeft' :
                    if (!$(value).prop('value')) return false;
                    dataCords.marginLeft = $(value).prop('value');
                    break;
                case 'marginTop' :
                    if (!$(value).prop('value')) return false;
                    dataCords.marginTop = $(value).prop('value');
                    break;
                case 'widthPlane' :
                    if (!$(value).prop('value')) return false;
                    dataCords.widthPlane = $(value).prop('value');
                    break;
                case 'heightPlane' :
                    if (!$(value).prop('value')) return false;
                    dataCords.heightPlane = $(value).prop('value');
                    break;
                case 'heightImage' :
                    if (!$(value).prop('value')) return false;
                    dataCords.heightImage = $(value).prop('value');
                    break;
                case 'widthImage' :
                    if (!$(value).prop('value')) return false;
                    dataCords.widthImage = $(value).prop('value');
                    break;

            }
        });

        if (Object.keys(dataCords).length == 7) {
            $.post({
                url: url,
                dataType: 'json',
                data: dataCords,
                beforeSend: function () {
                    $('.crop-modal-body').css('opacity', 0.5);
                    $('.preloader-crop').addClass('loading-crop');
                },
                success: function (response) {
                    if (!response.error) {
                        $('#cropbox').attr('src', response.response+'&t=t'+ Math.random(5));
                    }
                    $('img#cropbox').imgAreaSelect({
                        hide: true,
                    });
                    $('.crop-modal-body').css('opacity', 1);
                    $('.preloader-crop').removeClass('loading-crop');
                }
            });
        }
    },
    showModalCrop: function () {
        var self = this,
            url = $(self).data('action');

        $.post({
            url: url,
            success: function (responce) {
                if (responce) {
                    $(FrontendGallery.modalCrop).html(responce);
                    $('#wiperawa-gallery-modal').modal('show');
                    FrontendGallery.setCordsImageInInputs();
                }
            }
        });
    },
    setCordsImageInInputs: function () {
        $('img#cropbox').imgAreaSelect({
            handles: true,
            onSelectEnd: updateCords,
            fadeSpeed: 500,
            parent: '.block-crop-lib',
            // handles: true,
            // disable: true,
            //autoHide: true
        });

        function updateCords(img, c) {
            var image = $("#cropbox");

            $('#marginLeft').val(c.x1);
            $('#marginTop').val(c.y1);
            $('#widthPlane').val(c.width);
            $('#heightPlane').val(c.height);
            $('#heightImage').val(image.height());
            $('#widthImage').val(image.width());
        };
    },
    deleteFile: function () {
        var self = this,
            block = $(self).parents('.gallery-preview'),
            input = $(block).find(FrontendGallery.fileInput),
            controlPanel = $(block).find('.gallery-control-panel'),
            imageId = $(self).data('image-id'),
            url = $(self).data('action'),
            serialNumber = $(block).data('serial-number'),
            confirmation = confirm("Удалить данный элемент?"),
            dataSort = String($(block).data('sort'));

        if (confirmation === true) {
            if ($(block).data('role') === FrontendGallery.statusDownloaded) {
                $.post({
                    url: url,
                    data: {id: imageId},
                    success: function (response) {
                        if (response) {
                            $(block).append('<input type="file" name="gallery-file-input-' + serialNumber + '" data-role="gallery-file-input">');
                            $(block).append('<span class="glyphicon glyphicon-plus gallery-span-add" data-role="file-add"></span>');
                        }
                    }
                });
            } else {
                $(block).append('<span class="glyphicon glyphicon-plus gallery-span-add" data-role="file-add"></span>');
            }

            $(controlPanel).remove();
            $(block).removeClass('downloaded').addClass('empty');
            $(block).css({'background-color': '#e6e6e6', 'background-image': 'none'});
            $(block).data('role', 'file-empty').attr('data-role', 'file-empty');
            $(block).data('sort', '91' + dataSort).attr('data-sort', '91' + dataSort);
            $(input).replaceWith(input.val('').clone(true));


            FrontendGallery.sorting();
        }
    },
    openProvider: function () {

        var self = this,
            input = $(self).find('[data-role=gallery-file-input]');

        $(input)[0].click();
    },
    parseFiles: function () {
        var files = this.files,
            self = this;

        for (var i = 0; i < files.length; i++) {
            var file = files[i];

            if (!file.type.match(/image\/(jpeg|jpg|png|gif|vnd.microsoft.icon)/)) {
                alert('Фотография должна быть в формате jpg, png или gif');
                file.value = '';
                continue;
            }

            if (file.size > FrontendGallery.maxFileSize) {
                alert('Размер фотографии не должен превышать 2 Мб');
                file.value = '';
                continue;
            }

            FrontendGallery.createPreview(file, self);
        }
        FrontendGallery.sorting();
    },
    createPreview: function (file, self) {

        var reader = new FileReader();
        var block = $(self).parents('.gallery-preview');
        var glyphicon = $(block).find(FrontendGallery.spanAdd);
        var preloader = $('.gallary-upload-images').data('preloader');
        var dataSort = String($(block).data('sort')).slice(1);

        $(block).data('sort', dataSort).attr('data-sort', dataSort);

        //file reading
        reader.onloadstart = function () {
            $(block).addClass('loading').css('background-image', 'url(' + preloader + ')');//+
        };
        //file upload
        reader.onloadend = function () {
            $(block).removeClass('loading').css('background-image', ' ');
        };

        //success read event
        reader.onload = function (event) {

            $(block).css('background-image', 'url(' + event.target.result + ')');//+
            $(block).data('role', 'file-tmp').attr('data-role', 'file-tmp');

            $(glyphicon).remove();
            $(block).append('' +
                '<div class="col-sm-4 gallery-control-panel">' +
                '   <a><i class="fa fa-times gallery-icon-remove" data-role="file-remove"></i><a/>' +
                '</div>');
            FrontendGallery.hideErrorMessage(block);

        };
        reader.readAsDataURL(file);

    },
    sorting: function () {
        //need do sorting
        return false;

        $('.gallery-preview').sort(function (a, b) {

            return a.dataset.sort > b.dataset.sort;

        }).appendTo('.block-preview')
    },
    parceImgInPreview: function (preview) {


        var bg_url = $(preview).css('background-image');

        return bg_url.replace(/url\(['"]?(.*?)['"]?\)/i, "$1");
    },
    preloaderStart: function () {

        $('.gallery-preview').addClass('loading');
        $('.gallery-preview').each(function () {
            var self = this;
            var image = document.createElement('img');


            $(image).on('load', imgLoad);

            function imgLoad() {

                $(self).removeClass('loading');
                // $('.gallery-preview').addClass('loading');


            }

            // image.onload = function () {
            //     console.log('load');
            //     $(self).removeClass('loading');
            // };


            image.src = wiperawa.frontendGallery.parceImgInPreview(this);

        });
    },
    validate: function () {
        var success = true,
            self = this;
        $(document).find('.block-preview').children('[data-required=1]').each(function () {
            var self = this,
                fileValue = $(self).find('[data-role=gallery-file-input]').val();
            if (fileValue == '' && $(self).data('required') == 1) {
                FrontendGallery.getErrorMessage(self);
                success = false;
            }
        });

        return success;
    },
    getErrorMessage: function (self) {

        $(self).css('border', '1px solid #de2323');
        $(self).find('.error').removeClass('hide');
    },
    hideErrorMessage: function (self) {

        $(self).css('border', '1px dashed #9c9595');
        $(self).find('.error').addClass('hide');
    }
};

wiperawa.frontendGallery.preloaderStart();
wiperawa.frontendGallery.init();
