{namespace name='frontend/plugins/payment/logo'}
{if $sUserData.additional.payment.name == 'wirecard_ccard' || $sUserData.additional.payment.name == 'wirecard_ccard-moto' }
    <img src="{link file='frontend/_public/images/ccard.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_maestro' }
    <img src="{link file='frontend/_public/images/maestro.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_eps' }
    <img src="{link file='frontend/_public/images/eps.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_ideal' }
    <img src="{link file='frontend/_public/images/ideal.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_giropay' }
    <img src="{link file='frontend/_public/images/giropay.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_sofortueberweisung' }
    <img src="{link file='frontend/_public/images/sofortueberweisung.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_bancontact_mistercash' }
    <img src="{link file='frontend/_public/images/bancontact_mistercash.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_przelewy24' }
    <img src="{link file='frontend/_public/images/przelewy24.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_moneta' }
    <img src="{link file='frontend/_public/images/moneta.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_poli' }
    <img src="{link file='frontend/_public/images/poli.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_pbx' }
    <img src="{link file='frontend/_public/images/pbx.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_psc' }
    <img src="{link file='frontend/_public/images/psc.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_paypal' }
    <img src="{link file='frontend/_public/images/paypal.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_sepa-dd' }
    <img src="{link file='frontend/_public/images/sepa-dd.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_invoice' }
    <img src="{link file='frontend/_public/images/invoice.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_installment' }
    <img src="{link file='frontend/_public/images/installment.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_skrillwallet' }
    <img src="{link file='frontend/_public/images/skrillwallet.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_ekonto' }
    <img src="{link file='frontend/_public/images/ekonto.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_trustly' }
    <img src="{link file='frontend/_public/images/trustly.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_ccard-moto' }
    <img src="{link file='frontend/_public/images/ccard-moto.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_tatrapay' }
    <img src="{link file='frontend/_public/images/tatrapay.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_epay' }
    <img src="{link file='frontend/_public/images/epay.png'}"/>
{/if}
{if $sUserData.additional.payment.name == 'wirecard_voucher' }
    <img src="{link file='frontend/_public/images/voucher.png'}"/>
{/if}
