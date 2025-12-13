<?php

require 'vendor/autoload.php';

use App\Enums\ProductLifecycleStage;
use App\Enums\ProductStatus;

echo "Testing enums...\n";

try {
    $status = ProductStatus::DRAFT;
    echo "ProductStatus::DRAFT = " . $status->value . "\n";
    
    $lifecycle = ProductLifecycleStage::RELEASED;
    echo "ProductLifecycleStage::RELEASED = " . $lifecycle->value . "\n";
    
    echo "Enums work correctly!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}