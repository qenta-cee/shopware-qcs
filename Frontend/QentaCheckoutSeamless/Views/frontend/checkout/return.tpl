{namespace name='frontend/checkout/return'}
{extends file="frontend/index/index.tpl"}

{block name='frontend_index_content_left'}{/block}

{block name='frontend_index_breadcrumb'}{/block}

{block name='frontend_index_navigation'}{/block}

{block name='frontend_index_navigation_categories_top'}{/block}

{block name='frontend_index_search'}{/block}

{block name='frontend_index_content_left'}{/block}

{block name="frontend_index_footer"}{/block}

{block name="frontend_index_shopware_footer"}{/block}

{block name="frontend_index_header_css_screen"}
<style type="text/css">
    .shopware_footer {
        display: none;
    }
    body {
        text-align: center; font-size: small;
    }
</style>
{/block}

{block name="frontend_index_header_javascript"}
<script type="text/javascript" src="{link file='frontend/_resources/javascript/qentacee.js'}"></script>
<script type="text/javascript">
    (function($) {
        $(document).ready(function() {
            iframeBreakout('{$redirectUrl}');
        });
    })(jQuery);
</script>
{/block}

{block name='frontend_index_content'}
<div>
    <p>{s name="QentaCheckoutSeamlessPaymentRedirectHeader"}Weiterleitung{/s}</p>
    <p>{s name="QentaCheckoutSeamlessPaymentRedirectText"}Sie werden nun weitergeleitet.{/s}</p>
    <p>{s name="QentaCheckoutSeamlessPaymentRedirectLinkText"}Falls Sie nicht weitergeleitet werden, klicken Sie bitte{/s}
        <a href="#" onclick="iframeBreakout('{$redirectUrl}')">
            {s name="QentaCheckoutSeamlessPaymentRedirectLink"}hier.{/s}
        </a>
    </p>
</div>
{/block}