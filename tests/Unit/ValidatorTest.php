<?php

namespace Tests\Unit;

use Fretelweb\PhpValidator\Validator;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertIsArray;

require __DIR__ . '/../../vendor/autoload.php';

final class ValidatorTest extends TestCase
{


   public function testValidatorFail(): void
   {
      $data = [
         'nombre'   => '',
         'apellido' => '',
         'email'    => '',
      ];

      $v   = Validator::Make($data, [
         'nombre'   => 'required',
         'apellido' => 'required',
         'email'    => 'required|email',
      ]);
      $res = $v->validated();
      self::assertNull($res);
      $errors = $v->errors();
      self::assertArrayHasKey('nombre', $errors);
      self::assertArrayHasKey('apellido', $errors);
      self::assertArrayHasKey('email', $errors);
   }

   public function testValidatorPass(): void
   {
      $data = [
         'nombre'   => 'ronny',
         'apellido' => 'fretel',
         'email'    => 'rfretel',
      ];


      $v   = Validator::Make($data, [
         'nombre'   => 'required',
         'apellido' => 'required',
         'email'    => 'required|email',
      ]);
      $res = $v->validated();
      self::assertIsArray($res);
   }
}
