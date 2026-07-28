# Plugin SDK (0.1.0 readiness)

Third-party packages extend VoodBuilder **without editing Core files**. Register from your ServiceProvider `boot()` / `packageBooted()`.

## Block

```php
use Voodflow\Voodbuilder\Voodbuilder;

Voodbuilder::grapesJsBlock(
    id: 'acme-hello',
    label: 'Acme Hello',
    category: 'Acme',
    content: '<div class="p-4">Hello</div>',
);
```

## Binding source

```php
use Voodflow\Voodbuilder\Contracts\GrapesJsBindingSource;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingField;

Voodbuilder::grapesJsBindingSource(new class implements GrapesJsBindingSource {
    public function id(): string { return 'acme.site'; }
    public function label(): string { return 'Acme site'; }
    public function package(): string { return 'acme'; }
    public function packageLabel(): string { return 'Acme'; }
    public function fields(): array {
        return [new BindingField('tagline', 'Tagline', BindingField::TYPE_TEXT)];
    }
    public function resolve(string $fieldId, BindingContext $context): ?string {
        return $fieldId === 'tagline' ? '…' : null;
    }
});
```

## Condition

```php
Voodbuilder::grapesJsCondition('acme_feature_flag', function (array $condition, $page): bool {
    return ($condition['value'] ?? null) === 'on';
});
```

## Editor labels

```php
Voodbuilder::grapesJsEditorLabels(fn (): array => [
    'acmeHello' => __('acme::ui.hello'),
]);
```

Popup UI strings are owned by `voodflow/voodbuilder-popups` (`voodbuilder-popups::popups.*`).

## Capabilities

```php
Voodbuilder::can('dynamic-data.collections');
```

Gate premium surfaces with entitlements; do not hard-code plan names in plugins.

## Sample

`tests/Fixtures/SamplePlugin/SampleAcmePlugin.php` + `tests/Architecture/SampleThirdPartyPluginTest.php`.

## Asset schemas (templates / components)

JSON payloads for import/export remain package-internal until marketplace Phase. Prefer documenting additive keys only.
