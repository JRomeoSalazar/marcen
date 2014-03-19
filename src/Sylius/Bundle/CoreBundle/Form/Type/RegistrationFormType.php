<?php

/*
* This file is part of the Sylius package.
*
* (c) Paweł Jędrzejewski
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Sylius\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Cmf\Component\Routing\ChainRouter as Router;
use Symfony\Component\Translation\TranslatorInterface;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;

class RegistrationFormType extends BaseType
{
    protected $translator;
    protected $router;

    public function __construct($class, TranslatorInterface $translator, Router $router)
    {
        $this->translator = $translator;
        $this->router = $router;
        parent::__construct($class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstName', 'text', array('label' => 'sylius.form.user.first_name'));
        $builder->add('lastName', 'text', array('label' => 'sylius.form.user.last_name'));

        parent::buildForm($builder, $options);

        // disclaimer label
        $disclaimer_text = $this->translator->trans('sylius.form.user.disclaimer.self');
        $href = $this->router->generate('sylius_disclaimer');
        $aviso = '<a href="'.$href.'" title="'.$disclaimer_text.'" target="_blank">'.$disclaimer_text.'</a>';
        $label = $this->translator->trans('sylius.form.user.disclaimer.label', array('%aviso%' => $aviso));

        $builder->add('disclaimer', 'checkbox', array('label' => $label));

        // remove the username field
        $builder->remove('username');
    }

    public function getName()
    {
        return 'sylius_user_registration';
    }
}
