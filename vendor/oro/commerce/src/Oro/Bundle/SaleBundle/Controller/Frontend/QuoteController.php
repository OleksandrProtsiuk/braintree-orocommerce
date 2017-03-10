<?php

namespace Oro\Bundle\SaleBundle\Controller\Frontend;

use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\QuoteDemandSubtotalsCalculatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Form\Type\QuoteDemandType;

class QuoteController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_sale_quote_frontend_view", requirements={"id"="\d+"})
     * @Layout()
     * @Acl(
     *      id="oro_sale_quote_frontend_view",
     *      type="entity",
     *      class="OroSaleBundle:Quote",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     * @ParamConverter("quote", options={"repository_method" = "getQuote"})
     *
     * @param Quote $quote
     * @return array
     */
    public function viewAction(Quote $quote)
    {
        if (!$quote->isAcceptable()) {
            $this->addFlash('notice', $this->get('translator')->trans('oro.sale.controller.quote.expired.message'));
        }

        return [
            'data' => ['entity' => $quote, 'quote' => $quote]
        ];
    }

    /**
     * @Route("/", name="oro_sale_quote_frontend_index")
     * @Layout(vars={"entity_class"})
     * @Acl(
     *      id="oro_sale_quote_frontend_index",
     *      type="entity",
     *      class="OroSaleBundle:Quote",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_sale.entity.quote.class')
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_sale_quote_frontend_info", requirements={"id"="\d+"})
     * @Template("OroSaleBundle:Quote/Frontend/widget:info.html.twig")
     * @AclAncestor("oro_sale_quote_frontend_view")
     *
     * @param Quote $quote
     * @return array
     */
    public function infoAction(Quote $quote)
    {
        return [
            'entity' => $quote
        ];
    }

    /**
     * @Route("/choice/{id}", name="oro_sale_quote_frontend_choice", requirements={"id"="\d+"})
     * @Layout()
     * @Acl(
     *      id="oro_sale_quote_frontend_choice",
     *      type="entity",
     *      class="OroSaleBundle:Quote",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @param Request $request
     * @param QuoteDemand $quoteDemand
     * @return array|Response
     */
    public function choiceAction(Request $request, QuoteDemand $quoteDemand)
    {
        if (!$quoteDemand->getQuote()->isAcceptable()) {
            return new RedirectResponse($request->headers->get('referer'));
        }

        $form = $this->createForm(QuoteDemandType::NAME, $quoteDemand);
        if ($request->isMethod(Request::METHOD_POST)) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $actionGroupRegistry = $this->get('oro_action.action_group_registry');
                $actionGroup = $actionGroupRegistry
                    ->findByName('oro_sale_frontend_quote_accept_and_submit_to_order');
                if ($actionGroup) {
                    $actionData = $actionGroup->execute(new ActionData(['data' => $quoteDemand]));

                    $this->getDoctrine()->getManagerForClass(QuoteDemand::class)->flush();

                    $redirectUrl = $actionData->getRedirectUrl();
                    if ($redirectUrl) {
                        if ($request->isXmlHttpRequest()) {
                            return new JsonResponse(['redirectUrl' => $redirectUrl]);
                        } else {
                            return $this->redirect($redirectUrl);
                        }
                    }
                }
            }
        }

        return [
            'data' => [
                'data' => $quoteDemand,
                'form' => $form->createView(),
                'quote' => $quoteDemand->getQuote(),
                'totals' => (object)$this->getSubtotalsCalculator()->calculateSubtotals($quoteDemand)
            ]
        ];
    }

    /**
     * @Route("/subtotals/{id}", name="oro_sale_quote_frontend_subtotals", requirements={"id"="\d+"})
     * @Layout()
     * @Acl(
     *      id="oro_sale_quote_frontend_subtotals",
     *      type="entity",
     *      class="OroSaleBundle:Quote",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @param Request $request
     * @param QuoteDemand $quoteDemand
     * @return array
     */
    public function subtotalsAction(Request $request, QuoteDemand $quoteDemand)
    {
        $form = $this->createForm(QuoteDemandType::NAME, $quoteDemand);

        if ($request->isMethod(Request::METHOD_POST)) {
            $form->handleRequest($request);
        }

        return [
            'data' => [
                'totals' => (object)$this->getSubtotalsCalculator()->calculateSubtotals($quoteDemand)
            ]
        ];
    }

    /**
     * @return QuoteDemandSubtotalsCalculatorInterface
     */
    protected function getSubtotalsCalculator()
    {
        return $this->get('oro_sale.quote_demand.subtotals_calculator_main');
    }
}
