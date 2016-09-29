{namespace name='frontend/payment_wirecard/error'}
{block name="frontend_index_header_css_screen" append}
<link type="text/css" media="all" rel="stylesheet" href="{link file='frontend/_resources/styles/wirecard.css'}" />
{/block}

{block name="frontend_index_content_top" prepend}
<div class="error agb_confirm" id="errors" {if !$wirecard_error}style="display:none;"{/if}>
    <ul id="errorList" class="wirecard_errormessages">
        <li id="wirecard_error_agb" style="display:none;">{s name="ConfirmErrorAGB" namespace="frontend/checkout/confirm"}Bitte best&auml;tigen Sie unsere AGB.{/s}</li>
        {if 'cancel' eq $wirecard_error}
            <li>{s name='WirecardMessageActionCancel'}Der Zahlungsvorgang wurde von Ihnen abgebrochen.{/s}</li>
        {elseif 'undefined' eq $wirecard_error}
            <li>{s name='WirecardMessageActionUndefined'}Die Zeitrahmen f&uml;r eine erfolgreiche Zahlung ist &Uuml;berschritten. Bitte wiederholen Sie den Zahlungsvorgang.{/s}</li>
        {elseif 'failure' eq $wirecard_error}
            <li>{s name='WirecardMessageActionFailure'}W&auml;hrend des Zahlungsvorgangs ist ein Fehler aufgetreten. Bitte versuchen Sie es noch einmal oder w&auml;hlen eine andere Zahlungsart aus.{/s}</li>
        {elseif 'error_payment_bankideal' eq $wirecard_error}
            <li>{s name='WirecardMessageErrorBankIdeal'}Bitte w&auml;hlen Sie Ihre Bank aus.{/s}</li>
        {elseif 'error_payment_bankeps' eq $wirecard_error}
            <li>{s name='WirecardMessageErrorBankEPS'}Bitte w&auml;hlen Sie Ihre Bank aus.{/s}</li>
        {elseif 'external_error' eq $wirecard_error}
            <li>{$wirecard_error_message}</li>
        {/if}
    </ul>
</div>
{/block}