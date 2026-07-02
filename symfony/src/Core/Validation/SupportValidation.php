<?php

namespace App\Core\Validation;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Provides validation support via constructor injection.
 */
trait SupportValidation
{
    private ValidatorInterface $validator;

    #[Required]
    public function setValidator(
        ValidatorInterface $validator,
    ): void {
        $this->validator = $validator;
    }


    protected function validate(object $object, array $validationGroups = []): void
    {
        $errors = $this->validator->validate($object, groups: $validationGroups);

        if ($errors->count() > 0) {
            throw new ValidatorException($this->createErrorMessage($errors), Response::HTTP_BAD_REQUEST);
        }
    }

    private function createErrorMessage(
        ConstraintViolationListInterface $violations,
    ): string {
        $errors = [];

        /** @var ConstraintViolation $violation */
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return (string)json_encode($errors);
    }
}
