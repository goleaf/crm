<?php

declare(strict_types=1);

use App\Support\Helpers\StringHelper;
use Illuminate\Support\HtmlString;

it('wraps long words with HTML breaks', function (): void {
    $wrapped = StringHelper::wordWrap(
        value: 'Supercalifragilisticexpialidocious',
        characters: 10,
        break: '<br>',
        cutLongWords: true,
    );

    expect($wrapped)
        ->toBeInstanceOf(HtmlString::class)
        ->and($wrapped->toHtml())->toBe('Supercalif<br>ragilistic<br>expialidoc<br>ious');
});

it('returns the placeholder when value is empty', function (): void {
    expect(StringHelper::wordWrap(null))->toBe('—')
        ->and(StringHelper::wordWrap('   ', emptyPlaceholder: null))->toBeNull();
});

it('escapes HTML input by default', function (): void {
    $wrapped = StringHelper::wordWrap(
        value: '<b>bold</b> content',
        characters: 50,
        break: '<br>',
    );

    expect($wrapped)
        ->toBeInstanceOf(HtmlString::class)
        ->and($wrapped->toHtml())->toBe('&lt;b&gt;bold&lt;/b&gt; content');
});
