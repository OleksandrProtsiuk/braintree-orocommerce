<?php

namespace Oro\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\RFPBundle\Entity\RequestStatus;
use Oro\Bundle\RFPBundle\Form\Handler\RequestStatusHandler;
use Oro\Bundle\RFPBundle\Form\Type\RequestStatusType;

class RequestStatusController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_rfp_request_status_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_rfp_request_status_view",
     *      type="entity",
     *      class="OroRFPBundle:RequestStatus",
     *      permission="VIEW"
     * )
     *
     * @param RequestStatus $requestStatus
     * @return array
     */
    public function viewAction(RequestStatus $requestStatus)
    {
        return [
            'entity' => $requestStatus
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_rfp_request_status_info", requirements={"id"="\d+"})
     * @Template("OroRFPBundle:RequestStatus/widget:info.html.twig")
     * @AclAncestor("oro_rfp_request_status_view")
     *
     * @param RequestStatus $requestStatus
     * @return array
     */
    public function infoAction(RequestStatus $requestStatus)
    {
        return [
            'entity' => $requestStatus
        ];
    }

    /**
     * @Route("/", name="oro_rfp_request_status_index")
     * @Template
     * @AclAncestor("oro_rfp_request_status_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_rfp.entity.request.status.class')
        ];
    }

    /**
     * @Route("/create", name="oro_rfp_request_status_create")
     * @Template("OroRFPBundle:RequestStatus:update.html.twig")
     * @Acl(
     *     id="oro_rfp_request_status_create",
     *     type="entity",
     *     permission="CREATE",
     *     class="OroRFPBundle:RequestStatus"
     * )
     */
    public function createAction()
    {
        $requestStatus = new RequestStatus();
        return $this->process($requestStatus);
    }

    /**
     * @Route("/update/{id}", name="oro_rfp_request_status_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *     id="oro_rfp_request_status_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroRFPBundle:RequestStatus"
     * )
     *
     * @param RequestStatus $requestStatus
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updateAction(RequestStatus $requestStatus)
    {
        return $this->process($requestStatus);
    }

    /**
     * @param RequestStatus $requestStatus
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function process(RequestStatus $requestStatus)
    {
        $form = $this->createForm(RequestStatusType::NAME);

        $handler = new RequestStatusHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass(
                $this->container->getParameter('oro_rfp.entity.request.status.class')
            )
        );
        $handler->setDefaultLocale($this->container->getParameter('stof_doctrine_extensions.default_locale'));

        return $this->get('oro_form.model.update_handler')
            ->handleUpdate(
                $requestStatus,
                $form,
                function (RequestStatus $requestStatus) {
                    return [
                        'route' => 'oro_rfp_request_status_update',
                        'parameters' => [
                            'id' => $requestStatus->getId()
                        ]
                    ];
                },
                function (RequestStatus $requestStatus) {
                    return [
                        'route' => 'oro_rfp_request_status_view',
                        'parameters' => [
                            'id' => $requestStatus->getId()
                        ]
                    ];
                },
                $this->get('translator')->trans('oro.rfp.message.request_status_saved'),
                $handler
            )
            ;
    }
}
