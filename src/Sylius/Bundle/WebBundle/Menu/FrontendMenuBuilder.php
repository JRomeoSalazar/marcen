<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\WebBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Sylius\Bundle\MoneyBundle\Twig\SyliusMoneyExtension;
use Sylius\Component\Cart\Provider\CartProviderInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Frontend menu builder.
 *
 * @author Paweł Jędrzejewski <pjedrzejewski@diweb.pl>
 */
class FrontendMenuBuilder extends MenuBuilder
{
    /**
     * Currency repository.
     *
     * @var RepositoryInterface
     */
    protected $exchangeRateRepository;

    /**
     * Taxonomy repository.
     *
     * @var RepositoryInterface
     */
    protected $taxonomyRepository;

    /**
     * Cart provider.
     *
     * @var CartProviderInterface
     */
    protected $cartProvider;

    /**
     * Money extension.
     *
     * @var SyliusMoneyExtension
     */
    protected $moneyExtension;

    /**
     * Constructor.
     *
     * @param FactoryInterface         $factory
     * @param SecurityContextInterface $securityContext
     * @param TranslatorInterface      $translator
     * @param EventDispatcherInterface $eventDispatcher
     * @param RepositoryInterface      $exchangeRateRepository
     * @param RepositoryInterface      $taxonomyRepository
     * @param CartProviderInterface    $cartProvider
     * @param SyliusMoneyExtension     $moneyExtension
     */
    public function __construct(
        FactoryInterface         $factory,
        SecurityContextInterface $securityContext,
        TranslatorInterface      $translator,
        EventDispatcherInterface $eventDispatcher,
        RepositoryInterface      $exchangeRateRepository,
        RepositoryInterface      $taxonomyRepository,
        CartProviderInterface    $cartProvider,
        SyliusMoneyExtension     $moneyExtension
    )
    {
        parent::__construct($factory, $securityContext, $translator, $eventDispatcher);

        $this->exchangeRateRepository = $exchangeRateRepository;
        $this->taxonomyRepository = $taxonomyRepository;
        $this->cartProvider = $cartProvider;
        $this->moneyExtension = $moneyExtension;
    }

    /**
     * Builds frontend main menu.
     *
     * @param Request $request
     *
     * @return ItemInterface
     */
    public function createMainMenu(Request $request)
    {
        $menu = $this->factory->createItem('root', array(
            'childrenAttributes' => array(
                'class' => 'nav navbar-nav sylius-frontend-main'
            )
        ));

        if ($this->cartProvider->hasCart()) {
            $cart = $this->cartProvider->getCart();
            $cartTotals = array('items' => $cart->getTotalItems(), 'total' => $cart->getTotal());
        } else {
            $cartTotals = array('items' => 0, 'total' => 0);
        }

        $menu->addChild('cart', array(
            'route' => 'sylius_cart_summary',
            'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.main.cart', array(
                '%items%' => $cartTotals['items'],
                '%total%' => $this->moneyExtension->formatPrice($cartTotals['total'])
            ))),
            'labelAttributes' => array('icon' => 'icon-shopping-cart icon-large')
        ))->setLabel($this->translate('sylius.frontend.menu.main.cart', array(
            '%items%' => $cartTotals['items'],
            '%total%' => $this->moneyExtension->formatPrice($cartTotals['total'])
        )));

        if ($this->securityContext->getToken() && $this->securityContext->isGranted('ROLE_USER')) {
            $route = $this->request === null ? '' : $this->request->get('_route');

            if (1 === preg_match('/^(sylius_account)|(fos_user)/', $route)) {
                $menu->addChild('shop', array(
                    'route' => 'sylius_homepage',
                    'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.account.shop')),
                    'labelAttributes' => array('icon' => 'icon-th icon-large', 'iconOnly' => false)
                ))->setLabel($this->translate('sylius.frontend.menu.account.shop'));
            } else {
                $menu->addChild('account', array(
                    'route' => 'sylius_account_homepage',
                    'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.main.account')),
                    'labelAttributes' => array('icon' => 'icon-user icon-large', 'iconOnly' => false)
                ))->setLabel($this->translate('sylius.frontend.menu.main.account'));
            }

            $menu->addChild('logout', array(
                'route' => 'fos_user_security_logout',
                'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.main.logout')),
                'labelAttributes' => array('icon' => 'icon-off icon-large', 'iconOnly' => false)
            ))->setLabel($this->translate('sylius.frontend.menu.main.logout'));
        } else {
            $menu->addChild('login', array(
                'route' => 'fos_user_security_login',
                'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.main.login')),
                'labelAttributes' => array('icon' => 'icon-lock icon-large', 'iconOnly' => false)
            ))->setLabel($this->translate('sylius.frontend.menu.main.login'));
            $menu->addChild('register', array(
                'route' => 'fos_user_registration_register',
                'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.main.register')),
                'labelAttributes' => array('icon' => 'icon-user icon-large', 'iconOnly' => false)
            ))->setLabel($this->translate('sylius.frontend.menu.main.register'));
        }


        if ($this->securityContext->getToken() && $this->securityContext->isGranted('ROLE_SYLIUS_ADMIN')) {

            $routeParams = array(
                'route' => 'sylius_backend_dashboard',
                'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.main.administration')),
                'labelAttributes' => array('icon' => 'icon-briefcase icon-large', 'iconOnly' => false)
            );

            if ($this->securityContext->isGranted('ROLE_PREVIOUS_ADMIN')) {
                $routeParams = array_merge($routeParams, array(
                    'route' => 'sylius_switch_user_return',
                    'routeParameters' => array(
                        'username' => $this->securityContext->getToken()->getUsername(),
                        '_switch_user' => '_exit'
                    )
                ));
            }

            $menu->addChild('administration', $routeParams)->setLabel($this->translate('sylius.frontend.menu.main.administration'));
        }
        else {
            $menu->addChild('condiciones_generales', array(
                'route' => 'sylius_condiciones_generales',
                'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.main.condiciones_generales')),
                'labelAttributes' => array('icon' => 'icon-check-sign icon-large')
            ))->setLabel($this->translate('sylius.frontend.menu.main.condiciones_generales'));
        }

        return $menu;
    }

    /**
     * Builds frontend currency menu.
     *
     * @return ItemInterface
     */
    public function createCurrencyMenu()
    {
        $menu = $this->factory->createItem('root', array(
            'childrenAttributes' => array(
                'class' => 'nav nav-pills'
            )
        ));

        foreach ($this->exchangeRateRepository->findAll() as $exchangeRate) {
            $menu->addChild($exchangeRate->getCurrency(), array(
                'route' => 'sylius_currency_change',
                'routeParameters' => array('currency' => $exchangeRate->getCurrency()),
                'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.currency', array('%currency%' => $exchangeRate->getCurrency()))),
            ))->setLabel(Intl::getCurrencyBundle()->getCurrencySymbol($exchangeRate->getCurrency()));
        }

        return $menu;
    }

    /**
     * Builds frontend taxonomies menu.
     *
     * @param Request $request
     *
     * @return ItemInterface
     */
    public function createTaxonomiesMenu(Request $request)
    {
        $menu = $this->factory->createItem('root', array(
            'childrenAttributes' => array(
                'class' => 'taxonomies'
            )
        ));

        $childOptions = array(
            'childrenAttributes' => array('class' => 'nav nav-pills nav-stacked'),
            'labelAttributes'    => array('class' => 'nav-header'),
        );

        $taxonomies = $this->taxonomyRepository->findAll();

        foreach ($taxonomies as $taxonomy) {
            $childOptions['childrenAttributes']['data-id'] = $taxonomy->getId();
            $childOptions['labelAttributes']['id'] = $taxonomy->getId();
            $child = $menu->addChild($taxonomy->getName(), $childOptions);

            if ($taxonomy->getRoot()->hasPath()) {
                $child->setLabelAttribute('data-image', $taxonomy->getRoot()->getPath());
            }

            $this->createTaxonomiesMenuNode($child, $taxonomy->getRoot());
        }

        return $menu;
    }

    private function createTaxonomiesMenuNode(ItemInterface $menu, TaxonInterface $taxon)
    {
        foreach ($taxon->getChildren() as $child) {
            $childMenu = $menu->addChild($child->getName(), array(
                'route'           => 'sylius_product_index_by_taxon',
                'routeParameters' => array('permalink' => $child->getPermalink())
            ));
            if ($child->getPath()) {
                $childMenu->setLabelAttribute('data-image', $child->getPath());
            }

            $this->createTaxonomiesMenuNode($childMenu, $child);
        }
    }

    /**
     * Builds frontend footer taxonomies menu.
     *
     * @param Request $request
     *
     * @return ItemInterface
     */
    public function createTaxonsMenu(Request $request)
    {
        $menu = $this->factory->createItem('root', array(
            'childrenAttributes' => array(
                'class' => 'list-unstyled'
            )
        ));

        $childOptions = array(
            'childrenAttributes' => array('class' => 'list-unstyled nested'),
            'labelAttributes'    => array('class' => 'nested-header'),
        );

        $taxonomies = $this->taxonomyRepository->findAll();

        foreach ($taxonomies as $taxonomy) {
            $child = $menu->addChild($taxonomy->getName(), $childOptions);

            $this->createTaxonsMenuNode($child, $taxonomy->getRoot());
        }

        return $menu;
    }

    private function createTaxonsMenuNode(ItemInterface $menu, TaxonInterface $taxon)
    {
        foreach ($taxon->getChildren() as $child) {
            $childMenu = $menu->addChild($child->getName(), array(
                'route'           => 'sylius_product_index_by_taxon',
                'routeParameters' => array('permalink' => $child->getPermalink()),
                'labelAttributes' => array('icon' => 'icon-angle-right', 'iconOnly' => false)
            ));

            $this->createTaxonomiesMenuNode($childMenu, $child);
        }
    }

    /**
     * Builds frontend social menu.
     *
     * @param Request $request
     *
     * @return ItemInterface
     */
    public function createSocialMenu(Request $request)
    {
        $menu = $this->factory->createItem('root', array(
            'childrenAttributes' => array(
                'class' => 'nav navbar-nav sylius-frontend-social'
            )
        ));

        $menu->addChild('facebook', array(
            'uri' => 'http://facebook.com/ciclosmarcen',
            'linkAttributes' => array(
                'title' => 'Facebook Ciclos Marcen',
                'class' => 'facebook',
                'target' => '_blank'
                ),
            'labelAttributes' => array('icon' => 'icon-facebook icon-large', 'iconOnly' => true)
        ));
        $menu->addChild('twitter', array(
            'uri' => 'https://twitter.com/CiclosMarcen',
            'linkAttributes' => array(
                'title' => 'Twitter Ciclos Marcen',
                'class' => 'twitter',
                'target' => '_blank'
                ),
            'labelAttributes' => array('icon' => 'icon-twitter icon-large', 'iconOnly' => true)
        ));

        return $menu;
    }

    /**
     * Builds frontend contact menu.
     *
     * @param Request $request
     *
     * @return ItemInterface
     */
    public function createContactMenu(Request $request)
    {
        $menu = $this->factory->createItem('root', array(
            'childrenAttributes' => array(
                'class' => 'list-unstyled'
            )
        ));

        $menu->addChild('facebook', array(
            'uri' => 'http://facebook.com/ciclosmarcen',
            'linkAttributes' => array(
                'title' => 'Facebook Ciclos Marcen',
                'class' => 'facebook',
                'target' => '_blank'
                ),
            'labelAttributes' => array('icon' => 'icon-fixed-width icon-facebook-sign icon-large', 'iconOnly' => false)
        ))->setLabel('facebook.com/ciclosmarcen');

        $menu->addChild('twitter', array(
            'uri' => 'https://twitter.com/CiclosMarcen',
            'linkAttributes' => array(
                'title' => 'Twitter Ciclos Marcen',
                'class' => 'twitter',
                'target' => '_blank'
                ),
            'labelAttributes' => array('icon' => 'icon-fixed-width icon-twitter-sign icon-large', 'iconOnly' => false)
        ))->setLabel('twitter.com/CiclosMarcen');

        $menu->addChild('phone', array(
            'labelAttributes' => array('icon' => 'icon-fixed-width icon-phone icon-large', 'iconOnly' => false)
        ))->setLabel('976 426 040');

        $menu->addChild('mail', array(
            'uri' => 'mailto:info@ciclosmarcen.com',
            'linkAttributes' => array('title' => 'Email Ciclos Marcen'),
            'labelAttributes' => array('icon' => 'icon-fixed-width icon-envelope icon-large', 'iconOnly' => false)
        ))->setLabel('info@ciclosmarcen.com');

        $menu->addChild('address', array(
            'labelAttributes' => array('icon' => 'icon-fixed-width icon-home icon-large', 'iconOnly' => false)
        ))->setLabel('C/Reina Fabiola, 20');

        $menu->addChild('quienes_somos', array(
            'route' => 'sylius_quienes_somos',
            'linkAttributes' => array('title' => 'Sobre Ciclos Marcen'),
            'labelAttributes' => array('icon' => 'icon-fixed-width icon-info-sign icon-large', 'iconOnly' => false)
        ))->setLabel('Sobre Ciclos Marcen');

        return $menu;
    }

    /**
     * Creates user account menu
     *
     * @param Request $request
     *
     * @return ItemInterface
     */
    public function createAccountMenu(Request $request)
    {
        $menu = $this->factory->createItem('root', array(
            'childrenAttributes' => array(
                'class' => 'nav account'
            )
        ));

        $childOptions = array(
            'childrenAttributes' => array('class' => 'nav nav-list'),
            'labelAttributes'    => array('class' => 'nav-header')
        );

        $child = $menu->addChild($this->translate('sylius.account.title'), $childOptions);

        $child->addChild('account', array(
            'route' => 'sylius_account_homepage',
            'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.account.homepage')),
            'labelAttributes' => array('icon' => 'icon-home', 'iconOnly' => false)
        ))->setLabel($this->translate('sylius.frontend.menu.account.homepage'));

        $child->addChild('profile', array(
            'route' => 'fos_user_profile_edit',
            'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.account.profile')),
            'labelAttributes' => array('icon' => 'icon-info-sign', 'iconOnly' => false)
        ))->setLabel($this->translate('sylius.frontend.menu.account.profile'));

        $child->addChild('password', array(
            'route' => 'fos_user_change_password',
            'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.account.password')),
            'labelAttributes' => array('icon' => 'icon-lock', 'iconOnly' => false)
        ))->setLabel($this->translate('sylius.frontend.menu.account.password'));

        $child->addChild('orders', array(
            'route' => 'sylius_account_order_index',
            'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.account.orders')),
            'labelAttributes' => array('icon' => 'icon-briefcase', 'iconOnly' => false)
        ))->setLabel($this->translate('sylius.frontend.menu.account.orders'));

        $child->addChild('addresses', array(
            'route' => 'sylius_account_address_index',
            'linkAttributes' => array('title' => $this->translate('sylius.frontend.menu.account.addresses')),
            'labelAttributes' => array('icon' => 'icon-envelope', 'iconOnly' => false)
        ))->setLabel($this->translate('sylius.frontend.menu.account.addresses'));

        return $menu;
    }

    /**
     * Builds frontend block menu.
     *
     * @param Request $request
     *
     * @return ItemInterface
     */
    public function createBlockMenu(Request $request)
    {
        $menu = $this->factory->createItem('root', array(
            'childrenAttributes' => array(
                'class' => 'nav navbar-nav sylius-frontend-block'
            )
        ));

        // Noticias
        $menu->addChild('noticias', array(
            'route' => 'sylius_noticia_index',
            'linkAttributes' => array('title' => 'Noticias')
        ))->setLabel('Noticias');

        // Tienda
        $menu->addChild('tienda', array(
            'route' => 'sylius_page_showPage',
            'routeParameters' => array('id' => 'tienda'),
            'linkAttributes' => array('title' => 'Tienda')
        ))->setLabel('Tienda');

        // Tienda
        $menu->addChild('servicioTaller', array(
            'route' => 'sylius_page_showPage',
            'routeParameters' => array('id' => 'servicio-taller'),
            'linkAttributes' => array('title' => 'Servicio Taller')
        ))->setLabel('Servicio Taller');

        // Ocasión
        $child = $menu->addChild('ocasión', array(
            'uri' => '#',
            'linkAttributes' => array(
                'title' => 'Ocasión',
                'class' => 'dropdown-toggle',
                'data-toggle' => 'dropdown'
            ),
            'labelAttributes' => array('caret' => true),
            'childrenAttributes' => array('class' => 'dropdown-menu', 'role' => 'menu')
        ))->setLabel('Ocasión');
            // Bicicletas ocasión
            $child->addChild('bicicletasOcasion', array(
                'route' => 'sylius_page_showPage',
                'routeParameters' => array('id' => 'ocasion/bicicletas-ocasion/indice'),
                'linkAttributes' => array('title' => 'Bicicletas ocasión')
            ))->setLabel('Bicicletas Ocasión');
            // Componentes ocasión
            $child->addChild('componentesOcasion', array(
                'route' => 'sylius_page_showPage',
                'routeParameters' => array('id' => 'ocasion/componentes-ocasion/indice'),
                'linkAttributes' => array('title' => 'Componentes ocasión')
            ))->setLabel('Componentes Ocasión');

        // Ver y saber más
        $child = $menu->addChild('verYSaberMas', array(
            'uri' => '#',
            'linkAttributes' => array(
                'title' => 'Ver y saber más',
                'class' => 'dropdown-toggle',
                'data-toggle' => 'dropdown'
            ),
            'labelAttributes' => array('caret' => true),
            'childrenAttributes' => array('class' => 'dropdown-menu', 'role' => 'menu')
        ))->setLabel('Ver y saber más');
            // Merida Bikes
            $child->addChild('meridaBikes', array(
                'route' => 'sylius_page_showPage',
                'routeParameters' => array('id' => 'ver-y-saber-mas/merida-bikes'),
                'linkAttributes' => array('title' => '¿Qué es Merida Bikes?')
            ))->setLabel('¿Qué es Merida Bikes?');
            // Tienda MSD
            $child->addChild('tiendaMSD', array(
                'route' => 'sylius_page_showPage',
                'routeParameters' => array('id' => 'ver-y-saber-mas/tienda-msd'),
                'linkAttributes' => array('title' => 'Tienda MSD')
            ))->setLabel('Tienda MSD');

        // Enlaces
        $menu->addChild('enlaces', array(
            'route' => 'sylius_page_showPage',
            'routeParameters' => array('id' => 'enlaces'),
            'linkAttributes' => array('title' => 'Enlaces'),
            'labelAttributes' => array('iconOnly' => false)
        ))->setLabel('Enlaces');

        return $menu;
    }

}
