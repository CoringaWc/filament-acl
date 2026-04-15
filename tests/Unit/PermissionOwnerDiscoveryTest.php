<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use ReflectionMethod;

class PermissionOwnerDiscoveryTest extends TestCase
{
    public function test_resolve_widget_label_uses_widget_heading_from_instance(): void
    {
        $discovery = app(PermissionOwnerDiscovery::class);
        $method = new ReflectionMethod(PermissionOwnerDiscovery::class, 'resolveWidgetLabel');

        $label = $method->invoke($discovery, FakeHeadingWidget::class);

        self::assertSame('Fake Heading Widget', $label);
    }

    public function test_resolve_widget_label_strips_htmlable_headings(): void
    {
        $discovery = app(PermissionOwnerDiscovery::class);
        $method = new ReflectionMethod(PermissionOwnerDiscovery::class, 'resolveWidgetLabel');

        $label = $method->invoke($discovery, FakeHtmlableHeadingWidget::class);

        self::assertSame('HTML Heading Widget', $label);
    }
}

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
