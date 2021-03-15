{namespace name='frontend/checkout/qenta_finish'}
{extends file="parent:frontend/checkout/finish.tpl"}

{block name="frontend_checkout_finish_teaser" prepend}
 {if true eq $pendingPayment}
    <div class="teaser qenta_pending"><h2 class="center">{s name='QentaMessageActionPending'}Ihre Zahlung wurde vom Finanzdienstleister noch nicht bestätigt.{/s}</h2></div>
 {/if}
{/block}
