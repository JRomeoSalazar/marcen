<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\CoreBundle\Controller;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\CoreBundle\Checkout\SyliusCheckoutEvents;
use Sylius\Bundle\CoreBundle\OrderProcessing\StateResolver;
use Sylius\Bundle\CoreBundle\SyliusOrderEvents;
use Sylius\Bundle\PaymentsBundle\Model\PaymentInterface;
use Sylius\Bundle\PaymentsBundle\SyliusPaymentEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\EventDispatcher\GenericEvent;

class OrderController extends ResourceController
{
    /**
     * @param Request $request
     * @param integer $id
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function indexByUserAction(Request $request, $id)
    {
        $user = $this->get('sylius.repository.user')->findOneById($id);

        if (!$user) {
            throw new NotFoundHttpException('Requested user does not exist.');
        }

        $paginator = $this
            ->getRepository()
            ->createByUserPaginator($user, $this->config->getSorting())
        ;

        $paginator->setCurrentPage($request->get('page', 1), true, true);
        $paginator->setMaxPerPage($this->config->getPaginationMaxPerPage());

        return $this->render('SyliusWebBundle:Backend/Order:indexByUser.html.twig', array(
            'user'   => $user,
            'orders' => $paginator
        ));
    }

    /**
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function releaseInventoryAction()
    {
        $order = $this->findOr404($this->getRequest());

        $this->get('event_dispatcher')->dispatch(SyliusOrderEvents::PRE_RELEASE, new GenericEvent($order));

        $this->domainManager->update($order);

        $this->get('event_dispatcher')->dispatch(SyliusOrderEvents::POST_RELEASE, new GenericEvent($order));

        return $this->redirectHandler->redirectToReferer();
    }

    private function getFormFactory()
    {
        return $this->get('form.factory');
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function completePaymentAction($id)
    {
        /* Obtenemos la order */
        $order = $this->get('sylius.repository.order')->findOneById($id);
        if (!isset($order)) {
            throw new NotFoundHttpException('Requested ORDER does not exist. id:'.$id);
        }

        /* Obtenemos el estado completado y la cantidad total del pedido. */
        $amount = $order->getTotal();
        $state = PaymentInterface::STATE_COMPLETED;

        /* Le decimos la cantidad que se ha pagado y que el pago está completado. */
        $payment = $order->getPayment();
        $previousState = $order->getPayment()->getState();
        $payment->setAmount($amount);
        $payment->setState($state);

        if ($previousState !== $payment->getState()) {
            $this->get('event_dispatcher')->dispatch(
                SyliusPaymentEvents::PRE_STATE_CHANGE,
                new GenericEvent($order->getPayment(), array('previous_state' => $previousState))
            );
        }

        // Lanzamos el evento para actualizar la cantidad total del pedido en el pago (sólo si state == 'completed').
        $this->get('event_dispatcher')->dispatch(
            SyliusCheckoutEvents::FINALIZE_PRE_COMPLETE,
            new GenericEvent($order)
        );

        /* Persistimos el pago */
        $manager = $this->get('sylius.manager.payment');
        $manager->persist($payment);
        $manager->flush();

        /* Mensaje de éxito */
        $translator = $this->get('translator');
        $this->get('session')->getFlashBag()->add('success', $translator->trans('sylius.payment.success', array(), 'flashes'));

        /* Redirigimos */
        return $this->redirect($this->generateUrl('sylius_backend_order_index'));
    }
}
