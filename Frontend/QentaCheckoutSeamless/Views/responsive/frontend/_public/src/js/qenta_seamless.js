var qentaPayment = {
    pci3Iframes: {},
    checkoutButton: null,
    checkoutForm: null,

    init: function () {
        if ($('#confirm--form').length) {
            this.checkoutForm = $('#confirm--form');
        } else {
            return;
        }

        if ($('.main--actions button').length) {
            this.checkoutButton = $('.main--actions button');
        } else {
            return;
        }

        this.checkoutForm.bind('submit.qentaChechoutValidator', { self: this }, function(event) {
            var self = event.data.self;
            if(oldShopVersion) {
                var agbCheck = $('#sAGB');
                if ($(agbCheck).length) {
                    if (!$(agbCheck).prop('checked')) {
                        $("label[for='sAGB']").addClass('has--error');
                        self.showError(agbErrorMessage, 'NEW');
                        $('html, body').animate({scrollTop: 0}, 'slow');
                        event.preventDefault();
                    } else {
                        $("label[for='sAGB']").removeClass('has--error');
                        self.showError('', 'CLEAR');
                    }
                }
            }

            if (!self.checkoutSubmit()) {
                event.preventDefault();
            }
            else {
                self.showError('', 'CLEAR');
            }
        });

        if (typeof WirecardCEE_DataStorage !== "undefined") {
            var wdcee = new WirecardCEE_DataStorage();
            if ($('#qentaccardIframeContainer').length > 0) {
                this.pci3Iframes.ccard = wdcee.buildIframeCreditCard('qentaccardIframeContainer', '700px', '200px');
            } else if ($('#qentaccard-motoIframeContainer').length > 0) {
                this.pci3Iframes.ccard = wdcee.buildIframeCreditCard('qentaccard-motoIframeContainer', '700px', '200px');
            } else if ($('#qentamaestroIframeContainer').length > 0) {
                this.pci3Iframes.maestro = wdcee.buildIframeMaestro('qentamaestroIframeContainer', '700px', '200px');
            }
        }
    },

    checkoutSubmit: function()
    {
        $('#errors').hide();
        var self = this;

        var paymentType = $('#wd_payment_fields').find('[name="paymentType"]').val();

        if(paymentType == 'ccard' || paymentType == 'ccard-moto' || paymentType == 'maestro' || paymentType == 'elv' || paymentType == 'sepa-dd' || paymentType == 'giropay' || paymentType == 'pbx' || paymentType == 'voucher')
        {
            var ret = qentaPayment.usePaymentGateway(function(state) {

                if(state != 0)
                {
                    for(var x in state)
                    {
                        if(state[x].consumerMessage)
                        {
                            self.showError(state[x].consumerMessage, 'APPEND');
                        }
                        else
                        {
                            self.showError(state[x].message, 'APPEND');
                        }
                    }
                    $('html, body').animate({scrollTop:0}, 'slow');
                } else {
                    $(self.checkoutForm).unbind("submit.qentaChechoutValidator");
                    $(self.checkoutForm).submit();
                    return;
                }

                // restore checkout button
                $(self.checkoutButton).removeAttr('disabled');
                $(self.checkoutButton).find('div').remove('.js--loading');
                $(self.checkoutButton).append('<i class="icon--arrow-right"></i>');
            });

            // no postMessage support, issue a read request and check if valid paymentdata is available
            if (ret === null)
            {
                qentaPayment.datastorageRead(function (data) {
                    if (data.status == 1) {
                        $(self.checkoutForm).unbind("submit.qentaChechoutValidator");
                        $(self.checkoutForm).submit();
                    } else {
                        self.showError('', 'CLEAR');
                        self.showError(noPaymentdataMessage, 'APPEND');
                        $('html, body').animate({scrollTop:0}, 'slow');
                    }
                });
            }

            return false;
        }
        else if (paymentType == 'eps' || paymentType == 'ideal')
        {
            $(self.checkoutForm).append('<input type="hidden" name="financialInstitution" value="' + $('#financialInstitutions').val() + '" />');
        }

        return true;
    },

    usePaymentGateway: function (callback)
    {
        var paymentInformation = {};
        $("#wd_payment_fields").find(":input").each(function() {
            if(this.name == "paymentType" && $(this).val() == 'ccard-moto')
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
                var paymentFieldset = $('#confirm--form');
                for(var x in params)
                {
                    var field = document.createElement('input');
                    field.type = 'hidden';
                    field.name = x;
                    field.value = params[x];
                    $(paymentFieldset).append(field);
                }
                return callback(0);
            }
            else
            {
                return callback(responseObject.getErrors());
            }
        });
    },

    datastorageRead: function(callback)
    {
        $.ajax({
            type: "POST",
            url: qentaDatastorageReadUrl,
            dataType: 'json',
            contentType: 'application/json'
        }).done(function(ret) {
            callback(ret);
        });
    },

    showError: function(message, type)
    {

        var container = $('#errorContainer');
        if (container.css('display') == "none") {
            container.css('display', "block");
        }

        var contentArea = $(container).find('.alert--content');

        switch (type) {
            case 'APPEND':
                $(contentArea).append('<p>' + message + '</p>');
                break;
            case 'CLEAR':
                container.css('display', "none");
                $(contentArea).empty();
                break;
            case 'NEW':
                $(contentArea).html('<p>' + message + '</p>');
                break;
            default:
                this.showError('Invalid error display-Type' + type, 'APPEND');
        }
    },

    saveOrder: function()
    {
        $.ajax({
            type: "GET",
            url: qentaSaveOrderUrl,
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            context: this
        }).always(function (response, textStatus, jqXHR) {
            if(jqXHR.status != 200)
            {
                document.location.href = qentaCheckoutConfirmUrl;
            }
            else
            {
                if(response.redirectUrl && response.useIframe == false)
                {
                    document.location.href = response.redirectUrl;
                }
                else if(response.redirectUrl && response.useIframe == true)
                {
                    var iframe = document.createElement('iframe');
                    $(iframe).on('load', function(){
                        $.loadingIndicator.close();
                    });
                    iframe.setAttribute("src", response.redirectUrl);
                    iframe.setAttribute("id", "paymentIframe");
                    iframe.setAttribute("name", "paymentIframe");
                    $('#iframeContainer').append(iframe);
                }
                else
                {
                    document.location.href = qentaCheckoutConfirmUrl;
                }
            }
        });
    },

    iframeBreakout: function(redirectUrl)
    {
        parent.location.href = redirectUrl;
    },

    checkBirthday: function()
    {
        var m = $('#wcs-month').val();
        var d = $('#wcs-day').val();

        var dateStr = $('#wcs-year').val() + '-' + m + '-' + d;
        var minAge = 18;

        var birthdate = new Date(dateStr);
        var year = birthdate.getFullYear();
        var today = new Date();
        var limit = new Date((today.getFullYear() - minAge), today.getMonth(), today.getDate());
        if (birthdate < limit) {
            $('#wcs-birthdate').val(dateStr);
            $('#wcsPayolutionAging').hide();
            if ($('#wcsInvoiceTermsChecked').length) {
                if ($('#wcsInvoiceTermsChecked').is(':checked')) {
                    $('#wcsPayolutionTermsAccept').hide();
                    $('.is--primary').attr('disabled', false);
                } else {
                    $('.is--primary').attr('disabled', true);
                    $('#wcsPayolutionTermsAccept').show();
                }
            } else {
                $('#wcsPayolutionTermsAccept').hide();
                $('.is--primary').attr('disabled', false);
            }
        } else {
            $('#wcsPayolutionTermsAccept').hide();
            $('#wcs-birthdate').val("");
            if ($('#wcs-day').is(":visible") == true) {
                $('#wcsPayolutionAging').show();
                $('.is--primary').attr('disabled', true);
            }
        }
    }

};


$(document).ready(function() {
    var paymentType = $('#wd_payment_fields').find('[name="paymentType"]').val();
    if ( paymentType == 'invoice' || paymentType == 'installment' ) {
        $('#confirm--form').append('<input type="hidden" name="birthdate" id="wcs-birthdate" value="" />');
        qentaPayment.checkBirthday();
    } else {
        qentaPayment.init();
    }
});

