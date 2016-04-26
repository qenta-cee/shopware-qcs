{extends file="frontend/checkout/confirm.tpl"}

{block name="frontend_index_header_css_screen" append}
    <link type="text/css" media="all" rel="stylesheet" href="{link file='frontend/_resources/styles/wirecard.css'}" />
{/block}

{block name="frontend_index_header_javascript" append}
    {if $wirecardAdditional eq 'seamless'}
        <script type='text/javascript' src="{$wirecardJavascript}"></script>
    {/if}
    <script type="text/javascript">
        var wirecardDatastorageReadUrl = {$wirecardDatastorageReadUrl|json_encode};
        var noPaymentdataMessage = {$noPaymentdataMessage|json_encode};
    </script>
    <script type="text/javascript" src="{link file='frontend/_resources/javascript/wirecard_seamless.js'}"></script>
    <script type="text/javascript" src="{link file='frontend/_resources/javascript/wirecardcee.js'}"></script>
{/block}


{block name="frontend_index_content_top" append}
<div class="grid_20">
    <div class="error agb_confirm" id="agb_error" style="display:none;">
        {s name="ConfirmErrorAGB" namespace="frontend/checkout/confirm"}Bitte best&auml;tigen Sie unsere AGB{/s}</li>
    </div>
    <div class="error agb_confirm" id="errors" {if !$wirecard_error}style="display:none;"{/if}>
        <ul id="errorList" class="wirecard_errormessages">
			{if 'cancel' eq $wirecard_error}
				<li>{s name='WirecardMessageActionCancel'}Der Zahlungsvorgang wurde von Ihnen abgebrochen.{/s}</li>
			{elseif 'undefined' eq $wirecard_error}
				<li>{s name='WirecardMessageActionUndefined'}Die Zeitrahmen f&uuml;r eine erfolgreiche Zahlung ist &Uuml;berschritten. Bitte wiederholen Sie den Zahlungsvorgang.{/s}</li>
			{elseif 'failure' eq $wirecard_error}
				<li>{s name='WirecardMessageActionFailure'}W&auml;hrend des Zahlungsvorgangs ist ein Fehler aufgetreten. Bitte versuchen Sie es noch einmal oder w&auml;hlen eine andere Zahlungsart aus.{/s}</li>
			{elseif 'error_payment_bankideal' eq $wirecard_error}
				<li>{s name='WirecardMessageErrorBankIdeal'}Bitte w&auml;hlen Sie Ihre Bank aus.{/s}</li>
			{elseif 'error_payment_bankeps' eq $wirecard_error}
				<li>{s name='WirecardMessageErrorBankEPS'}Bitte w&auml;hlen Sie Ihre Bank aus.{/s}</li>
			{elseif 'error_init' eq $wirecard_error}
				<li>{s name='WirecardMessageErrorInit'}W&auml;hrend der Inititialisierung des Zahlungsvorgangs ist ein Fehler aufgetreten. Bitte versuchen Sie es noch einmal oder w&auml;hlen eine andere Zahlungsart aus.{/s}</li>
			{elseif 'external_error' eq $wirecard_error}
				<li>{$wirecard_message}</li>
			{/if}
        </ul>
    </div>
</div>
{/block}

{block name='frontend_checkout_confirm_payment' append}
    {if $wirecardAdditional eq 'financialInstitutions'}
    <div id="paymentOptions">
        <div class="grid_16 first">
            <h2 class="headingbox">{$wirecardAdditionalHeadline}</h2>
            <div class="inner_container wirecard_inner_container_financialInstitutions">
                <ul id="financialInstitutions_fields">
                    <li>
                        <div class="formRow">
                            <div class="formField" id="wd_payment_fields">
                                <label for="financialInstitutions">
                                    {s name="WirecardFinancialInstitutions"}Finanzinstitut{/s}:
                                </label>
                                <br>
                                <select name="financialInstitution" id="financialInstitutions">
                                    {foreach from=$financialInstitutions item=bank key=short}
                                        <option value="{$short}" {if $short eq $financialInstitutionsSelected}selected="selected" {/if}>
                                            {$bank}
                                        </option>
                                    {/foreach}
                                </select>
                                <input type="hidden" name="paymentType" value="{$paymentTypeName}">
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    {elseif $wirecardAdditional eq 'seamless'}
        <div id="paymentOptions">
            <div class="grid_16 first">
                <h2 class="headingbox">{$wirecardAdditionalHeadline}</h2>
                <div class="inner_container wirecard_inner_container_ccard">
                <ul id="wd_payment_fields">
                    {if 'ccard' eq $paymentTypeName || 'maestro' eq $paymentTypeName || 'ccard-moto' eq $paymentTypeName}
                        {if $hasPciCert}
                            {if $displayCardholder}
                            <li>
                                <div class="formRow">
                                    <div class="formField">
                                        <label for="ccard_cardholdername">
                                            {s name="WirecardCcardCardholdername"}Karteninhaber{/s}:
                                        </label>
                                        <br>
                                        <input name="cardholdername" id="ccard_cardholdername" value="" type="text">
                                    </div>
                                </div>
                            </li>
                            {/if}
                            <li>
                                <div class="formRow">
                                    <div class="formField">
                                        <label for="ccard_pan">
                                            {s name="WirecardCcardPAN"}Kartennummer{/s}:
                                        </label>
                                        <br>
                                        <input name="pan" id="ccard_pan" value="" type="text">
                                    </div>
                                </div>
                            </li>
                            {if $displayCvc}
                            <li>
                                <div class="formRow">
                                    <div class="formField">
                                        <label for="ccard_cardVerifyCode">
                                            {s name="WirecardCcardSecurityCode"}Kartenpr&uuml;fnummer{/s}:
                                        </label>
                                        <br>
                                        <input name="cardVerifyCode" id="ccard_cardVerifyCode" value="" type="text">
                                    </div>
                                </div>
                            </li>
                            {/if}
                            <li>
                                <div class="formRow">
                                    <div class="formField">
                                        <label for="ccard_expirationMonth">
                                            {s name="WirecardCcardExpiration"}Ablaufdatum{/s}:
                                        </label>
                                        <br>
                                        <div style="margin-top: 3px">
                                            <select name="expirationMonth" id="ccard_expirationMonth">
                                                <option value="01">01</option>
                                                <option value="02">02</option>
                                                <option value="03">03</option>
                                                <option value="04">04</option>
                                                <option value="05">05</option>
                                                <option value="06">06</option>
                                                <option value="07">07</option>
                                                <option value="08">08</option>
                                                <option value="09">09</option>
                                                <option value="10">10</option>
                                                <option value="11">11</option>
                                                <option value="12">12</option>
                                            </select>&nbsp;
                                            <select name="expirationYear" id="ccard_expirationYear">
                                                {foreach from=$cartYear item=year}
                                                    <option value="{$year}">{$year}</option>
                                                {/foreach}
                                            </select>&nbsp;
                                        </div>
                                    </div>
                                </div>
                            </li>
                            {if $displayIssueNumber}
                            <li>
                                <div class="formRow">
                                    <div class="formField">
                                        <label for="ccard_issueNumber">
                                            {s name="WirecardCcardIssueNumber"}Erkennungssequenz{/s}:
                                        </label>
                                        <br>
                                        <input name="issueNumber" id="ccard_issueNumber" value="" type="text">
                                    </div>
                                </div>
                            </li>
                            {/if}
                            {if $displayIssueDate}
                            <li>
                                <div class="formRow">
                                    <div class="formField">
                                        <label for="ccard_issueMonth">
                                            {s name="WirecardCcardIssueMonth"}Datum der Erkennungssequenz{/s}:
                                        </label>
                                        <br>
                                        <div style="margin-top: 3px">
                                            <select name="issueMonth" id="ccard_issueMonth">
                                                <option value="01">01</option>
                                                <option value="02">02</option>
                                                <option value="03">03</option>
                                                <option value="04">04</option>
                                                <option value="05">05</option>
                                                <option value="06">06</option>
                                                <option value="07">07</option>
                                                <option value="08">08</option>
                                                <option value="09">09</option>
                                                <option value="10">10</option>
                                                <option value="11">11</option>
                                                <option value="12">12</option>
                                            </select>&nbsp;
                                            <select name="issueYear" id="ccard_issueYear">
                                                {foreach from=$cartYear item=year}
                                                    <option value="{$year}">{$year}</option>
                                                {/foreach}
                                                </select>&nbsp;
                                        </div>
                                    </div>
                                </div>
                            </li>
                            {/if}

                            {if !$displayIssueNumber}<input type="hidden" name="issueNumber" id="ccard_issueNumber" value="">{/if}
                            {if !$displayIssueDate}
                            <input type="hidden" name="issueMonth" id="ccard_issueMonth" value="">
                            <input type="hidden" name="issueYear" id="ccard_issueYear" value="">
                            {/if}
                        {else}
                            <div id="wirecard{$paymentTypeName}IframeContainer"></div>
                        {/if}
                    {elseif 'pbx' eq $paymentTypeName}
                        <li>
                            <div class="formRow">
                                <div class="formField">
                                    <label for="pbx_payerPayboxNumber">
                                        {s name="WirecardPayboxNumber"}paybox Nummer{/s}:
                                    </label>
                                    <br>
                                    <input type="text" value="" id="pbx_payerPayboxNumber" name="payerPayboxNumber">
                                </div>
                            </div>
                        </li>
                    {elseif 'elv' eq $paymentTypeName}
                        <li>
                            <div class="formRow">
                                <div class="formField">
                                    <label for="elv_accountOwner">
                                        {s name="WirecardELVAccount"}Kontoinhaber{/s}:
                                    </label>
                                    <br>
                                    <input type="text" value="" id="elv_accountOwner" name="accountOwner">
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="formRow">
                                <div class="formField">
                                    <label for="elv_bankName">
                                        {s name="WirecardELVBank"}Bank{/s}:
                                    </label>
                                    <br>
                                    <input type="text" value="" id="elv_bankName" name="bankName">
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="formRow">
                                <div class="formField">
                                    <label for="elv_bankCountry">
                                        {s name="WirecardELVCountry"}Land{/s}:
                                    </label>
                                    <br>
                                    <select id="elv_bankCountry" name="bankCountry">
                                        <option value="at">&Ouml;sterreich</option>
                                        <option value="de">Deutschland</option>
                                        <option value="nl">Niederlande</option>
                                    </select>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="formRow">
                                <div class="formField">
                                    <label for="elv_bankNumber">
                                        {s name="WirecardELVBLZ"}Bankleitzahl{/s}:
                                    </label>
                                    <br>
                                    <input type="text" value="" id="elv_bankNumber" name="bankNumber">
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="formRow">
                                <div class="formField">
                                    <label for="elv_bankAccount">
                                        {s name="WirecardELVAccountNumber"}Kontonummer{/s}:
                                    </label>
                                    <br>
                                    <input type="text" value="" id="elv_bankAccount" name="bankAccount">
                                </div>
                            </div>
                        </li>
                    {elseif 'sepa-dd' eq $paymentTypeName}
                        <li>
                            <div class="formRow">
                                <div class="formField">
                                    <label for="sepa-dd_accountOwner">
                                        {s name="WirecardSEPADDAccount"}Kontoinhaber{/s}:
                                    </label>
                                    <br>
                                    <input type="text" value="" id="sepa-dd_accountOwner" name="accountOwner">
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="formRow">
                                <div class="formField">
                                    <label for="sepa-dd_bankAccountIban">
                                        {s name="WirecardSEPADDIban"}IBAN{/s}:
                                    </label>
                                    <br>
                                    <input type="text" value="" id="sepa-dd_bankAccountIban" name="bankAccountIban">
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="formRow">
                                <div class="formField">
                                    <label for="sepa-dd_bankBic">
                                        {s name="WirecardSEPADDBic"}BIC{/s}:
                                    </label>
                                    <br>
                                    <input type="text" value="" id="sepa-dd_bankBic" name="bankBic">
                                </div>
                            </div>
                        </li>
                    {elseif 'giropay' eq $paymentTypeName}
                        <li>
                            <div class="formRow">
                                <div class="formField">
                                    <label for="giropay_bankNumber">
                                        {s name="WirecardELVBLZ"}Bankleitzahl{/s}:
                                    </label>
                                    <br>
                                    <input type="text" value="" id="giropay_bankNumber" name="bankNumber">
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="formRow">
                                <div class="formField">
                                    <label for="giropay_accountOwner">
                                        {s name="WirecardELVAccount"}Kontoinhaber{/s}:
                                    </label>
                                    <br>
                                    <input type="text" value="" id="giropay_accountOwner" name="accountOwner">
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="formRow">
                                <div class="formField">
                                    <label for="giropay_bankAccount">
                                        {s name="WirecardELVAccountNumber"}Kontonummer{/s}:
                                    </label>
                                    <br>
                                    <input type="text" value="" id="giropay_bankAccount" name="bankAccount">
                                </div>
                            </div>
                        </li>
                    {elseif 'voucher' eq $paymentTypeName}
                        <li>
                            <div class="formRow">
                                <div class="formField">
                                    <label for="voucher_voucherId">
                                        {s name="WirecardVoucherId"}Gutschein Id{/s}:
                                    </label>
                                    <br>
                                    <input type="text" value="" id="voucher_voucherId" name="voucherId">
                                </div>
                            </div>
                        </li>
                    {/if}
                    <input type="hidden" name="paymentType" value="{$paymentTypeName}">
                    </ul>
                </div>
                <hr class="space" />
            </div>
        </div>
    {else}
        <span id="wd_payment_fields">
        <input type="hidden" name="paymentType" value="{$paymentTypeName}">
        </span>
    {/if}

{/block}