<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

test('resolve widget label uses widget heading from instance', function (): void {
    /** @var TestCase $this */
    $discovery = app(PermissionOwnerDiscovery::class);
    $method = new ReflectionMethod(PermissionOwnerDiscovery::class, 'resolveWidgetLabel');

    $label = $method->invoke($discovery, FakeHeadingWidget::class);

    expect($label)->toBe('Fake Heading Widget');
});

test('resolve widget label strips htmlable headings', function (): void {
    /** @var TestCase $this */
    $discovery = app(PermissionOwnerDiscovery::class);
    $method = new ReflectionMethod(PermissionOwnerDiscovery::class, 'resolveWidgetLabel');

    $label = $method->invoke($discovery, FakeHtmlableHeadingWidget::class);

    expect($label)->toBe('HTML Heading Widget');
});

class FakeHeadingWidget extends ChartWidget
{
    public function getHeading(): string
    {
        return 'Fake Heading Widget';
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

class FakeHtmlableHeadingWidget extends ChartWidget
{
    public function getHeading(): Htmlable
    {
        return new class implements Htmlable
        {
            public function toHtml(): string
            {
                return '<strong>HTML Heading Widget</strong>';
            }
        };
    }

    protected function getType(): string
    {
        return 'bar';

    }
}
