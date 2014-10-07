<?php
// src/Sylius/Bundle/CoreBundle/Form/Type/NoticiaType.php
namespace Sylius\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class NoticiaType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('titulo', null, array('label' => 'sylius.form.noticia.titulo'))
                ->add('contenido', 'textarea', array(
                    'label' => 'sylius.form.noticia.contenido',
                    'attr' => array('class' => 'tinymce')
                ));
    }
    
    public function getName()
    {
        return 'sylius_noticia';
    }
}