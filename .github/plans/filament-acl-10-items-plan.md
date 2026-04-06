# Plano de Implementação — filament-acl (10 itens)

**Data:** 2026-04-06  
**Projeto:** `coringawc/filament-acl` em `/home/coringawc/filament-acl`  
**Stack:** PHP 8.2+, Filament v5, Laravel 12, Spatie Permission v7, PHPUnit 12, PHPStan 3

---

## Fase 0 — Inventário Completo

### Arquivos a CRIAR (2)

| # | Arquivo | Item |
|---|--------|------|
| 1 | `workbench/app/Enums/NavigationGroup.php` | Item 6 |
| 2 | `workbench/app/Filament/Pages/Login.php` | Item 8 |

### Arquivos a MODIFICAR (8)

| # | Arquivo | Itens |
|---|--------|-------|
| 1 | `src/Resources/Permissions/PermissionResource.php` | 1 · 2 · 3 · 4 · 10 |
| 2 | `resources/lang/en/filament-acl.php` | 4 · 10 |
| 3 | `resources/lang/pt_BR/filament-acl.php` | 4 · 10 |
| 4 | `workbench/app/Filament/Resources/Posts/PostResource.php` | 6 |
| 5 | `workbench/app/Filament/Resources/Categories/CategoryResource.php` | 6 |
| 6 | `workbench/app/Filament/Resources/ModerationPosts/PostResource.php` | 5 |
| 7 | `workbench/app/Policies/UserPolicy.php` | 7 |
| 8 | `workbench/app/Providers/Filament/AdminPanelProvider.php` | 8 · 10 |
| 9 | `workbench/app/Filament/Resources/Users/UserResource.php` | 9 |

**Total: 2 arquivos criados · 9 arquivos modificados**

### Testes a CRIAR (3)

| # | Arquivo |
|---|--------|
| 1 | `tests/Feature/Workbench/UserPolicyPermissionTest.php` |
| 2 | `tests/Feature/Workbench/PermissionResourceFormTest.php` |
| 3 | `tests/Feature/Workbench/NavigationGroupEnumTest.php` |

---

## Ordem de Execução (com dependências)

```
Passo 1 — Traduções (sem dependências)
  ├─ resources/lang/en/filament-acl.php       [Items 4, 10]
  └─ resources/lang/pt_BR/filament-acl.php    [Items 4, 10]

Passo 2 — Novo enum e nova page (sem dependências entre si)
  ├─ workbench/app/Enums/NavigationGroup.php  [Item 6 — novo]
  └─ workbench/app/Filament/Pages/Login.php   [Item 8 — novo]

Passo 3 — Workbench resources/policies (depende do Passo 2)
  ├─ Posts/PostResource.php                   [Item 6 — usa NavigationGroup]
  ├─ Categories/CategoryResource.php          [Item 6 — usa NavigationGroup]
  ├─ ModerationPosts/PostResource.php         [Item 5]
  ├─ workbench/app/Policies/UserPolicy.php    [Item 7]
  └─ Users/UserResource.php                   [Item 9]

Passo 4 — AdminPanelProvider (depende de Login.php existir)
  └─ AdminPanelProvider.php                   [Items 8, 10]

Passo 5 — PermissionResource (maior arquivo, consolidar todas as mudanças)
  └─ PermissionResource.php                   [Items 1, 2, 3, 4, 10]

Passo 6 — Testes (após todas as implementações)
  ├─ UserPolicyPermissionTest.php
  ├─ PermissionResourceFormTest.php
  └─ NavigationGroupEnumTest.php
```

---

## Detalhamento por Item

---

### Item 1 — Remover `->searchable()` do CheckboxList

**Arquivo:** `src/Resources/Permissions/PermissionResource.php`  
**Localização:** método `makePermissionCheckboxList()` (aprox. linha 641)

**Contexto atual:**
```php
protected static function makePermissionCheckboxList(string $statePath, array $options): CheckboxList
{
    return CheckboxList::make($statePath)
        ->hiddenLabel()
        ->options($options)
        ->bulkToggleable()
        ->searchable()        // ← REMOVER esta linha
        ->columns(2)
        ->columnSpanFull();
}
```

**Código final:**
```php
protected static function makePermissionCheckboxList(string $statePath, array $options): CheckboxList
{
    return CheckboxList::make($statePath)
        ->hiddenLabel()
        ->options($options)
        ->bulkToggleable()
        ->columns(2)
        ->columnSpanFull();
}
```

**Teste:** Nenhum teste unitário específico necessário — comportamento coberto pelos testes de renderização do form.

---

### Item 2 — Toggle "Selecionar Todas" na Section do nome da role

**Arquivo:** `src/Resources/Permissions/PermissionResource.php`  
**Localização:** método `form()`, dentro de `Section::make()->schema([...])`

**Novos imports necessários (adicionar ao topo do arquivo):**
```php
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
```

**Contexto atual (`form()`):**
```php
Section::make()
    ->schema([
        TextInput::make('name')
            ->label(...)
            ->required()
            ->maxLength(255)
            ->rule(...)
            ->unique(...),
        Hidden::make('guard_name')
            ->default(static::getDefaultGuardName()),
        Hidden::make(static::getPanelColumnName())
            ->default(static::resolveCurrentPanelScopeValue())
            ->dehydrated(static::shouldScopeRolesToCurrentPanel()),
    ])
    ->columns(2)
    ->columnSpanFull(),
```

**Código final da Section:**
```php
Section::make()
    ->schema([
        TextInput::make('name')
            ->label(__('filament-acl::filament-acl.resources.permissions.fields.name'))
            ->required()
            ->maxLength(255)
            ->rule(
                Rule::notIn([
                    Utils::getProtectedRoleName(),
                ]),
            )
            ->unique(
                ignoreRecord: true,
                modifyRuleUsing: function (Unique $rule): Unique {
                    $rule->where('guard_name', static::getDefaultGuardName());

                    if (static::shouldScopeRolesToCurrentPanel()) {
                        $rule->where(
                            static::getPanelColumnName(),
                            static::resolveCurrentPanelScopeValue(),
                        );
                    }

                    return $rule;
                },
            ),
        Toggle::make('select_all_permissions')
            ->label(fn (Get $get): string => $get('select_all_permissions')
                ? __('filament-acl::filament-acl.resources.permissions.section_toggle.deselect_all')
                : __('filament-acl::filament-acl.resources.permissions.section_toggle.select_all'))
            ->dehydrated(false)
            ->live()
            ->afterStateUpdated(function (bool $state, Set $set): void {
                foreach (static::getPermissionFieldDefinitions() as $path => $ids) {
                    $set($path, $state ? array_values($ids) : []);
                }
            })
            ->columnSpanFull(),
        Hidden::make('guard_name')
            ->default(static::getDefaultGuardName()),
        Hidden::make(static::getPanelColumnName())
            ->default(static::resolveCurrentPanelScopeValue())
            ->dehydrated(static::shouldScopeRolesToCurrentPanel()),
    ])
    ->columns(2)
    ->columnSpanFull(),
```

**Notas técnicas:**
- `getPermissionFieldDefinitions()` é `public static` e já existe — retorna `array<string, array<int, int|string>>` onde keys são state paths (`permission_groups.resources.{hash}`) e values são arrays de IDs de permissão.
- A closure em `afterStateUpdated` é chamada em tempo de render quando o usuário interage, momento em que o Panel context está disponível.
- `->live()` garante re-render do label após mudança de estado.
- `->dehydrated(false)` garante que o campo não vai para o `$data` do form submit.

**Teste focal:**
```php
// tests/Feature/Workbench/PermissionResourceFormTest.php
public function test_select_all_toggle_sets_all_permission_field_states(): void
{
    // Setup: criar role e permissões, autenticar admin
    // Usar Livewire::test(CreatePermission::class)
    //   ->fillForm(['select_all_permissions' => true])
    //   e verificar que os checkboxes estão preenchidos
    // Para verificar set inverso: toggle false → todos arrays vazios
}
```

---

### Item 3 — Badges por Section em `buildResourcePermissionSections()`

**Arquivo:** `src/Resources/Permissions/PermissionResource.php`  
**Localização:** métodos `buildResourcePermissionSections()` e um novo helper `countSectionPermissions()`

**Novo método helper (adicionar após `buildResourcePermissionSections()`):**
```php
/**
 * Recursively count permission options in a list of resource nodes.
 *
 * @param  array<int, array<string, mixed>>  $nodes
 */
protected static function countSectionPermissions(array $nodes): int
{
    $count = 0;

    foreach ($nodes as $node) {
        $count += count($node['options']);

        foreach ($node['relation_managers'] as $relationManager) {
            $count += count($relationManager['options']);
        }

        if ($node['children'] !== []) {
            $count += static::countSectionPermissions($node['children']);
        }
    }

    return $count;
}
```

**Modificação em `buildResourcePermissionSections()`:**

Contexto atual (trecho crítico):
```php
foreach ($resourceTree as $sectionLabel => $nodes) {
    $tabs = array_values(array_map(
        static fn (array $node): Tab => static::buildResourceNodeTab($node),
        $nodes,
    ));

    $sectionIcon = static::resolveResourceSectionIcon($nodes);

    $sections[] = Section::make($sectionLabel)
        ->icon($sectionIcon)
        ->schema([
```

Código final (adicionar `$sectionPermissionCount` e `->badge()`):
```php
foreach ($resourceTree as $sectionLabel => $nodes) {
    $tabs = array_values(array_map(
        static fn (array $node): Tab => static::buildResourceNodeTab($node),
        $nodes,
    ));

    $sectionIcon = static::resolveResourceSectionIcon($nodes);
    $sectionPermissionCount = static::countSectionPermissions($nodes);

    $sections[] = Section::make($sectionLabel)
        ->badge($sectionPermissionCount)
        ->icon($sectionIcon)
        ->schema([
```

> **Atenção:** `->badge()` deve vir antes de `->icon()` por convenção de legibilidade.

---

### Item 4 — Description com `trans_choice` nas Sections

**Arquivo:** `src/Resources/Permissions/PermissionResource.php`  
**Localização:** continuação do Item 3, ainda em `buildResourcePermissionSections()`

**Modificação — adicionar `->description()` logo após `->badge()`:**
```php
$sections[] = Section::make($sectionLabel)
    ->badge($sectionPermissionCount)
    ->description(trans_choice(
        'filament-acl::filament-acl.resources.permissions.section_description',
        $sectionPermissionCount,
    ))
    ->icon($sectionIcon)
    ->schema([
```

**Arquivos de tradução (Item 4 + Item 10 juntos):**

`resources/lang/en/filament-acl.php` — adicionar dentro de `resources.permissions`:
```php
'section_description' => '1 permission|:count permissions',
'navigation_group' => 'Access Control',
```

`resources/lang/pt_BR/filament-acl.php` — adicionar dentro de `resources.permissions`:
```php
'section_description' => '1 permissão|:count permissões',
'navigation_group' => 'Controle de Acesso',
```

**Resultado esperado:**
- Section com 1 permissão: "1 permission" / "1 permissão"
- Section com 5 permissões: "5 permissions" / "5 permissões"

> `trans_choice($key, $count)` com a forma simples `'singular|plural'` usa `:count` automaticamente quando `$count > 1`.

---

### Item 5 — Remover navigation group do ModerationPosts\PostResource

**Arquivo:** `workbench/app/Filament/Resources/ModerationPosts/PostResource.php`

**Contexto atual:**
```php
public static function getNavigationGroup(): ?string
{
    return __('workbench::workbench.resources.moderation_posts.navigation_group');
}
```

**Código final:**
```php
public static function getNavigationGroup(): ?string
{
    return null;
}
```

**Efeito:** `ModerationPosts\PostResource` deixa de ser agrupada — aparece solta na sidebar. Na aba de permissões do `PermissionResource`, será colocada na section padrão `'Resources'` (fallback do `resolveResourceSectionLabel()`).

---

### Item 6 — Enum `NavigationGroup` no workbench

**Arquivo novo:** `workbench/app/Enums/NavigationGroup.php`

```php
<?php

declare(strict_types=1);

namespace Workbench\App\Enums;

use Filament\Support\Contracts\HasIcon;

enum NavigationGroup: string implements HasIcon
{
    case Blog = 'Blog';

    public function getIcon(): string
    {
        return 'heroicon-o-document-text';
    }
}
```

**Atualização de `Posts/PostResource.php`:**

Adicionar import:
```php
use Workbench\App\Enums\NavigationGroup;
```

Substituir `getNavigationGroup()`:
```php
// Remover return type ?string; BackedEnum é aceito pelo Filament
public static function getNavigationGroup(): string | \UnitEnum | null
{
    return NavigationGroup::Blog;
}
```

**Atualização de `Categories/CategoryResource.php`:**

Mesmo padrão: adicionar import + substituir `getNavigationGroup()` para retornar `NavigationGroup::Blog`.

**Efeito no PermissionResource:**  
Em `resolveResourceSectionLabel()`, `$navigationGroup instanceof BackedEnum` → retorna `$navigationGroup->value` = `'Blog'`. Em `resolveResourceSectionIcon()`, `$navigationGroup instanceof HasIcon` → retorna `'heroicon-o-document-text'`. A section do Blog terá badge, ícone e descrição corretos.

---

### Item 7 — Bug UserPolicy: adicionar `ChecksPermission`

**Arquivo:** `workbench/app/Policies/UserPolicy.php`

**Código completo após modificação:**
```php
<?php

declare(strict_types=1);

namespace Workbench\App\Policies;

use CoringaWc\FilamentAcl\Policies\Concerns\ChecksPermission;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use Illuminate\Auth\Access\Response;
use Workbench\App\Models\User;

class UserPolicy
{
    use ChecksPermission;

    public function viewAny(User $user, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'viewAny', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function view(User $user, User $record, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'view', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function update(User $user, User $record): Response
    {
        return Response::allow();
    }
}
```

**Notas:**
- `viewAny` e `view` passam a checar permissão via `denyUnlessPermitted` antes de permitir.
- `update` permanece como `Response::allow()` conforme decisão — sem `PermissionAction`.
- Seguir exatamente o padrão de `PostPolicy.php` e `RolePolicy.php`.

---

### Item 8 — Auto-fill credenciais admin na tela de login

**Arquivo novo:** `workbench/app/Filament/Pages/Login.php`

```php
<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

class Login extends \Filament\Pages\Auth\Login
{
    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'email' => 'admin@filament-acl.test',
            'password' => 'password',
        ]);
    }
}
```

**Modificação em `AdminPanelProvider.php`:**

Adicionar import:
```php
use Workbench\App\Filament\Pages\Login;
```

Substituir `->login()` por `->login(Login::class)`:
```php
->login(Login::class)
```

---

### Item 9 — Email copyable no UserResource

**Arquivo:** `workbench/app/Filament/Resources/Users/UserResource.php`

**Em `infolist()`**, localizar:
```php
TextEntry::make('email')
    ->label(__('workbench::workbench.resources.users.fields.email')),
```
Adicionar `->copyable()`:
```php
TextEntry::make('email')
    ->label(__('workbench::workbench.resources.users.fields.email'))
    ->copyable(),
```

**Em `table()`**, localizar:
```php
TextColumn::make('email')
    ->label(__('workbench::workbench.resources.users.columns.email'))
    ->searchable(),
```
Adicionar `->copyable()`:
```php
TextColumn::make('email')
    ->label(__('workbench::workbench.resources.users.columns.email'))
    ->copyable()
    ->searchable(),
```

---

### Item 10 — Labels do PermissionResource traduzidas (navigation group fallback)

#### 10a — `PermissionResource.php`

**Localização:** método `getNavigationGroup()` (aprox. linha 213)

**Código atual:**
```php
public static function getNavigationGroup(): string | UnitEnum | null
{
    return static::getPermissionResourceConfiguration()?->getNavigationGroup()
        ?? config('filament-acl.resources.permissions.navigation_group');
}
```

**Código final:**
```php
public static function getNavigationGroup(): string | UnitEnum | null
{
    return static::getPermissionResourceConfiguration()?->getNavigationGroup()
        ?? config('filament-acl.resources.permissions.navigation_group')
        ?: __('filament-acl::filament-acl.resources.permissions.navigation_group');
}
```

**Lógica da cadeia:**
- `getPermissionResourceConfiguration()?->getNavigationGroup()` → não nulo → usa ele.
- Se nulo → `?? config(...)` → se config set → usa config.
- `(null ?? null) = null` → `null ?: __('...')` → usa a tradução como fallback.
- Se config retorna string vazia `''` → `'' ?: __('...')` → usa a tradução (falsy string).

#### 10b — `AdminPanelProvider.php`

**Remover as duas linhas do plugin:**
```php
->permissionsResourceNavigationLabel('Permissions')    // ← REMOVER
->permissionsResourceNavigationGroup('Access Control') // ← REMOVER
```

**Resultado após remoção:**
```php
->plugin(
    FilamentAclPlugin::make()
        ->permissionsResource()
        ->permissionsResourceNavigationSort(50),
);
```

#### 10c — Traduções (ver Item 4 — já declaradas acima)

Chaves `navigation_group` em `en` e `pt_BR` já descritas no Item 4.

---

## Fase 4 — Testes

### `tests/Feature/Workbench/UserPolicyPermissionTest.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Workbench\App\Filament\Resources\Users\UserResource;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

class UserPolicyPermissionTest extends TestCase
{
    /**
     * viewAny deve ser negado sem permissão (comportamento pós-fix do bug).
     */
    public function test_view_any_is_denied_without_permission(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $actor,
            ability: 'viewAny',
            target: User::class,
            action: UserResource::class,
        );

        self::assertFalse($response->allowed());
    }

    /**
     * viewAny deve ser permitido com a permissão correta.
     */
    public function test_view_any_is_allowed_with_permission(): void
    {
        $actor = User::factory()->create();
        $this->grantOwnerPermission($actor, 'viewAny', UserResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $actor,
            ability: 'viewAny',
            target: User::class,
            action: UserResource::class,
        );

        self::assertTrue($response->allowed());
    }

    /**
     * view deve ser negado sem permissão.
     */
    public function test_view_is_denied_without_permission(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $this->actingAs($actor);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $actor,
            ability: 'view',
            target: $target,
            action: UserResource::class,
        );

        self::assertFalse($response->allowed());
    }

    /**
     * update deve sempre ser permitido (sem checagem de permissão).
     */
    public function test_update_is_always_allowed_regardless_of_permissions(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $this->actingAs($actor);

        // Sem nenhuma permissão concedida
        $gate = $this->app['gate'];
        $response = $gate->inspect('update', $target);

        self::assertTrue($response->allowed());
    }
}
```

### `tests/Feature/Workbench/PermissionResourceFormTest.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Resources\Permissions\Pages\CreatePermission;
use CoringaWc\FilamentAcl\Resources\Permissions\Pages\EditPermission;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Workbench\App\Models\User;

class PermissionResourceFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('filament-acl:sync --panel=admin');
    }

    /**
     * O formulário de criação de role renderiza sem erros.
     */
    public function test_create_permission_form_renders(): void
    {
        $actor = User::factory()->create();
        $this->grantOwnerPermission($actor, 'create', PermissionResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        Livewire::test(CreatePermission::class)
            ->assertOk()
            ->assertSee(__('filament-acl::filament-acl.resources.permissions.section_toggle.select_all'));
    }

    /**
     * Toggle "select all" está presente e não é enviado no submit.
     */
    public function test_select_all_toggle_is_dehydrated_false(): void
    {
        $actor = User::factory()->create();
        $this->grantOwnerPermission($actor, 'create', PermissionResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        // O campo select_all_permissions com dehydrated(false)
        // não deve aparecer em assertFormFieldIsVisible quando serializado —
        // mas deve ser visível no form HTML.
        Livewire::test(CreatePermission::class)
            ->assertOk()
            ->assertFormFieldExists('select_all_permissions');
    }
}
```

### `tests/Feature/Workbench/NavigationGroupEnumTest.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Support\Contracts\HasIcon;
use Workbench\App\Enums\NavigationGroup;
use Workbench\App\Filament\Resources\Categories\CategoryResource;
use Workbench\App\Filament\Resources\ModerationPosts\PostResource as ModerationPostResource;
use Workbench\App\Filament\Resources\Posts\PostResource;

class NavigationGroupEnumTest extends TestCase
{
    public function test_navigation_group_enum_implements_has_icon(): void
    {
        self::assertInstanceOf(HasIcon::class, NavigationGroup::Blog);
    }

    public function test_navigation_group_blog_returns_correct_icon(): void
    {
        self::assertSame('heroicon-o-document-text', NavigationGroup::Blog->getIcon());
    }

    public function test_post_resource_returns_blog_navigation_group(): void
    {
        self::assertSame(NavigationGroup::Blog, PostResource::getNavigationGroup());
    }

    public function test_category_resource_returns_blog_navigation_group(): void
    {
        self::assertSame(NavigationGroup::Blog, CategoryResource::getNavigationGroup());
    }

    public function test_moderation_post_resource_returns_null_navigation_group(): void
    {
        self::assertNull(ModerationPostResource::getNavigationGroup());
    }
}
```

---

## Smoke Tests Finais

```bash
# 1. Formatação
docker compose exec php vendor/bin/pint --dirty

# 2. Análise estática
docker compose exec php vendor/bin/phpstan analyse --memory-limit=1G

# 3. Testes focais (novos)
docker compose exec php vendor/bin/phpunit tests/Feature/Workbench/UserPolicyPermissionTest.php --testdox
docker compose exec php vendor/bin/phpunit tests/Feature/Workbench/NavigationGroupEnumTest.php --testdox
docker compose exec php vendor/bin/phpunit tests/Feature/Workbench/PermissionResourceFormTest.php --testdox

# 4. Suíte completa
docker compose exec php vendor/bin/phpunit --testdox
```

---

## Checklist do Agente Revisor (Fase 5)

### Checklist — PermissionResource.php

- [ ] `->searchable()` removido de `makePermissionCheckboxList()` (Item 1)
- [ ] `Toggle::make('select_all_permissions')` com `->dehydrated(false)` + `->live()` + `->afterStateUpdated()` presente na Section do `name` (Item 2)
- [ ] Imports adicionados: `Toggle`, `Get`, `Set` (Item 2)
- [ ] `$set($path, $state ? array_values($ids) : [])` itera `getPermissionFieldDefinitions()` (Item 2)
- [ ] `countSectionPermissions(array $nodes): int` helper existe e é recursivo (Item 3)
- [ ] `->badge($sectionPermissionCount)` presente em cada Section de `buildResourcePermissionSections()` (Item 3)
- [ ] `->description(trans_choice('...section_description', $sectionPermissionCount))` presente (Item 4)
- [ ] `getNavigationGroup()` usa `?: __('filament-acl::filament-acl.resources.permissions.navigation_group')` como fallback final (Item 10)

### Checklist — lang files

- [ ] `section_description` presente em `en` e `pt_BR` com formato pipe `singular|plural` (Item 4)
- [ ] `navigation_group` presente em `en` (`'Access Control'`) e `pt_BR` (`'Controle de Acesso'`) (Item 10)

### Checklist — Workbench

- [ ] `workbench/app/Enums/NavigationGroup.php` existe com `case Blog`, `implements HasIcon`, `getIcon(): string` (Item 6)
- [ ] `Posts/PostResource::getNavigationGroup()` retorna `NavigationGroup::Blog` (Item 6)
- [ ] `Categories/CategoryResource::getNavigationGroup()` retorna `NavigationGroup::Blog` (Item 6)
- [ ] `ModerationPosts/PostResource::getNavigationGroup()` retorna `null` (Item 5)
- [ ] `UserPolicy` usa `ChecksPermission`, `viewAny` e `view` checam permissão, `update` sem checagem (Item 7)
- [ ] `workbench/app/Filament/Pages/Login.php` existe e preenche `email` + `password` em `mount()` (Item 8)
- [ ] `AdminPanelProvider::panel()` usa `->login(Login::class)` (Item 8)
- [ ] `AdminPanelProvider::panel()` **não** tem `->permissionsResourceNavigationGroup()` nem `->permissionsResourceNavigationLabel()` (Item 10)
- [ ] `UserResource::infolist()` tem `TextEntry::make('email')->copyable()` (Item 9)
- [ ] `UserResource::table()` tem `TextColumn::make('email')->copyable()` (Item 9)

### Checklist — Anti-Padrões (ausência)

- [ ] Nenhum campo de form com `->dehydrated(false)` sendo lido em mutators/observers
- [ ] Nenhum `env()` fora de config
- [ ] Nenhum `DB::` direto
- [ ] PHPStan sem erros novos além da baseline

---

*Plano salvo em: `.github/plans/filament-acl-10-items-plan.md`*
