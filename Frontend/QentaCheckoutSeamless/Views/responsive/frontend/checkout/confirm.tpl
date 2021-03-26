{namespace name='frontend/checkout/qenta'}
{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_index_header_javascript"}
{$smarty.block.parent}
    {if $qentaAdditional eq 'seamless'}
        <script type='text/javascript' src="{$qentaJavascript}"></script>
    {/if}
    <script type="text/javascript">
        var qentaDatastorageReadUrl = {$qentaDatastorageReadUrl|json_encode};
        var noPaymentdataMessage = {$noPaymentdataMessage|json_encode};
        var agbErrorMessage = {$confirmErrorAGB|json_encode};
        var oldShopVersion = false;
        if({$oldShopVersion|json_encode}) {
            oldShopVersion = {$oldShopVersion|json_encode};
        }
    </script>
    {if isset($ratePayScript)}
        {$ratePayScript}
    {/if}
{/block}


{block name="frontend_index_content_top" }
{$smarty.block.parent}
    <div class="grid_20">

        <div id="errorContainer" style="display:none;">
            {include file="frontend/_includes/messages.tpl" type="error" content=""}
        </div>

        <div class="error" id="errors" {if !$qenta_error}style="display:none;"{/if}>
			{if 'cancel' eq $qenta_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="{s name='QentaMessageActionCancel'}Der Zahlungsvorgang wurde von Ihnen abgebrochen.{/s}"}
			{elseif 'undefined' eq $qenta_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="{s name='QentaMessageActionUndefined'}Die Zeitrahmen f&uuml;r eine erfolgreiche Zahlung ist &Uuml;berschritten. Bitte wiederholen Sie den Zahlungsvorgang.{/s}"}
			{elseif 'failure' eq $qenta_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="{s name='QentaMessageActionFailure'}W&auml;hrend des Zahlungsvorgangs ist ein Fehler aufgetreten. Bitte versuchen Sie es noch einmal oder w&auml;hlen Sie eine andere Zahlungsart aus.{/s}"}
			{elseif 'error_payment_bankideal' eq $qenta_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="{s name='QentaMessageErrorBankIdeal'}Bitte w&auml;hlen Sie Ihre Bank aus.{/s}"}
			{elseif 'error_payment_bankeps' eq $qenta_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="{s name='QentaMessageErrorBankEPS'}Bitte w&auml;hlen Sie Ihre Bank aus.{/s}"}
			{elseif 'error_init' eq $qenta_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="{s name='QentaMessageErrorInit'}W&auml;hrend der Inititialisierung des Zahlungsvorgangs ist ein Fehler aufgetreten. Bitte versuchen Sie es noch einmal oder w&auml;hlen Sie eine andere Zahlungsart aus.{/s}"}
			{elseif 'external_error' eq $qenta_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="$qenta_message"}
            {/if}
        </div>
    </div>
{/block}

{block name='frontend_checkout_confirm_product_table'}
    {if $qentaAdditional eq 'financialInstitutions'}
        <div class="panel has--border is--rounded" id="wd_payment_fields">
            <div class="panel--title is--underline">
                <img src="{link file={$paymentLogo}}"/>{$qentaAdditionalHeadline}
            </div>

            <div class="panel--body is--wide">
                <div class="qenta--field">

                    {*<label for="ccard_cardholdername">{s name='QentaFinancialInstitutions'}Finanzinstitut{/s}:</label>*}
                    <select name="financialInstitution" id="financialInstitutions">

                        {foreach from=$financialInstitutions item=bank key=short}
                            <option value="{$short}"
                                    {if $short eq $financialInstitutionsSelected}selected="selected" {/if}>
                                {$bank}
                            </option>
                        {/foreach}
                    </select>
                </div>
                <div class="qenta--clearer"></div>
            </div>
            <input type="hidden" name="paymentType" value="{$paymentTypeName}">
        </div>
    {elseif $qentaAdditional eq 'seamless'}
        <div class="panel has--border is--rounded" id="wd_payment_fields">
            <div class="panel--title is--underline">
                <img src="{link file={$paymentLogo}}"/>{$qentaAdditionalHeadline}
            </div>
            {if 'ccard' eq $paymentTypeName || 'maestro' eq $paymentTypeName || 'ccard-moto' eq $paymentTypeName}
                <div class="panel--body is--wide">
                    {if $hasPciCert}

                        {if $displayCardholder}
                            <div class="qenta--field">
                                {*<label for="ccard_cardholdername">{s name='QentaCcardCardholdername'}Karteninhaber{/s}:</label>*}
                                <input name="cardholdername" type="text" id="ccard_cardholdername"
                                       placeholder="{s name='QentaCcardCardholdername'}Karteninhaber{/s}" value=""
                                       class="required text" autocomplete="off"/>
                            </div>
                        {/if}

                        {*<label for="ccard_pan">{s name='QentaCcardPAN'}Kartennummer{/s}:</label>*}
                        <div class="qenta--field">
                            <input name="pan" type="text" id="ccard_pan" autocomplete="off"
                                   placeholder="{s name='QentaCcardPAN'}Kartennummer{/s}" value=""
                                   class="required text"/>
                        </div>
                        <div class="qenta--clearer"></div>
                        {if $displayCvc}
                            <div class="qenta--field">
                                {*<label for="ccard_cardVerifyCode">{s name='QentaCcardSecurityCode'}Kartenpr&uuml;fnummer{/s}:</label>*}
                                <input name="cardVerifyCode" type="text" id="ccard_cardVerifyCode" autocomplete="off"
                                       placeholder="{s name='QentaCcardSecurityCode'}Kartenpr&uuml;fnummer{/s}"
                                       value=""
                                       class="qenta--cvc required text"/>
                            </div>
                        {/if}
                        <div class="qenta--field">
                            <div class="qenta--expiration">
                                <label for="ccard_expirationMonth">{s name='QentaCcardExpiration'}Ablaufdatum{/s}
                                    :</label>

                                <div class="qenta--expiration--month field--select">
                                    <select id="ccard_expirationMonth" name="expirationMonth" class="required">
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
                                    </select>
                                </div>
                                <div class="qenta--expiration--year field--select">

                                    <select name="expirationYear" id="ccard_expirationYear">
                                        {foreach from=$cartYear item=year}
                                            <option value="{$year}">{$year}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="qenta--clearer"></div>
                        {if $displayIssueNumber}
                            <div class="qenta--field">
                                {*<label for="ccard_cardVerifyCode">{s name='QentaCcardSecurityCode'}Kartenpr&uuml;fnummer{/s}:</label>*}
                                <input name="issueNumber" type="text" id="ccard_issueNumber" autocomplete="off"
                                       placeholder="{s name='QentaCcardIssueNumber'}Ausgabenummer{/s}" value=""
                                       class="qenta--cvc required text"/>
                            </div>
                        {/if}
                        {if $displayIssueDate}
                            <div class="qenta--field">
                                <div class="qenta--expiration">
                                    <label for="ccard_issueMonth">{s name='QentaCcardIssueMonth'}Ausgabedatum{/s}
                                        :</label>

                                    <div class="qenta--expiration--month field--select">
                                        <select id="ccard_issueMonth" name="issueMonth" class="required">
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
                                        </select>
                                    </div>
                                    <div class="qenta--expiration--year field--select">

                                        <select name="issueYear" id="ccard_issueYear">
                                            {foreach from=$cartYear item=year}
                                                <option value="{$year}">{$year}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="qenta--clearer"></div>
                        {/if}
                        {if !$displayIssueNumber}
                            <input type="hidden" name="issueNumber" id="ccard_issueNumber" value="">
                        {/if}
                        {if !$displayIssueDate}
                            <input type="hidden" name="issueMonth" id="ccard_issueMonth" value="">
                            <input type="hidden" name="issueYear" id="ccard_issueYear" value="">
                        {/if}

                    {else}
                        <div id="qenta{$paymentTypeName}IframeContainer"></div>
                    {/if}
                </div>
            {elseif 'pbx' eq $paymentTypeName}
                <div class="panel--body is--wide">
                    <div class="qenta--field">
                        <input name="payerPayboxNumber" type="text" id="pbx_payerPayboxNumber" autocomplete="off"
                               placeholder="{s name='QentaPayboxNumber'}paybox Nummer{/s}" value=""
                               class="required text"/>
                    </div>
                    <div class="qenta--clearer"></div>
                </div>
            {elseif 'sepa-dd' eq $paymentTypeName}
                <div class="panel--body is--wide">
                    <div class="qenta--field">
                        <input name="accountOwner" type="text" id="sepa-dd_accountOwner" autocomplete="off"
                               placeholder="{s name='QentaSEPADDAccount'}Kontoinhaber{/s}" value=""
                               class="required text"/>
                    </div>

                    <div class="qenta--field">
                        <input name="bankAccountIban" type="text" id="sepa-dd_bankAccountIban" autocomplete="off"
                               placeholder="{s name='QentaSEPADDIban'}IBAN{/s}" value=""
                               class="required text"/>
                    </div>

                    <div class="qenta--field">
                        <input name="bankBic" type="text" id="sepa-dd_bankBic" autocomplete="off"
                               placeholder="{s name='QentaSEPADDBic'}BIC{/s}" value=""
                               class="required text"/>
                    </div>
                    <div class="qenta--clearer"></div>
                </div>
            {elseif 'giropay' eq $paymentTypeName}
                <div class="panel--body is--wide">

                    <div class="qenta--field">
                        <input name="bankNumber" type="text" id="giropay_bankNumber" autocomplete="off"
                               placeholder="{s name='QentaELVBLZ'}Bankleitzahl{/s}" value=""
                               class="required text"/>
                    </div>

                    <div class="qenta--field">
                        <input name="accountOwner" type="text" id="giropay_accountOwner" autocomplete="off"
                               placeholder="{s name='QentaELVAccount'}Kontoinhaber{/s}" value=""
                               class="required text"/>
                    </div>

                    <div class="qenta--field">
                        <input name="bankAccount" type="text" id="giropay_bankAccount" autocomplete="off"
                               placeholder="{s name='QentaELVAccountNumber'}Kontonummer{/s}" value=""
                               class="required text"/>
                    </div>
                    <div class="qenta--clearer"></div>
                </div>
            {elseif 'voucher' eq $paymentTypeName}
                <div class="panel--body is--wide">

                    <div class="qenta--field">
                        <input name="voucherId" type="text" id="voucher_voucherId" autocomplete="off"
                               placeholder="{s name='QentaVOUCHERId'}Gutschein Id{/s}" value=""
                               class="required text"/>
                    </div>
                    <div class="qenta--clearer"></div>
                </div>
            {elseif 'invoice' eq $paymentTypeName || 'installment' eq $paymentTypeName}
                <div class="panel--body is--wide">
                    <div class="payment--selection-label is--underline" name="birthdate">{s name="QentaCheckoutSeamlessBirthday"}Geburtsdatum{/s}</div>
                    <div class="qenta--field">
                        <select name="days" id="wcs-day" onchange="qentaPayment.checkBirthday()" required>
                            <option value="0">-</option>
                            {foreach from=$days item=v}
                                <option value="{$v}" {if ($bDay == $v)}selected="selected"{/if}>{$v}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="qenta--field">
                        <select name="months" id="wcs-month" onchange="qentaPayment.checkBirthday()" required>
                            <option value="0">-</option>
                            {foreach from=$months item=v}
                                <option value="{$v}" {if ($bMonth == $v)}selected="selected"{/if}>{$v}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="qenta--field">
                        <select name="years" id="wcs-year"  onchange="qentaPayment.checkBirthday()" required>
                            <option value="0">-</option>
                            {foreach from=$years item=v}
                                <option value="{$v}" {if ($bYear == $v)}selected="selected"{/if}>{$v}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="clear" style="content:''; clear:both; float:none;"></div>
                    <span id="wcsPayolutionAging" style="color:red;font-weight:bold;display:none;">
		                    {s name="QentaCheckoutSeamlessBirthdayInformation"}Sie müssen mindestens 18 Jahre alt sein, um dieses Zahlungsmittel nutzen zu können.{/s}
		                </span>
                    {if $payolutionTerms}
                        <div class="qenta--clearer"></div>
                        <div class="payment--selection-label is--underline">
                            {s name="QentaCheckoutSeamlessPayolutionTermsHeader"}Payolution Konditionen{/s}
                        </div>
                            <ul class="list--checkbox list--unstyled">
                                <li class="block-group row--tos">
					            <span class="column--checkbox">
						            <input type="checkbox" required="required" aria-required="true" id="wcsInvoiceTermsChecked" onchange="qentaPayment.checkBirthday()" name="wcsInvoiceTermsChecked">
					            </span>
                                <span class="column--checkbox">
						            <label for="wcsInvoiceTermsChecked">{if $wcsPayolutionLink1}
                                        {s name="QentaCheckoutSeamlessPayolutionConsent1"}Mit der Übermittlung jener Daten an payolution, die für die Abwicklung von Zahlungen mit Kauf auf Rechnung und die Identitäts- und Bonitätsprüfung erforderlich sind, bin ich einverstanden. Meine {/s}
                                        {$wcsPayolutionLink1}
                                        {s name="QentaCheckoutSeamlessPayolutionLink"}Bewilligung{/s}
                                        {$wcsPayolutionLink2}
                                        {s name="QentaCheckoutSeamlessPayolutionConsent2"} kann ich jederzeit mit Wirkung für die Zukunft widerrufen.{/s}
                                    {else}
                                        {s name="QentaCheckoutSeamlessPayolutionConsent1"}Mit der Übermittlung jener Daten an payolution, die für die Abwicklung von Zahlungen mit Kauf auf Rechnung und die Identitäts- und Bonitätsprüfung erforderlich sind, bin ich einverstanden. Meine {/s}
                                        {s name="QentaCheckoutSeamlessPayolutionLink"}Bewilligung{/s}
                                        {s name="QentaCheckoutSeamlessPayolutionConsent2"} kann ich jederzeit mit Wirkung für die Zukunft widerrufen.{/s}
                                    {/if}
						            </label>
					            </span>
                            </li>
                            </ul>
                        <span id="wcsPayolutionTermsAccept" style="color:red;font-weight:bold;display:none;">
                            {s name="QentaCheckoutSeamlessPayolutionTermsAccept"}Bitte akzeptieren Sie die payolution Konditionen.{/s}
                        </span>
                        <div class="clear" style="content:''; clear:both; float:none;"></div>
                    {/if}
                </div>
            {/if}
            <input type="hidden" name="paymentType" value="{$paymentTypeName}">
        </div>
    {else}
        <span id="wd_payment_fields">
        <input type="hidden" name="paymentType" value="{$paymentTypeName}">
        </span>
    {/if}
{$smarty.block.parent}
{/block}
