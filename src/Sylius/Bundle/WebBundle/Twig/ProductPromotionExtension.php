<?php

namespace Sylius\Bundle\WebBundle\Twig;

use Sylius\Component\Resource\Repository\RepositoryInterface;

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
     * Constructor.
     *
     * @param RepositoryInterface       $productRepository
     * @param RepositoryInterface       $variantRepository
     */
    public function __construct(RepositoryInterface $productRepository, RepositoryInterface $variantRepository)
    {
        $this->productRepository = $productRepository;
        $this->variantRepository = $variantRepository;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('product_promotion', array($this, 'productPromotionFilter')),
        );
    }

    public function productPromotionFilter($amount, $itemId, $item = "product")
    {
        $item == "product" ? $producto = $this->productRepository->find($itemId) : $producto = $this->variantRepository->find($itemId)->getProduct();
        if (!isset($producto)) {
            return $amount;
        }

        if ($producto->hasProductPromotions()) {
            foreach ($producto->getProductPromotions() as $productPromotion) {
                $amount = $amount-($amount*$productPromotion->getPorcentaje()/100);
            }
        }

        return $amount;
    }

    public function getName()
    {
        return 'sylius_product_promotion_extension';
    }
}