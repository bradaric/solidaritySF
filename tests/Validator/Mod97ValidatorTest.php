<?php

namespace App\Tests\Validator;

use App\Validator\Mod97;
use App\Validator\Mod97Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class Mod97ValidatorTest extends TestCase
{
    private Mod97Validator $validator;
    private MockObject&ExecutionContextInterface $context;
    private MockObject&ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->validator = new Mod97Validator();

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

    #[DataProvider('validAccountNumbersProvider')]
    public function testValidAccountNumbers(string $accountNumber): void
    {
        $constraint = new Mod97();

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($accountNumber, $constraint);
    }

    #[DataProvider('invalidAccountNumbersProvider')]
    public function testInvalidAccountNumbers(string $accountNumber): void
    {
        $constraint = new Mod97();

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($this->violationBuilder);

        $this->validator->validate($accountNumber, $constraint);
    }

    public static function validAccountNumbersProvider(): array
    {
        return [
            ['265104031000361092'],
            ['265641031000363827'],
            ['325950050022911790'],
            ['150000002501288698'],
        ];
    }

    public static function invalidAccountNumbersProvider(): array
    {
        return [
            ['265104031000361093'],  // Change last digit of valid number
            ['265641031000363821'],  // Change last digit of valid number
            [''], // Empty string
        ];
    }

    public function testNullIsValid(): void
    {
        $constraint = new Mod97();

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(null, $constraint);
    }
}
