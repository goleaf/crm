<?php

declare(strict_types=1);

namespace App\Filament\Widgets\System;

use App\Filament\Components\MinimalTabs;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * MinimalTabs Performance Monitoring Widget
 * 
 * Displays real-time performance metrics for the MinimalTabs component.
 */
class MinimalTabsPerformanceWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        return [
            Stat::make('Component Status', $this->getComponentStatus())
                ->description('Filament v4.3+ compatibility')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Performance Score', $this->getPerformanceScore())
                ->description('Based on benchmark tests')
                ->descriptionIcon('heroicon-m-bolt')
                ->color($this->getPerformanceColor()),
                
            Stat::make('Memory Efficiency', $this->getMemoryEfficiency())
                ->description('Memory usage optimization')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('info'),
        ];
    }
    
    private function getComponentStatus(): string
    {
        $reflection = new \ReflectionClass(MinimalTabs::class);
        $parentClass = $reflection->getParentClass();
        
        return $parentClass->getName() === 'Filament\Schemas\Components\Tabs' 
            ? 'Compatible' 
            : 'Needs Update';
    }
    
    private function getPerformanceScore(): string
    {
        // Run quick performance test
        $startTime = microtime(true);
        
        $tabs = MinimalTabs::make('Benchmark')
            ->minimal()
            ->compact()
            ->minimal(false)
            ->minimal();
            
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to ms
        
        if ($executionTime < 1) {
            return 'Excellent';
        } elseif ($executionTime < 5) {
            return 'Good';
        } elseif ($executionTime < 10) {
            return 'Fair';
        } else {
            return 'Needs Optimization';
        }
    }
    
    private function getPerformanceColor(): string
    {
        $score = $this->getPerformanceScore();
        
        return match ($score) {
            'Excellent' => 'success',
            'Good' => 'info',
            'Fair' => 'warning',
            'Needs Optimization' => 'danger',
            default => 'gray',
        };
    }
    
    private function getMemoryEfficiency(): string
    {
        $initialMemory = memory_get_usage();
        
        $tabs = MinimalTabs::make('Memory Test');
        for ($i = 0; $i < 100; $i++) {
            $tabs->minimal($i % 2 === 0);
        }
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024; // KB
        
        if ($memoryIncrease < 1) {
            return 'Optimal';
        } elseif ($memoryIncrease < 5) {
            return 'Good';
        } elseif ($memoryIncrease < 10) {
            return 'Fair';
        } else {
            return 'High Usage';
        }
    }
    
    public static function canView(): bool
    {
        return auth()->user()?->can('view_system_performance') ?? false;
    }
}