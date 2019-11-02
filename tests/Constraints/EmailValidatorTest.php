<?php declare(strict_types = 1);

namespace Anezi\Validator\Test\Constraints;

use Anezi\Validator\Constraints\Email;
use Anezi\Validator\Constraints\EmailValidator;
use Anezi\Validator\Test\ConstraintViolationAssertion;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EmailValidatorTest extends TestCase
{
    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    /**
     * @var ConstraintValidatorInterface
     */
    protected $validator;

    protected $group;
    protected $metadata;
    protected $object;
    protected $value;
    protected $root;
    protected $propertyPath;
    protected $constraint;
    protected $defaultTimezone;

    public function setUp()
    {
        $this->group = 'MyGroup';
        $this->metadata = null;
        $this->object = null;
        $this->value = 'InvalidValue';
        $this->root = 'root';
        $this->propertyPath = 'property.path';

        // Initialize the context with some constraint so that we can
        // successfully build a violation.
        $this->constraint = new NotNull();

        $this->context = $this->createContext();
        $this->validator = new EmailValidator(false);
        $this->validator->initialize($this->context);

        $this->setDefaultTimezone('UTC');
    }

    protected function createContext(): ExecutionContext
    {
        if (interface_exists('\Symfony\Component\Translation\TranslatorInterface')) {
            $translatorInterface = '\Symfony\Component\Translation\TranslatorInterface';
        } else {
            $translatorInterface = '\Symfony\Contracts\Translation\TranslatorInterface';
        }

        $translator = $this->getMockBuilder($translatorInterface)->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ValidatorInterface $validator */
        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();

        $contextualValidator = $this->getMockBuilder(ContextualValidatorInterface::class)->getMock();

        $context = new ExecutionContext($validator, $this->root, $translator);
        $context->setGroup($this->group);
        $context->setNode($this->value, $this->object, $this->metadata, $this->propertyPath);
        $context->setConstraint($this->constraint);

        $validator
            ->method('inContext')
            ->with($context)
            ->willReturn($contextualValidator);

        return $context;
    }

    protected function setDefaultTimezone($defaultTimezone): void
    {
        // Make sure this method can not be called twice before calling
        // also restoreDefaultTimezone()
        if (null === $this->defaultTimezone) {
            $this->defaultTimezone = date_default_timezone_get();
            date_default_timezone_set($defaultTimezone);
        }
    }

    protected function assertNoViolation(): void
    {
        $this->assertSame(
            0,
            $violationsCount = \count($this->context->getViolations()),
            sprintf('0 violation expected. Got %u.', $violationsCount)
        );
    }

    /**
     * @param string $message
     *
     * @return ConstraintViolationAssertion
     */
    protected function buildViolation(string $message): ConstraintViolationAssertion
    {
        return new ConstraintViolationAssertion($this->context, $message, $this->constraint);
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new Email());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new Email());

        $this->assertNoViolation();
    }

    public function testValidEmails(): void
    {
        $this->validator->validate('foo@60minutemail.com', new Email());

        $this->assertNoViolation();
    }

    public function testDefaultValue(): void
    {
        $this->validator->validate('hassan@anezi.net', new Email(['checkThrowaway' => null]));

        $this->assertNoViolation();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('hassan@anezi.net', new Url());
    }

    public function testInvalidEmail(): void
    {
        $this->validator->validate('foo@60minutemail.com', new Email(['checkThrowaway' => true]));

        $this
            ->buildViolation('This value is not a valid email address.')
            ->setParameter('{{ value }}', '"foo@60minutemail.com"')
            ->setCode(Email::THROWAWAY_CHECK_FAILED_ERROR)
            ->assertRaised();
    }
}
