# Plano de Implementação — filament-acl Improvements
**Data:** 2026-04-05  
**Branch:** `5.x`  
**Executor:** Implementation Executor

---

## Fase 0 — Inventário Completo

### Arquivos a CRIAR (3)

| # | Caminho | Item |
|---|---------|------|
| 1 | `src/Models/Role.php` | 4 |
| 2 | `src/Models/Permission.php` | 4 |
| 3 | `workbench/lang/en/workbench.php` | 1 |

### Arquivos a MODIFICAR (9)

| # | Caminho | Item | Resumo da mudança |
|---|---------|------|-------------------|
| 1 | `workbench/app/Models/Role.php` | 4 | Trocar extends para `CoringaWc\FilamentAcl\Models\Role` |
| 2 | `workbench/app/Providers/WorkbenchServiceProvider.php` | 1 | Registrar namespace de tradução `'workbench'` em `boot()` |
| 3 | `workbench/app/Filament/Resources/Posts/PostResource.php` | 1 | Labels com `__()` em form, infolist e table |
| 4 | `workbench/app/Filament/Resources/ModerationPosts/PostResource.php` | 1 | Labels com `__()` em form, infolist e table |
| 5 | `workbench/app/Filament/Resources/Categories/CategoryResource.php` | 1 | Labels com `__()` em form, infolist e table |
| 6 | `workbench/app/Filament/Resources/Users/UserResource.php` | 1 | Labels com `__()` em form, infolist e table |
| 7 | `resources/lang/en/filament-acl.php` | 2 | Adicionar sub-array `'actions'` em `resources.permissions` |
| 8 | `resources/lang/pt_BR/filament-acl.php` | 2 | Adicionar sub-array `'actions'` em `resources.permissions` |
| 9 | `src/Resources/Permissions/PermissionResource.php` | 2 + 3 | 4 mudanças cirúrgicas: tradução de abilities + resolveOwnerLabel + ícones de section + ícones de tab |

**Total: 12 arquivos** (3 criações + 9 modificações)

### Dependências de ordem obrigatórias

```
Fase 1 (Item 4)  →  independente; pode ser executada a qualquer momento
Fase 2 (Item 1)  →  independente de Fase 1; pode ser em paralelo
Fase 3 (Item 2)  →  edita PermissionResource.php; independente de Fases 1 e 2
Fase 4 (Item 3)  →  edita PermissionResource.php APÓS Fase 3
                    (Item 3 deve ser aplicado ao mesmo arquivo depois do Item 2)
Fase 5           →  apenas after todas as fases anteriores
```

---

## Fase 1 — Item 4: Models Base no Plugin

### 1.1 — Criar `src/Models/Role.php`

Criar o diretório `src/Models/` (não existe) e o arquivo:

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as BaseRole;

class Role extends BaseRole
{
    use HasFactory;
}
```

### 1.2 — Criar `src/Models/Permission.php`

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as BasePermission;

class Permission extends BasePermission
{
    use HasFactory;
}
```

### 1.3 — Modificar `workbench/app/Models/Role.php`

**Antes:**
```php
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
```

**Depois:**
```php
use CoringaWc\FilamentAcl\Models\Role as PluginRole;

class Role extends PluginRole
```

Remover o `use Spatie\Permission\Models\Role as SpatieRole;` e substituir pelo import do plugin. O use `HasFactory` permanece (já existe via herança, mas manter explícito na classe do workbench é opcional — remover se duplicado, manter se não for).

**Arquivo final completo:**

```php
<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use CoringaWc\FilamentAcl\Models\Role as PluginRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends PluginRole
{
    use HasFactory;
}
```

> ⚠️ `WorkbenchServiceProvider` mantém `config(['permission.models.role' => Role::class])` apontando para `Workbench\App\Models\Role` — não alterar essa linha.

---

## Fase 2 — Item 1: Tradução do Workbench

### 2.1 — Criar `workbench/lang/en/workbench.php`

```php
<?php

declare(strict_types=1);

return [
    'resources' => [
        'posts' => [
            'fields' => [
                'author'     => 'Author',
                'title'      => 'Title',
                'status'     => 'Status',
                'categories' => 'Categories',
                'content'    => 'Content',
            ],
            'columns' => [
                'title'      => 'Title',
                'author'     => 'Author',
                'status'     => 'Status',
                'categories' => 'Categories',
            ],
        ],
        'moderation_posts' => [
            'fields' => [
                'title'  => 'Title',
                'status' => 'Status',
            ],
            'columns' => [
                'title'  => 'Title',
                'status' => 'Status',
            ],
        ],
        'categories' => [
            'fields' => [
                'name'        => 'Name',
                'description' => 'Description',
                'posts'       => 'Posts',
            ],
            'columns' => [
                'name'        => 'Name',
                'description' => 'Description',
                'posts_count' => 'Posts Count',
            ],
        ],
        'users' => [
            'fields' => [
                'name'  => 'Name',
                'email' => 'Email',
                'roles' => 'Roles',
            ],
            'columns' => [
                'name'  => 'Name',
                'email' => 'Email',
                'roles' => 'Roles',
            ],
        ],
    ],
];
```

### 2.2 — Modificar `workbench/app/Providers/WorkbenchServiceProvider.php`

Adicionar uma linha no método `boot()`, **após** `App::setLocale(...)`:

```php
$this->loadTranslationsFrom(__DIR__ . '/../../lang', 'workbench');
```

O `boot()` resultante fica:

```php
public function boot(): void
{
    App::setLocale(config('app.locale'));

    $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'workbench');

    View::prependNamespace('filament-panels', __DIR__ . '/../../resources/views/vendor/filament-panels');
    View::addNamespace('workbench', __DIR__ . '/../../resources/views');

    Gate::policy(Category::class, CategoryPolicy::class);
    Gate::policy(Post::class, PostPolicy::class);
    Gate::policy(Role::class, RolePolicy::class);
    Gate::policy(User::class, UserPolicy::class);
}
```

### 2.3 — Modificar `workbench/app/Filament/Resources/Posts/PostResource.php`

**Método `form()`** — alterar os campos com label hardcoded e adicionar labels nos campos sem label:

```php
Select::make('user_id')
    ->label(__('workbench::workbench.resources.posts.fields.author'))  // era: ->label('Author')
    // ... demais métodos inalterados

TextInput::make('title')
    ->label(__('workbench::workbench.resources.posts.fields.title'))   // NOVO — antes sem label
    ->required()
    ->maxLength(255),

TextInput::make('status')
    ->label(__('workbench::workbench.resources.posts.fields.status'))  // NOVO
    ->default('draft')
    ->required(),

Select::make('categories')
    ->label(__('workbench::workbench.resources.posts.fields.categories')) // NOVO
    ->relationship('categories', 'name')
    // ...

Textarea::make('content')
    ->label(__('workbench::workbench.resources.posts.fields.content'))  // NOVO
    ->columnSpanFull(),
```

**Método `infolist()`**:

```php
TextEntry::make('user.name')
    ->label(__('workbench::workbench.resources.posts.fields.author')),  // era: ->label('Author')

TextEntry::make('title')
    ->label(__('workbench::workbench.resources.posts.fields.title')),   // NOVO

TextEntry::make('status')
    ->label(__('workbench::workbench.resources.posts.fields.status')),  // NOVO

TextEntry::make('content')
    ->label(__('workbench::workbench.resources.posts.fields.content')), // NOVO

TextEntry::make('categories_list')
    ->label(__('workbench::workbench.resources.posts.columns.categories')) // era: ->label('Categories')
    ->state(...)
```

**Método `table()`**:

```php
TextColumn::make('title')
    ->label(__('workbench::workbench.resources.posts.columns.title'))    // NOVO
    ->searchable(),

TextColumn::make('user.name')
    ->label(__('workbench::workbench.resources.posts.columns.author')),  // era: ->label('Author')

TextColumn::make('status')
    ->label(__('workbench::workbench.resources.posts.columns.status'))   // NOVO
    ->badge(),

TextColumn::make('categories_list')
    ->label(__('workbench::workbench.resources.posts.columns.categories')) // era: ->label('Categories')
    ->state(...)
    ->wrap(),
```

### 2.4 — Modificar `workbench/app/Filament/Resources/ModerationPosts/PostResource.php`

**Método `form()`**:

```php
TextInput::make('title')
    ->label(__('workbench::workbench.resources.moderation_posts.fields.title'))   // NOVO
    ->disabled(),

TextInput::make('status')
    ->label(__('workbench::workbench.resources.moderation_posts.fields.status'))  // NOVO
    ->disabled(),
```

**Método `infolist()`**:

```php
TextEntry::make('title')
    ->label(__('workbench::workbench.resources.moderation_posts.fields.title')),  // NOVO

TextEntry::make('status')
    ->label(__('workbench::workbench.resources.moderation_posts.fields.status')), // NOVO
```

**Método `table()`**:

```php
TextColumn::make('title')
    ->label(__('workbench::workbench.resources.moderation_posts.columns.title'))   // NOVO
    ->searchable(),

TextColumn::make('status')
    ->label(__('workbench::workbench.resources.moderation_posts.columns.status'))  // NOVO
    ->badge(),
```

### 2.5 — Modificar `workbench/app/Filament/Resources/Categories/CategoryResource.php`

**Método `form()`**:

```php
TextInput::make('name')
    ->label(__('workbench::workbench.resources.categories.fields.name'))        // NOVO
    ->required()
    ->maxLength(255),

Textarea::make('description')
    ->label(__('workbench::workbench.resources.categories.fields.description')) // NOVO
    ->columnSpanFull(),

Select::make('posts')
    ->label(__('workbench::workbench.resources.categories.fields.posts'))       // NOVO
    ->relationship('posts', 'title')
    // ...
```

**Método `infolist()`**:

```php
TextEntry::make('name')
    ->label(__('workbench::workbench.resources.categories.fields.name')),       // NOVO

TextEntry::make('description')
    ->label(__('workbench::workbench.resources.categories.fields.description')), // NOVO

TextEntry::make('posts_list')
    ->label(__('workbench::workbench.resources.categories.fields.posts'))        // era: ->label('Posts')
    ->state(...)
```

**Método `table()`**:

```php
TextColumn::make('name')
    ->label(__('workbench::workbench.resources.categories.columns.name'))        // NOVO
    ->searchable(),

TextColumn::make('description')
    ->label(__('workbench::workbench.resources.categories.columns.description')) // NOVO
    ->limit(40),

TextColumn::make('posts_count')
    ->label(__('workbench::workbench.resources.categories.columns.posts_count')) // NOVO
    ->counts('posts')
    ->badge(),
```

### 2.6 — Modificar `workbench/app/Filament/Resources/Users/UserResource.php`

**Método `form()`** — apenas o Select de roles não tem label; os TextInputs de name/email também precisam:

```php
TextInput::make('name')
    ->label(__('workbench::workbench.resources.users.fields.name'))   // NOVO
    ->required(),

TextInput::make('email')
    ->label(__('workbench::workbench.resources.users.fields.email'))  // NOVO
    ->email()
    ->required(),

Select::make('roles')
    ->label(__('workbench::workbench.resources.users.fields.roles'))  // NOVO
    ->disabled(...)
    // ...
```

**Método `infolist()`**:

```php
TextEntry::make('name')
    ->label(__('workbench::workbench.resources.users.fields.name')),   // NOVO

TextEntry::make('email')
    ->label(__('workbench::workbench.resources.users.fields.email')),  // NOVO

TextEntry::make('roles.name')
    ->label(__('workbench::workbench.resources.users.fields.roles'))   // era: ->label('Roles')
    ->badge()
    ->state(...)
```

**Método `table()`**:

```php
TextColumn::make('name')
    ->label(__('workbench::workbench.resources.users.columns.name'))   // NOVO
    ->searchable(),

TextColumn::make('email')
    ->label(__('workbench::workbench.resources.users.columns.email'))  // NOVO
    ->searchable(),

TextColumn::make('visible_roles')
    ->label(__('workbench::workbench.resources.users.columns.roles'))  // era: ->label('Roles')
    ->badge()
    ->state(...)
```

---

## Fase 3 — Item 2: Tradução dos Labels de Abilities

### 3.1 — Modificar `resources/lang/en/filament-acl.php`

Adicionar a sub-chave `'actions'` dentro de `'resources' => ['permissions' => [...]]`.

**Localização exata:** após a chave `'groups'`, dentro do array `'permissions'`.

**Diff da estrutura:**

```php
// ANTES:
'groups' => [
    'resources' => 'Resources',
    'ungrouped' => 'Other',
],
// (fim do array 'permissions')

// DEPOIS:
'groups' => [
    'resources' => 'Resources',
    'ungrouped' => 'Other',
],
'actions' => [
    'view_any'     => 'View Any',
    'view'         => 'View',
    'create'       => 'Create',
    'update'       => 'Update',
    'delete'       => 'Delete',
    'force_delete' => 'Force Delete',
    'restore'      => 'Restore',
    'replicate'    => 'Replicate',
    'reorder'      => 'Reorder',
],
```

### 3.2 — Modificar `resources/lang/pt_BR/filament-acl.php`

Mesma posição (após `'groups'`):

```php
'actions' => [
    'view_any'     => 'Ver todos',
    'view'         => 'Visualizar',
    'create'       => 'Criar',
    'update'       => 'Atualizar',
    'delete'       => 'Excluir',
    'force_delete' => 'Excluir definitivamente',
    'restore'      => 'Restaurar',
    'replicate'    => 'Replicar',
    'reorder'      => 'Reordenar',
],
```

> `resources/lang/pt-BR/filament-acl.php` faz `return require __DIR__ . '/../pt_BR/filament-acl.php';` — **não precisa ser alterado**.

### 3.3 — Modificar `src/Resources/Permissions/PermissionResource.php` — método `getOwnerPermissionOptions()`

**Localizar** o trecho exato (linhas ~635-637):

```php
            $options[$permission->getKey()] = Str::of($ability)->headline()->toString();
```

**Substituir por:**

```php
            $translationKey = "filament-acl::filament-acl.resources.permissions.actions.{$ability}";
            $translated = __($translationKey);
            $options[$permission->getKey()] = $translated !== $translationKey
                ? $translated
                : Str::of($ability)->headline()->toString();
```

> A verificação `$translated !== $translationKey` detecta se o `__()` não encontrou a chave (Laravel retorna a própria chave quando não há tradução) — mantendo o fallback para abilities customizadas não cobertas pelo lang file.

---

## Fase 4 — Item 3: Ícones e Labels via Métodos Reais

> ⚠️ **Aplicar em `src/Resources/Permissions/PermissionResource.php` APÓS a Fase 3** (ambas editam o mesmo arquivo).  
> **Verificação prévia já realizada:** `Tab` (linha 24 do vendor) usa `HasIcon`; `Section` (linha 44) usa `HasIcon`. Ambos suportam `->icon()`.

### 4.1 — Substituir `resolveOwnerLabel()`

**Localizar** o método atual (linhas ~950-965):

```php
protected static function resolveOwnerLabel(PermissionOwnerRegistration $ownerRegistration): string
{
    return match ($ownerRegistration->ownerType) {
        PermissionEntityType::Resource => Str::headline(Str::beforeLast(class_basename($ownerRegistration->ownerClass), 'Resource')),
        PermissionEntityType::RelationManager => Str::headline(Str::beforeLast(class_basename($ownerRegistration->ownerClass), 'RelationManager')),
        PermissionEntityType::Page => Str::headline(Str::beforeLast(class_basename($ownerRegistration->ownerClass), 'Page')),
        PermissionEntityType::Widget => Str::headline(Str::beforeLast(class_basename($ownerRegistration->ownerClass), 'Widget')),
        default => Str::headline(class_basename($ownerRegistration->ownerClass)),
    };
}
```

**Substituir por:**

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

> **Justificativa:** `getModelLabel()` é herdado de `Resource` para todas as classes resource, portanto o `method_exists` é redundante. A chamada via `withOwnerConfigurationContext` garante que o painel e o resource configuration key estejam definidos corretamente ao resolver o label, respeitando qualquer lógica contextual do resource.

### 4.2 — Adicionar método `resolveResourceSectionIcon()`

Adicionar **após** o método `resolveResourceSectionLabel()` (linha ~580):

```php
protected static function resolveResourceSectionIcon(string $resourceClass): string | BackedEnum | Htmlable | null
{
    $cluster = $resourceClass::getCluster();

    if (($cluster !== null) && is_subclass_of($cluster, Cluster::class)) {
        return $cluster::getNavigationIcon();
    }

    return null;
}
```

> O mesmo padrão `is_subclass_of($cluster, Cluster::class)` já é usado em `resolveResourceSectionLabel()` na linha acima — não há novo risco de PHPStan aqui.  
> Sections de navigation groups (sem cluster) retornam `null` — sem ícone, que é o comportamento correto.

### 4.3 — Modificar `buildResourcePermissionSections()` para passar ícone à Section

**Localizar** o `foreach` dentro de `buildResourcePermissionSections()` (linhas ~355-370):

```php
        foreach ($resourceTree as $sectionLabel => $nodes) {
            $tabs = array_values(array_map(
                static fn (array $node): Tab => static::buildResourceNodeTab($node),
                $nodes,
            ));

            $sections[] = Section::make($sectionLabel)
                ->schema([
                    Tabs::make('resource_section_' . Str::slug($sectionLabel))
                        ->tabs($tabs),
                ])
                ->columnSpanFull()
                ->compact()
                ->collapsible()
                ->collapsed();
        }
```

**Substituir por:**

```php
        foreach ($resourceTree as $sectionLabel => $nodes) {
            $tabs = array_values(array_map(
                static fn (array $node): Tab => static::buildResourceNodeTab($node),
                $nodes,
            ));

            $sectionIcon = static::resolveResourceSectionIcon($nodes[0]['owner_class']);

            $sections[] = Section::make($sectionLabel)
                ->schema([
                    Tabs::make('resource_section_' . Str::slug($sectionLabel))
                        ->tabs($tabs),
                ])
                ->icon($sectionIcon)
                ->columnSpanFull()
                ->compact()
                ->collapsible()
                ->collapsed();
        }
```

> `$nodes[0]['owner_class']` é seguro: o `buildResourceTree()` produz `$sections[$sectionLabel][] = $node` e só inclui entradas com pelo menos um nó. Um array vazio nunca chega ao `foreach`.

### 4.4 — Adicionar campo `'icon'` nos nodes de `getDiscoverableResourceNodes()`

**Localizar** no método `getDiscoverableResourceNodes()` o bloco `$nodes[] = [...]` (linhas ~512-530):

```php
            $nodes[] = [
                'node_key' => $resourceRegistration->uniqueKey(),
                'owner_class' => $resourceRegistration->ownerClass,
                'registration_key' => $resourceRegistration->registrationKey,
                'label' => $resourceRegistration->label ?? static::resolveOwnerLabel($resourceRegistration),
                'section_label' => $resourceRegistration->sectionLabel ?? static::resolveResourceSectionLabel($resourceRegistration->ownerClass),
                'state_path' => static::makePermissionStatePath(
                    'resources',
                    $resourceRegistration->ownerClass,
                    $resourceRegistration->registrationKey,
                ),
                'options' => $options,
                'relation_managers' => static::getRelationManagerNodes($resourceRegistration),
            ];
```

**Substituir por:**

```php
            $nodes[] = [
                'node_key' => $resourceRegistration->uniqueKey(),
                'owner_class' => $resourceRegistration->ownerClass,
                'registration_key' => $resourceRegistration->registrationKey,
                'label' => $resourceRegistration->label ?? static::resolveOwnerLabel($resourceRegistration),
                'section_label' => $resourceRegistration->sectionLabel ?? static::resolveResourceSectionLabel($resourceRegistration->ownerClass),
                'state_path' => static::makePermissionStatePath(
                    'resources',
                    $resourceRegistration->ownerClass,
                    $resourceRegistration->registrationKey,
                ),
                'options' => $options,
                'relation_managers' => static::getRelationManagerNodes($resourceRegistration),
                'icon' => static::withOwnerConfigurationContext(
                    $resourceRegistration,
                    static fn (): string | BackedEnum | Htmlable | null => $resourceRegistration->ownerClass::getNavigationIcon(),
                ),
            ];
```

### 4.5 — Modificar `buildResourceNodeTab()` para usar `->icon()`

**Localizar** o `return [Tab::make($node['label'])...]` no método `buildResourceNodeTab()` (linhas ~474-491):

```php
        return [
            Tab::make($node['label'])
                ->schema(array_values(array_filter([
```

**Substituir apenas a linha de `Tab::make()`:**

```php
        return [
            Tab::make($node['label'])
                ->icon($node['icon'] ?? null)
                ->schema(array_values(array_filter([
```

> O campo `'icon'` é `string|BackedEnum|Htmlable|null`. `Tab::icon()` aceita `string|BackedEnum|Htmlable|Closure|null` (confirmado via `HasIcon`). Passar `null` equivale a não ter ícone — sem efeito visual.  
> Nodes de `getRelationManagerNodes()` não incluem `'icon'` — os tabs de RelationManager criados dentro do `foreach ($node['relation_managers'])` não recebem ícone (intencional: relation managers não têm navigation icon).

---

## Fase 5 — Testes e Qualidade

### 5.1 — Testes existentes a verificar (sem modificação esperada)

Rodar após cada fase para garantir não-regressão:

```bash
docker compose exec php vendor/bin/phpunit --testdox tests/Feature/Workbench/FilamentWorkbenchSmokeTest.php
docker compose exec php vendor/bin/phpunit --testdox tests/Feature/Workbench/SyncPermissionsCommandTest.php
docker compose exec php vendor/bin/phpunit --testdox tests/Feature/Workbench/CategoryResourcePermissionTest.php
docker compose exec php vendor/bin/phpunit --testdox tests/Feature/Workbench/PostResourcePermissionTest.php
```

### 5.2 — Testes novos a criar

#### Teste 1: `tests/Unit/Models/PluginModelsTest.php`

**Propósito:** Verificar que os models do plugin existem, extendem as classes Spatie corretas, e que o workbench Role extende o model do plugin.

```bash
docker compose exec php vendor/bin/phpunit --testdox --make-test tests/Unit/Models/PluginModelsTest.php
```

**Cenários obrigatórios:**
1. `CoringaWc\FilamentAcl\Models\Role` is instance of `Spatie\Permission\Models\Role`
2. `CoringaWc\FilamentAcl\Models\Permission` is instance of `Spatie\Permission\Models\Permission`
3. `Workbench\App\Models\Role` is instance of `CoringaWc\FilamentAcl\Models\Role`
4. `Workbench\App\Models\Role` usa trait `HasFactory`

**Esqueleto mínimo:**

```php
<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit\Models;

use CoringaWc\FilamentAcl\Models\Permission;
use CoringaWc\FilamentAcl\Models\Role;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;
use Workbench\App\Models\Role as WorkbenchRole;

class PluginModelsTest extends TestCase
{
    public function test_plugin_role_extends_spatie_role(): void
    {
        $this->assertInstanceOf(SpatieRole::class, new Role());
    }

    public function test_plugin_permission_extends_spatie_permission(): void
    {
        $this->assertInstanceOf(SpatiePermission::class, new Permission());
    }

    public function test_workbench_role_extends_plugin_role(): void
    {
        $this->assertInstanceOf(Role::class, new WorkbenchRole());
    }

    public function test_plugin_role_uses_has_factory(): void
    {
        $this->assertContains(HasFactory::class, class_uses_recursive(Role::class));
    }
}
```

#### Teste 2: `tests/Unit/PermissionResource/AbilityLabelTranslationTest.php`

**Propósito:** Verificar que os labels de ability são traduzidos quando a chave existe no lang file, e que o fallback funciona para abilities desconhecidas.

> **Nota:** Este teste é Unit — não precisa de banco de dados. Testará via injeção de tradução mockada OU via teste de snapshot da string resultante ao carregar translations reais.

**Cenários obrigatórios:**
1. `view_any` retorna `'View Any'` (EN)
2. `force_delete` retorna `'Force Delete'` (EN)
3. Uma ability custom não cadastrada ('custom_action') retorna o Str::headline fallback ('Custom Action')

> **Desafio:** `getOwnerPermissionOptions()` depende de banco (query de permissions). Preferir testar a lógica de translação isolada extraindo-a para um método protegido testável — se possível. Caso contrário, criar um Feature test que use o seeder do workbench.

**Alternativa Feature:** Verificar nos testes existentes (`SyncPermissionsCommandTest.php`) se as labels de ability são validadas — se já há cobertura indireta, documentar e não duplicar.

### 5.3 — Comandos de qualidade obrigatórios (executar após todas as fases)

```bash
# 1. Formatar código
docker compose exec php vendor/bin/pint --dirty

# 2. Análise estática
docker compose exec php vendor/bin/phpstan analyse \
  src/Models/Role.php \
  src/Models/Permission.php \
  src/Resources/Permissions/PermissionResource.php \
  workbench/app/Models/Role.php \
  workbench/app/Providers/WorkbenchServiceProvider.php \
  workbench/app/Filament/Resources/Posts/PostResource.php \
  workbench/app/Filament/Resources/ModerationPosts/PostResource.php \
  workbench/app/Filament/Resources/Categories/CategoryResource.php \
  workbench/app/Filament/Resources/Users/UserResource.php \
  --memory-limit=1G

# 3. Suite completa de testes
docker compose exec php vendor/bin/phpunit --testdox
```

### 5.4 — Checklist de verificação manual

- [ ] `src/Models/Role.php` and `Permission.php` — namespace correto `CoringaWc\FilamentAcl\Models`
- [ ] `workbench/app/Models/Role.php` — extends `CoringaWc\FilamentAcl\Models\Role`
- [ ] `workbench/lang/en/workbench.php` existe e tem todas as chaves das 4 seções
- [ ] `WorkbenchServiceProvider::boot()` chama `loadTranslationsFrom(..., 'workbench')`
- [ ] Todos os 4 resources do workbench usam `__('workbench::workbench.resources.X.Y.Z')`
- [ ] `resources/lang/en/filament-acl.php` tem `'actions'` com 9 keys
- [ ] `resources/lang/pt_BR/filament-acl.php` tem `'actions'` com 9 keys em pt_BR
- [ ] `getOwnerPermissionOptions()` — lógica de tradução com fallback presente
- [ ] `resolveOwnerLabel()` — case `Resource` usa `withOwnerConfigurationContext` + `getModelLabel()`
- [ ] `resolveResourceSectionIcon()` — novo método existe após `resolveResourceSectionLabel()`
- [ ] `buildResourcePermissionSections()` — `->icon($sectionIcon)` na Section
- [ ] `getDiscoverableResourceNodes()` — campo `'icon'` no array
- [ ] `buildResourceNodeTab()` — `->icon($node['icon'] ?? null)` no Tab
- [ ] PHPStan sem erros novos (baseline vazia — qualquer erro novo é regressão)
- [ ] Pint sem diff após aplicar
- [ ] Todos os testes passando (incluindo `FilamentWorkbenchSmokeTest`)
