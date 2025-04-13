<?php

namespace App\Tests\Validator;

use App\Validator\Phone;
use App\Validator\PhoneValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class PhoneValidatorTest extends TestCase
{
    private PhoneValidator $validator;
    private MockObject&ExecutionContextInterface $context;
    private MockObject&ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->validator = new PhoneValidator();

        // Create mock objects using createMock()
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);

        // Set up the violation builder mock
        $this->violationBuilder->method('addViolation')
            ->willReturn(null);

        // Set up the context mock
        $this->context->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->initialize($this->context);
    }

    #[DataProvider('validPhoneNumbersProvider')]
    public function testValidPhoneNumbers(string $phoneNumber): void
    {
        $constraint = new Phone();

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($phoneNumber, $constraint);
    }

    #[DataProvider('invalidPhoneNumbersProvider')]
    public function testInvalidPhoneNumbers(mixed $phoneNumber): void
    {
        $constraint = new Phone();

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($this->violationBuilder);

        $this->validator->validate($phoneNumber, $constraint);
    }

    public static function validPhoneNumbersProvider(): array
    {
        return [
            ['0651234567'],    // 10-digit mobile number
            ['065123456'],     // 9-digit mobile number
            ['0111234567'],    // Belgrade landline
            ['0654545656'],    // From fixtures
        ];
    }

    public static function invalidPhoneNumbersProvider(): array
    {
        return [
            ['06512'],        // Too short
            ['0651234567890'], // Too long
            ['1651234567'],   // Doesn't start with 0
            ['065abcdefg'],   // Contains letters
            [''],             // Empty string
            [12345],         // Not a string
            ['0651234 56'],  // Contains space
            ['065-123456'],  // Contains dash
            ['+381651234567'], // International format not allowed
        ];
    }

    public function testNullIsValid(): void
    {
        $constraint = new Phone();

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(null, $constraint);
    }
}
