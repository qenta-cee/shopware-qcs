{namespace name='frontend/checkout/wirecard_seamless'}
{extends file=$headerTemplate}

{block name='frontend_index_content_left'}{/block}

{block name='frontend_index_breadcrumb'}<hr class="clear" />{/block}

{block name="frontend_index_header_css_screen" append}
<link type="text/css" media="all" rel="stylesheet" href="{link file='frontend/_resources/styles/wirecard.css'}" />
{/block}

{block name="frontend_index_header_javascript" append}
<script type="text/javascript">
    var wirecardSaveOrderUrl = {$saveOrderUrl|json_encode};
    var wirecardCheckoutConfirmUrl = {$checkoutConfirmUrl|json_encode};
</script>

<script type="text/javascript" src="{link file='frontend/_resources/javascript/wirecard_seamless.js'}"></script>
<script type="text/javascript" src="{link file='frontend/_resources/javascript/wirecardcee.js'}"></script>
<script type="text/javascript">
    (function($) {
        $(document).ready(function() {
            saveOrder();
            $('#payment_frame').css('display', 'none');
            $('#payment_loader').css('display', 'block');

            $('#payment_frame').load(function(){
                $('#payment_loader').css('display', 'none');
                $('#payment_frame').css('display', 'block');
            });
        });
    })(jQuery);
</script>
{/block}

{* Main content *}
{block name="frontend_index_content"}
<div id="payment" class="grid_20" style="margin:10px 0 10px 20px;width:959px;">
    <h2 class="headingbox_dark largesize">{s name="WirecardCheckoutSeamlessPaymentHeader"}Bitte f&uuml;hren Sie nun die Zahlung durch:{/s}</h2>
    <div id="payment_loader" class="ajaxSlider" style="height:780px;border:0 none;display:none">
        <div class="loader" style="width:80px;margin-left:-50px;">{s name="WirecardCheckoutSeamlessPaymentInfoWait"}Bitte warten...{/s}</div>
        <div id="innerpayment_loader"></div>
    </div>
</div>
{/block}

