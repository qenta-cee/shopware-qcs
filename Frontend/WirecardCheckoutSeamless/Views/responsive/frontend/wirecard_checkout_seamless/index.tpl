{extends file=$headerTemplate}

{block name='frontend_index_content_left'}{/block}

{block name='frontend_index_breadcrumb'}
    <hr class="clear"/>
{/block}

{block name="frontend_index_header_javascript_jquery_lib" append}
    <script type="text/javascript">
        var wirecardSaveOrderUrl = {$saveOrderUrl|json_encode};
        var wirecardCheckoutConfirmUrl = {$checkoutConfirmUrl|json_encode};
    </script>
    <script type="text/javascript">
        (function ($) {
            $(document).ready(function () {
                wirecardPayment.saveOrder();
                $.loadingIndicator.open();
            });
        })(jQuery);
    </script>
{/block}

{* Main content *}
{block name="frontend_index_content"}
    <div class="content block">
        <div id="payment" class="grid_20" style="margin:10px 0 10px 20px;width:959px;">
            <h2 class="headingbox_dark largesize">{s name="WirecardCheckoutSeamlessPaymentHeader"}Bitte f&uuml;hren Sie nun die Zahlung durch:{/s}</h2>

            {*<div id="payment_loader" class="ajaxSlider" style="height:500px;border:0 none;display:none">*}
                {*<div class="loader"*}
                     {*style="width:80px;margin-left:-50px;">{s name="WirecardCheckoutSeamlessPaymentInfoWait"}Bitte warten...{/s}</div>*}
                <div id="iframeContainer"></div>
            {*</div>*}
        </div>
    </div>
{/block}

