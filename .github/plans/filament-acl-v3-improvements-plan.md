# Plano de Implementação — filament-acl v3 Improvements

**Data:** 2026-04-07
**Repositório:** `/home/coringawc/filament-acl`
**Status:** Rascunho finalizado para Executor

---

## Contexto do Codebase

### Arquivos centrais confirmados

| Arquivo | Papel |
|---------|-------|
| `src/Resources/Permissions/PermissionResource.php` | Resource central; contém `buildResourceTree()`, `buildResourceGroupTab()`, `buildResourceNodeTab()`, `buildStandaloneNodeSchema()`, `resolveResourceSectionLabel()` (linha ~846), `isSectionStandalone()` |
| `src/Support/PermissionOwnerDiscovery.php` | Discovery; contém `resolveResourceSectionLabel()` (linha ~295), `makeResourceRegistration()` |
| `src/Support/Utils.php` | `resolvePermissionOwnerClass()`, `shouldDisplayPermissionOwner()`, `shouldRegisterPermissionOwner()` |
| `src/Resources/Concerns/HasResourcePermissions.php` | Trait para resources; `getSharedPermissionOwner()`, `getPermissionSubject()`, `getPermissionCustomActions()`, `getPermissionActions()` |
| `src/RelationManagers/Concerns/HasRelationManagerPermissions.php` | Trait para RMs; mesmos métodos |
| `src/FilamentAclPlugin.php` | Plugin fluente principal |
| `config/filament-acl.php` | Configuração pública |

### Fluxo de agrupamento (F1 impacto)

```
discoverResources()                          (PermissionOwnerDiscovery)
  → makeResourceRegistration()
      → resolveResourceSectionLabel()        ← aqui PermissionOwnerDiscovery

getDiscoverableResourceNodes()              (PermissionResource)
  → para cada PermissionOwnerRegistration:
      sectionLabel = registration.sectionLabel ?? resolveResourceSectionLabel() ← aqui PermissionResource (estático)

buildResourceTree(nodes)                    (PermissionResource)
  → agrupa nodes por section_label
  → ksort por section label

buildResourcePermissionSections()
  → para cada section:
      isSectionStandalone() → standalone vs grouped
      buildStandaloneNodeSchema() | buildResourceGroupTab() → Tabs internas aqui
```

---

## Inventário Global de Arquivos

### F1 — Agrupamento configurável por NavGroup e Cluster

| Operação | Arquivo |
|----------|---------|
| MODIFICAR | `config/filament-acl.php` |
| MODIFICAR | `src/FilamentAclPlugin.php` |
| MODIFICAR | `src/Support/PermissionOwnerDiscovery.php` |
| MODIFICAR | `src/Resources/Permissions/PermissionResource.php` |
| CRIAR | `tests/Feature/Workbench/ResourceGroupingConfigTest.php` |

### F2 — Estilo configurável das tabs internas

| Operação | Arquivo |
|----------|---------|
| MODIFICAR | `config/filament-acl.php` |
| MODIFICAR | `src/FilamentAclPlugin.php` |
| MODIFICAR | `src/Resources/Permissions/PermissionResource.php` |
| CRIAR | `tests/Feature/Workbench/InnerTabsStyleTest.php` |

### F3 — Shared permissions para nested resources + testes

| Operação | Arquivo |
|----------|---------|
| MODIFICAR | `workbench/app/Filament/Resources/Posts/Resources/Categories/CategoryResource.php` |
| MODIFICAR | `workbench/app/Filament/Resources/Categories/Resources/Posts/PostResource.php` |
| CRIAR | `tests/Feature/Workbench/SharedPermissionOwnerTest.php` |

### F4 — PHP Attributes para configuração de permissões

| Operação | Arquivo |
|----------|---------|
| CRIAR | `src/Attributes/SharedPermissionOwner.php` |
| CRIAR | `src/Attributes/PermissionSubject.php` |
| CRIAR | `src/Attributes/ShouldNotRegisterPermissions.php` |
| CRIAR | `src/Attributes/PermissionCustomActions.php` |
| CRIAR | `src/Attributes/PermissionActions.php` |
| MODIFICAR | `src/Support/Utils.php` |
| MODIFICAR | `src/Resources/Concerns/HasResourcePermissions.php` |
| MODIFICAR | `src/RelationManagers/Concerns/HasRelationManagerPermissions.php` |
| MODIFICAR | `workbench/app/Filament/Resources/Posts/PostResource.php` *(exemplo de uso)* |
| CRIAR | `tests/Feature/Attributes/PhpAttributesTest.php` |

### F5 — Migrar para Pest

| Operação | Arquivo |
|----------|---------|
| MODIFICAR | `composer.json` |
| MODIFICAR | `.github/workflows/run-tests.yml` |
| VERIFICAR | `phpunit.xml.dist` *(compatível, provavelmente sem mudança)* |

### F6 — Renomear branch 5.x → main

| Operação | Arquivo |
|----------|---------|
| DOCUMENTAR | *(passos git — nenhum arquivo modificado pelo executor)* |

**Total: 7 arquivos novos, 12 modificações**

---

## Feature 1 — Agrupamento configurável por NavGroup e Cluster

### Ordem de execução

```
1. config/filament-acl.php
2. src/FilamentAclPlugin.php
3. src/Support/PermissionOwnerDiscovery.php
4. src/Resources/Permissions/PermissionResource.php
5. tests/Feature/Workbench/ResourceGroupingConfigTest.php
```

---

### 1.1 — `config/filament-acl.php`

Dentro de `'resources' => ['permissions' => [...]]`, adicionar as subchaves novas após a chave `'cluster'` existente:

```php
'sections' => [
    'group_by_navigation_group' => true,
    'group_by_cluster' => true,
],
```

**Posição exata:** imediatamente após `'cluster' => null,` no bloco `resources.permissions`.

---

### 1.2 — `src/FilamentAclPlugin.php`

Adicionar dois campos protegidos no topo da classe (junto com `$permissionsResourceOptions`):

```php
protected ?bool $groupByNavigationGroup = null;
protected ?bool $groupByCluster = null;
```

Adicionar dois métodos fluentes públicos:

```php
public function groupByNavigationGroup(bool $condition = true): static
{
    $this->groupByNavigationGroup = $condition;

    return $this;
}

public function usesGroupByNavigationGroup(): bool
{
    return $this->groupByNavigationGroup
        ?? (bool) config('filament-acl.resources.permissions.sections.group_by_navigation_group', true);
}

public function groupByCluster(bool $condition = true): static
{
    $this->groupByCluster = $condition;

    return $this;
}

public function usesGroupByCluster(): bool
{
    return $this->groupByCluster
        ?? (bool) config('filament-acl.resources.permissions.sections.group_by_cluster', true);
}
```

---

### 1.3 — `src/Support/PermissionOwnerDiscovery.php`

Modificar `resolveResourceSectionLabel()`:

**Assinatura atual:**
```php
protected function resolveResourceSectionLabel(Panel $panel, string $resourceClass, ?string $registrationKey = null): string
```

**Lógica nova:** ler as duas configs antes de proceder com a resolução atual.

```php
protected function resolveResourceSectionLabel(Panel $panel, string $resourceClass, ?string $registrationKey = null): string
{
    $groupByCluster = (bool) config('filament-acl.resources.permissions.sections.group_by_cluster', true);
    $groupByNavigationGroup = (bool) config('filament-acl.resources.permissions.sections.group_by_navigation_group', true);

    /** @var class-string<Cluster>|null $cluster */
    $cluster = $this->evaluateInPanel(
        panel: $panel,
        callback: fn (): ?string => $this->evaluateResourceWithConfiguration(
            $resourceClass,
            $registrationKey,
            static fn (): ?string => $resourceClass::getCluster(),
        ),
    );

    // Cluster tem prioridade. Se group_by_cluster=false, não agrupa por cluster —
    // mas ainda verifica navigation_group (se group_by_navigation_group=true).
    if ($groupByCluster && ($cluster !== null) && is_subclass_of($cluster, Cluster::class)) {
        return $cluster::getNavigationLabel();
    }

    $navigationGroup = $this->evaluateInPanel(
        panel: $panel,
        callback: fn (): mixed => $this->evaluateResourceWithConfiguration(
            $resourceClass,
            $registrationKey,
            static fn (): mixed => $resourceClass::getNavigationGroup(),
        ),
    );

    if ($groupByNavigationGroup && $navigationGroup !== null) {
        return (string) match (true) {
            $navigationGroup instanceof \BackedEnum => $navigationGroup->value,
            $navigationGroup instanceof \UnitEnum => $navigationGroup->name,
            is_string($navigationGroup) => $navigationGroup,
            default => '',
        };
    }

    // Standalone: usa o próprio navigation label do resource como section label
    return (string) $this->evaluateInPanel(
        panel: $panel,
        callback: fn (): string => $this->evaluateResourceWithConfiguration(
            $resourceClass,
            $registrationKey,
            static fn (): string => (string) $resourceClass::getNavigationLabel(),
        ),
    );
}
```

**Observação:** quando `group_by_navigation_group=false` mas o resource tem navGroup preenchido, retorna o navigation label do resource (comportamento standalone). Quando `group_by_cluster=false` mas `group_by_navigation_group=true`, checa o navGroup normalmente.

---

### 1.4 — `src/Resources/Permissions/PermissionResource.php`

Modificar `resolveResourceSectionLabel(string $resourceClass): string` (método estático, linha ~846):

```php
protected static function resolveResourceSectionLabel(string $resourceClass): string
{
    $groupByCluster = (bool) config('filament-acl.resources.permissions.sections.group_by_cluster', true);
    $groupByNavigationGroup = (bool) config('filament-acl.resources.permissions.sections.group_by_navigation_group', true);

    $cluster = $resourceClass::getCluster();

    if ($groupByCluster && ($cluster !== null) && is_subclass_of($cluster, Cluster::class)) {
        return $cluster::getNavigationLabel();
    }

    $navigationGroup = $resourceClass::getNavigationGroup();

    if ($groupByNavigationGroup && $navigationGroup !== null) {
        return (string) match (true) {
            $navigationGroup instanceof BackedEnum => $navigationGroup->value,
            $navigationGroup instanceof UnitEnum => $navigationGroup->name,
            is_string($navigationGroup) => $navigationGroup,
            default => '',
        };
    }

    return (string) $resourceClass::getNavigationLabel();
}
```

Modificar também `isSectionStandalone()`: o método atual verifica cluster/navGroup diretamente na classe. Após F1, o comportamento "standalone" depende das configs. Substituir por:

```php
protected static function isSectionStandalone(array $nodes): bool
{
    $firstNode = $nodes[0] ?? null;

    if ($firstNode === null) {
        return true;
    }

    $resourceClass = $firstNode['owner_class'];

    $groupByCluster = (bool) config('filament-acl.resources.permissions.sections.group_by_cluster', true);
    $groupByNavigationGroup = (bool) config('filament-acl.resources.permissions.sections.group_by_navigation_group', true);

    $cluster = $resourceClass::getCluster();

    if ($groupByCluster && ($cluster !== null) && is_subclass_of($cluster, Cluster::class)) {
        return false;
    }

    $navigationGroup = $resourceClass::getNavigationGroup();

    if ($groupByNavigationGroup && $navigationGroup !== null) {
        return false;
    }

    return true;
}
```

---

### 1.5 — `tests/Feature/Workbench/ResourceGroupingConfigTest.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Tests\TestCase;

class ResourceGroupingConfigTest extends TestCase
{
    // Caso 1: defaults — resources com navGroup ficam agrupados
    public function test_resources_with_navigation_group_are_grouped_by_default(): void
    {
        // Dois resources com mesmo navGroup devem resultar em section_label idêntico
    }

    // Caso 2: group_by_navigation_group=false → resources ficam individuais
    public function test_resources_with_navigation_group_are_standalone_when_group_by_navigation_group_is_false(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => false]);
        // section_label do resource deve ser seu próprio navigation label
    }

    // Caso 3: group_by_cluster=false → resources com cluster ficam individuais
    public function test_resources_with_cluster_are_standalone_when_group_by_cluster_is_false(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_cluster' => false]);
    }

    // Caso 4: ambos false → todos standalone
    public function test_all_resources_are_standalone_when_both_grouping_options_are_false(): void
    {
        config([
            'filament-acl.resources.permissions.sections.group_by_navigation_group' => false,
            'filament-acl.resources.permissions.sections.group_by_cluster' => false,
        ]);
    }

    // Caso 5: cluster tem prioridade sobre navGroup quando ambos true
    public function test_cluster_takes_priority_over_navigation_group_when_both_enabled(): void
    {
        // Resource com cluster deve usar cluster label, não navGroup label
    }

    // Caso 6: fluent methods no plugin
    public function test_plugin_fluent_methods_override_config(): void
    {
        // groupByNavigationGroup(false) e groupByCluster(false) no plugin
    }
}
```

### Cenários obrigatórios de cobertura

| # | Cenário | Método de teste |
|---|---------|-----------------|
| 1 | Defaults mantêm agrupamento por navGroup | Verificar `section_label` via `resolveResourceSectionLabel()` |
| 2 | `group_by_navigation_group=false` → section_label = navigation label do resource | idem |
| 3 | `group_by_cluster=false` → section_label = navigation label do resource | idem |
| 4 | Cluster tem prioridade quando `group_by_cluster=true` e navGroup também existe | idem |
| 5 | `group_by_cluster=false` com `group_by_navigation_group=true` → navGroup ainda agrupa | idem |

### Impacto em testes existentes (F1)

- Nenhum teste existente verifica `section_label` diretamente; impacto baixo.
- `SyncPermissionsCommandTest` não verifica labels de seção — sem impacto.
- `FilamentWorkbenchSmokeTest` pode verificar renderização — revisar após implementação.

---

## Feature 2 — Estilo configurável das tabs internas

### Ordem de execução

```
1. config/filament-acl.php
2. src/FilamentAclPlugin.php
3. src/Resources/Permissions/PermissionResource.php
4. tests/Feature/Workbench/InnerTabsStyleTest.php
```

---

### 2.1 — `config/filament-acl.php`

Dentro de `'resources' => ['permissions' => [...]]`, após `'sections'`, adicionar:

```php
'inner_tabs' => [
    'position' => 'top',    // 'top' (padrão) ou 'start' (lateral/vertical)
    'contained' => false,   // bool, default false
],
```

---

### 2.2 — `src/FilamentAclPlugin.php`

Adicionar campos e métodos:

```php
protected ?string $innerTabsPosition = null;
protected ?bool $innerTabsContained = null;

public function innerTabsPosition(string $position): static
{
    $this->innerTabsPosition = $position;    // 'top' ou 'start'

    return $this;
}

public function getInnerTabsPosition(): string
{
    return $this->innerTabsPosition
        ?? (string) config('filament-acl.resources.permissions.inner_tabs.position', 'top');
}

public function innerTabsContained(bool $condition = true): static
{
    $this->innerTabsContained = $condition;

    return $this;
}

public function usesInnerTabsContained(): bool
{
    return $this->innerTabsContained
        ?? (bool) config('filament-acl.resources.permissions.inner_tabs.contained', false);
}
```

---

### 2.3 — `src/Resources/Permissions/PermissionResource.php`

**Adicionar método helper estático privado:**

```php
protected static function makeInnerTabs(string $id): Tabs
{
    $tabs = Tabs::make($id);

    try {
        $plugin = FilamentAclPlugin::get();
        $position = $plugin->getInnerTabsPosition();
        $contained = $plugin->usesInnerTabsContained();
    } catch (\Throwable) {
        $position = (string) config('filament-acl.resources.permissions.inner_tabs.position', 'top');
        $contained = (bool) config('filament-acl.resources.permissions.inner_tabs.contained', false);
    }

    if ($position === 'start') {
        $tabs->tabPosition('start');
    }

    if ($contained) {
        $tabs->contained();
    }

    return $tabs;
}
```

> **Nota técnica:** verificar a API exata de `Tabs` no Filament v5 para os métodos `tabPosition()` e `contained()`. Usar `mcp_laravel-boost_search-docs` para confirmar antes de implementar.

**Substituir todas as instâncias de `Tabs::make(...)` que criam tabs internas** (nested resources e relation managers) pelas chamadas ao helper:

Instâncias em `buildResourceGroupTab()`:
- `Tabs::make('resource_children_' . $uniqueId)` → `static::makeInnerTabs('resource_children_' . $uniqueId)`

Instâncias em `buildResourceNodeTab()`:
- `Tabs::make('resource_children_' . $uniqueId)` → `static::makeInnerTabs('resource_children_' . $uniqueId)`

Instâncias em `buildStandaloneNodeSchema()`:
- `Tabs::make('children_' . Str::slug(...)...)` → `static::makeInnerTabs('children_' . Str::slug(...)...)`

**NÃO substituir** os `Tabs::make(...)` externos (tabs de Resources/Pages/Widgets/Custom na linha `Tabs::make('permission_groups_tabs')` em `form()` e `Tabs::make('section_' . $sectionId)` em `buildResourcePermissionSections()`).

---

### 2.4 — `tests/Feature/Workbench/InnerTabsStyleTest.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Tests\TestCase;

class InnerTabsStyleTest extends TestCase
{
    // Caso 1: config padrão — sem modificar tabs (posição top, não contained)
    public function test_inner_tabs_default_config_does_not_apply_start_position(): void

    // Caso 2: position=start — tabs com tabPosition('start')
    public function test_inner_tabs_with_start_position_are_configured_correctly(): void

    // Caso 3: contained=true — tabs com contained()
    public function test_inner_tabs_with_contained_true_are_configured_correctly(): void

    // Caso 4: fluent method no plugin sobrescreve config
    public function test_plugin_fluent_methods_override_config_for_inner_tabs(): void
}
```

> **Nota:** estes testes verificam a configuração dos objetos `Tabs` retornados, inspecionando o componente gerado. Verificar API de testes do Filament v5 antes de implementar.

### Impacto em testes existentes (F2)

- Testes existentes não inspecionam configuração de Tabs — impacto zero se o comportamento visual padrão for mantido (`position=top`, `contained=false`).

---

## Feature 3 — Shared permissions para nested resources + testes

### Ordem de execução

```
1. workbench/app/Filament/Resources/Posts/Resources/Categories/CategoryResource.php
2. workbench/app/Filament/Resources/Categories/Resources/Posts/PostResource.php
3. tests/Feature/Workbench/SharedPermissionOwnerTest.php
```

---

### 3.1 — Nested resource Posts → Categories

Arquivo: `workbench/app/Filament/Resources/Posts/Resources/Categories/CategoryResource.php`

Adicionar o método **após** a declaração das propriedades e **antes** de `getPages()`:

```php
/**
 * @return class-string|null
 */
public static function getSharedPermissionOwner(): ?string
{
    return \Workbench\App\Filament\Resources\Categories\CategoryResource::class;
}
```

**Impacto esperado:**
- `Utils::resolvePermissionOwnerClass(NestedPostCategoryResource::class)` retornará `CategoryResource::class`
- `Utils::shouldDisplayPermissionOwner(NestedPostCategoryResource::class)` retornará `false`
- O nested resource desaparecerá da permissions UI
- O sync NÃO criará permissões separadas para ele

---

### 3.2 — Nested resource Categories → Posts

Arquivo: `workbench/app/Filament/Resources/Categories/Resources/Posts/PostResource.php`

Adicionar o método **antes** de `getPages()`:

```php
/**
 * @return class-string|null
 */
public static function getSharedPermissionOwner(): ?string
{
    return \Workbench\App\Filament\Resources\Posts\PostResource::class;
}
```

**Impacto esperado:** idem acima, mas usando `PostResource` como owner canônico.

---

### 3.3 — `tests/Feature/Workbench/SharedPermissionOwnerTest.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Support\Utils;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Workbench\App\Filament\Resources\Categories\CategoryResource;
use Workbench\App\Filament\Resources\Categories\Resources\Posts\PostResource as NestedCategoryPostResource;
use Workbench\App\Filament\Resources\Posts\PostResource;
use Workbench\App\Filament\Resources\Posts\Resources\Categories\CategoryResource as NestedPostCategoryResource;
use Workbench\App\Models\Category;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

class SharedPermissionOwnerTest extends TestCase
{
    // ── shouldDisplayPermissionOwner ────────────────────────────────────────

    public function test_nested_post_category_resource_does_not_appear_in_permissions_ui(): void
    {
        self::assertFalse(Utils::shouldDisplayPermissionOwner(NestedPostCategoryResource::class));
    }

    public function test_nested_category_post_resource_does_not_appear_in_permissions_ui(): void
    {
        self::assertFalse(Utils::shouldDisplayPermissionOwner(NestedCategoryPostResource::class));
    }

    public function test_canonical_category_resource_still_appears_in_permissions_ui(): void
    {
        self::assertTrue(Utils::shouldDisplayPermissionOwner(CategoryResource::class));
    }

    public function test_canonical_post_resource_still_appears_in_permissions_ui(): void
    {
        self::assertTrue(Utils::shouldDisplayPermissionOwner(PostResource::class));
    }

    // ── resolvePermissionOwnerClass ─────────────────────────────────────────

    public function test_nested_post_category_resolves_to_canonical_category_resource(): void
    {
        self::assertSame(
            CategoryResource::class,
            Utils::resolvePermissionOwnerClass(NestedPostCategoryResource::class),
        );
    }

    public function test_nested_category_post_resolves_to_canonical_post_resource(): void
    {
        self::assertSame(
            PostResource::class,
            Utils::resolvePermissionOwnerClass(NestedCategoryPostResource::class),
        );
    }

    // ── sync não cria permissões duplicadas ─────────────────────────────────

    public function test_sync_does_not_create_permissions_for_nested_post_category_resource(): void
    {
        Permission::query()->delete();

        Artisan::call('filament-acl:sync', ['--panel' => ['admin']]);

        // Nested resource NÃO deve ter permissões próprias
        $nestedSubject = app(\CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject::class)->resolve(
            entityClass: NestedPostCategoryResource::class,
            entityType: PermissionEntityType::Resource,
            panelId: 'admin',
        );

        // Não deve existir nenhuma permissão com o subject do nested resource
        self::assertDatabaseMissing('permissions', ['name' => "ViewAny:{$nestedSubject}"]);
        self::assertDatabaseMissing('permissions', ['name' => "Create:{$nestedSubject}"]);
    }

    public function test_sync_does_not_create_permissions_for_nested_category_post_resource(): void
    {
        Permission::query()->delete();

        Artisan::call('filament-acl:sync', ['--panel' => ['admin']]);

        $nestedSubject = app(\CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject::class)->resolve(
            entityClass: NestedCategoryPostResource::class,
            entityType: PermissionEntityType::Resource,
            panelId: 'admin',
        );

        self::assertDatabaseMissing('permissions', ['name' => "ViewAny:{$nestedSubject}"]);
        self::assertDatabaseMissing('permissions', ['name' => "Create:{$nestedSubject}"]);
    }

    // ── autorização usa as permissões do resource canônico ──────────────────

    public function test_nested_post_category_resource_is_authorized_using_canonical_category_permission(): void
    {
        $user = User::factory()->create();
        $this->grantOwnerPermission($user, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
        $this->actingAs($user);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Category::class,
            action: NestedPostCategoryResource::class,
        );

        self::assertTrue($response->allowed());
    }

    public function test_nested_category_post_resource_is_authorized_using_canonical_post_permission(): void
    {
        $user = User::factory()->create();
        $this->grantOwnerPermission($user, 'viewAny', PostResource::class, PermissionEntityType::Resource);
        $this->actingAs($user);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Post::class,
            action: NestedCategoryPostResource::class,
        );

        self::assertTrue($response->allowed());
    }

    public function test_nested_post_category_resource_is_denied_without_any_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Category::class,
            action: NestedPostCategoryResource::class,
        );

        self::assertFalse($response->allowed());
    }
}
```

### Impacto em testes existentes (F3)

> ⚠️ **Atenção crítica:** adicionar `getSharedPermissionOwner()` nos nested resources do workbench **quebrará** os seguintes testes existentes:

**`CategoryResourcePermissionTest`:**
- `test_it_allows_the_nested_post_categories_resource_with_its_own_subject_permission()` — este teste cria permissão para `NestedPostCategoryResource::class` e espera que a autorização passe. Com `getSharedPermissionOwner()`, o nested resource usa o subject do `CategoryResource`, então a permissão criada com o subject antigo do nested resource não vai mais resolver. **Este teste DEVE ser modificado** para usar a permissão do `CategoryResource`.

**Ação necessária:** No arquivo `tests/Feature/Workbench/CategoryResourcePermissionTest.php`, modificar `test_it_allows_the_nested_post_categories_resource_with_its_own_subject_permission()` para:
- Criar permissão para `CategoryResource::class` (não mais para o nested)
- Verificar que a autorização do nested resource é concedida via redirect para o owner canônico

**`CategoriesRelationManagerPermissionTest`:** Verificar se o relation manager também é afetado pela mudança de ownership do nested resource. Provavelmente não, pois o RM está no resource canonical path.

**`SyncPermissionsCommandTest`:** Verificar se o sync ainda cobre os subjects corretos após a adição de `getSharedPermissionOwner()`.

---

## Feature 4 — PHP Attributes para configuração de permissões

### Decisão técnica crítica: precedência de Attribute vs método

**Regra:** O Attribute só é usado quando o método correspondente **não foi sobrescrito** na subclasse.

**Implementação da detecção de override:**

```php
// Em HasResourcePermissions ou em Utils como helper
private static function isMethodOverriddenInSubclass(string $class, string $method, string $traitClass): bool
{
    $reflectionClass = new \ReflectionClass($class);
    $reflectionMethod = $reflectionClass->getMethod($method);

    return $reflectionMethod->getDeclaringClass()->getName() !== $traitClass;
}
```

**Exemplo de uso em `getPermissionSubject()`:**

```php
public static function getPermissionSubject(): ?string
{
    // Se foi sobrescrito na subclasse, respeita o override
    if (static::isMethodOverriddenInSubclass(static::class, 'getPermissionSubject', HasResourcePermissions::class)) {
        // Mas se retornar null (valor padrão do trait), ainda verifica o attribute
        // Versão mais simples: método sobrescrito TEM prioridade
    }

    // Verifica o attribute
    $reflection = new \ReflectionClass(static::class);
    $attributes = $reflection->getAttributes(PermissionSubjectAttribute::class);

    if ($attributes !== []) {
        return $attributes[0]->newInstance()->subject;
    }

    return null;
}
```

**Implementação recomendada (mais simples e segura):**

Nos traits, a lógica de leitura de attribute fica em métodos auxiliares separados. Os métodos públicos chamam os auxiliares ANTES de retornar o default:

```php
// HasResourcePermissions
public static function getPermissionSubject(): ?string
{
    return static::resolvePermissionSubjectFromAttribute() ?? null;
}

private static function resolvePermissionSubjectFromAttribute(): ?string
{
    $reflection = new \ReflectionClass(static::class);
    $attrs = $reflection->getAttributes(\CoringaWc\FilamentAcl\Attributes\PermissionSubject::class);

    return $attrs !== [] ? $attrs[0]->newInstance()->subject : null;
}
```

Se a subclasse sobrescreve `getPermissionSubject()` retornando um valor não-null, o attribute é ignorado porque o método sobrescrito nunca chama o parent. **Este é o comportamento correto**: subclasse sobrescreve → vence. Attribute na classe que usa o trait padrão → vence sobre o `return null` do padrão.

**Cache de Reflection:**

As leituras de attribute são cacheadas em array estático no helper para evitar overhead:

```php
/** @var array<string, array<string, mixed>> $attributeCache */
private static array $attributeCache = [];
```

---

### Ordem de execução

```
1. src/Attributes/SharedPermissionOwner.php
2. src/Attributes/PermissionSubject.php
3. src/Attributes/ShouldNotRegisterPermissions.php
4. src/Attributes/PermissionCustomActions.php
5. src/Attributes/PermissionActions.php
6. src/Support/Utils.php         (integrar #[SharedPermissionOwner] e #[ShouldNotRegisterPermissions])
7. src/Resources/Concerns/HasResourcePermissions.php   (integrar restantes)
8. src/RelationManagers/Concerns/HasRelationManagerPermissions.php  (integrar restantes)
9. workbench/app/Filament/Resources/Posts/PostResource.php  (exemplo)
10. tests/Feature/Attributes/PhpAttributesTest.php
```

---

### 4.1 — `src/Attributes/SharedPermissionOwner.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class SharedPermissionOwner
{
    /**
     * @param class-string $ownerClass
     */
    public function __construct(public readonly string $ownerClass) {}
}
```

---

### 4.2 — `src/Attributes/PermissionSubject.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class PermissionSubject
{
    public function __construct(public readonly string $subject) {}
}
```

---

### 4.3 — `src/Attributes/ShouldNotRegisterPermissions.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class ShouldNotRegisterPermissions {}
```

---

### 4.4 — `src/Attributes/PermissionCustomActions.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class PermissionCustomActions
{
    /**
     * @param array<int, string> $actions
     */
    public function __construct(public readonly array $actions) {}
}
```

---

### 4.5 — `src/Attributes/PermissionActions.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Attributes;

use Attribute;

/**
 * Substitui completamente a lista de actions (NÃO faz merge com defaults).
 * Para adicionar actions ao invés de substituir, use PermissionCustomActions.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class PermissionActions
{
    /**
     * @param array<int, string> $actions
     */
    public function __construct(public readonly array $actions) {}
}
```

---

### 4.6 — `src/Support/Utils.php`

**Adicionar import:** `use CoringaWc\FilamentAcl\Attributes\SharedPermissionOwner;`
**Adicionar import:** `use CoringaWc\FilamentAcl\Attributes\ShouldNotRegisterPermissions;`

**Modificar `resolvePermissionOwnerClass()`** para também checar o attribute `#[SharedPermissionOwner]`:

```php
public static function resolvePermissionOwnerClass(string $ownerClass): string
{
    $resolvedOwnerClass = $ownerClass;
    $visitedClasses = [];

    while (! in_array($resolvedOwnerClass, $visitedClasses, true)) {
        $visitedClasses[] = $resolvedOwnerClass;

        // 1. Tenta via método (comportamento atual)
        $sharedOwnerClass = match (true) {
            method_exists($resolvedOwnerClass, 'getSharedPermissionOwner') => $resolvedOwnerClass::getSharedPermissionOwner(),
            method_exists($resolvedOwnerClass, 'getPermissionOwnerClass') => $resolvedOwnerClass::getPermissionOwnerClass(),
            default => null,
        };

        // 2. Se o método retornou null ou o próprio owner, verifica attribute
        if (! is_string($sharedOwnerClass) || blank($sharedOwnerClass) || ($sharedOwnerClass === $resolvedOwnerClass)) {
            $sharedOwnerClass = static::resolveSharedOwnerFromAttribute($resolvedOwnerClass);
        }

        if (! is_string($sharedOwnerClass) || blank($sharedOwnerClass) || ($sharedOwnerClass === $resolvedOwnerClass)) {
            break;
        }

        $resolvedOwnerClass = $sharedOwnerClass;
    }

    return $resolvedOwnerClass;
}

/**
 * @param class-string $ownerClass
 * @return class-string|null
 */
private static function resolveSharedOwnerFromAttribute(string $ownerClass): ?string
{
    $reflection = new \ReflectionClass($ownerClass);
    $attributes = $reflection->getAttributes(SharedPermissionOwner::class);

    if ($attributes === []) {
        return null;
    }

    /** @var SharedPermissionOwner $instance */
    $instance = $attributes[0]->newInstance();

    return $instance->ownerClass;
}
```

**Modificar `shouldRegisterPermissionOwner()`** para também checar `#[ShouldNotRegisterPermissions]`:

```php
public static function shouldRegisterPermissionOwner(string $ownerClass): bool
{
    if (
        (bool) config('filament-acl.integration.require_explicit_opt_in', true)
        && (! method_exists($ownerClass, 'shouldRegisterPermissions'))
    ) {
        return false;
    }

    // Verifica o attribute ShouldNotRegisterPermissions ANTES do método
    // (attribute tem prioridade quando o método não foi sobrescrito,
    //  mas como o attribute só está disponível quando explicitamente declarado
    //  na classe, verificar o attribute antes é seguro)
    $reflection = new \ReflectionClass($ownerClass);

    if ($reflection->getAttributes(ShouldNotRegisterPermissions::class) !== []) {
        return false;
    }

    if (! method_exists($ownerClass, 'shouldRegisterPermissions')) {
        return true;
    }

    return (bool) $ownerClass::shouldRegisterPermissions();
}
```

**Nota sobre precedência para `ShouldNotRegisterPermissions`:** O attribute é verificado antes do método. Se a classe declara `#[ShouldNotRegisterPermissions]` MAS sobrescreve `shouldRegisterPermissions()` retornando `true`, o attribute vence. Isso é intencional — é o comportamento mais seguro para evitar vazamentos acidentais de permissões.

---

### 4.7 — `src/Resources/Concerns/HasResourcePermissions.php`

**Adicionar imports:**
```php
use CoringaWc\FilamentAcl\Attributes\PermissionActions as PermissionActionsAttribute;
use CoringaWc\FilamentAcl\Attributes\PermissionCustomActions as PermissionCustomActionsAttribute;
use CoringaWc\FilamentAcl\Attributes\PermissionSubject as PermissionSubjectAttribute;
```

**Modificar `getPermissionSubject()`:**

```php
public static function getPermissionSubject(): ?string
{
    $reflection = new \ReflectionClass(static::class);
    $attributes = $reflection->getAttributes(PermissionSubjectAttribute::class);

    if ($attributes !== []) {
        return $attributes[0]->newInstance()->subject;
    }

    return null;
}
```

**Modificar `getPermissionCustomActions()`:**

```php
public static function getPermissionCustomActions(): array
{
    $reflection = new \ReflectionClass(static::class);
    $attributes = $reflection->getAttributes(PermissionCustomActionsAttribute::class);

    if ($attributes !== []) {
        return $attributes[0]->newInstance()->actions;
    }

    return [];
}
```

**Modificar `getPermissionActions()`** para checar `#[PermissionActions]` antes de montar a lista default:

```php
public static function getPermissionActions(): array
{
    if (! static::shouldRegisterPermissions()) {
        return [];
    }

    // Attribute PermissionActions substitui TODA a lista
    $reflection = new \ReflectionClass(static::class);
    $actionAttributes = $reflection->getAttributes(PermissionActionsAttribute::class);

    if ($actionAttributes !== []) {
        return $actionAttributes[0]->newInstance()->actions;
    }

    $sharedPermissionOwner = static::resolvePermissionOwnerClass();

    if (($sharedPermissionOwner !== static::class) && method_exists($sharedPermissionOwner, 'getPermissionActions')) {
        /** @var array<int, string> $sharedActions */
        $sharedActions = $sharedPermissionOwner::getPermissionActions();

        return $sharedActions;
    }

    return array_values(array_unique([
        ...app(DefaultPermissionActionRegistry::class)->forResource(),
        ...static::getPermissionCustomActions(),
    ]));
}
```

---

### 4.8 — `src/RelationManagers/Concerns/HasRelationManagerPermissions.php`

Aplicar as mesmas modificações descritas em 4.7, adaptando:
- `getPermissionSubject()` → mesma lógica com `PermissionSubjectAttribute`
- `getPermissionCustomActions()` → mesma lógica com `PermissionCustomActionsAttribute`
- `getPermissionActions()` → mesma lógica com `PermissionActionsAttribute`

Usar o mesmo padrão de try-avoid NaN com cache de reflection se necessário.

---

### 4.9 — `workbench/app/Filament/Resources/Posts/PostResource.php` (exemplo)

Adicionar um uso de attribute como exemplo de documentação. Usar `#[PermissionCustomActions(['publish'])]` para demonstrar o attribute no workbench:

```php
use CoringaWc\FilamentAcl\Attributes\PermissionCustomActions;

#[PermissionCustomActions(['publish'])]
class PostResource extends Resource
```

> **Atenção:** esta adição mudará o output de `getPermissionActions()` para o `PostResource` e **pode quebrar testes existentes** que verificam a lista de actions do Post. Avaliar se o exemplo deve ficar em outro resource menos crítico, ou criar um novo resource de exemplo no workbench.

**Alternativa mais segura:** criar `workbench/app/Filament/Resources/ModerationPosts/PostResource.php` com o exemplo de attribute, deixando `PostResource` sem modificação.

---

### 4.10 — `tests/Feature/Attributes/PhpAttributesTest.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Attributes;

use CoringaWc\FilamentAcl\Attributes\PermissionActions;
use CoringaWc\FilamentAcl\Attributes\PermissionCustomActions;
use CoringaWc\FilamentAcl\Attributes\PermissionSubject;
use CoringaWc\FilamentAcl\Attributes\SharedPermissionOwner;
use CoringaWc\FilamentAcl\Attributes\ShouldNotRegisterPermissions;
use CoringaWc\FilamentAcl\Resources\Concerns\HasResourcePermissions;
use CoringaWc\FilamentAcl\Support\Utils;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Resources\Resource;

class PhpAttributesTest extends TestCase
{
    // ── #[PermissionSubject] ────────────────────────────────────────────────

    public function test_permission_subject_attribute_is_read_from_resource(): void
    {
        // Cria classe inline com o attribute e verifica getPermissionSubject()
        $resource = new class extends Resource {
            use HasResourcePermissions;
        };
        // Resource base: retorna null
        self::assertNull($resource::getPermissionSubject());

        // Classe com attribute
        eval('
            use CoringaWc\FilamentAcl\Attributes\PermissionSubject;
            use CoringaWc\FilamentAcl\Resources\Concerns\HasResourcePermissions;
            #[PermissionSubject("CustomSubject")]
            class TestResourceWithSubject extends \Filament\Resources\Resource {
                use HasResourcePermissions;
                public static function getModel(): string { return \stdClass::class; }
            }
        ');
        self::assertSame('CustomSubject', \TestResourceWithSubject::getPermissionSubject());
    }

    // ── #[ShouldNotRegisterPermissions] ─────────────────────────────────────

    public function test_should_not_register_permissions_attribute_prevents_registration(): void
    {
        self::assertFalse(Utils::shouldRegisterPermissionOwner(\TestResourceWithAttributeNotRegister::class));
    }

    // ── #[SharedPermissionOwner] ────────────────────────────────────────────

    public function test_shared_permission_owner_attribute_resolves_owner_class(): void
    {
        // Cria classe inline com o attribute e verifica resolvePermissionOwnerClass()
        self::assertSame(
            \TestCanonicalResource::class,
            Utils::resolvePermissionOwnerClass(\TestSharedOwnerResource::class),
        );
    }

    // ── #[PermissionCustomActions] ──────────────────────────────────────────

    public function test_permission_custom_actions_attribute_is_merged_with_defaults(): void
    {
        // getPermissionActions() deve incluir os defaults + as custom actions do attribute
        $actions = \TestResourceWithCustomActions::getPermissionActions();
        self::assertContains('publish', $actions);
        self::assertContains('viewAny', $actions);
    }

    // ── #[PermissionActions] ────────────────────────────────────────────────

    public function test_permission_actions_attribute_replaces_entire_action_list(): void
    {
        // getPermissionActions() deve retornar APENAS as actions do attribute, sem defaults
        $actions = \TestResourceWithFullActions::getPermissionActions();
        self::assertSame(['viewAny', 'create'], $actions);
        self::assertNotContains('update', $actions);
        self::assertNotContains('delete', $actions);
    }

    // ── Precedência: método sobrescrito vence sobre attribute ────────────────

    public function test_overridden_method_takes_precedence_over_attribute(): void
    {
        // Classe que tem #[PermissionSubject("AttrSubj")] mas sobrescreve getPermissionSubject() retornando "MethodSubj"
        // O método sobrescrito deve vencer
        self::assertSame('MethodSubj', \TestResourceMethodOverridesAttribute::getPermissionSubject());
    }
}
```

> **Nota de implementação:** as classes de teste inline acima são exemplos de intenção. O executor deve adaptar para usar classes reais definidas como inner classes PHP ou auxiliares de teste, dependendo da viabilidade.

### Impacto em testes existentes (F4)

- `SyncPermissionsCommandTest`: pode sofrer impacto se o exemplo em `PostResource` for adicionado. Verificar após implementação.
- `PostResourcePermissionTest`: verificar se `getPermissionActions()` para `PostResource` mudou.

---

## Feature 5 — Migrar para Pest

### Ordem de execução

```
1. composer.json           (adicionar pestphp/pest ^3.0 em require-dev)
2. phpunit.xml.dist        (verificar; provavelmente sem mudança)
3. .github/workflows/run-tests.yml  (corrigir se necessário)
```

---

### 5.1 — `composer.json`

Adicionar em `require-dev`:

```json
"pestphp/pest": "^3.0"
```

Adicionar também o plugin necessário para o autoloader:

```json
"pestphp/pest": "^3.0",
"pestphp/pest-plugin-laravel": "^3.0"
```

> **Nota:** verificar se o workbench também precisa de `pestphp/pest-plugin-livewire` para os testes Livewire/Filament.

Atualizar `config.allow-plugins` se necessário:

```json
"pestphp/pest-plugin": true
```

Substituir o script `"test"` em `scripts`:

```json
"test": "pest",
"test:types": "phpstan analyse --memory-limit=1G",
```

---

### 5.2 — `phpunit.xml.dist`

O arquivo atual referencia `xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/12.1/phpunit.xsd"`. Pest v3 usa PHPUnit 11/12 internamente, portanto é **compatível** com o `phpunit.xml.dist` atual.

**Nenhuma modificação necessária** a menos que o Pest exija uma chave diferente. Verificar a documentação do Pest v3 para confirmar.

---

### 5.3 — `.github/workflows/run-tests.yml`

O workflow já usa `vendor/bin/pest --ci` na linha `Execute tests`. Se ao rodar `composer require pestphp/pest` sem `pestphp/pest`, o workflow com `vendor/bin/pest` falharia. Com Pest instalado, funciona.

**Verificar:** o workflow instala dependências com `composer require "laravel/framework:${{ matrix.laravel }}" ...` antes de `composer update`. Estrutura já funcional.

**Possível ajuste necessário:** se `phpunit/phpunit` e `pestphp/pest` conflitarem em versão, remover `phpunit/phpunit` de `require-dev` na próxima etapa (Pest inclui PHPUnit como dependência).

**Ação recomendada:** após fazer `composer require --dev pestphp/pest:^3.0 pestphp/pest-plugin-laravel:^3.0`, remover `phpunit/phpunit` de `require-dev` para eliminá-lo como dependência direta (Pest o puxa como transitiva). Atualizar o composer script `"test"` para `"pest"`.

---

### Impacto em testes existentes (F5)

- Os testes PHPUnit existentes **continuam funcionando** com Pest sem conversão
- A coverage de runner (`vendor/bin/pest --ci`) funciona com classes PHPUnit
- Nenhuma conversão de sintaxe obrigatória

---

## Feature 6 — Renomear branch `5.x` → `main`

### Passos git (executar manualmente — NÃO fazer push neste plano)

```bash
# 1. Garantir que estamos na branch 5.x
git checkout 5.x

# 2. Criar branch main localmente a partir de 5.x
git checkout -b main

# 3. Verificar os workflows — atualizar se necessário
# Os workflows já usam `branches: [main]` — nenhuma mudança necessária

# 4. (Quando pronto para publicar) Push da nova branch
# git push origin main

# 5. (Opcional) Definir main como branch padrão no GitHub
# Fazer via GitHub UI: Settings > Branches > Default branch → main

# 6. (Opcional) Deletar branch 5.x remota após confirmar CI verde em main
# git push origin --delete 5.x
```

### Verificação dos workflows

**`run-tests.yml`:**
```yaml
on:
  push:
    branches: [main]    ← já usa main
  pull_request:
    branches: [main]    ← já usa main
```
Nenhuma alteração necessária.

**`fix-php-code-style-issues.yml`:** usa `paths: ['**.php']` — sem branch trigger específica. Sem alteração.

**`phpstan.yml`:** sem branch trigger específica. Sem alteração.

**`update-changelog.yml` e `dependabot-auto-merge.yml`:** verificar se há referências a `5.x`. Se sim, atualizar para `main`.

---

## Checklist de Qualidade por Feature

### Antes de cada feature

- [ ] Ler arquivos de contexto relevantes antes de editar
- [ ] Verificar siblings (arquivos irmãos) para seguir padrões existentes
- [ ] Para componentes Filament, consultar docs antes de implementar

### Comandos obrigatórios após cada feature

```bash
# Dentro do container Docker
docker compose exec php vendor/bin/phpstan analyse --memory-limit=1G
docker compose exec php vendor/bin/pint --dirty
docker compose exec php vendor/bin/pest --compact
```

### Checklist final global

- [ ] F1: `resolveResourceSectionLabel()` em AMBOS `PermissionOwnerDiscovery` e `PermissionResource` atualizados
- [ ] F1: `isSectionStandalone()` atualizado para respeitar as configs
- [ ] F1: config com defaults `true` para manter retrocompatibilidade
- [ ] F2: `makeInnerTabs()` helper criado e chamado em todos os 3 locais corretos
- [ ] F2: tabs externas (`permission_groups_tabs`, `section_*`) NÃO modificadas
- [ ] F3: `SyncPermissionsCommandTest` ainda passa após adicionar `getSharedPermissionOwner()`
- [ ] F3: `CategoryResourcePermissionTest::test_it_allows_the_nested...` atualizado (vai quebrar)
- [ ] F4: cache de Reflection nos helpers para evitar overhead
- [ ] F4: precedência correta verificada com testes (attribute vs override)
- [ ] F5: `vendor/bin/pest --ci` executa todos os testes sem erro
- [ ] F6: nenhum push feito — apenas documentação

---

## Decisões Técnicas em Aberto para o Executor

| # | Decisão | Recomendação |
|---|---------|-------------|
| D1 | Onde cachear os atributos PHP (F4) | Cache estático em `Utils` usando `array<class-string, array<string, mixed>>` com chave `"{class}::{attribute}"` |
| D2 | API de `Tabs` no Filament v5 para `tabPosition('start')` e `contained()` | Confirmar via `mcp_laravel-boost_search-docs` antes de implementar F2 |
| D3 | Se remover `phpunit/phpunit` de require-dev ao migrar para Pest | Sim, recomendado para evitar conflitos; Pest já inclui PHPUnit como dependência transitiva |
| D4 | Exemplo de attribute no workbench (F4) | Usar `ModerationPostResource` em vez de `PostResource` para não quebrar testes existentes |
| D5 | `CategoryResourcePermissionTest::test_it_allows_the_nested...` quebra com F3 | Modificar o teste para usar permissão do resource canônico (`CategoryResource`), não do nested |
| D6 | `resolvePermissionOwnerClass()` em Utils verifica attribute ANTES ou DEPOIS do método | DEPOIS: só usa attribute quando o método retorna null/self; isso preserva compatibilidade com overrides existentes |

---

## Referências de Código

### Utils::resolvePermissionOwnerClass() — linha ~416
```
src/Support/Utils.php:416
```

### Utils::shouldRegisterPermissionOwner() — linha ~442
```
src/Support/Utils.php:442
```

### PermissionOwnerDiscovery::resolveResourceSectionLabel() — linha ~295
```
src/Support/PermissionOwnerDiscovery.php:295
```

### PermissionResource::buildResourceTree() — linha ~558
```
src/Resources/Permissions/PermissionResource.php:558
```

### PermissionResource::buildResourceGroupTab() — linha ~632
```
src/Resources/Permissions/PermissionResource.php:632
```

### PermissionResource::buildResourceNodeTab() — linha ~703
```
src/Resources/Permissions/PermissionResource.php:703
```

### PermissionResource::buildStandaloneNodeSchema() — linha ~510
```
src/Resources/Permissions/PermissionResource.php:510
```

### PermissionResource::resolveResourceSectionLabel() — linha ~846
```
src/Resources/Permissions/PermissionResource.php:846
```

### PermissionResource::isSectionStandalone() — linha ~497
```
src/Resources/Permissions/PermissionResource.php:497
```
