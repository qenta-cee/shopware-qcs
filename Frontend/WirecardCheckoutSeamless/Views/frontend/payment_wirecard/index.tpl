{namespace name='frontend/payment_wirecard/index'}
{extends file="frontend/index/index.tpl"}

{block name='frontend_index_content_left'}{/block}

{block name='frontend_index_breadcrumb'}<hr class="clear" />{/block}


{* Javascript *}
{block name="frontend_index_header_javascript" append}
<script type="text/javascript">
//<![CDATA[
    jQuery(document).ready(function($) {
        $('#payment_frame').css('display', 'none');
        $('#payment_loader').css('display', 'block');

        $('#payment_frame').load(function(){
            $('#payment_loader').css('display', 'none');
            $('#payment_frame').css('display', 'block');
        });
    });
//]]>
</script>
{/block}

{* Main content *}
{block name="frontend_index_content"}
<div id="payment" class="grid_20" style="margin:10px 0 10px 20px;width:959px;">

    <h2 class="headingbox_dark largesize">{s name="WirecardCheckoutSeamlessHeader"}Bitte f&uuml;hren Sie nun die Zahlung durch:{/s}</h2>
    <div style="width:100%; text-align: center;">
    <iframe id="payment_frame" frameborder="0" border="0" frameBorder="0" src="{$gatewayUrl}"></iframe>
    <div id="payment_loader" class="ajaxSlider" style="height:100px;border:0 none;display:none">
        <div class="loader" style="width:80px;margin-left:-50px;">{s name="WirecardCheckoutSeamlessInfoWait"}Bitte warten...{/s}</div>
    </div>
    </div>
</div>
<div class="doublespace">&nbsp;</div>
{/block}