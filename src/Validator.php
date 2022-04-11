<?php

declare(strict_types=1);

namespace Fretelweb\PhpValidator;

final class Validator
{

   private const DEFAULT_VALIDATION_ERRORS = [
      'required'     => 'El campo %s es requerido',
      'email'        => 'El campo %s no es un correo electrónico válido',
      'min'          => 'El campo %s debe contener al menos %s caracteres',
      'max'          => 'El campo %s debe ser menor que %s caracteres',
      'between'      => 'El campo %s debe estar entre %d y %d caracteres',
      'same'         => 'El campo %s debe ser igual a %s',
      'alphanumeric' => 'El campo %s debe contener solo números y letras',
      'secure'       => 'El campo %s debe tener entre 8 y 64 caracteres, contener al menos un carácter, una letra mayúscula, una letra minúscula y un carácter espacial',
      'unique'       => 'El campo %s ya se encuentra registrado',
   ];
   /**
    * @var array
    */

   public static function Make($data, $rules, $message = []): Validator
   {
      return new Validator($data, $rules, $message);
   }

   private $data;
   private $rules;
   private $messages;
   private $errors = [];
   private $isValidated = false;
   private $dataValidated;

   public function __construct($data, $rules, $messages = [])
   {
      $this->data     = $data;
      $this->rules    = $rules;
      $this->messages = $messages;
   }

   public function validated():?array
   {
      if ( ! $this->isValidated) {
         $this->validate();
      }
      return $this->dataValidated ;
   }

   public function validate(): bool
   {
      if ($this->isValidated) return empty($this->errors);

      $this->isValidated = true;
      $validation_errors = array_merge(self::DEFAULT_VALIDATION_ERRORS, $this->getMessages());
      $this->errors      = [];

      foreach ($this->rules as $field => $options) {
         $rules = $this->splitRule($options, '|');
         foreach ($rules as $rule) {
            $params = [];
            if (strpos($rule, ':')) {
               [$rule_name, $param_str] = $this->splitRule($rule, ':');
               $params = $this->splitRule($param_str, ",");
            } else {
               $rule_name = trim($rule);
            }

            $fn = 'is_' . $rule_name;

            if (method_exists($this, $fn)) {
               $pass = $this->{$fn}($this->data, $field, ...$params);
               if ( ! $pass) {
                  $this->errors[$field] = sprintf($this->messages[$field][$rule_name] ?? $validation_errors[$rule_name], $field, ...$params);
               }
            }
         }
         if (empty($this->errors[$field])) {
            $this->dataValidated[$field] = $this->data[$field];
         }
      }
      return empty($this->errors);
   }

   public function errors(): array
   {
      return $this->errors;
   }

   /**
    * Return true if a string is not empty
    *
    * @param array  $data
    * @param string $field
    *
    * @return bool
    */
   private function is_required(array $data, string $field): bool
   {
      return isset($data[$field]) && trim($data[$field]) !== '';
   }

   /**
    * Return true if the value is a valid email
    *
    * @param array  $data
    * @param string $field
    *
    * @return bool
    */
   private function is_email(array $data, string $field): bool
   {
      if (empty($data[$field])) {
         return true;
      }

      return filter_var($data[$field], FILTER_VALIDATE_EMAIL);
   }

   /**
    * Return true if a string has at least min length
    *
    * @param array  $data
    * @param string $field
    * @param int    $min
    *
    * @return bool
    */
   private function is_min(array $data, string $field, int $min): bool
   {
      if ( ! isset($data[$field])) {
         return true;
      }

      return mb_strlen($data[$field]) >= $min;
   }

   /**
    * Return true if a string cannot exceed max length
    *
    * @param array  $data
    * @param string $field
    * @param int    $max
    *
    * @return bool
    */
   private function is_max(array $data, string $field, int $max): bool
   {
      if ( ! isset($data[$field])) {
         return true;
      }

      return mb_strlen($data[$field]) <= $max;
   }

   /**
    * @param array  $data
    * @param string $field
    * @param int    $min
    * @param int    $max
    *
    * @return bool
    */
   private function is_between(array $data, string $field, int $min, int $max): bool
   {
      if ( ! isset($data[$field])) {
         return true;
      }

      $len = mb_strlen($data[$field]);
      return $len >= $min && $len <= $max;
   }

   /**
    * Return true if a string equals the other
    *
    * @param array  $data
    * @param string $field
    * @param string $other
    *
    * @return bool
    */
   private function is_same(array $data, string $field, string $other): bool
   {
      if (isset($data[$field], $data[$other])) {
         return $data[$field] === $data[$other];
      }

      if ( ! isset($data[$field]) && ! isset($data[$other])) {
         return true;
      }

      return false;
   }

   /**
    * Return true if a string is alphanumeric
    *
    * @param array  $data
    * @param string $field
    *
    * @return bool
    */
   private function is_alphanumeric(array $data, string $field): bool
   {
      if ( ! isset($data[$field])) {
         return true;
      }

      return ctype_alnum($data[$field]);
   }

   /**
    * Return true if a password is secure
    *
    * @param array  $data
    * @param string $field
    *
    * @return bool
    */
   private function is_secure(array $data, string $field): bool
   {
      if ( ! isset($data[$field])) {
         return false;
      }

      $pattern = "#.*^(?=.{8,64})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*\W).*$#";
      return preg_match($pattern, $data[$field]);
   }


   /**
    * Return true if the $value is unique in the column of a table
    *
    * @param array  $data
    * @param string $field
    * @param string $table
    * @param string $column
    *
    */
   private function is_unique(array $data, string $field, string $table, string $column)
   {
      //  if (!isset($data[$field])) {
      //      return true;
      //  }

      //  $sql = "SELECT $column FROM $table WHERE $column = :value";

      //  $stmt = db()->prepare($sql);
      //  $stmt->bindValue(":value", $data[$field]);

      //  $stmt->execute();

      //  return $stmt->fetchColumn() === false;
   }


   private function splitRule($rule, $separator): array
   {
      return array_map('trim', explode($separator, $rule));
   }

   private function getMessages(): array
   {
      return array_filter($this->messages, static function ($message) {
         return is_string($message);
      });
   }


   /**
    * Connect to the database and returns an instance of PDO class
    * or false if the connection fails
    *
    * @return PDO
    */
   // function db(): PDO
   // {
   //     static $pdo;
   //     // if the connection is not initialized
   //     // connect to the database
   //     if (!$pdo) {
   //         return new PDO(
   //             sprintf("mysql:host=%s;dbname=%s;charset=UTF8", DB_HOST, DB_NAME),
   //             DB_USER,
   //             DB_PASSWORD,
   //             [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
   //         );
   //     }
   //     return $pdo;
   // }

}
