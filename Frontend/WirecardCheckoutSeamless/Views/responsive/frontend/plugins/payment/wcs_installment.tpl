{namespace name='frontend/WirecardCheckoutSeamless/payment'}

{if $sUserData.additional.payment.name == 'wirecard_installment' }
	<script type="text/javascript">
		window.onload = function() {
			$(document).ready(function() {
				checkbirthday();
			});
		};

		function checkbirthday() {
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
				$('.is--primary').attr('disabled', false);
			}
			else {
				$('#wcs-birthdate').val("");
				if($('#wcs-day').is(":visible") == true ) {
					$('#wcsPayolutionAging').show();
					$('.is--primary').attr('disabled', true);
				}
			}
		}
	</script>

	<div class="payment--form-group">
		<div class="payment--selection-label is--underline" name="birthdate">{s name="WirecardCheckoutSeamlessBirthday"}Geburtsdatum{/s}</div>
		<div class="payment--form-group">
			<div class="row">
				<input type="hidden" name="birthdate" id="wcs-birthdate" value="" />
				<div class="column--quantity">
					<select name="days" id="wcs-day" class="form-control days input-sm" onchange="checkbirthday();" required>
						<option value="">-</option>
						{foreach from=$days item=v}
							<option value="{$v}" {if ($bDay == $v)}selected="selected"{/if}>{$v}&nbsp;&nbsp;</option>
						{/foreach}
					</select>
				</div>
				<div class="column--quantity">
					<select name="months" id="wcs-month" class="form-control months input-sm" onchange="checkbirthday()" required>
						<option value="">-</option>
						{foreach from=$months item=v}
							<option value="{$v}" {if ($bMonth == $v)}selected="selected"{/if}>{$v}&nbsp;&nbsp;</option>
						{/foreach}
					</select>
				</div>
				<div class="column--quantity">
					<select name="years" id="wcs-year" class="form-control years input-sm" onchange="checkbirthday()" required>
						<option value="">-</option>
						{foreach from=$years item=v}
							<option value="{$v}" {if ($bYear == $v)}selected="selected"{/if}>{$v}&nbsp;&nbsp;</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="clear" style="content:''; clear:both; float:none;"></div>
			<span id="wcsPayolutionAging" style="color:red;font-weight:bold;display:none;">
		{s name="WirecardCheckoutSeamlessBirthdayInformation"}Sie müssen mindestens 18 Jahre alt sein, um dieses Zahlungsmittel nutzen zu können.{/s}
		</span>
		</div>
	</div>
{/if}
{if $wcsPayolutionTerms && $sUserData.additional.payment.name == 'wirecard_installment' }
	<div class="payment--form-group">
		<div class="payment--selection-label is--underline">
			{s name="WirecardCheckoutSeamlessPayolutionTermsHeader"}Payolution Konditionen{/s}
		</div>
		<div class="payment--form-group">
			<ul class="list--checkbox list--unstyled">
				<li class="block-group row--tos">
					<span class="column--checkbox">
						<input type="checkbox" required="required" aria-required="true" id="wcsInvoiceTermsChecked" name="wcsInvoiceTermsChecked">
					</span>
					<span class="column--checkbox">
						<label for="wcsInvoiceTermsChecked">
							{if $wcsPayolutionLink1}
								{s name="WirecardCheckoutSeamlessPayolutionConsent1"}Mit der Übermittlung jener Daten an payolution, die für die Abwicklung von Zahlungen mit Kauf auf Rechnung und die Identitäts- und Bonitätsprüfung erforderlich sind, bin ich einverstanden. Meine {/s}
								{$wcsPayolutionLink1}
								{s name="WirecardCheckoutSeamlessPayolutionLink"}Bewilligung{/s}
								{$wcsPayolutionLink2}
								{s name="WirecardCheckoutSeamlessPayolutionConsent2"} kann ich jederzeit mit Wirkung für die Zukunft widerrufen.{/s}
							{else}
								{s name="WirecardCheckoutSeamlessPayolutionConsent1"}Mit der Übermittlung jener Daten an payolution, die für die Abwicklung von Zahlungen mit Kauf auf Rechnung und die Identitäts- und Bonitätsprüfung erforderlich sind, bin ich einverstanden. Meine {/s}
								{s name="WirecardCheckoutSeamlessPayolutionLink"}Bewilligung{/s}
								{s name="WirecardCheckoutSeamlessPayolutionConsent2"} kann ich jederzeit mit Wirkung für die Zukunft widerrufen.{/s}
							{/if}
						</label>
					</span>
				</li>
			</ul>
		</div>
	</div>
{/if}
