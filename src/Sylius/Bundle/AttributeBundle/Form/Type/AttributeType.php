<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\AttributeBundle\Form\Type;

use Sylius\Bundle\AttributeBundle\Form\EventListener\BuildAttributeFormChoicesListener;
use Sylius\Component\Attribute\Model\AttributeTypes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Attribute type.
 *
 * @author Paweł Jędrzejewski <pjedrzejewski@diweb.pl>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class AttributeType extends AbstractType
{
    /**
     * Subject name.
     *
     * @var string
     */
    protected $subjectName;

    /**
     * Data class.
     *
     * @var string
     */
    protected $dataClass;

    /**
     * Validation groups.
     *
     * @var array
     */
    protected $validationGroups;

    /**
     * Translator
     * 
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Constructor.
     *
     * @param string               $subjectName
     * @param string               $dataClass
     * @param array                $validationGroups
     * @param TranslatorInterface  $translator
     */
    public function __construct($subjectName, $dataClass, array $validationGroups, TranslatorInterface $translator)
    {
        $this->subjectName = $subjectName;
        $this->dataClass = $dataClass;
        $this->validationGroups = $validationGroups;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $labels = array();
        foreach (AttributeTypes::getChoices() as $label) {
            $labels[] = $this->translator->trans(('sylius.form.attribute.type.'.$label));
        }

        $builder
            ->add('name', 'text', array(
                'label' => 'sylius.form.attribute.name'
            ))
            ->add('presentation', 'text', array(
                'label' => 'sylius.form.attribute.presentation'
            ))
            ->add('type', 'choice', array(
                'choices' => $labels,
                'label' => 'sylius.form.attribute.type.0'
            ))
            ->addEventSubscriber(new BuildAttributeFormChoicesListener($builder->getFormFactory()))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(array(
                'data_class'        => $this->dataClass,
                'validation_groups' => $this->validationGroups
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return sprintf('sylius_%s_attribute', $this->subjectName);
    }
}
