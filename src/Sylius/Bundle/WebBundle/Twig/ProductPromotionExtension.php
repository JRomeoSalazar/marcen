<?php

namespace Sylius\Bundle\WebBundle\Twig;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Core\Model\ProductInterface;

class ProductPromotionExtension extends \Twig_Extension
{
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
     * Promotion repository
     *
     * @var RepositoryInterface
     */
    protected $promotionRepository;

    /**
     * Constructor.
     *
     * @param RepositoryInterface   $productRepository
     * @param RepositoryInterface   $variantRepository
     * @param RepositoryInterface   $promotionRepository
     */
    public function __construct(RepositoryInterface $productRepository, RepositoryInterface $variantRepository, RepositoryInterface $promotionRepository)
    {
        $this->productRepository    = $productRepository;
        $this->variantRepository    = $variantRepository;
        $this->promotionRepository  = $promotionRepository;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('product_promotion', array($this, 'productPromotionFilter')),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('show_ribbon', array($this, 'showRibbon')),
        );
    }

    public function productPromotionFilter($amount, $itemId, $item = "product")
    {
        $item == "product" ? $product = $this->productRepository->find($itemId) : $product = $this->variantRepository->find($itemId)->getProduct();
        
        if (!isset($product)) {
            return $amount;
        }

        $discount = $this->getDiscount($product);

        return $amount-$discount;
    }


    /**
     * @param ProductInterface $product
     *
     * @return Boolean
     */
    public function showRibbon(ProductInterface $product) {
        $discount = $this->getDiscount($product);
        if ($discount != 0) return true;
    }


    /**
     * @param ProductInterface $product
     *
     * @return integer
     */
    protected function getDiscount(ProductInterface $product) {

        $discount = 0;

        $amount = $product->getPrice();

        // Aplicamos las promociones de producto
        if ($product->hasProductPromotions()) {
            foreach ($product->getProductPromotions() as $productPromotion) {
                $discount += $amount*$productPromotion->getPorcentaje()/100;
            }
        }

        // Aplicamos las promociones generales por taxonomía

        /* Obtenemos las promociones que tienen alguna regla de taxonomía */
        $promotions = $this->promotionRepository->findTaxonomyActive();

        /* Obtenemos un array con los 'id' de los taxons del producto */
        $product_taxons = array();
        foreach ($product->getTaxons() as $taxon) {
            $product_taxons[] = $taxon->getId();
        }

        /* Comprobamos si concuerdan algún taxon del producto con algún taxon de las reglas */
        foreach ($promotions as $promotion) {
            $sw = 0;
            foreach ($promotion->getRules() as $rule) {
                if ($rule->getType() != 'taxonomy') continue;
                $configuration = $rule->getConfiguration();
                foreach ($configuration['taxons'] as $taxon) {
                    if (in_array($taxon, $product_taxons) && !$configuration['exclude']) $sw = 1;
                    elseif (!in_array($taxon, $product_taxons) && $configuration['exclude']) $sw = 1;
                }
                if ($sw) {
                    foreach ($promotion->getActions() as $action) {
                        $action_configuration = $action->getConfiguration();
                        if ($action->getType() == 'percentage_discount') {
                            $discount += $amount*$action_configuration['percentage'];
                        }
                        else if ($action->getType() == 'fixed_discount') {
                            $discount += $action_configuration['amount'];
                        }
                    }
                }
            }
        }

        return $discount;
    }

    public function getName()
    {
        return 'sylius_product_promotion_extension';
    }
}