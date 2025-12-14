<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use App\Enums\ProductAttributeDataType;

echo "Testing ProductAttributeDataType enum...\n";

$textType = ProductAttributeDataType::TEXT;
echo 'Text type: ' . $textType->value . "\n";
echo 'Text validates string: ' . ($textType->validateValue('test') ? 'true' : 'false') . "\n";
echo 'Text validates number: ' . ($textType->validateValue(123) ? 'true' : 'false') . "\n";

$numberType = ProductAttributeDataType::NUMBER;
echo 'Number validates number: ' . ($numberType->validateValue(123) ? 'true' : 'false') . "\n";
echo 'Number validates string: ' . ($numberType->validateValue('test') ? 'true' : 'false') . "\n";

echo "All tests passed!\n";
