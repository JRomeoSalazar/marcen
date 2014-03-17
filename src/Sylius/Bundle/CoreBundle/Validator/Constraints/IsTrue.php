<?php
// src/Sylius/Bundle/CoreBundle/Validator/Constraints/IsTrue.php
namespace Sylius\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsTrue extends Constraint
{
    public $message = 'sylius.user.disclaimer.is_true';
}