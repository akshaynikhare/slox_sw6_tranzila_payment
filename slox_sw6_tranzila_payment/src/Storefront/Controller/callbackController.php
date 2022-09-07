<?php declare(strict_types=1);

namespace slox_sw6_tranzila_payment\Storefront\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @RouteScope(scopes={"storefront"})
 */
class callbackController extends StorefrontController
{
    
    /**
     * @Route("/checkout/tranzila/payfail", name="frontend.checkout.tranzila.payfail", options={"seo"="false"},defaults={"csrf_protected"=false}, methods={"POST"})
     */
    public function payfail(Request $request, SalesChannelContext $context)
    {   

        $message ='';
        if(isset($_POST['message'])){
            $message ='<h1 class="finish-header" style="color: red;">';
            $message.=$_POST['message'];
            $message.='</h1>';
            }
        return $this->renderStorefront('@slox_sw6_tranzila_payment/callback/payfail.html.twig', ['data' => json_encode($_POST),'status'=> 'fail','message' => $message,'redirectURL'=>'/checkout/confirm']  );
    }



    /**
     * @Route("/checkout/tranzila/paySuccess", name="frontend.checkout.tranzila.paySuccess", options={"seo"="false"},defaults={"csrf_protected"=false}, methods={"POST"})
     */
    public function paySuccess(Request $request, SalesChannelContext $context)
    {

        if(isset($_POST['paymentToken'])){
            return $this->forwardToRoute('payment.finalize.transaction', ['_sw_payment_token' => $_POST['paymentToken'],'data' => json_encode($_POST),'status'=> 'Success', 'csrf_protected' => false]);
            }

            $message ='<h1 class="finish-header" style="color: red;">';
            $message.='we faced some techinical error confirming your order.';
            $message.='</h1>';
            $message.='<h3 class="finish-header" style="color: red;">';
            $message.='please contact Support with your order detail below';
            $message.=' </h3>';
            $message.=' </h3>';
            $message.=' <h2 class="finish-header" >';
            $message.='please contact Support.';
            $message.='</h3>';

        return $this->renderStorefront('@slox_sw6_tranzila_payment/callback/paySuccess.html.twig', ['message' => $message ,'data' => json_encode($_POST),'status'=> 'Success', 'redirectURL'=> '/']  );

    }


}