<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\WebBundle\Twig;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Sylius\Bundle\MoneyBundle\Twig\SyliusMoneyExtension;

class SyliusPromotionExtension extends \Twig_Extension
{
    /**
     * @var RepositoryInterface
     */
    private $countryRepository;

    /**
     * @var RepositoryInterface
     */
    private $taxonRepository;

    /**
     * @var RepositoryInterface
     */
    private $variantRepository;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var SyliusMoneyExtension
     */
    protected $moneyExtension;

    /**
     * Constructor.
     *
     * @param RepositoryInterface   $countryRepository
     * @param RepositoryInterface   $taxonRepository
     * @param RepositoryInterface   $variantRepository
     * @param TranslatorInterface   $translator
     * @param SyliusMoneyExtension  $moneyExtension
     */
    public function __construct(
        RepositoryInterface  $countryRepository,
        RepositoryInterface  $taxonRepository,
        RepositoryInterface  $variantRepository,
        TranslatorInterface  $translator,
        SyliusMoneyExtension $moneyExtension
    )
    {
        $this->countryRepository = $countryRepository;
        $this->taxonRepository   = $taxonRepository;
        $this->variantRepository = $variantRepository;
        $this->translator        = $translator;
        $this->moneyExtension    = $moneyExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('sylius_rule_configuration', array($this, 'ruleConfiguration')),
            new \Twig_SimpleFunction('sylius_action_configuration', array($this, 'actionConfiguration')),
        );
    }

    /**
     * Receives rule key, value and type and returns formatted value.
     *
     * @param string $key
     * @param string $value
     * @param string $type
     *
     * @return string
     */
    public function ruleConfiguration($key, $value, $type)
    {
        switch ($type) {
            case 'item_count':
                if ($key == 'equal') {
                    if ($value) return $this->translator->trans('sylius.yes');
                    else return $this->translator->trans('sylius.no');
                }
                else return $value;
                break;

            case 'item_total':
                if ($key == 'amount') {
                    return $this->moneyExtension->formatPrice($value);
                }
                else {
                    if ($value) return $this->translator->trans('sylius.yes');
                    else return $this->translator->trans('sylius.no');
                }
                break;

            case 'shipping_country':
                $country = $this->countryRepository->find($value);
                if (null !== $country) return $country->getName();
                else return "";
                break;

            case 'taxonomy':
                if ($key == 'taxons') {
                    $taxons_ids = explode(', ', $value);
                    foreach ($taxons_ids as $taxon_id ) {
                        $taxon = $this->taxonRepository->find($taxon_id);
                        if (null !== $taxon) {
                            if (isset($taxons)) {
                                $taxons = $taxons." <span class='label label-default'>".$taxon->getName()."</span>";
                            }
                            else {
                                $taxons = "<span class='label label-default'>".$taxon->getName()."</span>";
                            }
                        }
                    }
                    if (isset($taxons)) {
                        return $taxons;
                    }
                    else return "";
                }
                else {
                    if ($value) return $this->translator->trans('sylius.yes');
                    else return $this->translator->trans('sylius.no');
                }
                break;

            case 'user_loyality':
                switch ($key) {
                    case 'unit':
                        return $this->translator->trans('sylius.promotion.rules.unit_config.'.$value);
                        break;

                    case 'after':
                        if ($value) return $this->translator->trans('sylius.yes');
                        else return $this->translator->trans('sylius.no');
                        break;
                    
                    default:
                        return $value;
                        break;
                }
            
            default:
                return $value;
                break;
        }
    }

    /**
     * Receives action key, value and type and returns formatted value.
     *
     * @param string $key
     * @param string $value
     * @param string $type
     *
     * @return string
     */
    public function actionConfiguration($key, $value, $type)
    {
        switch ($type) {
            case 'fixed_discount':
                if ($key == 'amount') return $this->moneyExtension->formatPrice($value);
                else return $value;
                break;

            case 'percentage_discount':
            case 'shipping_discount':
                if ($key == 'percentage') return ($value*100)."%";
                break;

            case 'add_product':
                switch ($key) {
                    case 'variant':
                        return $this->variantRepository->find($value)->getProduct()->getName();
                        break;
                    

                    case 'price':
                        return $this->moneyExtension->formatPrice($value);
                        break;

                    default:
                        return $value;
                        break;
                }
            
            default:
                return $value;
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sylius_configuration';
    }
}