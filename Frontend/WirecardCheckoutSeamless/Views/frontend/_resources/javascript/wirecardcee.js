function checkoutSubmit()
{
    $('#agb_error').hide();
    $('#errors').hide();
    $("label[for='sAGB']").removeClass('instyle_error');
    $('ul#errorList').empty();

    if (document.getElementById('errors').style.display=="block"){
        document.getElementById('errors').style.display="none";
    }

    if ($('#sAGB').length)
    {
        $('#sAGB').parent().css('background-color', 'transparent');
        if (!$('#sAGB').attr('checked'))
        {
            $('#agb_error').show();
            $("label[for='sAGB']").addClass('instyle_error');
            $('html, body').animate({scrollTop:0}, 'slow');
            return false;
        }
    }

    var paymentType = $('#wd_payment_fields').find('[name="paymentType"]').val();
    if(paymentType == 'ccard' || paymentType == 'ccard-moto' || paymentType == 'maestro' || paymentType == 'elv' || paymentType == 'sepa-dd' || paymentType == 'giropay' || paymentType == 'pbx' || paymentType == 'voucher')
    {
        var ret = wirecardPayment.usePaymentGateway(function(state) {
            if(state != 0)
            {
                // suppress errors in iframe mode
                if (wirecardPayment.hasIframe(paymentType)) {
                    return;
                }
                for(x in state)
                {
                    if(state[x].consumerMessage)
                    {
                        showError(state[x].consumerMessage, 'APPEND');
                    }
                    else
                    {
                        showError(state[x].message, 'APPEND');
                    }
                }
                $('html, body').animate({scrollTop:0}, 'slow');
            }
            else
            {
                $('#basketButton').parents('form:first').submit();
            }
        });

        // no postMessage support, issue a read request and check if valid paymentdata is available
        if (ret === null)
        {
            wirecardPayment.datastorageRead(function (data) {
                if (data.status == 1) {
                    $('#basketButton').parents('form:first').submit();
                } else {
                    showError('', 'CLEAR');
                    showError(noPaymentdataMessage, 'APPEND');
                    $('html, body').animate({scrollTop:0}, 'slow');
                }
            });
        }

        return false;
    }
    else if (paymentType == 'eps' || paymentType == 'ideal')
    {
        var paymentForm = $('#basketButton').parents('form:first');
        paymentForm.append('<input type="hidden" name="financialInstitution" value="' + $('#financialInstitutions').val() + '" />');
        return true;
    }
    else
    {
        return true;
    }

    return false;
}

function showError(message, type)
{
    if (document.getElementById('errors').style.display=="none"){
        document.getElementById('errors').style.display="block";
    }
    if(document.getElementById('errorList'))
    {
        var errorList = document.getElementById('errorList');
    }
    else
    {
        var errorList = document.createElement('ul');
        errorList.id ='errorList';
        var errorDiv = document.getElementById('errors');
        errorDiv.appendChild(errorList);
    }
    switch(type)
    {
        case 'APPEND':
            break;
        case 'CLEAR':
            errorList.innerHtml = '';
            return;
            break;
        case 'NEW':
            errorList.innerHTML = '';
            break;
        default:
            showError('Invalid error display-Type' + type, 'APPEND');
    }
    var errorMessage = document.createElement('li');
    errorMessage.innerHTML = message;
    errorList.appendChild(errorMessage);
}

function saveOrder()
{
    var XMLHTTP = null;
    // Mozilla, Opera, Safari, Internet Explorer 7
    if (window.XMLHttpRequest)
    {
        XMLHTTP = new XMLHttpRequest();
    }
    // Internet Explorer 6 and older
    else if (window.ActiveXObject)
    {
        try
        {
            XMLHTTP = new ActiveXObject("Msxml2.XMLHTTP");
        }
        catch (ex)
        {
            try
            {
                XMLHTTP = new ActiveXObject("Microsoft.XMLHTTP");
            }
            catch (ex)
            {
                document.location.href = wirecard;
            }
        }
    }
    XMLHTTP.onreadystatechange = function() {
        if(XMLHTTP.readyState == 4)
        {
            //alert(XMLHTTP.responseText);
            if(XMLHTTP.status != 200)
            {
                document.location.href = wirecardCheckoutConfirmUrl;
            }
            else
            {
                var response = jQuery.parseJSON(XMLHTTP.responseText);
                if(response.redirectUrl && response.useIframe == false)
                {
                    document.location.href = response.redirectUrl;
                }
                else if(response.redirectUrl && response.useIframe == true)
                {
                    var iframe = document.createElement('iframe');
                    iframe.setAttribute("src", response.redirectUrl);
                    iframe.setAttribute("id", "paymentIframe");
                    iframe.setAttribute("name", "paymentIframe");
                    var overlay = document.getElementById('innerpayment_loader');
                    overlay.style.display = "block";
                    document.body.appendChild(iframe);
                    //overlay.innerHTML = iframe;
                }
                else
                {
                    document.location.href = wirecardCheckoutConfirmUrl;
                }
            }
        }
    }
    XMLHTTP.open('GET', wirecardSaveOrderUrl);
    XMLHTTP.send();
}

function iframeBreakout(redirectUrl)
{
    parent.location.href = redirectUrl;
}

