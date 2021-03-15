{namespace name='frontend/payment_qenta/error'}
{block name="frontend_index_header_css_screen" append}
<link type="text/css" media="all" rel="stylesheet" href="{link file='frontend/_resources/styles/qenta.css'}" />
{/block}

{block name="frontend_index_content_top" prepend}
<div class="error agb_confirm" id="errors" {if !$qenta_error}style="display:none;"{/if}>
    <ul id="errorList" class="qenta_errormessages">
        <li id="qenta_error_agb" style="display:none;">{s name="ConfirmErrorAGB" namespace="frontend/checkout/confirm"}Bitte best&auml;tigen Sie unsere AGB.{/s}</li>
        {if 'cancel' eq $qenta_error}
            <li>{s name='QentaMessageActionCancel'}Der Zahlungsvorgang wurde von Ihnen abgebrochen.{/s}</li>
        {elseif 'undefined' eq $qenta_error}
            <li>{s name='QentaMessageActionUndefined'}Die Zeitrahmen f&uml;r eine erfolgreiche Zahlung ist &Uuml;berschritten. Bitte wiederholen Sie den Zahlungsvorgang.{/s}</li>
        {elseif 'failure' eq $qenta_error}
            <li>{s name='QentaMessageActionFailure'}W&auml;hrend des Zahlungsvorgangs ist ein Fehler aufgetreten. Bitte versuchen Sie es noch einmal oder w&auml;hlen eine andere Zahlungsart aus.{/s}</li>
        {elseif 'error_payment_bankideal' eq $qenta_error}
            <li>{s name='QentaMessageErrorBankIdeal'}Bitte w&auml;hlen Sie Ihre Bank aus.{/s}</li>
        {elseif 'error_payment_bankeps' eq $qenta_error}
            <li>{s name='QentaMessageErrorBankEPS'}Bitte w&auml;hlen Sie Ihre Bank aus.{/s}</li>
        {elseif 'external_error' eq $qenta_error}
            <li>{$qenta_error_message}</li>
        {/if}
    </ul>
</div>
{/block}