<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Component\Promotion\Processor;

use Sylius\Bundle\SettingsBundle\Model\Settings;
use Sylius\Component\Addressing\Matcher\ZoneMatcherInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Promotion\Action\PromotionApplicatorInterface;
use Sylius\Component\Promotion\Checker\PromotionEligibilityCheckerInterface;
use Sylius\Component\Promotion\Model\PromotionSubjectInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxation\Calculator\CalculatorInterface;
use Sylius\Component\Taxation\Resolver\TaxRateResolverInterface;

/**
 * Process all active promotions.
 *
 * Checks all rules and applies configured actions if rules are eligible.
 *
 * @author Saša Stamenković <umpirsky@gmail.com>
 */
class PromotionProcessor implements PromotionProcessorInterface
{
    /**
     * Promotion repository.
     *
     * @var RepositoryInterface
     */
    protected $promotionRepository;

    /**
     * Promotion elegibility checker.
     *
     * @var PromotionEligibilityCheckerInterface
     */
    protected $checker;

    /**
     * Promotion applicator.
     *
     * @var PromotionApplicatorInterface
     */
    protected $applicator;

    /**
     * Adjustment repository.
     *
     * @var RepositoryInterface
     */
    protected $adjustmentRepository;

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
     * @param RepositoryInterface                   $promotionRepository
     * @param PromotionEligibilityCheckerInterface  $checker
     * @param PromotionApplicatorInterface          $applicator
     * @param RepositoryInterface                   $adjustmentRepository
     * @param CalculatorInterface                   $calculator
     * @param TaxRateResolverInterface              $taxRateResolver
     * @param ZoneMatcherInterface                  $zoneMatcher
     * @param Settings                              $taxationSettings
     */
    public function __construct(
        RepositoryInterface $promotionRepository,
        PromotionEligibilityCheckerInterface $checker,
        PromotionApplicatorInterface $applicator,
        RepositoryInterface $adjustmentRepository,
        CalculatorInterface $calculator,
        TaxRateResolverInterface $taxRateResolver,
        ZoneMatcherInterface $zoneMatcher,
        Settings $taxationSettings
    )
    {
        $this->promotionRepository = $promotionRepository;
        $this->checker = $checker;
        $this->applicator = $applicator;
        $this->adjustmentRepository = $adjustmentRepository;
        $this->calculator = $calculator;
        $this->taxRateResolver = $taxRateResolver;
        $this->zoneMatcher = $zoneMatcher;
        $this->settings = $taxationSettings;
    }

    public function process(PromotionSubjectInterface $subject)
    {
        foreach ($subject->getPromotions() as $promotion) {
            $this->applicator->revert($subject, $promotion);
        }

        // Eliminamos las promociones de producto que pudiera haber.
        $subject->removePromotionAdjustments();

        $promotions = $this->promotionRepository->findActive();
        $eligiblePromotions = array();

        foreach ($promotions as $promotion) {
            if (!$this->checker->isEligible($subject, $promotion)) {
                continue;
            }

            if ($promotion->isExclusive()) {
                return $this->applicator->apply($subject, $promotion);
            }

            $eligiblePromotions[] = $promotion;
        }

        foreach ($eligiblePromotions as $promotion) {
            $this->applicator->apply($subject, $promotion);
        }

        // Obtenemos las promociones de producto

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

        $promotions = array();

        foreach ($subject->getItems() as $item) {
            /* Obtenemos el producto */
            $product = $item->getProduct();

            if ($product->hasProductPromotions()) {
                /* Obtenemos el impuesto que corresponde al producto */
                $rate = $this->taxRateResolver->resolve($product, array('zone' => $zone));

                /* Aplicamos los ajustes al item */
                $item->calculateTotal();

                foreach ($product->getProductPromotions() as $productPromotion) {
                    
                    /* Precio del item una vez hecho el descuento */
                    $productPromotionAmount = $item->getTotal()*$productPromotion->getPorcentaje()/100;
                    $productPromotionTaxAmount = $this->calculator->calculate($productPromotionAmount, $rate);
                    $productPromotionFinalAmount = $productPromotionAmount + $productPromotionTaxAmount;

                    $description = sprintf('%s (%d%%)', $product->getName(), $productPromotion->getPorcentaje());

                    $promotions[$description] = array(
                        'amount'   => (isset($promotions[$description]['amount']) ? $promotions[$description]['amount'] : 0) + $productPromotionFinalAmount
                    );
                }

                foreach ($promotions as $description => $productPromotion) {
                    // Obtenemos la cantidad a descontar
                    $amount = $productPromotion['amount'];
                    $amount *= -1;

                    $adjustment = $this->adjustmentRepository->createNew();

                    $adjustment->setAmount($amount);
                    $adjustment->setLabel(OrderInterface::PROMOTION_ADJUSTMENT);
                    $adjustment->setDescription($description);

                    /*** Comprobamos que la promoción no se haya añadido ya ***/
                    $sw = 1;
                    foreach ($subject->getPromotionAdjustments() as $promocion_actual) {
                        if ($promocion_actual->getDescription() == $description) $sw = 0;
                    }

                    if ($sw) $subject->addAdjustment($adjustment);
                }
            }
        }
    }
}
