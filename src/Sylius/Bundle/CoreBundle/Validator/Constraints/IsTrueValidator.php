<?php
// src/Sylius/Bundle/CoreBundle/Validator/Constraints/ContainsAlphanumericValidator.php
namespace Sylius\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsTrueValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$value) {
            $this->context->addViolation(
                $constraint->message
            );
        }
    }
}