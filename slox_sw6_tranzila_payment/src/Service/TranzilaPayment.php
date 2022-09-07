<?php declare( strict_types = 1 );

namespace slox_sw6_tranzila_payment\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class TranzilaPayment implements AsynchronousPaymentHandlerInterface {
    /**
    * @var OrderTransactionStateHandler
    */
    private $transactionStateHandler;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct( OrderTransactionStateHandler $transactionStateHandler , SystemConfigService $systemConfigService) {
        $this->transactionStateHandler = $transactionStateHandler;
        $this->systemConfigService = $systemConfigService;
    }

    /**
    * @throws AsyncPaymentProcessException
    */

    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {


    if($this->systemConfigService->get('slox_sw6_tranzila_payment.config.terminalname'))
    {
            $transactionId = $transaction->getOrderTransaction()->getId();

            $tranzilaurl='https://direct.tranzila.com/';
            $tranzilaurl.=$this->systemConfigService->get('slox_sw6_tranzila_payment.config.terminalname');
            $tranzilaurl.='/iframenew.php?lang=';
            $tranzilaurl.='us';

            $tranzilaurl.='&currency=1&cred_type=1';
            
            $tranzilaurl.='&'.'sum'.'='.$transaction->getOrderTransaction()->getAmount()->getTotalPrice();
            $tranzilaurl.='&'.'buttonLabel'.'='.urlencode ($this->systemConfigService->get('slox_sw6_tranzila_payment.config.paybuttonLabel'));
            $tranzilaurl.='&'.'trButtonColor'.'='.trim($this->systemConfigService->get('slox_sw6_tranzila_payment.config.trButtonColor'),'#');
            $tranzilaurl.='&'.'trBgColor'.'='.trim($this->systemConfigService->get('slox_sw6_tranzila_payment.config.trBgColor'),'#');
            $tranzilaurl.='&'.'trTextColor'.'='.trim($this->systemConfigService->get('slox_sw6_tranzila_payment.config.trTextColor'),'#')    ;
        
            $tranzilaurl.='&'.'name'.'='.$salesChannelContext->getCustomer()->__toString();
            $tranzilaurl.='&'.'email'.'='.$salesChannelContext->getCustomer()->getEmail();
            $tranzilaurl.='&'.'billing_address'.'='.json_encode($salesChannelContext->getCustomer()->getActiveBillingAddress());
            $tranzilaurl.='&'.'remote_ip'.'='.$salesChannelContext->getCustomer()->getRemoteAddress();
            $tranzilaurl.='&'.'billing_city'.'='.$salesChannelContext->getCustomer()->getActiveBillingAddress()->getCity();
            $tranzilaurl.='&'.'order_token'.'='.$salesChannelContext->getToken();

            $parts = parse_url($transaction->getreturnUrl());
            $query='';
            parse_str($parts['query'], $query);

            $tranzilaurl.='&'.'paymentToken'.'='.$query['_sw_payment_token'];
            
            $this->transactionStateHandler->process($transaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());
            return new RedirectResponse($tranzilaurl , 302);
    }

    $route = $salesChannelContext->getSalesChannel()->getDomains()->first()->getUrl().'/checkout/tranzila/payfail?message='.urlencode('please set tranzila terminal name in the plugin setting');
    return new RedirectResponse($route , 302);

    }

    /**
    * @throws CustomerCanceledAsyncPaymentException
    */

    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {

        $tranzilaResponse   =  json_decode($request->get('data'),true);
        $paymentState = $request->get('status');
        $context = $salesChannelContext->getContext();
        if ( $paymentState === 'Success' ) {
            // Payment completed, set transaction status to 'paid'
            $this->transactionStateHandler->paid( $transaction->getOrderTransaction()->getId(), $context );

        } else {
            // Payment not completed, set transaction status to 'open'
           $this->transactionStateHandler->fail( $transaction->getOrderTransaction()->getId(), $context );
        }
    }

}
