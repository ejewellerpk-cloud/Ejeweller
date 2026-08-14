"use strict";

let paymentMethod = $('input[name=paymentMethod]:checked', '#paymentForm').val();
let showStatus    = false;
for (let item in gateway) {
    if (item === paymentMethod) {
        if (gateway[item]) {
            showStatus = true;
            $('#' + item + '_div').show();
            $('#' + item + '_div').find('input,select,textarea').prop('disabled', false);
        }
    } else {
        $('#' + item + '_div').hide();
        if (item !== 'swich') {
            $('#' + item + '_div').find('input,select,textarea').prop('disabled', true);
        }
    }
}

$('#swich_div').find('input,select,textarea').prop('disabled', false);
if (paymentMethod === 'swich') {
    showStatus = true;
    $('#swich_div').show();
}

let clickGateway = false;
for (let item in onClickGateway) {
    if (item === paymentMethod) {
        showStatus   = true;
        clickGateway = true;
        break;
    }
}

let form = document.getElementById('paymentForm');
if (showStatus) {
    $('#loading-show').addClass('hidden');
    $('#confirmBtn').removeClass('hidden');
    $('#backBtn').removeClass('hidden');

    if (clickGateway) {
        $('#confirmBtn').addClass('hidden');
        $('#backBtn').addClass('hidden');
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        $('#swich_div').find('input,select,textarea').prop('disabled', false);
        ['swich_msisdn_posted', 'swich_email_posted', 'swich_method_posted'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.disabled = false;
        });
        let submit = false;
        for (let item in submitGateway) {
            if (item === paymentMethod) {
                submit = true;
                window[paymentMethod + '_payment']();
                break;
            }
        }

        if (!submit) {
            form.submit();
        }
    });
} else {
    form.submit();
}
