{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_index_header_javascript" append}

    {if $wirecardAdditional eq 'seamless'}
        <script type='text/javascript' src="{$wirecardJavascript}"></script>
    {/if}
    <script type="text/javascript">
        var wirecardDatastorageReadUrl = {$wirecardDatastorageReadUrl|json_encode};
        var noPaymentdataMessage = {$noPaymentdataMessage|json_encode};
        var agbErrorMessage = {$confirmErrorAGB|json_encode};
    </script>
{/block}


{block name="frontend_index_content_top" append}
    <div class="grid_20">

        <div id="errorContainer" style="display:none;">
            {include file="frontend/_includes/messages.tpl" type="error" content=""}
        </div>

        <div class="error" id="errors" {if !$wirecard_error}style="display:none;"{/if}>
			{if 'cancel' eq $wirecard_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="{s name='WirecardMessageActionCancel'}Der Zahlungsvorgang wurde von Ihnen abgebrochen.{/s}"}
			{elseif 'undefined' eq $wirecard_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="{s name='WirecardMessageActionUndefined'}Die Zeitrahmen f&uuml;r eine erfolgreiche Zahlung ist &Uuml;berschritten. Bitte wiederholen Sie den Zahlungsvorgang.{/s}"}
			{elseif 'failure' eq $wirecard_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="{s name='WirecardMessageActionFailure'}W&auml;hrend des Zahlungsvorgangs ist ein Fehler aufgetreten. Bitte versuchen Sie es noch einmal oder w&auml;hlen Sie eine andere Zahlungsart aus.{/s}"}
			{elseif 'error_payment_bankideal' eq $wirecard_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="{s name='WirecardMessageErrorBankIdeal'}Bitte w&auml;hlen Sie Ihre Bank aus.{/s}"}
			{elseif 'error_payment_bankeps' eq $wirecard_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="{s name='WirecardMessageErrorBankEPS'}Bitte w&auml;hlen Sie Ihre Bank aus.{/s}"}
			{elseif 'error_init' eq $wirecard_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="{s name='WirecardMessageErrorInit'}W&auml;hrend der Inititialisierung des Zahlungsvorgangs ist ein Fehler aufgetreten. Bitte versuchen Sie es noch einmal oder w&auml;hlen Sie eine andere Zahlungsart aus.{/s}"}
			{elseif 'external_error' eq $wirecard_error}
				{include file="frontend/_includes/messages.tpl" type="error" content="$wirecard_message"}
            {/if}
        </div>
    </div>
{/block}

{block name='frontend_checkout_confirm_product_table' prepend}
    {if $wirecardAdditional eq 'financialInstitutions'}
        <div class="panel has--border is--rounded" id="wd_payment_fields">
            <div class="panel--title is--underline">
                {$wirecardAdditionalHeadline}
            </div>

            <div class="panel--body is--wide">
                <div class="wirecard--field">

                    {*<label for="ccard_cardholdername">{s name='WirecardFinancialInstitutions'}Finanzinstitut{/s}:</label>*}
                    <select name="financialInstitution" id="financialInstitutions">

                        {foreach from=$financialInstitutions item=bank key=short}
                            <option value="{$short}"
                                    {if $short eq $financialInstitutionsSelected}selected="selected" {/if}>
                                {$bank}
                            </option>
                        {/foreach}
                    </select>
                </div>
                <div class="wirecard--clearer"></div>
            </div>
            <input type="hidden" name="paymentType" value="{$paymentTypeName}">
        </div>
    {elseif $wirecardAdditional eq 'seamless'}
        <div class="panel has--border is--rounded" id="wd_payment_fields">
            <div class="panel--title is--underline">
                {$wirecardAdditionalHeadline}
            </div>
            {if 'ccard' eq $paymentTypeName || 'maestro' eq $paymentTypeName || 'ccard-moto' eq $paymentTypeName}
                <div class="panel--body is--wide">
                    {if $hasPciCert}

                        {if $displayCardholder}
                            <div class="wirecard--field">
                                {*<label for="ccard_cardholdername">{s name='WirecardCcardCardholdername'}Karteninhaber{/s}:</label>*}
                                <input name="cardholdername" type="text" id="ccard_cardholdername"
                                       placeholder="{s name='WirecardCcardCardholdername'}Karteninhaber{/s}" value=""
                                       class="required text" autocomplete="off"/>
                            </div>
                        {/if}

                        {*<label for="ccard_pan">{s name='WirecardCcardPAN'}Kartennummer{/s}:</label>*}
                        <div class="wirecard--field">
                            <input name="pan" type="text" id="ccard_pan" autocomplete="off"
                                   placeholder="{s name='WirecardCcardPAN'}Kartennummer{/s}" value=""
                                   class="required text"/>
                        </div>
                        <div class="wirecard--clearer"></div>
                        {if $displayCvc}
                            <div class="wirecard--field">
                                {*<label for="ccard_cardVerifyCode">{s name='WirecardCcardSecurityCode'}Kartenpr&uuml;fnummer{/s}:</label>*}
                                <input name="cardVerifyCode" type="text" id="ccard_cardVerifyCode" autocomplete="off"
                                       placeholder="{s name='WirecardCcardSecurityCode'}Kartenpr&uuml;fnummer{/s}"
                                       value=""
                                       class="wirecard--cvc required text"/>
                            </div>
                        {/if}
                        <div class="wirecard--field">
                            <div class="wirecard--expiration">
                                <label for="ccard_expirationMonth">{s name='WirecardCcardExpiration'}Ablaufdatum{/s}
                                    :</label>

                                <div class="wirecard--expiration--month field--select">
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
                                <div class="wirecard--expiration--year field--select">

                                    <select name="expirationYear" id="ccard_expirationYear">
                                        {foreach from=$cartYear item=year}
                                            <option value="{$year}">{$year}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="wirecard--clearer"></div>
                        {if $displayIssueNumber}
                            <div class="wirecard--field">
                                {*<label for="ccard_cardVerifyCode">{s name='WirecardCcardSecurityCode'}Kartenpr&uuml;fnummer{/s}:</label>*}
                                <input name="issueNumber" type="text" id="ccard_issueNumber" autocomplete="off"
                                       placeholder="{s name='WirecardCcardIssueNumber'}Ausgabenummer{/s}" value=""
                                       class="wirecard--cvc required text"/>
                            </div>
                        {/if}
                        {if $displayIssueDate}
                            <div class="wirecard--field">
                                <div class="wirecard--expiration">
                                    <label for="ccard_issueMonth">{s name='WirecardCcardIssueMonth'}Ausgabedatum{/s}
                                        :</label>

                                    <div class="wirecard--expiration--month field--select">
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
                                    <div class="wirecard--expiration--year field--select">

                                        <select name="issueYear" id="ccard_issueYear">
                                            {foreach from=$cartYear item=year}
                                                <option value="{$year}">{$year}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="wirecard--clearer"></div>
                        {/if}
                        {if !$displayIssueNumber}
                            <input type="hidden" name="issueNumber" id="ccard_issueNumber" value="">
                        {/if}
                        {if !$displayIssueDate}
                            <input type="hidden" name="issueMonth" id="ccard_issueMonth" value="">
                            <input type="hidden" name="issueYear" id="ccard_issueYear" value="">
                        {/if}

                    {else}
                        <div id="wirecard{$paymentTypeName}IframeContainer"></div>
                    {/if}
                </div>
            {elseif 'pbx' eq $paymentTypeName}
                <div class="panel--body is--wide">
                    <div class="wirecard--field">
                        <input name="payerPayboxNumber" type="text" id="pbx_payerPayboxNumber" autocomplete="off"
                               placeholder="{s name='WirecardPayboxNumber'}paybox Nummer{/s}" value=""
                               class="required text"/>
                    </div>
                    <div class="wirecard--clearer"></div>
                </div>
            {elseif 'elv' eq $paymentTypeName}
                <div class="panel--body is--wide">
                    <div class="wirecard--field">
                        <input name="accountOwner" type="text" id="elv_accountOwner" autocomplete="off"
                               placeholder="{s name='WirecardELVAccount'}Kontoinhaber{/s}" value=""
                               class="required text"/>
                    </div>

                    <div class="wirecard--field">
                        <input name="bankName" type="text" id="elv_bankName" autocomplete="off"
                               placeholder="{s name='WirecardELVBank'}Bank{/s}" value=""
                               class="required text"/>
                    </div>

                    <div class="wirecard--field">
                        <select id="elv_bankCountry" name="bankCountry"
                                paceholder="{s name='WirecardELVCountry'}Land{/s}">
                            <option value="at">&Ouml;sterreich</option>
                            <option value="de">Deutschland</option>
                            <option value="nl">Niederlande</option>
                        </select>
                    </div>

                    <div class="wirecard--field">
                        <input name="bankNumber" type="text" id="elv_bankNumber" autocomplete="off"
                               placeholder="{s name='WirecardELVBLZ'}Bankleitzahl{/s}" value=""
                               class="required text"/>
                    </div>

                    <div class="wirecard--field">
                        <input name="bankAccount" type="text" id="elv_bankAccount" autocomplete="off"
                               placeholder="{s name='WirecardELVAccountNumber'}Kontonummer{/s}" value=""
                               class="required text"/>
                    </div>
                    <div class="wirecard--clearer"></div>
                </div>
            {elseif 'sepa-dd' eq $paymentTypeName}
                <div class="panel--body is--wide">
                    <div class="wirecard--field">
                        <input name="accountOwner" type="text" id="sepa-dd_accountOwner" autocomplete="off"
                               placeholder="{s name='WirecardSEPADDAccount'}Kontoinhaber{/s}" value=""
                               class="required text"/>
                    </div>

                    <div class="wirecard--field">
                        <input name="bankAccountIban" type="text" id="sepa-dd_bankAccountIban" autocomplete="off"
                               placeholder="{s name='WirecardSEPADDIban'}IBAN{/s}" value=""
                               class="required text"/>
                    </div>

                    <div class="wirecard--field">
                        <input name="bankBic" type="text" id="sepa-dd_bankBic" autocomplete="off"
                               placeholder="{s name='WirecardSEPADDBic'}BIC{/s}" value=""
                               class="required text"/>
                    </div>
                    <div class="wirecard--clearer"></div>
                </div>
            {elseif 'giropay' eq $paymentTypeName}
                <div class="panel--body is--wide">

                    <div class="wirecard--field">
                        <input name="bankNumber" type="text" id="giropay_bankNumber" autocomplete="off"
                               placeholder="{s name='WirecardELVBLZ'}Bankleitzahl{/s}" value=""
                               class="required text"/>
                    </div>

                    <div class="wirecard--field">
                        <input name="accountOwner" type="text" id="giropay_accountOwner" autocomplete="off"
                               placeholder="{s name='WirecardELVAccount'}Kontoinhaber{/s}" value=""
                               class="required text"/>
                    </div>

                    <div class="wirecard--field">
                        <input name="bankAccount" type="text" id="giropay_bankAccount" autocomplete="off"
                               placeholder="{s name='WirecardELVAccountNumber'}Kontonummer{/s}" value=""
                               class="required text"/>
                    </div>
                    <div class="wirecard--clearer"></div>
                </div>
            {elseif 'voucher' eq $paymentTypeName}
                <div class="panel--body is--wide">

                    <div class="wirecard--field">
                        <input name="voucherId" type="text" id="voucher_voucherId" autocomplete="off"
                               placeholder="{s name='WirecardVOUCHERId'}Gutschein Id{/s}" value=""
                               class="required text"/>
                    </div>
                    <div class="wirecard--clearer"></div>
                </div>
            {/if}
            <input type="hidden" name="paymentType" value="{$paymentTypeName}">
        </div>
    {else}
        <span id="wd_payment_fields">
        <input type="hidden" name="paymentType" value="{$paymentTypeName}">
        </span>
    {/if}

{/block}