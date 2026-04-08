# Plano de Refatoração — Limpeza do Config `filament-acl.php`

> Status: **Em elaboração**
> Data: 2026-04-07

---

## Fase 0 — Inventário e Análise

### Decisões Tomadas

1. Callbacks (`resolve_permission_subject_using`, `build_permission_key_using`) saem do config → padrão `*Using()` no `FilamentPermissionManager` (já existente).
2. Core Runtime Services (`subject_resolver`, `permission_key_builder`, `permission_store`) saem do config → hardcode no ServiceProvider com bind contra contracts. App consumidor sobrescreve via `$this->app->singleton(Contract::class, CustomImpl::class)`.

### Inventário

| # | Arquivo | Ação |
|---|---------|------|
| 1 | `config/filament-acl.php` | `[MODIFICAR]` — remover seções `callbacks` e 3 chaves de runtime services |
| 2 | `workbench/config/filament-acl.php` | `[MODIFICAR]` — remover seções `callbacks` e 3 chaves de runtime services |
| 3 | `src/FilamentPermissionServiceProvider.php` | `[MODIFICAR]` — hardcode defaults nos bindings, remover leitura de config para callbacks e services |
| 4 | `README.md` | `[MODIFICAR]` — atualizar seção "Runtime Customization" e lista de config areas |
| 5 | `AGENTS.md` | Sem alteração necessária (já descreve o padrão `*Using()`) |
| 6 | `tests/Unit/FilamentPermissionManagerTest.php` | Sem alteração (testa callbacks via Manager, não via config) |
| 7 | `tests/Unit/ConfiguredPermissionSubjectResolverTest.php` | Sem alteração (usa Facade, não config callbacks) |

**Total: 4 arquivos modificados, 0 arquivos criados.**

---

## Fase 1 — Mudanças no ServiceProvider

### `src/FilamentPermissionServiceProvider.php`

**Antes (bindings leem config):**
```php
$this->app->singleton(ResolvesPermissionSubject::class, function ($app): ResolvesPermissionSubject {
    $resolver = config('filament-acl.subject_resolver', ConfiguredPermissionSubjectResolver::class);
    return $app->make($resolver);
});
// idem para BuildsPermissionKey e StoresPermissions
```

**Depois (hardcode defaults):**
```php
$this->app->singleton(ResolvesPermissionSubject::class, ConfiguredPermissionSubjectResolver::class);
$this->app->singleton(BuildsPermissionKey::class, DefaultPermissionKeyBuilder::class);
$this->app->singleton(StoresPermissions::class, SpatiePermissionStore::class);
```

**Remover bloco de callbacks:**
```php
// REMOVER INTEIRO:
$callbacks = config('filament-acl.callbacks', []);
if ($callbacks['resolve_permission_subject_using'] ?? null) { ... }
if ($callbacks['build_permission_key_using'] ?? null) { ... }
```

**Remover imports não mais necessários:** nenhum — os imports das classes default ainda são usados.

---

## Fase 2 — Mudanças no Config

### `config/filament-acl.php`

1. Remover seção "Core Runtime Services" (linhas ~32-42): `subject_resolver`, `permission_key_builder`, `permission_store` + docblock
2. Remover seção "Runtime Callbacks" (linhas finais): `callbacks` + docblock
3. Remover `use` statements não utilizados: `ConfiguredPermissionSubjectResolver`, `DefaultPermissionKeyBuilder`, `SpatiePermissionStore`

### `workbench/config/filament-acl.php`

1. Remover seção "Core Runtime Services": `subject_resolver`, `permission_key_builder`, `permission_store` + docblock
2. Remover seção "Runtime Callbacks": `callbacks` + docblock  
3. Remover `use` statements não utilizados: `ConfiguredPermissionSubjectResolver`, `DefaultPermissionKeyBuilder`, `SpatiePermissionStore`

---

## Fase 3 — README

### Seção "Runtime Customization"

- Remover subseção "### Via Config" inteira
- Manter/melhorar subseção "### Via Facade" 
- Adicionar nota sobre como trocar implementação inteira via container bind
- Atualizar lista de "Important areas" removendo "runtime services" e "callbacks"

---

## Fase 4 — Verificação de Testes

Testes verificados que NÃO precisam de alteração:
- `FilamentPermissionManagerTest::test_it_stores_custom_callbacks` — testa Manager diretamente, não config
- `ConfiguredPermissionSubjectResolverTest` — usa Facade `FilamentPermission::resolvePermissionSubjectUsing()`, não config

Nenhum teste existente depende das config keys sendo removidas.

---

## Fase 5 — Qualidade

```bash
docker compose exec php vendor/bin/pint --dirty
docker compose exec php vendor/bin/phpstan analyse --memory-limit=1G
docker compose exec php vendor/bin/phpunit --testdox
```

---

## Breaking Changes

| Antes | Depois | Migração para app consumidor |
|-------|--------|------------------------------|
| `config('filament-acl.subject_resolver')` | Removido | Usar `$this->app->singleton(ResolvesPermissionSubject::class, MyResolver::class)` no ServiceProvider |
| `config('filament-acl.permission_key_builder')` | Removido | Usar `$this->app->singleton(BuildsPermissionKey::class, MyBuilder::class)` no ServiceProvider |
| `config('filament-acl.permission_store')` | Removido | Usar `$this->app->singleton(StoresPermissions::class, MyStore::class)` no ServiceProvider |
| `config('filament-acl.callbacks.resolve_permission_subject_using')` | Removido | Usar `app(FilamentPermissionManager::class)->resolvePermissionSubjectUsing(...)` ou Facade |
| `config('filament-acl.callbacks.build_permission_key_using')` | Removido | Usar `app(FilamentPermissionManager::class)->buildPermissionKeyUsing(...)` ou Facade |
