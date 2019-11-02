<?php declare(strict_types = 1);

namespace Anezi\Validator\Test;

use PHPUnit\Framework\Assert;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ConstraintViolationAssertion
{
    /**
     * @var ExecutionContextInterface
     */
    private $context;

    /**
     * @var ConstraintViolationAssertion[]
     */
    private $assertions;

    private $message;
    private $parameters = [];
    private $invalidValue = 'InvalidValue';
    private $propertyPath = 'property.path';
    private $plural;
    private $code;
    private $constraint;
    private $cause;

    public function __construct(
        ExecutionContextInterface $context,
        $message,
        Constraint $constraint = null,
        array $assertions = []
    ) {
        $this->context = $context;
        $this->message = $message;
        $this->constraint = $constraint;
        $this->assertions = $assertions;
    }

    public function setParameter($key, $value): ConstraintViolationAssertion
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    public function setCode($code): ConstraintViolationAssertion
    {
        $this->code = $code;

        return $this;
    }

    public function assertRaised(): void
    {
        $expected = [];
        foreach ($this->assertions as $assertion) {
            $expected[] = $assertion->getViolation();
        }
        $expected[] = $this->getViolation();

        $violations = iterator_to_array($this->context->getViolations());

        Assert::assertSame(
            $expectedCount = \count($expected),
            $violationsCount = \count($violations),
            sprintf('%u violation(s) expected. Got %u.', $expectedCount, $violationsCount)
        );

        reset($violations);

        foreach ($expected as $violation) {
            Assert::assertEquals($violation, current($violations));
            next($violations);
        }
    }

    private function getViolation(): ConstraintViolation
    {
        return new ConstraintViolation(
            null,
            $this->message,
            $this->parameters,
            $this->context->getRoot(),
            $this->propertyPath,
            $this->invalidValue,
            $this->plural,
            $this->code,
            $this->constraint,
            $this->cause
        );
    }
}
