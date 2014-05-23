<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Component\Core\Promotion\Action;

use Sylius\Bundle\SettingsBundle\Model\Settings;
use Sylius\Component\Addressing\Matcher\ZoneMatcherInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Promotion\Action\PromotionActionInterface;
use Sylius\Component\Promotion\Model\PromotionInterface;
use Sylius\Component\Promotion\Model\PromotionSubjectInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxation\Calculator\CalculatorInterface;
use Sylius\Component\Taxation\Resolver\TaxRateResolverInterface;

/**
 * Percentage discount action.
 *
 * @author Paweł Jędrzejewski <pjedrzejewski@diweb.pl>
 */
class PercentageDiscountAction implements PromotionActionInterface
{
    /**
     * Adjustment repository.
     *
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * Tax calculator.
     *
     * @var CalculatorInterface
     */
    protected $calculator;

    /**
     * Tax rate resolver.
     *
     * @var TaxRateResolverInterface
     */
    protected $taxRateResolver;

    /**
     * Zone matcher.
     *
     * @var ZoneMatcherInterface
     */
    protected $zoneMatcher;

    /**
     * Taxation settings.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Constructor.
     *
     * @param RepositoryInterface $repository
     * @param CalculatorInterface      $calculator
     * @param TaxRateResolverInterface $taxRateResolver
     * @param ZoneMatcherInterface     $zoneMatcher
     * @param Settings                 $taxationSettings
     */
    public function __construct(
        RepositoryInterface $repository,
        CalculatorInterface $calculator,
        TaxRateResolverInterface $taxRateResolver,
        ZoneMatcherInterface $zoneMatcher,
        Settings $taxationSettings
    )
    {
        $this->repository = $repository;
        $this->calculator = $calculator;
        $this->taxRateResolver = $taxRateResolver;
        $this->zoneMatcher = $zoneMatcher;
        $this->settings = $taxationSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(PromotionSubjectInterface $subject, array $configuration, PromotionInterface $promotion)
    {
        /* Averiguamos la zona fiscal del pedido */
        $zone = null;

        if (null !== $subject->getShippingAddress()) {
            $zone = $this->zoneMatcher->match($subject->getShippingAddress()); // Match the tax zone.
        }

        if ($this->settings->has('default_tax_zone')) {
            $zone = $zone ?: $this->settings->get('default_tax_zone'); // If address does not match any zone, use the default one.
        }

        if (null === $zone) {
            throw new NotFoundHttpException("Debe establecer una 'Zona fiscal por defecto' en la 'Configuración de impuestos'.");
        }

        // Obtenemos la suma total de los items incluidos los impuestos
        $itemsTotal = 0;
        $items = array();
        foreach ($subject->getItems() as $item) {
            $product = $item->getProduct();
            $item->calculateTotal();
            $rate = $this->taxRateResolver->resolve($product, array('zone' => $zone));
            $tax = $this->calculator->calculate($item->getTotal(), $rate);
            $items[] = array(
                'item'  =>  $item,
                'tax'   =>  $tax
            );
            $itemsTotal = $itemsTotal + $item->getTotal() + $tax; 
        }

        // Si la promoción no contiene sólo la regla "Taxonomy"
        $rules = array();
        foreach ($promotion->getRules() as $rule) {
            $rules[] = $rule->getType(); 
        }

        if (!in_array('taxonomy', $rules) || count($rules) > 1) {
            $adjustment = $this->repository->createNew();

            $adjustment->setAmount(- $itemsTotal * ($configuration['percentage']));
            $adjustment->setLabel(OrderInterface::PROMOTION_ADJUSTMENT);
            $adjustment->setDescription($promotion->getDescription());

            $subject->addAdjustment($adjustment);
        }
        else {
            foreach ($promotion->getRules() as $rule) {
                $ruleConfiguration = $rule->getConfiguration();
            }
            $promotionsTotal = 0;
            foreach ($items as $item) {
                foreach ($item['item']->getProduct()->getTaxons() as $taxon) {
                    if ($ruleConfiguration['taxons']->contains($taxon->getId())) {
                        if (!$ruleConfiguration['exclude']) {
                            $promotionsTotal = $promotionsTotal + $item['item']->getTotal() + $item['tax'];
                        }
                    }
                }
            }
            $adjustment = $this->repository->createNew();

            $adjustment->setAmount(- $promotionsTotal * ($configuration['percentage']));
            $adjustment->setLabel(OrderInterface::PROMOTION_ADJUSTMENT);
            $adjustment->setDescription($promotion->getDescription());

            $subject->addAdjustment($adjustment);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function revert(PromotionSubjectInterface $subject, array $configuration, PromotionInterface $promotion)
    {
        $subject->removePromotionAdjustments();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFormType()
    {
        return 'sylius_promotion_action_percentage_discount_configuration';
    }
}
