function initModal(buttonSelector, dataUrl, options) {
    
    $(buttonSelector).on('click', function() {
        var id = $(this).data(options.id);
        clearForm(options.modalID);
        if (id) {
            $(options.modalID + ' #title').text(options.titleEdit);
            $(options.modalID + ' #submitBtn').text(options.submitTextEdit);
            getData(dataUrl, id, options.modalID, options.dataTransform);
        } else {
            $(options.modalID + ' #title').text(options.titleCreate);
            $(options.modalID + ' #submitBtn').text(options.submitTextCreate);
            $(options.modalID).modal('show');
        }
    });
}

function getData(url, id, modalID, dataTransform) {
    $.ajax({
        type: 'GET',
        dataType: 'JSON',
        url: url + id + '/edit',
        success: function(response) {
            var data = dataTransform ? dataTransform(response) : response ;
            fillForm(data, modalID);
            $(modalID).modal('show');
        },
        error: function(error) {
            console.error('Error al obtener los datos:', error);
        }
    });
}

function fillForm(data, modalID) {

    for (const key in data) {
        if (data.hasOwnProperty(key) && key != 'password') {
            $(modalID + ' #' + key).val(data[key]);
        }
    }
    $(modalID + ' input#email').prop('readonly', true);

}

function clearForm(modalID) {
    $(modalID + ' input').not('[name="_token"],[name^="permissions"]').val('');
    $(modalID + 'img').attr('src','');
    $(modalID + ' .invalid-feedback').empty().hide();
    $(modalID + ' .form-control').removeClass('is-invalid');
    $(modalID + ' input#email').prop('readonly', false);
    $(modalID + ' select').prop('selectedIndex', -1);
}


function initFormSubmission(formSelector, modalSelector) {
    $(formSelector).submit(function(e) {
        e.preventDefault();
        // var formData = $(this).serialize();
        var formData = new FormData($(formSelector)[0]);

        EraseEventClick(formSelector);

        // let $upfile = $('input[name="image"]');
        // formData.append("upfile", $upfile.prop('files')[0]);

        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $(modalSelector).modal('hide');
                if (response.redirect) {
                    window.location.href = response.redirect;
                }
            },
            error: function(response) {
                EraseEventClick(formSelector, response);
                if (response.status === 422) {
                    var errors = response.responseJSON.errors;
                    $('.invalid-feedback').empty().hide();
                    $('.form-control').removeClass('is-invalid');
                    $.each(errors, function(key, value) {
                        var input = $(formSelector + ' input[name="' + key + '"]');
                        var errorDiv = $(formSelector + ' #' + key + 'Error');
                        input.addClass('is-invalid');
                        errorDiv.text(value[0]).show();
                    });
                    $(modalSelector).modal('show');
                }
            }
        });
    });
}

function EraseEventClick(formSelector, response = null) {

    $(formSelector + ' #submitBtn').off().on('click', function(event) {
        if (response) {
            $(this).prop('disabled',false);
        }else{
            $(this).prop('disabled',true);
        }

    });
}

