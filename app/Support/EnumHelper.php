<?php

namespace App\Support;

use BackedEnum;

class EnumHelper
{

  public static function values(string $enumClass): array
  {
    return array_map(
      fn(BackedEnum $case) => $case->value,
      $enumClass::cases()
    );
  }


  public static function names(string $enumClass): array
  {
    return array_map(
      fn(BackedEnum $case) => $case->name,
      $enumClass::cases()
    );
  }
}
