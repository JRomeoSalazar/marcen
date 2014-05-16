<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\ProductBundle\Controller;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Product\Builder\PrototypeBuilderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Prototype controller.
 *
 * @author Paweł Jędrzejewski <pjedrzejewski@sylius.pl>
 */
class PrototypeController extends ResourceController
{
    /**
     * Build a product from the given prototype.
     * Everything else works exactly like in product
     * creation action.
     *
     * @param Request $request
     * @param mixed   $id
     *
     * @return Response
     */
    public function buildAction(Request $request, $id)
    {
        $prototype = $this->findOr404($request, array('id' => $id));
        $productController = $this->getProductController();

        $product = $productController->createNew();

        $this
            ->getBuilder()
            ->build($prototype, $product)
        ;

        $form = $productController->getForm($product);

        if ($request->isMethod('POST') && $form->submit($request)->isValid()) {
            $eventDispatcher = $this->getEventDispatcher();
            $eventDispatcher->dispatch('sylius.product.pre_create', new GenericEvent($product));

            $manager = $this->get('doctrine')->getManager();
            $manager->persist($product);
            $manager->flush();

            $translator = $this->getTranslator();
            $message = $translator->trans('sylius.resource.create', array('%resource%' => 'Product'), 'flashes');
            $this->get('session')->getFlashBag()->add('success', $message);


            $eventDispatcher->dispatch('sylius.product.post_create', new GenericEvent($product));

            return $this->redirectHandler->redirectTo($product);
        }

        return $productController->render($this->config->getTemplate('build.html'), array(
            'product_prototype' => $prototype,
            'product'           => $product,
            'form'              => $form->createView()
        ));
    }

    /**
     * Get product controller.
     *
     * @return Controller
     */
    protected function getProductController()
    {
        return $this->get('sylius.controller.product');
    }

    /**
     * Get prototype builder.
     *
     * @return PrototypeBuilderInterface
     */
    protected function getBuilder()
    {
        return $this->get('sylius.builder.product_prototype');
    }

    /**
     * Get event dispatcher.
     *
     * @return EventDispatcherInterface
     */
    protected function getEventDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     * Get translator.
     *
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->container->get('translator');
    }
}
