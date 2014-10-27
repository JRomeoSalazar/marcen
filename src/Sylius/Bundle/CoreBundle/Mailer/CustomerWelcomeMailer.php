<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\CoreBundle\Mailer;

use Sylius\Component\Core\Model\UserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * CustomerWelcomeMailer implementation
 *
 * @author Daniel Richter <nexyz9@gmail.com>
 */
class CustomerWelcomeMailer extends AbstractMailer implements CustomerWelcomeMailerInterface
{
	/**
	 * @var UrlGeneratorInterface
	 */
    protected $router;

    /**
     * Constructor.
     *
     * @param UrlGeneratorInterface $router
     */
    public function __construct(UrlGeneratorInterface $router, TwigMailerInterface $mailer, array $parameters)
    {
        $this->router = $router;

        parent::__construct($mailer, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function sendCustomerWelcome(UserInterface $user)
    {	
    	$linkNoticias = $this->router->generate('sylius_noticia_index', null, true);

        $this->sendEmail(array('user' => $user, 'linkNoticias' => $linkNoticias), array($user->getEmail() => $user->getFirstName()));
    }
}
