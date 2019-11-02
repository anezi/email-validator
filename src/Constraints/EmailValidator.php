<?php declare(strict_types=1);

namespace Anezi\Validator\Constraints;

use EmailChecker\Adapter\BuiltInAdapter;
use EmailChecker\EmailChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\EmailValidator as BaseEmailValidator;
use Symfony\Component\Validator\Exception\RuntimeException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @author  Hassan Amouhzi <hassan@amouhzi.com>
 * @license Proprietary See License file.
 */
class EmailValidator extends BaseEmailValidator
{
    private $checkThrowaway;

    public function __construct($strict = false, $checkThrowaway = true)
    {
        parent::__construct($strict);

        $this->checkThrowaway = $checkThrowaway;
    }

    /**
     * {@inheritdoc}
     * @throws UnexpectedTypeException If constraint is not an Email.
     * @throws RuntimeException
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Email) {
            throw new UnexpectedTypeException($constraint, Email::class);
        }

        $oldCount = count($this->context->getViolations());
        parent::validate($value, $constraint);
        $newCount = count($this->context->getViolations());

        if (null === $constraint->checkThrowaway) {
            $constraint->checkThrowaway = $this->checkThrowaway;
        }

        if ($oldCount === $newCount && $constraint->checkThrowaway) {
            $this->checkThrowaway($value, $constraint);
        }
    }

    /**
     * @param string $value
     * @param Email  $constraint
     *
     * @throws RuntimeException
     */
    private function checkThrowaway($value, Email $constraint): void
    {
        if (!class_exists(EmailChecker::class)) {
            throw new RuntimeException('Disposable email detection requires mattketmo/email-checker');
        }

        $emailValidator = new EmailChecker(new BuiltInAdapter());

        if (!$emailValidator->isValid($value)) {
            /** @var ConstraintViolationBuilderInterface $violation */
            $violation = $this->context->buildViolation($constraint->message);

            $violation
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Email::THROWAWAY_CHECK_FAILED_ERROR)
                ->addViolation();
        }
    }
}
