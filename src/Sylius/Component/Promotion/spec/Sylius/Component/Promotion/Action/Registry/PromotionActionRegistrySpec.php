<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Sylius\Component\Promotion\Action\Registry;

use PhpSpec\ObjectBehavior;
use Sylius\Component\Promotion\Action\PromotionActionInterface;
use Sylius\Component\Promotion\Model\ActionInterface;

/**
 * @author Saša Stamenković <umpirsky@gmail.com>
 */
class PromotionActionRegistrySpec extends ObjectBehavior
{
    function it_should_be_initializable()
    {
        $this->shouldHaveType('Sylius\Component\Promotion\Action\Registry\PromotionActionRegistry');
    }

    function it_should_be_Sylius_promotion_action_registry()
    {
        $this->shouldImplement('Sylius\Component\Promotion\Action\Registry\PromotionActionRegistryInterface');
    }

    function it_should_initialize_actions_array_by_default()
    {
        $this->getActions()->shouldReturn(array());
    }

    function it_should_register_action_under_given_type(PromotionActionInterface $action)
    {
        $this->hasAction(ActionInterface::TYPE_FIXED_DISCOUNT)->shouldReturn(false);
        $this->registerAction(ActionInterface::TYPE_FIXED_DISCOUNT, $action);
        $this->hasAction(ActionInterface::TYPE_FIXED_DISCOUNT)->shouldReturn(true);
    }

    function it_should_complain_if_trying_to_register_action_with_taken_name(PromotionActionInterface $action)
    {
        $this->registerAction(ActionInterface::TYPE_FIXED_DISCOUNT, $action);

        $this
            ->shouldThrow('Sylius\Component\Promotion\Action\Registry\ExistingPromotionActionException')
            ->duringRegisterAction(ActionInterface::TYPE_FIXED_DISCOUNT, $action)
        ;
    }

    function it_should_unregister_action_with_given_name(PromotionActionInterface $action)
    {
        $this->registerAction(ActionInterface::TYPE_FIXED_DISCOUNT, $action);
        $this->hasAction(ActionInterface::TYPE_FIXED_DISCOUNT)->shouldReturn(true);

        $this->unregisterAction(ActionInterface::TYPE_FIXED_DISCOUNT);
        $this->hasAction(ActionInterface::TYPE_FIXED_DISCOUNT)->shouldReturn(false);
    }

    function it_should_retrieve_registered_action_by_name(PromotionActionInterface $action)
    {
        $this->registerAction(ActionInterface::TYPE_FIXED_DISCOUNT, $action);
        $this->getAction(ActionInterface::TYPE_FIXED_DISCOUNT)->shouldReturn($action);
    }

    function it_should_complain_if_trying_to_retrieve_non_existing_checker()
    {
        $this
            ->shouldThrow('Sylius\Component\Promotion\Action\Registry\NonExistingPromotionActionException')
            ->duringGetAction(ActionInterface::TYPE_FIXED_DISCOUNT)
        ;
    }
}
