var wirecardPayment = {
    pci3Iframes: {},

    init: function () {

        $('#basketButton').bind('click', function() {
            var paymentType = $('#wd_selected_paymenttype').val();
            return checkoutSubmit();
        });


        if (typeof WirecardCEE_DataStorage !== "undefined") {
            var wdcee = new WirecardCEE_DataStorage();
            if ($('#wirecardccardIframeContainer').length > 0) {
                this.pci3Iframes.ccard = wdcee.buildIframeCreditCard('wirecardccardIframeContainer', '700px', '200px');
            } else if ($('#wirecardccard-motoIframeContainer').length > 0) {
                this.pci3Iframes.ccard = wdcee.buildIframeCreditCard('wirecardccard-motoIframeContainer', '700px', '200px');
            }
        }
    },

    usePaymentGateway: function (callback)
    {
        var paymentInformation = {};
        $("#wd_payment_fields").find(":input").each(function() {
            if(this.name == "paymentType" && ($(this).val() == 'maestro' || $(this).val() == 'ccard-moto'))
            {
                //overwrite paymentType with ccard
                paymentInformation[this.name] = 'ccard';
            }
            else{
                paymentInformation[this.name] = $(this).val();
            }
        });

        paymentInformation.paymentType = paymentInformation.paymentType.toUpperCase();

        return this.qpaySeamlessRequest(paymentInformation, callback);
    },

    hasIframe: function (paymenttype) {
        return typeof this.pci3Iframes[paymenttype] !== "undefined";
    },

    getIframe: function (paymenttype) {
        return this.pci3Iframes[paymenttype];
    },

    qpaySeamlessRequest: function (paymentInformation, callback)
    {
        var DataStorage = new WirecardCEE_DataStorage();
        return DataStorage.storePaymentInformation(paymentInformation, function(responseObject) {
            if(responseObject.getStatus() == 0)
            {
                $("#wd_payment_fields").find(":input").each(function() {
                    $(this).val("");
                });

                var params = responseObject.getAnonymizedPaymentInformation();
                var paymentFieldset = document.getElementById('paymentOptions');
                for(x in params)
                {
                    var field = document.createElement('input');
                    field.type = 'hidden';
                    field.name = x;
                    field.value = params[x];
                    paymentFieldset.appendChild(field);
                }
                callback(0);
            }
            else
            {
                callback(responseObject.getErrors());
            }
        });
    },

    datastorageRead: function(callback)
    {
        $.ajax({
            type: "POST",
            url: wirecardDatastorageReadUrl,
            dataType: 'json',
            contentType: 'application/json'
        }).done(function(ret) {
            callback(ret);
        });
    }
};


$(document).ready(function() {

    wirecardPayment.init();
});

