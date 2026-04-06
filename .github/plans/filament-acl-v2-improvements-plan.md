# filament-acl v2 Improvements — Plano de Implementação Faseado

> **Pacote:** `coringawc/filament-acl` (branch `5.x`)  
> **Stack:** PHP 8.4, Laravel 12, Filament v5, Livewire v4, Spatie Laravel Permission v7  
> **Baseline:** 70/70 testes passando  
> **Última atualização:** 2026-04-05

---

## Decisões Confirmadas

| # | Decisão | Valor |
|---|---------|-------|
| 1 | Toggle-All scope | Por **Section** inteira (Alpine.js no Section header) |
| 2 | Subject namespace strategy | `basename` (default, backward-compatible), `fqcn`, `custom` |
| 3 | Policy commands | Ambos: `make-policy` (unitário) + `generate-policies` (batch) |
| 4 | Workbench policies | Todos os models: Category, Comment, User, Post (Post já existe) |

---

## Fase 0 — Inventário Completo

### Arquivos NOVOS (28 arquivos)

#### Stubs (2)
- `stubs/policy.stub` — template stub para policy gerada
- `stubs/policy.plain.stub` — template stub sem ChecksPermission (futuro, opcional — **SKIP por agora**)

#### Commands (2)
- `src/Commands/MakePolicyCommand.php` — gera UMA policy para um model/resource
- `src/Commands/GeneratePoliciesCommand.php` — gera policies batch para todos os resources de um panel

#### Support (1)
- `src/Support/PolicyGenerator.php` — classe de geração de policy (shared entre ambos os commands)

#### Enums (1)
- `src/Enums/SubjectResolutionStrategy.php` — `Basename`, `Fqcn`, `Custom`

#### Workbench Policies (2 novos)
- `workbench/app/Policies/CommentPolicy.php` — policy com ChecksPermission
- `workbench/app/Policies/UserPolicy.php` — **REESCREVER** para usar ChecksPermission

#### Tests (8)
- `tests/Feature/Commands/MakePolicyCommandTest.php`
- `tests/Feature/Commands/GeneratePoliciesCommandTest.php`
- `tests/Unit/PolicyGeneratorTest.php`
- `tests/Unit/SubjectResolutionStrategyTest.php`
- `tests/Feature/SubjectResolutionStrategyIntegrationTest.php`
- `tests/Feature/Resources/SectionToggleTest.php`
- `tests/Unit/TranslationCompletenessTest.php`
- `tests/Feature/Workbench/PolicySmokeTest.php`

### Arquivos MODIFICADOS (12 arquivos) `[MODIFICAR]`

#### Config
- `config/filament-acl.php` `[MODIFICAR]` — adicionar `subject_resolver.strategy` key

#### Support
- `src/Support/ConfiguredPermissionSubjectResolver.php` `[MODIFICAR]` — respeitar strategy
- `src/Support/Utils.php` `[MODIFICAR]` — helper `getSubjectResolutionStrategy()`

#### Resources
- `src/Resources/Permissions/PermissionResource.php` `[MODIFICAR]` — Section toggle Alpine.js, melhorar `resolveOwnerLabel()`

#### Lang
- `resources/lang/en/filament-acl.php` `[MODIFICAR]` — adicionar labels faltantes
- `resources/lang/pt_BR/filament-acl.php` `[MODIFICAR]` — adicionar labels faltantes

#### Service Provider
- `src/FilamentPermissionServiceProvider.php` `[MODIFICAR]` — registrar novos commands

#### Workbench
- `workbench/app/Policies/UserPolicy.php` `[MODIFICAR]` — reescrever com ChecksPermission
- `workbench/database/seeders/PermissionSeeder.php` `[MODIFICAR]` — expandir com novos cenários
- `workbench/database/seeders/DatabaseSeeder.php` `[MODIFICAR]` — se necessário para novas policies

#### README
- `README.md` `[MODIFICAR]` — documentar novas features (commands, strategy, section toggle)

**Total: 28 novos + 12 modificados = 40 arquivos**

---

## Fase 1 — Traduções e Enum (sem dependências)

> **Objetivo:** Base linguística e enum de strategy prontos antes de qualquer lógica.

### 1.1 — SubjectResolutionStrategy Enum

**Arquivo:** `src/Enums/SubjectResolutionStrategy.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Enums;

enum SubjectResolutionStrategy: string
{
    case Basename = 'basename';
    case Fqcn = 'fqcn';
    case Custom = 'custom';
}
```

- Sem interfaces Filament (`HasLabel`, etc.) — enum interno, não exibido em UI
- Valores lowercase para config file ergonomics

### 1.2 — Expandir Traduções

**Arquivo:** `resources/lang/en/filament-acl.php` — adicionar ao array `permission_labels`:

```php
'permission_labels' => [
    // ...existing...
    'view_any' => 'View Any',
    'view' => 'View',
    'create' => 'Create',
    'update' => 'Update',
    'delete' => 'Delete',
    'delete_any' => 'Delete Any',
    'force_delete' => 'Force Delete',
    'force_delete_any' => 'Force Delete Any',
    'restore' => 'Restore',
    'restore_any' => 'Restore Any',
    'replicate' => 'Replicate',
    'reorder' => 'Reorder',
    'associate' => 'Associate',
    'attach' => 'Attach',
    'detach' => 'Detach',
    'detach_any' => 'Detach Any',
    'dissociate' => 'Dissociate',
    'dissociate_any' => 'Dissociate Any',
],
```

Adicionar também ao nível `resources.permissions`:

```php
'section_toggle' => [
    'select_all' => 'Select All',
    'deselect_all' => 'Deselect All',
],
```

**Arquivo:** `resources/lang/pt_BR/filament-acl.php` — equivalente pt_BR:

```php
'permission_labels' => [
    // ...existing...
    'view_any' => 'Ver todos',
    'view' => 'Visualizar',
    'create' => 'Criar',
    'update' => 'Atualizar',
    'delete' => 'Excluir',
    'delete_any' => 'Excluir em massa',
    'force_delete' => 'Excluir definitivamente',
    'force_delete_any' => 'Excluir definitivamente em massa',
    'restore' => 'Restaurar',
    'restore_any' => 'Restaurar em massa',
    'replicate' => 'Replicar',
    'reorder' => 'Reordenar',
    'associate' => 'Associar',
    'attach' => 'Anexar',
    'detach' => 'Desanexar',
    'detach_any' => 'Desanexar em massa',
    'dissociate' => 'Dissociar',
    'dissociate_any' => 'Dissociar em massa',
],
```

**Nota sobre `resolveAbilityLabel()`:** O método já busca `filament-acl::filament-acl.permission_labels.{ability}` usando a ability como key. Ele converte `viewAny` → procura `view_any` via `__()` (Laravel auto-lowercase com underscore?). 

**Verificação necessária:** Como `resolveAbilityLabel()` mapeia abilities camelCase para keys snake_case. Ler método atual:

```php
protected static function resolveAbilityLabel(string $ability): string
{
    $translationKey = "filament-acl::filament-acl.permission_labels.{$ability}";
    $translated = __($translationKey);
    // ...
}
```

Isso significa que a key no lang deve ser **camelCase** (exatamente como a ability) ou precisamos de uma conversão. Atualmente no lang temos `view_any` (snake_case) mas a ability é `viewAny` (camelCase).

**⚠️ DECISÃO CRÍTICA:** Verificar se o translate funciona com `permission_labels.viewAny` → `permission_labels.view_any`. Se NÃO funciona (o que é provável, pois PHP array keys são case-sensitive), então:

- **Opção A:** Mudar as keys do lang para camelCase: `'viewAny' => 'View Any'`
- **Opção B:** Adicionar conversão no `resolveAbilityLabel()`: `Str::snake($ability)`
- **Recomendação:** Opção B — é backward-compatible e resolve o problema raiz. Adicionar ao método:

```php
protected static function resolveAbilityLabel(string $ability): string
{
    // Try exact key first (backward-compatible)
    $translationKey = "filament-acl::filament-acl.permission_labels.{$ability}";
    $translated = __($translationKey);

    if ($translated !== $translationKey) {
        return $translated;
    }

    // Try snake_case key
    $snakeKey = "filament-acl::filament-acl.permission_labels." . Str::snake($ability);
    $snakeTranslated = __($snakeKey);

    if ($snakeTranslated !== $snakeKey) {
        return $snakeTranslated;
    }

    return Str::headline($ability);
}
```

### 1.3 — Config: Adicionar subject strategy

**Arquivo:** `config/filament-acl.php` — dentro do bloco de `subject_resolver` (atualmente é apenas a FQCN da classe):

**Estado atual:**
```php
'subject_resolver' => ConfiguredPermissionSubjectResolver::class,
```

**Novo formato:**
```php
'subject_resolver' => [
    'class' => ConfiguredPermissionSubjectResolver::class,
    'strategy' => 'basename', // 'basename' | 'fqcn' | 'custom'
],
```

**⚠️ BACKWARD COMPATIBILITY:** O binding em `FilamentPermissionServiceProvider` precisa aceitar ambos os formatos:
- String (FQCN legacy) → funciona como hoje
- Array com `class` + `strategy` → novo formato

### Smoke Test — Fase 1

```bash
docker compose exec php vendor/bin/phpunit --testdox --filter="SubjectResolutionStrategy|Translation"
docker compose exec php vendor/bin/phpstan analyse --memory-limit=1G src/Enums/SubjectResolutionStrategy.php resources/lang/
docker compose exec php vendor/bin/pint --dirty
```

---

## Fase 2 — Subject Resolution Strategy

> **Objetivo:** O `ConfiguredPermissionSubjectResolver` respeita a nova strategy. Todos os consumidores (sync, resource UI, key builder) produzem subjects consistentes.

### 2.1 — Utils: helper de strategy

**Arquivo:** `src/Support/Utils.php` `[MODIFICAR]`

Adicionar método:

```php
public static function getSubjectResolutionStrategy(): SubjectResolutionStrategy
{
    $config = config('filament-acl.subject_resolver');

    if (is_array($config)) {
        $strategy = $config['strategy'] ?? 'basename';
    } else {
        $strategy = 'basename';
    }

    return SubjectResolutionStrategy::tryFrom($strategy) ?? SubjectResolutionStrategy::Basename;
}

public static function getSubjectResolverClass(): string
{
    $config = config('filament-acl.subject_resolver');

    if (is_array($config)) {
        return $config['class'] ?? ConfiguredPermissionSubjectResolver::class;
    }

    if (is_string($config)) {
        return $config;
    }

    return ConfiguredPermissionSubjectResolver::class;
}
```

### 2.2 — ConfiguredPermissionSubjectResolver: respeitar strategy

**Arquivo:** `src/Support/ConfiguredPermissionSubjectResolver.php` `[MODIFICAR]`

Alterar `resolve()` para incluir strategy check **entre** o Manager callback e o `subject_overrides`:

```php
public function resolve(
    string $entityClass,
    PermissionEntityType $entityType,
    ?string $panelId = null,
    ?string $registrationKey = null,
    array $meta = [],
): string {
    $resolvedOwnerClass = Utils::resolvePermissionOwnerClass($entityClass);

    // 1. Owner method (highest priority)
    if (method_exists($resolvedOwnerClass, 'getPermissionSubject')) {
        $entitySubject = $resolvedOwnerClass::getPermissionSubject();
        if (is_string($entitySubject) && ($entitySubject !== '')) {
            return $entitySubject;
        }
    }

    // 2. Manager callback
    $resolvedSubject = $this->manager->resolvePermissionSubject(
        ownerClass: $entityClass,
        ownerType: $entityType,
        panelId: $panelId,
        registrationKey: $registrationKey,
        meta: $meta,
    );
    if (filled($resolvedSubject)) {
        return $resolvedSubject;
    }

    // 3. Config subject_overrides map
    $subjectOverride = config("filament-acl.subject_overrides.{$entityClass}");
    if (is_string($subjectOverride) && ($subjectOverride !== '')) {
        return $subjectOverride;
    }

    // 4. Strategy-based resolution
    $strategy = Utils::getSubjectResolutionStrategy();

    return match ($strategy) {
        SubjectResolutionStrategy::Fqcn => $resolvedOwnerClass,
        SubjectResolutionStrategy::Custom => throw new \RuntimeException(
            "Subject resolution strategy is 'custom' but no callback or subject_override is configured for [{$entityClass}]."
        ),
        default => Utils::defaultPermissionSubject(
            entityClass: $resolvedOwnerClass,
            entityType: $entityType,
            registrationKey: $registrationKey,
        ),
    };
}
```

**Lógica:**
- `basename` → comportamento atual, fallback idêntico ao existente
- `fqcn` → retorna FQCN completo do resolved owner class
- `custom` → exige callback ou subject_override; lança exceção se nenhum estiver configurado (fail-fast em dev)

### 2.3 — FilamentPermissionServiceProvider: backward-compatible binding

**Arquivo:** `src/FilamentPermissionServiceProvider.php` `[MODIFICAR]`

O binding do `ResolvesPermissionSubject` deve usar `Utils::getSubjectResolverClass()`:

```php
$this->app->singleton(
    ResolvesPermissionSubject::class,
    fn ($app) => $app->make(Utils::getSubjectResolverClass()),
);
```

### Smoke Test — Fase 2

```bash
docker compose exec php vendor/bin/phpunit --testdox --filter="ConfiguredPermissionSubjectResolver|SubjectResolution"
docker compose exec php vendor/bin/phpstan analyse --memory-limit=1G src/Support/ConfiguredPermissionSubjectResolver.php src/Support/Utils.php src/Enums/
docker compose exec php vendor/bin/pint --dirty
```

---

## Fase 3 — Policy Generation Commands

> **Objetivo:** Dois comandos que geram policies usando o trait `ChecksPermission` do pacote.

### 3.1 — Policy Stub

**Arquivo:** `stubs/policy.stub`

```php
<?php

declare(strict_types=1);

namespace {{ namespace }};

use {{ checksPermissionTrait }};
use {{ permissionActionClass }};
use Illuminate\Auth\Access\Response;
use {{ namespacedModel }};
use {{ namespacedUserModel }};

class {{ class }}
{
    use ChecksPermission;
{{ methods }}
}
```

**Template de método (single_parameter):**
```php
    public function {{ method }}({{ userModel }} $user, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, '{{ ability }}', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }
```

**Template de método (com record):**
```php
    public function {{ method }}({{ userModel }} $user, {{ model }} $record, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, '{{ ability }}', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }
```

### 3.2 — PolicyGenerator service

**Arquivo:** `src/Support/PolicyGenerator.php`

Classe responsável por gerar o conteúdo PHP da policy:

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class PolicyGenerator
{
    public function __construct(
        protected Filesystem $filesystem,
    ) {}

    /**
     * @param  array<int, string>  $methods
     * @param  array<int, string>  $singleParameterMethods
     */
    public function generate(
        string $modelClass,
        string $userModelClass,
        string $policyNamespace,
        string $policyClassName,
        array $methods,
        array $singleParameterMethods,
    ): string {
        $stub = $this->resolveStub();
        
        $methodsContent = $this->generateMethods(
            modelClass: $modelClass,
            userModelClass: $userModelClass,
            methods: $methods,
            singleParameterMethods: $singleParameterMethods,
        );

        return str_replace([
            '{{ namespace }}',
            '{{ checksPermissionTrait }}',
            '{{ permissionActionClass }}',
            '{{ namespacedModel }}',
            '{{ namespacedUserModel }}',
            '{{ class }}',
            '{{ userModel }}',
            '{{ methods }}',
        ], [
            $policyNamespace,
            'CoringaWc\\FilamentAcl\\Policies\\Concerns\\ChecksPermission',
            'CoringaWc\\FilamentAcl\\Support\\PermissionAction',
            $modelClass,
            $userModelClass,
            $policyClassName,
            class_basename($userModelClass),
            $methodsContent,
        ], $stub);
    }

    /**
     * @param  array<int, string>  $methods
     * @param  array<int, string>  $singleParameterMethods
     */
    protected function generateMethods(
        string $modelClass,
        string $userModelClass,
        array $methods,
        array $singleParameterMethods,
    ): string {
        $output = '';
        $modelBasename = class_basename($modelClass);
        $userBasename = class_basename($userModelClass);

        foreach ($methods as $method) {
            $isSingleParam = in_array($method, $singleParameterMethods, true);
            
            if ($isSingleParam) {
                $output .= $this->renderSingleParameterMethod($method, $userBasename);
            } else {
                $output .= $this->renderRecordParameterMethod($method, $userBasename, $modelBasename);
            }
        }

        return $output;
    }

    protected function resolveStub(): string
    {
        // Check published stubs first
        $publishedPath = config('filament-acl.stubs.path', base_path('stubs/filament-acl'));
        $publishedStub = rtrim($publishedPath, '/') . '/policy.stub';

        if ($this->filesystem->exists($publishedStub)) {
            return $this->filesystem->get($publishedStub);
        }

        // Fallback to package stub
        $packageStub = dirname(__DIR__, 2) . '/stubs/policy.stub';
        
        return $this->filesystem->get($packageStub);
    }

    // ... renderSingleParameterMethod(), renderRecordParameterMethod() helpers
}
```

### 3.3 — MakePolicyCommand

**Arquivo:** `src/Commands/MakePolicyCommand.php`

```php
#[AsCommand(
    name: 'filament-acl:make-policy',
    description: 'Generate an ACL policy for a model',
)]
class MakePolicyCommand extends Command
{
    use Prohibitable;

    protected $signature = 'filament-acl:make-policy
        {model : The fully qualified model class name}
        {--resource= : The resource class to resolve the model from}
        {--panel= : Panel ID for user model resolution}
        {--force : Overwrite existing policy}';
```

**Lógica:**
1. Resolve model class do argumento (ou do `--resource` via `$resourceClass::getModel()`)
2. Determina namespace e path da policy: `config('filament-acl.policies.path')` + model basename + `Policy`
3. Gera usando `PolicyGenerator`
4. Se arquivo existe e `--force` não foi passado → pedir confirmação (ou falhar em `--no-interaction`)
5. Se `config('filament-acl.policies.merge')` é `true` e arquivo existe → mesclar métodos faltantes (via simple string detection de `function methodName(`)

### 3.4 — GeneratePoliciesCommand

**Arquivo:** `src/Commands/GeneratePoliciesCommand.php`

```php
#[AsCommand(
    name: 'filament-acl:generate-policies',
    description: 'Generate ACL policies for all discovered resources',
)]
class GeneratePoliciesCommand extends Command
{
    use Prohibitable;

    protected $signature = 'filament-acl:generate-policies
        {--panel=* : Panel IDs to discover resources from}
        {--force : Overwrite existing policies}';
```

**Lógica:**
1. Itera todos os panels (ou os filtrados por `--panel`)
2. Para cada panel: `PermissionOwnerDiscovery::discoverResources($panel)`
3. Para cada resource: extrai model class via `$resourceClass::getModel()`
4. Chama `PolicyGenerator` para cada model
5. Respeita `config('filament-acl.policies.generate')` → se false, aborta com mensagem
6. Report final: "Generated X policies, skipped Y existing"

### 3.5 — Registrar commands no ServiceProvider

**Arquivo:** `src/FilamentPermissionServiceProvider.php` `[MODIFICAR]`

Adicionar ao array de commands:

```php
$this->commands([
    Commands\SyncPermissionsCommand::class,
    Commands\AdminUserCommand::class,
    Commands\InstallCommand::class,
    Commands\MakePolicyCommand::class,        // NOVO
    Commands\GeneratePoliciesCommand::class,   // NOVO
]);
```

### Smoke Test — Fase 3

```bash
docker compose exec php vendor/bin/phpunit --testdox --filter="MakePolicy|GeneratePolicy|PolicyGenerator"
docker compose exec php vendor/bin/phpstan analyse --memory-limit=1G src/Commands/ src/Support/PolicyGenerator.php
docker compose exec php vendor/bin/pint --dirty
```

---

## Fase 4 — Section Toggle na Permissions UI

> **Objetivo:** Cada Section na tab Resources tem um botão que toggle todas as checkboxes dentro dela.

### 4.1 — Implementação Alpine.js no PermissionResource

**Arquivo:** `src/Resources/Permissions/PermissionResource.php` `[MODIFICAR]`

**Abordagem:** Usar uma `Action` como `headerActions` no `Section`, que dispara JavaScript via `alpineClickHandler()`.

**Alternativa mais simples e robusta:** Usar `Section::headerActions()` com um `Action` que usa `->alpineClickHandler()` para iterar todos os checkboxes dentro do Section DOM.

```php
protected static function buildResourcePermissionSections(): array
{
    // ...existing logic...
    
    $sections[] = Section::make($sectionLabel)
        ->icon($sectionIcon)
        ->headerActions([
            \Filament\Actions\Action::make('toggle_section_' . Str::slug($sectionLabel))
                ->label(__('filament-acl::filament-acl.resources.permissions.section_toggle.select_all'))
                ->link()
                ->size('sm')
                ->alpineClickHandler(<<<'JS'
                    const section = $el.closest('[data-section]') || $el.closest('.fi-section');
                    if (!section) return;
                    const checkboxes = section.querySelectorAll('input[type="checkbox"]');
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    checkboxes.forEach(cb => {
                        if (cb.checked !== !allChecked) {
                            cb.click();
                        }
                    });
                JS),
        ])
        ->schema([/* ...existing... */])
        ->columnSpanFull()
        ->compact()
        ->collapsible()
        ->collapsed();
}
```

**⚠️ VERIFICAÇÃO NECESSÁRIA:** Confirmar que `alpineClickHandler()` existe no Filament v5 Action. Se não existir, alternativas:
- `->action(null)->extraAttributes(['x-on:click' => '...'])` 
- Custom Blade component
- `->livewireClickHandlerEnabled(false)` + Alpine `x-on:click`

**Investigar via Laravel Boost docs antes de implementar.**

### 4.2 — Traduções para section toggle

Já coberto na Fase 1 (`section_toggle.select_all`, `section_toggle.deselect_all`).

### Smoke Test — Fase 4

```bash
docker compose exec php vendor/bin/phpunit --testdox --filter="SectionToggle|PermissionResource"
docker compose exec php vendor/bin/phpstan analyse --memory-limit=1G src/Resources/
docker compose exec php vendor/bin/pint --dirty
# Verificação manual no workbench browser: toggle funciona, estado sincroniza
```

---

## Fase 5 — Melhorar Owner Labels no PermissionResource

> **Objetivo:** Labels mais inteligentes usando APIs nativas do Filament quando disponíveis.

### 5.1 — resolveOwnerLabel() melhorado

**Arquivo:** `src/Resources/Permissions/PermissionResource.php` `[MODIFICAR]`

**Método atual:**
```php
protected static function resolveOwnerLabel(PermissionOwnerRegistration $ownerRegistration): string
{
    $ownerClass = $ownerRegistration->ownerClass;
    return match ($ownerRegistration->ownerType) {
        PermissionEntityType::Resource => static::withOwnerConfigurationContext(
            $ownerRegistration,
            static fn (): string => $ownerClass::getModelLabel(),
        ),
        PermissionEntityType::RelationManager => Str::headline(Str::beforeLast(class_basename($ownerClass), 'RelationManager')),
        PermissionEntityType::Page => Str::headline(Str::beforeLast(class_basename($ownerClass), 'Page')),
        PermissionEntityType::Widget => Str::headline(Str::beforeLast(class_basename($ownerClass), 'Widget')),
        default => Str::headline(class_basename($ownerClass)),
    };
}
```

**Novo:** Para Pages, tentar usar `getTitle()` ou `getNavigationLabel()` quando disponível:

```php
protected static function resolveOwnerLabel(PermissionOwnerRegistration $ownerRegistration): string
{
    $ownerClass = $ownerRegistration->ownerClass;

    return match ($ownerRegistration->ownerType) {
        PermissionEntityType::Resource => static::withOwnerConfigurationContext(
            $ownerRegistration,
            static fn (): string => $ownerClass::getModelLabel(),
        ),
        PermissionEntityType::Page => static::resolvePageLabel($ownerRegistration),
        PermissionEntityType::Widget => static::resolveWidgetLabel($ownerRegistration),
        PermissionEntityType::RelationManager => static::resolveRelationManagerLabel($ownerRegistration),
        default => Str::headline(class_basename($ownerClass)),
    };
}

protected static function resolvePageLabel(PermissionOwnerRegistration $ownerRegistration): string
{
    $ownerClass = $ownerRegistration->ownerClass;

    try {
        return static::withOwnerConfigurationContext(
            $ownerRegistration,
            static function () use ($ownerClass): string {
                if (method_exists($ownerClass, 'getNavigationLabel')) {
                    $label = $ownerClass::getNavigationLabel();
                    if (filled($label)) {
                        return $label;
                    }
                }

                return Str::headline(Str::beforeLast(class_basename($ownerClass), 'Page'));
            },
        );
    } catch (\Throwable) {
        return Str::headline(Str::beforeLast(class_basename($ownerClass), 'Page'));
    }
}

protected static function resolveWidgetLabel(PermissionOwnerRegistration $ownerRegistration): string
{
    $ownerClass = $ownerRegistration->ownerClass;

    try {
        if (method_exists($ownerClass, 'getHeading')) {
            $heading = $ownerClass::getHeading();
            if (filled($heading) && is_string($heading)) {
                return $heading;
            }
        }
    } catch (\Throwable) {
        // fallback
    }

    return Str::headline(Str::beforeLast(class_basename($ownerClass), 'Widget'));
}

protected static function resolveRelationManagerLabel(PermissionOwnerRegistration $ownerRegistration): string
{
    $ownerClass = $ownerRegistration->ownerClass;

    try {
        if (method_exists($ownerClass, 'getTitle')) {
            $title = static::withOwnerConfigurationContext(
                $ownerRegistration,
                static fn (): string => (string) $ownerClass::getTitle(new \stdClass, ''),
            );
            if (filled($title)) {
                return $title;
            }
        }
    } catch (\Throwable) {
        // fallback
    }

    return Str::headline(Str::beforeLast(class_basename($ownerClass), 'RelationManager'));
}
```

**Nota:** `RelationManager::getTitle()` requer `$ownerRecord` e `$pageClass` — por isso tem try/catch com fallback. Se falhar, cai no headline genérico.

### Smoke Test — Fase 5

```bash
docker compose exec php vendor/bin/phpunit --testdox --filter="PermissionResource|resolveOwnerLabel"
docker compose exec php vendor/bin/phpstan analyse --memory-limit=1G src/Resources/
docker compose exec php vendor/bin/pint --dirty
```

---

## Fase 6 — Workbench Improvements

> **Objetivo:** Workbench demonstra todas as features novas, policies completas.

### 6.1 — CommentPolicy (novo)

**Arquivo:** `workbench/app/Policies/CommentPolicy.php`

Seguir padrão do `PostPolicy`:
- `use ChecksPermission`
- Métodos: `viewAny`, `view`, `create`, `update`, `delete`
- Todos com `PermissionAction | string | null $permissionAction = null`
- Model: `Workbench\App\Models\Comment`
- User: `Workbench\App\Models\User`

### 6.2 — UserPolicy reescrita

**Arquivo:** `workbench/app/Policies/UserPolicy.php` `[MODIFICAR]`

**Atual:** Não usa `ChecksPermission`, retorna `Response::allow()` incondicional.

**Novo:** Usar `ChecksPermission` como os demais:
- Métodos: `viewAny`, `view`, `update` (manter os que já existem)
- Adicionar `PermissionAction` como último parâmetro
- Cada método chama `denyUnlessPermitted()`

### 6.3 — Expandir PermissionSeeder

**Arquivo:** `workbench/database/seeders/PermissionSeeder.php` `[MODIFICAR]`

- Adicionar permissões para CommentPolicy ao role `posts_only`
- Demonstrar role com acesso parcial a resources

### 6.4 — (Factories já existem)

CategoryFactory e CommentFactory já existem no workbench. Nenhuma ação necessária.

### Smoke Test — Fase 6

```bash
docker compose exec php vendor/bin/phpunit --testdox --filter="Workbench|PolicySmoke"
docker compose exec php vendor/bin/phpstan analyse --memory-limit=1G
docker compose exec php vendor/bin/pint --dirty
```

---

## Fase 7 — Testes

> **Objetivo:** Cobertura completa de todas as features novas.

### 7.1 — tests/Unit/SubjectResolutionStrategyTest.php (novo)
- Enum values match expected strings
- `tryFrom()` para invalid value retorna null

### 7.2 — tests/Feature/SubjectResolutionStrategyIntegrationTest.php (novo)
- **basename strategy:** resolução idêntica ao comportamento atual (regression test)
- **fqcn strategy:** retorna FQCN completo do owner
- **custom strategy sem callback:** lança RuntimeException
- **custom strategy com subject_override:** usa override
- **custom strategy com manager callback:** usa callback
- **Prioridade mantida:** `getPermissionSubject()` sempre ganha sobre strategy

### 7.3 — tests/Feature/Commands/MakePolicyCommandTest.php (novo)
- Gera policy com métodos corretos para model existente
- Respeita `single_parameter_methods` — viewAny/create sem record param
- `--force` sobrescreve arquivo existente
- Sem `--force` e arquivo existe → falha com mensagem
- Usa stub customizado quando publicado em `stubs/filament-acl/policy.stub`
- Policy gerada contém `use ChecksPermission`
- Policy gerada importa `PermissionAction`

### 7.4 — tests/Feature/Commands/GeneratePoliciesCommandTest.php (novo)
- Gera policies para todos os resources descobertos no panel
- `--panel` filtra corretamente
- `--force` sobrescreve todas
- Respeita `config('filament-acl.policies.generate')` = false → aborta
- Conta corretas de criados/skipped

### 7.5 — tests/Unit/PolicyGeneratorTest.php (novo)
- Output do generator contém namespace correto
- Model class importado corretamente
- User model class importado corretamente
- Métodos gerados correspondem ao array passado
- Single-parameter methods não têm $record

### 7.6 — tests/Feature/Resources/SectionToggleTest.php (novo)
- PermissionResource renderiza headerActions nas Sections
- Alpine.js click handler está presente no DOM
- (Browser test não é padrão — testar presença do componente via Livewire::test)

### 7.7 — tests/Unit/TranslationCompletenessTest.php (novo)
- Todas as keys em `en/filament-acl.php` existem em `pt_BR/filament-acl.php`
- Nenhuma key de `permission_labels` está faltando para as abilities em `DefaultPermissionActionRegistry`

### 7.8 — tests/Feature/Workbench/PolicySmokeTest.php (novo)
- CommentPolicy resolves corretamente no Gate
- UserPolicy (reescrita) funciona com ChecksPermission
- User com role 'moderator' pode acessar todos os resources
- User com role 'posts_only' só acessa Posts

### Suite completa

```bash
docker compose exec php vendor/bin/phpunit --testdox
```

**Target: 70 (existentes) + ~35 novos (estimativa) = ~105 testes**

---

## Fase 8 — Checklist do Agente Revisor

### Checklist — Backward Compatibility
- [ ] `basename` strategy produz resultados idênticos ao comportamento anterior?
- [ ] Config `subject_resolver` aceita tanto string (FQCN) quanto array?
- [ ] Nenhum teste existente quebrou?
- [ ] PermissionResource UI funciona identicamente com `basename` (default)?
- [ ] Commands existentes (sync, install, admin-user) inalterados?

### Checklist — Policy Generation
- [ ] Stub usa `ChecksPermission` trait?
- [ ] Todos os métodos de `config('policies.methods')` são gerados?
- [ ] `single_parameter_methods` recebem apenas `$user`?
- [ ] Demais métodos recebem `$user, Model $record`?
- [ ] Todos recebem `PermissionAction | string | null $permissionAction = null`?
- [ ] `--force` funciona corretamente?
- [ ] Stubs publicáveis em `stubs/filament-acl/`?
- [ ] `policies.should_generate` / `policies.generate` respeitado?

### Checklist — Subject Resolution
- [ ] 3 strategies funcionam: `basename`, `fqcn`, `custom`?
- [ ] Cadeia de prioridade preservada: owner method > manager callback > overrides > strategy?
- [ ] `fqcn` strategy: sync gera permissions com FQCN como subject?
- [ ] `custom` strategy sem callback: exceção clara?

### Checklist — Section Toggle
- [ ] Alpine.js handler presente em cada Section?
- [ ] Toggle seleciona/deseleciona todas as checkboxes?
- [ ] Estado Livewire sincroniza após toggle (Filament reage ao change event)?

### Checklist — Traduções
- [ ] Todos as abilities em `DefaultPermissionActionRegistry` têm label em en e pt_BR?
- [ ] `resolveAbilityLabel()` resolve camelCase → snake_case para busca no lang?
- [ ] Nenhuma key faltante entre en e pt_BR?

### Checklist — Workbench
- [ ] CommentPolicy existe e usa ChecksPermission?
- [ ] UserPolicy reescrita com ChecksPermission?
- [ ] PermissionSeeder atualizado?
- [ ] Smoke test do workbench passa?

### Checklist — Qualidade
- [ ] PHPStan sem erros novos?
- [ ] Pint sem diff?
- [ ] Todos os testes passam?
- [ ] Nenhum anti-padrão do AGENTS.md violado?

---

## Ordem de Dependências entre Fases

```
Fase 1 (Traduções + Enum)
    ↓
Fase 2 (Subject Strategy) ←── depende do Enum da Fase 1
    ↓
Fase 3 (Policy Commands) ←── independente das Fases 1-2, mas precisa do ServiceProvider
    ↓ (pode paralelizar com Fase 4)
Fase 4 (Section Toggle) ←── independente, pode rodar em paralelo com Fase 3
    ↓
Fase 5 (Owner Labels) ←── independente, pode rodar em paralelo com Fase 3-4
    ↓
Fase 6 (Workbench) ←── depende de Fase 3 (command para gerar policies) e traduções
    ↓
Fase 7 (Testes) ←── depende de todas as fases anteriores
    ↓
Fase 8 (Revisão) ←── depende de tudo
```

**Paralelização possível:** Fases 3, 4 e 5 são independentes entre si.

---

## Resumo Executivo

| Fase | Arquivos Novos | Arquivos Modificados | Escopo |
|------|:-:|:-:|--------|
| 1 — Traduções + Enum | 1 | 3 | SubjectResolutionStrategy enum, lang files, config |
| 2 — Subject Strategy | 0 | 3 | ConfiguredPermissionSubjectResolver, Utils, ServiceProvider |
| 3 — Policy Commands | 4 | 1 | MakePolicyCommand, GeneratePoliciesCommand, PolicyGenerator, stub |
| 4 — Section Toggle | 0 | 1 | PermissionResource Alpine.js |
| 5 — Owner Labels | 0 | 1 | PermissionResource resolveOwnerLabel |
| 6 — Workbench | 1 | 2 | CommentPolicy, UserPolicy rewrite, seeder |
| 7 — Testes | 8 | 0 | Full test coverage |
| 8 — Revisão | 0 | 1 | README update + checklist |
| **Total** | **14 novos** | **12 modificados** | |

> **Nota:** A contagem do inventário inicial (28 novos) incluía estimativas conservadoras. A contagem refinada é 14 novos + 12 modificados = 26 arquivos, excluindo o README update da fase 8.
