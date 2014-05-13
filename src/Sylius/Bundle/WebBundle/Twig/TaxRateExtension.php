<?php

namespace Sylius\Bundle\WebBundle\Twig;

use Sylius\Bundle\SettingsBundle\Model\Settings;
use Sylius\Bundle\TaxationBundle\Resolver\TaxRateResolverInterface;
use Sylius\Bundle\ResourceBundle\Model\RepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaxRateExtension extends \Twig_Extension
{
    /**
     * Tax rate resolver.
     *
     * @var TaxRateResolverInterface
     */
    protected $taxRateResolver;

    /**
     * Taxation settings.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Product repository
     *
     * @var RepositoryInterface
     */
    protected $productRepository;

    /**
     * Variant repository
     *
     * @var RepositoryInterface
     */
    protected $variantRepository;

    /**
     * Constructor.
     *
     * @param TaxRateResolverInterface  $taxRateResolver
     * @param Settings                  $taxationSettings
     * @param RepositoryInterface       $productRepository
     * @param RepositoryInterface       $variantRepository
     */
    public function __construct(
        TaxRateResolverInterface $taxRateResolver,
        Settings $taxationSettings,
        RepositoryInterface $productRepository,
        RepositoryInterface $variantRepository
    )
    {
        $this->taxRateResolver = $taxRateResolver;
        $this->settings = $taxationSettings;
        $this->productRepository = $productRepository;
        $this->variantRepository = $variantRepository;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('tax_rate', array($this, 'taxRateFilter')),
        );
    }

    public function taxRateFilter($amount, $itemId, $item = "product")
    {
        $item == "product" ? $producto = $this->productRepository->find($itemId) : $producto = $this->variantRepository->find($itemId);
        if (!isset($producto)) {
            return $amount;
        }

        if ($this->settings->has('default_tax_zone')) {
            $zone = $this->settings->get('default_tax_zone');
        }
        else {
            throw new NotFoundHttpException("Debe establecer una 'Zona fiscal por defecto' en la 'Configuración de impuestos'.");
        }

        $rate = $this->taxRateResolver->resolve($producto, array('zone' => $zone));

        return $amount = $amount + $amount*$rate->getAmount();
    }

    public function getName()
    {
        return 'tax_rate_extension';
    }
}