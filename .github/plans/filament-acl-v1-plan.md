# Filament ACL V1 — Plano de Implementação

**Data:** 2026-04-04  
**Projeto:** `/home/coringawc/filament-acl`  
**Projeto de referência:** `/home/coringawc/siasgfacil-filament`  
**Objetivo:** criar um plugin Filament reutilizável para permissões contextuais por `Resource`, `RelationManager` e actions customizadas, mantendo o fluxo padrão de `Gate` / `Policy` do Laravel, mas removendo a dependência de subjects derivados do `Model`.

## Referências Oficiais Validadas

- Filament plugins, getting started: https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/01-getting-started.md
- Filament panel plugins: https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/02-panel-plugins.md
- Filament building a panel plugin: https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/03-building-a-panel-plugin.md
- Filament configurable resources and pages: https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/05-configurable-resources-and-pages.md
- Filament resource authorization: https://github.com/filamentphp/filament/blob/5.x/docs/09-advanced/06-security.md
- Filament relation managers, `canViewForRecord()`: https://github.com/filamentphp/filament/blob/5.x/docs/03-resources/07-managing-relationships.md
- Filament actions, `authorize()` e `authorizationTooltip()`: https://github.com/filamentphp/filament/blob/5.x/packages/actions/docs/01-overview.md
- Filament strict authorization: https://github.com/filamentphp/filament/blob/5.x/docs/05-panel-configuration.md
- Laravel authorization, policies com contexto adicional: https://github.com/laravel/docs/blob/13.x/authorization.md
- Spatie Permission, introduction: https://spatie.be/docs/laravel-permission/v7/introduction
- Spatie Permission, multiple guards: https://spatie.be/docs/laravel-permission/v7/basic-usage/multiple-guards

---

## Decisões Resolvidas

- **D1**: O pacote será publicado como `coringawc/filament-acl`, com namespace `CoringaWc\\FilamentAcl`.
- **D2**: O V1 cobre apenas `Resource`, `RelationManager` e o uso de `Actions` nativas do Filament, inclusive actions customizadas do app consumidor.
- **D3**: `Page`, `Widget` e `Cluster` ficam explicitamente fora do V1.
- **D4**: O pacote aceitará a classe dona (`Resource` ou `RelationManager`) como argumento adicional do `Gate` e a normalizará internamente para um objeto `PermissionAction` quando necessário; a policy nunca deve inferir o contexto por request, rota ou estado global.
- **D5**: O pacote continuará usando policies do Laravel como ponto de orquestração de permissão e domínio; a ordem padrão nas policies será: permissão primeiro, regra de domínio depois.
- **D6**: O pacote publicará migrations próprias compatíveis com `spatie/laravel-permission`, adicionando coluna de `panel` para `roles` e `permissions`; o núcleo de autorização não poderá depender semanticamente do schema para funcionar.
- **D7**: O subject de permissão será explícito e estável quando declarado pela classe consumidora; o fallback automático só existe para reduzir atrito de adoção.
- **D8**: `RelationManager` tem subject próprio por padrão; delegação para `relatedResource` será opcional e explícita.
- **D9**: actions customizadas não serão descobertas por parsing de código no V1; actions de permissão fora do conjunto padrão serão registradas explicitamente pela entidade dona.
- **D10**: A superfície primária de integração do pacote será por traits, helpers e contratos opt-in. O V1 não exigirá `BaseResource` nem `BaseRelationManager`.
- **D11**: O V1 terá uma camada de UI opcional para gestão de roles/permissões, desde que ela seja claramente separada do núcleo de permissões contextuais e permaneça extensível pelo projeto consumidor.
- **D12**: O pacote não terá dependência de Composer no `bezhansalleh/filament-shield`.
- **D13**: O Shield poderá ser usado apenas como referência de código e fonte de ideias de implementação, com adaptação explícita para o contexto do pacote.
- **D14**: O plano assume testes do plugin em PHPUnit para alinhar com o projeto consumidor, mesmo que o filament-acl inicial use Pest.
- **D15**: O V1 não prometerá proteção automática para `ToggleColumn`, `SelectColumn` e `CheckboxColumn`; o próprio Filament documenta que essas colunas não executam policy check automaticamente.
- **D16**: actions de permissão customizadas serão sincronizadas a partir da entidade dona (`Resource` ou `RelationManager`); não existirão subjects autônomos de action no V1.
- **D17**: O fluxo contextual do pacote deve preservar a semântica do Filament para `shouldCheckPolicyExistence()` e `strictAuthorization()`.
- **D18**: O V1 não deve expor `BaseResource` nem `BaseRelationManager`. Se durante a implementação surgir um bloqueio técnico real que a trait não consiga cobrir, isso deverá ser tratado como exceção de arquitetura e revisitado explicitamente.
- **D19**: O pacote será estritamente opt-in: só gerirá classes explicitamente registradas ou classes que adotem as traits/contratos do pacote.
- **D20**: Naming de subjects, abilities de domínio, `Gate::before()` e bridges com regras legadas específicas do app permanecerão responsabilidade do projeto consumidor.
- **D21**: O desenho do contexto deve permanecer compatível com `configurable resources` do Filament 5. O V1 pode limitar o `sync` desse cenário, mas não pode assumir para sempre que uma classe de `Resource` corresponde a um único subject.
- **D22**: O pacote deve oferecer comandos próprios para geração de policies contextualizadas, migrations e sincronização de permissões, com suporte a stubs publicáveis no host app, em linha com a DX do Shield.
- **D23**: A proibição de base classes vale para a infraestrutura de autorização (`Resource` / `RelationManager` do host app). Ela não impede o pacote de fornecer um resource base opcional para a UI de gestão de roles/permissões.
- **D24**: O pacote não deve expor uma `Action` custom própria nem um helper dedicado para `Action::authorize()`; a integração com actions deve usar a API nativa do Filament.
- **D25**: A API pública do pacote deve preferir `Permission*` e `*Action` em vez de `Acl*` e `*Context`.
- **D26**: O fluxo plug-and-play principal será: registrar o plugin no painel, adicionar trait na classe consumidora, usar `auth()->user()?->can(..., [..., OwnerClass::class])` e `->authorize(..., OwnerClass::class)` sempre que o alvo puder ser inferido pelo próprio Filament; config e provider ficam para customização excepcional.
- **D27**: A geração de `subject` e de `permission key` deve ser customizável por provider/callback, em molde semelhante ao Shield, sem exigir fork do pacote.
- **D28**: O pacote deve suportar estratégia configurável de escopo por `panel` nas tabelas de `roles` e `permissions`, sem depender de `enum` ou classe específica do app consumidor, e essa estratégia deve poder ser ligada ou desligada fluentemente no `plugin()`.

---

## Problema que o Pacote Precisa Resolver

Hoje o `siasgfacil-filament` tem ACL acoplada a `Model` e não a `Resource` / `RelationManager`. Isso gera três efeitos colaterais principais:

1. **Mesmo model, múltiplos contexts de UI, permissão diferente**  
   Exemplo real: `User` e `ContractingProcess` aparecem em mais de um `Resource` / `RelationManager` com necessidades de ACL distintas.

2. **Policies cegas ao contexto de origem**  
   O Filament autoriza built-ins via policy do model, mas a policy não recebe, por padrão, qual `Resource` ou `RelationManager` disparou a decisão.

3. **Escalada de complexidade estrutural**  
   O projeto passou a usar models estendidos e policies duplicadas apenas para criar namespace de permissão, em vez de representar diferenças reais de domínio.

Casos concretos do projeto de referência que devem orientar o V1:

- `TenantUsersRelationManager` usa checks diretos como `can('permissions', User::class)` e `can('transfer', User::class)` em vários pontos.
- `AdvanceStatusAction` usa `auth()->user()?->can('advanceStatus', $record)` sem contexto explícito.
- `ContractingProcessesRelationManager` e `MyWallet\\ContractingProcessesRelationManager` expõem o mesmo model em contexts distintos.
- A base atual de policy em `app/Policies/Base/BasePolicy.php` centraliza checks de permissão por `Resource`, mas continua exigindo classes de policy por contexto.

O plugin precisa atacar exatamente esse ponto: **o contexto de permissão deve passar a vir da entidade do Filament e não do model**.

---

## Constraints Descobertas no Projeto Piloto

Estas constraints vieram da leitura do `siasgfacil-filament` e precisam ser tratadas como requisitos do V1, não como melhorias futuras.

### C1. Mesmo model em múltiplos contexts reais

O app repete os mesmos modelos em vários contexts de `Resource` e `RelationManager`, por exemplo:

- `User` em:
  - `app/Filament/Admin/Clusters/ManageUsers/Resources/Users/UserResource.php`
  - `app/Filament/Admin/Clusters/ManageTenants/Resources/TenantUsers/TenantUserResource.php`
  - `app/Filament/App/Clusters/ManageUsers/Resources/Users/UserResource.php`
- `Tenant` em:
  - `app/Filament/Admin/Clusters/ManageTenants/Resources/Tenants/TenantResource.php`
  - `app/Filament/Admin/Clusters/ManageWallets/Resources/Tenants/TenantResource.php`
  - `app/Filament/Admin/Clusters/ManageWallets/Resources/Wallets/Resources/Tenants/TenantResource.php`
  - `app/Filament/Admin/Resources/MyWallet/Resources/Tenants/TenantResource.php`
- `ContractingProcess`, `Commitment`, `Invoice` e `Proposal` repetidos entre cluster de carteiras, árvore de tenant da carteira e `MyWallet`

**Implicação:** o pacote precisa tratar subject por contexto do Filament desde a base, sem depender de `class_basename($model)` nem de model subclassing.

### C2. Hierarquia profunda de Resources

Há resources altamente aninhados com `ParentResourceRegistration`, por exemplo:

- `WalletResource -> TenantResource -> ContractingProcessResource -> CommitmentResource -> InvoiceResource`
- espelhos equivalentes em `MyWallet`

**Implicação:** fallback de subject e inspeção precisam suportar hierarquia profunda e classes aninhadas sem colisão de nomes.

### C3. Relation managers heterogêneos

O app usa pelo menos três perfis de relation manager:

1. com `relatedResource` explícito  
   Ex.: `app/Filament/Admin/Resources/Commitments/RelationManagers/InvoicesRelationManager.php`

2. sem `relatedResource`, com ACL própria  
   Ex.: `app/Filament/Admin/Clusters/ManageTenants/Resources/Tenants/RelationManagers/TenantUsersRelationManager.php`

3. com override de `canViewForRecord()`  
   Ex.: `app/Filament/Admin/Resources/MyWallet/RelationManagers/ContractingProcessesRelationManager.php`

**Implicação:** o pacote não pode assumir um único caminho de RM. Ele precisa cobrir subject próprio, delegação opcional e override controlado de visibilidade.

### C4. Actions que operam sobre records diferentes do gatilho

Há actions que:

- operam no próprio record do resource  
  Ex.: `AdvanceStatusAction`, `ArchiveAction`
- operam no `ownerRecord` de um relation manager a partir de um pivot/record filho  
  Ex.: `GrantEvaluationLicenseAction`, `CancelEvaluationLicenseAction`, `GrantCourtesyLicenseAction`
- operam num parent derivado de outro record  
  Ex.: `ReleaseLicenseAction` autoriza via `$record->commitment`
- usam `abort_unless()` dentro da action além do `visible()`

**Implicação:** a integração nativa com actions precisa suportar:

- record action
- model/class action
- owner-record action
- parent-derived action
- proteção de UI e proteção de execução sem criar uma `Action` custom do pacote

### C4.1. Nem toda ability custom tem assinatura de policy inferível

No projeto piloto já existem casos em que a decisão de autorização pode depender de mais de dois argumentos além do usuário:

- record atual + tenant de destino
- record atual + ownerRecord
- record atual + parent derivado

**Implicação:** o gerador genérico de policy do pacote não pode assumir que toda ability custom cabe em `single param` ou `record param`. Para essas situações, o V1 deve gerar bloco manual via stub customizável ou `TODO`, nunca uma assinatura inventada silenciosamente.

### C5. ACL atual muito baseada em `visible()` + `can()`

Não há uso de `->authorize()` nas actions customizadas hoje. O padrão predominante é:

- esconder com `->visible(...)`
- chamar `auth()->user()?->can(...)`
- às vezes reforçar com `abort_unless(...)`

Arquivos representativos:

- `app/Filament/Admin/Clusters/ManageTenants/Resources/Tenants/RelationManagers/TenantUsersRelationManager.php`
- `app/Filament/Admin/Clusters/ManageWallets/Resources/Wallets/Resources/Tenants/Resources/ContractingProcesses/Actions/*.php`
- `app/Filament/Admin/Resources/Invoices/Actions/*.php`
- `app/Filament/Admin/Resources/Commitments/Actions/*.php`

**Implicação:** o pacote precisa oferecer um caminho de retrofit simples para migrar de `visible()+can()` para `authorize()` sem quebrar a UX.

### C6. State machines cruzam domínios

Há state machines em:

- `ContractingProcess`
- `Commitment`
- `CommitmentItem`
- `Invoice`
- `License`
- `Tenant`

Além disso, várias transições colaterais encadeiam mudanças em outros agregados:

- `States/Commitment/Transitions/ToFinalized.php`
- `States/Commitment/Transitions/ToCancelled.php`
- `States/CommitmentItem/Transitions/ToPaymentReceived.php`
- `States/CommitmentItem/Transitions/ToCancelled.php`
- `States/Invoice/Transitions/SyncsInvoiceCoverage.php`

**Implicação:** o pacote não pode assumir que permission check equivale a transition check. O design precisa manter ACL contextual separado da validação de estado/transição de domínio.

### C7. Há checks diretos por permission string fora do fluxo model policy

Exemplo real:

- `app/Filament/Admin/Clusters/ManageWallets/Concerns/InteractsWithContractingProcessBoard.php`

Lá existe check direto de permissão string para o Kanban:

- `KanbanMoveStatus:ExtendsWalletContractingProcess`

**Implicação:** o pacote precisa prever bridge/mapeamento para checks diretos existentes e oferecer uma forma canônica de gerar a permission key sem exigir `auth()->user()->can('RawPermissionString')`.

### C8. Models estendidos não servem só para policy

Alguns models estendidos fazem mais do que trocar policy:

- global scopes por painel/contexto em `Extends/User/*`, `Extends/Tenant/*`
- relation overrides em `Extends/Wallet/MyWallet/ContractingProcess.php`
- filtros por `Filament::auth()->id()` em `MyWallet`

**Implicação:** o plugin deve remover a necessidade de subclassing para ACL, mas não deve pressupor que todos os models estendidos possam desaparecer. O V1 precisa conviver com eles.

### C9. O Filament 5 permite registrar a mesma classe de `Resource` múltiplas vezes

Pelo mecanismo de `configurable resources`, um plugin ou panel pode expor a mesma classe com chaves/configurações distintas.

**Implicação:** o pacote não pode fechar sua API em torno da ideia de “uma classe = um subject”. O desenho do contexto e do subject resolver precisa aceitar um identificador opcional de registro para não criar um beco sem saída técnico.

### C10. O app piloto já mostra um caso claro de UI reutilizável

Os resources:

- `app/Filament/Admin/Clusters/ManageUsers/Resources/AdministrationRoles/AdministrationRoleResource.php`
- `app/Filament/Admin/Clusters/ManageTenants/Resources/ApplicationRoles/ApplicationRoleResource.php`

compartilham praticamente a mesma UI:

- mesmo formulário base
- mesma tabela base
- mesmas páginas de list/create/edit
- mesma lógica de sincronização de permissões

e diferem principalmente em:

- query base
- labels
- cluster / navigation
- panel/contexto do conjunto de permissões

**Implicação:** a camada de UI do plugin deve nascer como resource base extensível, com hooks de customização, em vez de forçar cada projeto a duplicar a mesma implementação para cada contexto.

---

## Escopo do V1

### Dentro do escopo

- Base de autorização contextual para `Filament\\Resources\\Resource`
- Base de autorização contextual para `Filament\\Resources\\RelationManagers\\RelationManager`
- Helpers para `Filament\\Actions\\Action`
- Resolução de subject por classe consumidora
- Registro explícito de abilities por entidade
- Sincronização de permissões no backend do Spatie
- Traits de policy para ACL contextual
- UI opcional e extensível para gestão de roles/permissões
- Comandos de inspeção e sync
- Estratégia de adoção incremental no `siasgfacil-filament`

### Guardrails de genericidade

- O pacote resolve apenas ACL contextual para entidades do Filament.
- O pacote não define semântica de domínio para abilities como `transfer`, `invite`, `advanceStatus` ou equivalentes; ele apenas oferece infraestrutura para registrá-las e autorizá-las com contexto.
- O pacote não tenta modelar regras de tenancy, ownership, scoping de query, state machine ou workflow do app consumidor.
- O pacote não assume estrutura de pastas, clusters, painéis, naming convention ou hierarquia de resources do app consumidor além do que for explicitamente configurado.
- O pacote não obriga o app a trocar a herança das classes do Filament; a adoção principal é composicional.
- A UI do pacote, quando usada, deve ser opcional e construída em cima do núcleo contextual, nunca o contrário.

### Fora do escopo

- Builder visual de permissões
- Autodiscovery de custom actions por AST / reflection profunda de closures
- Integração com `Page`, `Widget`, `Cluster`
- Substituir o sistema de roles do Spatie
- Importação automática destrutiva de permissões do Shield sem confirmação explícita
- Dependência de runtime em `bezhansalleh/filament-shield`

---

## Critérios de Sucesso do V1

- Um `Resource` e um `RelationManager` diferentes podem apontar para o mesmo model e produzir keys de permissão distintas, sem duplicar model.
- Policies conseguem receber uma `PermissionAction` adicional, ou a classe dona diretamente, e decidir permissão + domínio com a mesma policy.
- `Action::authorize()` continua nativa, aceitando a classe dona como argumento adicional sempre que o contexto precisar ser explícito.
- O pacote consegue sincronizar permissões de `Resources` e `RelationManagers` conhecidos.
- O pacote preserva o comportamento do Filament quando policy ou método de policy não existe.
- O `siasgfacil-filament` consegue migrar pelo menos um slice real:
  `TenantUsersRelationManager`, `AdvanceStatusAction` e os dois `ContractingProcessesRelationManager`.

---

## Inventário Inicial

### FilamentAcl do pacote

| # | Arquivo | Ação | Papel no V1 |
| --- | --- | --- | --- |
| 1 | `composer.json` | MODIFICAR | trocar placeholders, alinhar namespace, dependências e scripts |
| 2 | `README.md` | MODIFICAR | descrever o plugin real e o fluxo de uso |
| 3 | `config/filament-acl.php` | REMOVER | substituir por `config/filament-acl.php` |
| 4 | `src/FilamentAclPlugin.php` | REMOVER | substituir por `src/FilamentPermissionPlugin.php` |
| 5 | `src/FilamentAclServiceProvider.php` | REMOVER | substituir por `src/FilamentPermissionServiceProvider.php` |
| 6 | `src/FilamentAcl.php` | REMOVER | não faz sentido no pacote final |
| 7 | `src/FilamentAclTheme.php` | REMOVER | fora do escopo |
| 8 | `src/Facades/FilamentAcl.php` | REMOVER | substituir por facade real do manager |
| 9 | `src/Commands/FilamentAclCommand.php` | REMOVER | substituir pelos comandos do domínio |
| 10 | `database/migrations/create_permission_tables.php.stub` | MODIFICAR | migration base compatível com Spatie + coluna `panel` |
| 11 | `database/factories/ModelFactory.php` | REMOVER | o pacote não terá model próprio no V1 |
| 12 | `tests/Pest.php` | REMOVER | migrar a suíte para PHPUnit |
| 13 | `tests/ExampleTest.php` | REMOVER | substituir por testes reais |
| 14 | `tests/DebugTest.php` | REMOVER | substituir por testes reais |
| 15 | `tests/TestCase.php` | MODIFICAR | alinhar com namespace final, Testbench e providers do pacote |

### Novos arquivos previstos no pacote

| # | Arquivo final | Ação | Papel |
| --- | --- | --- | --- |
| 1 | `.github/plans/filament-acl-v1-plan.md` | NOVO | especificação de implementação |
| 2 | `config/filament-acl.php` | NOVO | configuração pública do pacote |
| 3 | `src/FilamentPermissionPlugin.php` | NOVO | plugin object do Filament |
| 4 | `src/FilamentPermissionServiceProvider.php` | NOVO | service provider do pacote |
| 5 | `src/FilamentPermissionManager.php` | NOVO | façade de orquestração e pontos de extensão |
| 6 | `src/Facades/FilamentPermission.php` | NOVO | facade de acesso ao manager |
| 7 | `src/Enums/PermissionEntityType.php` | NOVO | enum de tipo de entidade |
| 8 | `src/Support/PermissionAction.php` | NOVO | DTO imutável do contexto de permissão |
| 9 | `src/Contracts/HasPermissionSubject.php` | NOVO | contrato para subject explícito |
| 10 | `src/Contracts/HasPermissionActions.php` | NOVO | contrato para actions explícitas |
| 11 | `src/Contracts/ResolvesPermissionSubject.php` | NOVO | contrato de resolver de subject |
| 12 | `src/Contracts/BuildsPermissionKey.php` | NOVO | contrato de key builder |
| 13 | `src/Contracts/StoresPermissions.php` | NOVO | contrato do backend de persistência |
| 14 | `src/Support/ConfiguredPermissionSubjectResolver.php` | NOVO | resolver padrão de subject |
| 15 | `src/Support/DefaultPermissionActionRegistry.php` | NOVO | registry de actions padrão por tipo |
| 16 | `src/Support/DefaultPermissionKeyBuilder.php` | NOVO | builder padrão de keys |
| 17 | `src/Support/SpatiePermissionStore.php` | NOVO | adapter para `spatie/laravel-permission` |
| 18 | `src/Support/PermissionGate.php` | NOVO | serviço para `Gate::inspect()` / `authorize()` com contexto |
| 19 | `src/Support/Discovery/PermissionDiscoveryResult.php` | NOVO | DTO de descoberta |
| 20 | `src/Support/Discovery/FilamentDirectoryDiscovery.php` | NOVO | descoberta de entities geridas |
| 21 | `src/Support/Registry/PermissionRegistry.php` | NOVO | registry puro de entities e permissions |
| 22 | `src/Support/Registry/CachedPermissionRegistry.php` | NOVO | cache por request/comando |
| 23 | `src/Policies/Concerns/ChecksPermission.php` | NOVO | trait de policy |
| 24 | `src/Resources/Concerns/HasResourcePermissions.php` | NOVO | trait principal para resources |
| 25 | `src/RelationManagers/Concerns/HasRelationManagerPermissions.php` | NOVO | trait principal para relation managers |
| 27 | `src/Commands/Concerns/CanGeneratePolicy.php` | NOVO | geração de policies por stub |
| 28 | `src/Commands/Concerns/CanManipulateFiles.php` | NOVO | utilitários de stub, colisão e escrita |
| 29 | `src/Commands/GenerateCommand.php` | NOVO | orquestra policies e sync de permissões |
| 30 | `src/Commands/PublishStubsCommand.php` | NOVO | publica stubs do pacote para o host app |
| 31 | `src/Commands/SyncPermissionsCommand.php` | NOVO | sync das permissões geridas pelo pacote |
| 32 | `src/Commands/InspectPermissionsCommand.php` | NOVO | inspeção de subject, actions e keys |
| 33 | `src/Commands/CheckPermissionsReadinessCommand.php` | NOVO | validação de dependências do host app |
| 34 | `src/Commands/BridgeShieldPermissionsCommand.php` | NOVO | relatório de migração do Shield |
| 35 | `src/Commands/MakeRoleResourceCommand.php` | NOVO | scaffold de resource concreta da UI base |
| 36 | `stubs/PermissionPolicy.stub` | NOVO | classe base de policy gerada |
| 37 | `stubs/SingleParamMethod.stub` | NOVO | métodos sem record |
| 38 | `stubs/RecordMethod.stub` | NOVO | métodos com record |
| 39 | `stubs/CustomMethod.stub` | NOVO | bloco customizável para métodos não inferíveis |
| 40 | `src/Resources/Roles/RoleManagementResource.php` | NOVO | resource base opcional da UI |
| 41 | `src/Resources/Roles/Pages/ListRoles.php` | NOVO | página base de listagem |
| 42 | `src/Resources/Roles/Pages/CreateRole.php` | NOVO | página base de criação |
| 43 | `src/Resources/Roles/Pages/EditRole.php` | NOVO | página base de edição |
| 44 | `src/Resources/Roles/Concerns/HasRoleManagementFormComponents.php` | NOVO | schema padrão de permissões |
| 45 | `stubs/RoleManagementResource.stub` | NOVO | scaffold de resource concreto que estende a UI base |
| 46 | `stubs/ListRoles.stub` | NOVO | scaffold de página de listagem |
| 47 | `stubs/CreateRole.stub` | NOVO | scaffold de página de criação |
| 48 | `stubs/EditRole.stub` | NOVO | scaffold de página de edição |
| 49 | `tests/Feature/PluginRegistrationTest.php` | NOVO | cobertura do plugin/provider |
| 50 | `tests/Feature/ResourceAuthorizationTest.php` | NOVO | cobertura de resource com permissão contextual |
| 51 | `tests/Feature/RelationManagerAuthorizationTest.php` | NOVO | cobertura de relation manager com permissão contextual |
| 52 | `tests/Feature/ActionAuthorizationTest.php` | NOVO | cobertura de custom action |
| 53 | `tests/Feature/GenerateCommandTest.php` | NOVO | cobertura do comando de geração |
| 54 | `tests/Feature/PublishStubsCommandTest.php` | NOVO | cobertura do publish de stubs |
| 55 | `tests/Feature/RoleManagementResourceTest.php` | NOVO | cobertura da UI base de roles/permissões |
| 56 | `tests/Feature/PermissionSyncCommandTest.php` | NOVO | cobertura do comando de sync |
| 57 | `tests/Feature/ShieldBridgeCommandTest.php` | NOVO | cobertura do relatório de migração |
| 58 | `tests/Unit/PermissionActionTest.php` | NOVO | DTO/action builder |
| 59 | `tests/Unit/ConfiguredPermissionSubjectResolverTest.php` | NOVO | subject resolver |
| 60 | `tests/Unit/DefaultPermissionKeyBuilderTest.php` | NOVO | key builder |
| 61 | `tests/Unit/FilamentDirectoryDiscoveryTest.php` | NOVO | descoberta de entities |

### Arquivos do projeto de referência que devem ser usados como casos-guia

| Arquivo | Papel no desenho |
| --- | --- |
| `/home/coringawc/siasgfacil-filament/app/Policies/Base/BasePolicy.php` | estado atual a ser superado |
| `/home/coringawc/siasgfacil-filament/app/FilamentShield/FilamentShield.php` | ideias reaproveitáveis de subject resolver e localized labels |
| `/home/coringawc/siasgfacil-filament/app/Filament/Admin/Clusters/ManageTenants/Resources/Tenants/RelationManagers/TenantUsersRelationManager.php` | caso real de `RelationManager` com abilities custom |
| `/home/coringawc/siasgfacil-filament/app/Filament/Admin/Clusters/ManageWallets/Resources/Wallets/Resources/Tenants/Resources/ContractingProcesses/Actions/AdvanceStatusAction.php` | caso real de `Action` custom |
| `/home/coringawc/siasgfacil-filament/app/Filament/Admin/Clusters/ManageWallets/Resources/Wallets/RelationManagers/ContractingProcessesRelationManager.php` | caso real com `relatedResource` |
| `/home/coringawc/siasgfacil-filament/app/Filament/Admin/Resources/MyWallet/RelationManagers/ContractingProcessesRelationManager.php` | caso real com mesmo model em outro context |

### Shield como modelo de DX, não de arquitetura

Arquivos do Shield inspecionados como referência operacional:

- `/home/coringawc/siasgfacil-filament/vendor/bezhansalleh/filament-shield/src/Commands/GenerateCommand.php`
- `/home/coringawc/siasgfacil-filament/vendor/bezhansalleh/filament-shield/src/Commands/PublishCommand.php`
- `/home/coringawc/siasgfacil-filament/vendor/bezhansalleh/filament-shield/src/Commands/SetupCommand.php`
- `/home/coringawc/siasgfacil-filament/vendor/bezhansalleh/filament-shield/src/Commands/Concerns/CanGeneratePolicy.php`
- `/home/coringawc/siasgfacil-filament/vendor/bezhansalleh/filament-shield/src/Commands/Concerns/CanManipulateFiles.php`
- `/home/coringawc/siasgfacil-filament/vendor/bezhansalleh/filament-shield/stubs/DefaultPolicy.stub`
- `/home/coringawc/siasgfacil-filament/vendor/bezhansalleh/filament-shield/stubs/SingleParamMethod.stub`
- `/home/coringawc/siasgfacil-filament/vendor/bezhansalleh/filament-shield/stubs/MultiParamMethod.stub`

O que deve ser reaproveitado como modelo:

- comando guarda-chuva de geração com resumo final e filtros por entidade
- traits internas para manipulação de arquivo e composição de policy stub
- convenção de override local de stubs em `base_path('stubs/...')`
- detecção de colisão e opção explícita de sobrescrita
- distinção entre métodos de policy de um parâmetro e métodos com record
- prompts interativos amigáveis, mas com modo não interativo estável por flags

O que não deve ser copiado para o `filament-acl`:

- runtime acoplado ao subject por `model`/`class`
- setup/install que altera AppServiceProvider, tenancy, migrations ou painel do host
- a resource/UI embutida do Shield como bloco monolítico e pouco extensível
- fluxo de super-admin e features administrativas fora do núcleo contextual de ACL

---

## Comandos Obrigatórios

### No repositório `filament-acl`

```bash
cd /home/coringawc/filament-acl

# 1. Higienizar o bootstrap gerado pelo skeleton e alinhar os nomes finais do pacote

# 2. Instalar / atualizar dependências após ajustar composer.json
composer install

# 3. Rodar suíte local do pacote
composer test

# 4. Rodar análise estática do pacote
composer analyse

# 5. Rodar code style do pacote
composer lint
```

### No repositório consumidor `siasgfacil-filament` durante o piloto

```bash
cd /home/coringawc/siasgfacil-filament

# 1. Usar path repository para consumir o pacote local
vendor/bin/sail composer update coringawc/filament-acl --with-all-dependencies

# 2. Sincronizar permissões do novo pacote
vendor/bin/sail artisan filament-acl:sync --panel=admin

# 3. Rodar testes focais do slice piloto
vendor/bin/sail artisan test --compact --filter=TenantUsersRelationManager
vendor/bin/sail artisan test --compact --filter=ContractingProcessesRelationManager
vendor/bin/sail artisan test --compact --filter=AdvanceStatusAction

# 4. Formatar mudanças do app durante a adoção
vendor/bin/sail bin pint --dirty --format agent
```

---

## Models / Persistência

### Estado do V1

- **Sem models próprios do pacote**
- **Com migrations próprias e publicáveis do pacote**
- **Persistência desacoplada por contrato**

### Contrato esperado do host app

- um backend de persistência resolvível por `StoresPermissions`
- se o adapter padrão for usado, `spatie/laravel-permission` instalado e migrado
- guard válido para criação e lookup das permissões no backend escolhido

### Componente

- **Component:** `CoringaWc\FilamentAcl\Support\SpatiePermissionStore`
- **Docs:** https://github.com/laravel/docs/blob/13.x/authorization.md
- **Config:** usar `config('permission.models.permission')`, `config('permission.models.role')` e guard do contexto
- **Responsabilidade:** implementar o adapter padrão do V1 sem contaminar o restante do pacote com detalhes do Spatie

### Regras do V1

- O runtime contextual do pacote independe do backend de persistência; `Gate`, `Policy`, `PermissionAction` e integração com Filament devem continuar funcionais sem conhecer Spatie.
- O pacote só cria / sincroniza permissões; não cria roles.
- O pacote não altera permissões que não estejam sob gestão explícita do `filament-acl`.
- Toda deleção de permissão stale precisa ser opt-in via flag de comando.

---

## Arquitetura do Núcleo

## Fase 0 — Limpeza do FilamentAcl e Identidade do Pacote

### Objetivo

Remover o ruído do filament-acl para que o restante do pacote seja implementado em cima de nomes e paths definitivos.

### Alterações obrigatórias

- Trocar todos os placeholders `coringawc`, `filament-acl`, `CoringaWc\\FilamentAcl` por `coringawc/filament-acl` e `CoringaWc\\FilamentAcl`
- Remover arquivos de exemplo que não terão função no V1
- Converter a suíte para PHPUnit
- Ajustar dependências mínimas do pacote para:
  - `spatie/laravel-package-tools:^1.15`
  - `filament/filament:^5.0`
  - `spatie/laravel-permission:^7.0`
  - `orchestra/testbench:^10.0`
  - `phpunit/phpunit:^12.0`
- Remover dependências Pest do filament-acl
- Remover qualquer sobra de `BaseResource` / `BaseRelationManager` do desenho inicial
- Preparar diretório `stubs/` do pacote como artefato público e versionado do V1

### Resultado esperado

- `composer.json` com namespace e metadata finais
- provider e plugin com nomes reais
- config file `config/filament-acl.php`
- stubs versionados em `stubs/` e caminho configurável em `config('filament-acl.stubs.path')`
- README limpo, sem instruções do template

---

## Fase 1 — Contratos e DTOs Base

### 1. `PermissionEntityType`

- **Component:** `CoringaWc\FilamentAcl\Enums\PermissionEntityType`
- **Location:** `src/Enums/PermissionEntityType.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/01-getting-started.md
- **Config:** enum backed por string com valores `resource`, `relation_manager`, `action`
- **Responsabilidade:** padronizar o tipo de entidade gerida pelo pacote

### 2. `PermissionAction`

- **Component:** `CoringaWc\FilamentAcl\Support\PermissionAction`
- **Location:** `src/Support/PermissionAction.php`
- **Docs:** https://github.com/laravel/docs/blob/13.x/authorization.md
- **Config:** objeto `readonly` com construtores nomeados
- **Estrutura mínima:**

```php
public function __construct(
    public string $ownerClass,
    public PermissionEntityType $ownerType,
    public string $subject,
    public ?string $registrationKey = null,
    public ?string $panelId = null,
    public ?string $permissionAction = null,
    public ?string $relatedResource = null,
    public array $meta = [],
) {}
```

- **Regra obrigatória:** `PermissionAction` é um DTO puro. Ele não resolve subject sozinho. O subject deve ser resolvido antes da criação do objeto.
- **Construtores nomeados obrigatórios:**
  - `forResource(string $resourceClass, string $subject, ?string $permissionAction = null, ?string $panelId = null, ?string $registrationKey = null, array $meta = []): self`
  - `forRelationManager(string $relationManagerClass, string $subject, ?string $permissionAction = null, ?string $panelId = null, ?string $relatedResource = null, ?string $registrationKey = null, array $meta = []): self`
  - `fromOwnerClass(string $ownerClass, PermissionEntityType $ownerType, string $subject, string $permissionAction, ?string $panelId = null, ?string $relatedResource = null, ?string $registrationKey = null, array $meta = []): self`

- **Responsabilidade:** carregar metadados avançados quando a classe dona sozinha não bastar.
- **Regra de API pública:** no fluxo comum do app consumidor, o argumento adicional do `Gate` deve continuar sendo a própria classe dona (`Resource::class` ou `RelationManager::class`). `PermissionAction` entra apenas quando o runtime precisar carregar metadados extras, como `registrationKey`, `panelId` ou `relatedResource`.

### 3. Contratos de declaração

#### `HasPermissionSubject`

- **Component:** `CoringaWc\FilamentAcl\Contracts\HasPermissionSubject`
- **Location:** `src/Contracts/HasPermissionSubject.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/05-configurable-resources-and-pages.md
- **Config:** método estático `getPermissionSubject(): ?string`

#### `HasPermissionActions`

- **Component:** `CoringaWc\FilamentAcl\Contracts\HasPermissionActions`
- **Location:** `src/Contracts/HasPermissionActions.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/09-advanced/06-security.md
- **Config:** método estático `getPermissionCustomActions(): array`

#### `ResolvesPermissionSubject`

- **Component:** `CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject`
- **Location:** `src/Contracts/ResolvesPermissionSubject.php`
- **Docs:** https://github.com/laravel/docs/blob/13.x/authorization.md
- **Config:** método `resolve(string $entityClass, PermissionEntityType $entityType, ?string $panelId = null, ?string $registrationKey = null, array $meta = []): string`

#### `BuildsPermissionKey`

- **Component:** `CoringaWc\FilamentAcl\Contracts\BuildsPermissionKey`
- **Location:** `src/Contracts/BuildsPermissionKey.php`
- **Docs:** https://github.com/laravel/docs/blob/13.x/authorization.md
- **Config:** método `build(string $ability, PermissionAction $action): string`

#### `StoresPermissions`

- **Component:** `CoringaWc\FilamentAcl\Contracts\StoresPermissions`
- **Location:** `src/Contracts/StoresPermissions.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/03-building-a-panel-plugin.md
- **Config:** métodos `findByName()`, `upsertMany()`, `listManaged()`

---

## Fase 2 — Subject Resolver, Abilities e Permission Keys

### 1. `ConfiguredPermissionSubjectResolver`

- **Component:** `CoringaWc\FilamentAcl\Support\ConfiguredPermissionSubjectResolver`
- **Location:** `src/Support/ConfiguredPermissionSubjectResolver.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/05-configurable-resources-and-pages.md
- **Config:** ordem de resolução:
  1. `getPermissionSubject()` da classe, se existir
  2. callback registrado via provider / manager
  3. map explícito em `config('filament-acl.subject_overrides')`
  4. fallback configurável por classe, panel e `registrationKey`

- **Regra obrigatória:** o fallback deve ser determinístico e estável; sem `class_basename()`
- **Regra obrigatória adicional:** o fallback é ponte de adoção, não contrato principal do pacote. O `inspect` deve marcar claramente quando um subject veio de fallback.

- **Formato de fallback recomendado:**
  - remover prefixo configurado, ex. `App\\Filament\\`
  - substituir `\\` por `-`
  - aplicar `Studly`
  - exemplo: `App\\Filament\\Admin\\Resources\\MyWallet\\RelationManagers\\ContractingProcessesRelationManager`
    vira `AdminResourcesMyWalletRelationManagersContractingProcessesRelationManager`

### 2. `DefaultPermissionActionRegistry`

- **Component:** `CoringaWc\FilamentAcl\Support\DefaultPermissionActionRegistry`
- **Location:** `src/Support/DefaultPermissionActionRegistry.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/09-advanced/06-security.md
- **Config:** ações de permissão padrão por tipo

#### Abilities padrão para `Resource`

```php
[
    'viewAny',
    'view',
    'create',
    'update',
    'delete',
    'deleteAny',
    'forceDelete',
    'forceDeleteAny',
    'restore',
    'restoreAny',
    'replicate',
    'reorder',
]
```

#### Abilities padrão para `RelationManager`

```php
[
    'viewAny',
    'view',
    'create',
    'update',
    'delete',
    'deleteAny',
    'forceDelete',
    'forceDeleteAny',
    'restore',
    'restoreAny',
    'replicate',
    'reorder',
    'associate',
    'dissociate',
    'dissociateAny',
    'attach',
    'detach',
    'detachAny',
]
```

#### Regra obrigatória para actions customizadas

- actions customizadas nunca entram por auto-discovery textual.
- toda action de permissão fora do conjunto padrão precisa ser declarada explicitamente pela entidade dona.

### 3. `DefaultPermissionKeyBuilder`

- **Component:** `CoringaWc\FilamentAcl\Support\DefaultPermissionKeyBuilder`
- **Location:** `src/Support/DefaultPermissionKeyBuilder.php`
- **Docs:** https://github.com/laravel/docs/blob/13.x/authorization.md
- **Config:** usar:

```php
[
    'separator' => ':',
    'ability_case' => 'studly',
    'subject_case' => 'preserve',
]
```

- **Formato padrão:** `Ability:Subject`
- **Exemplos desejados:**
  - `ViewAny:TenantUsers`
  - `Transfer:TenantUsers`
  - `AdvanceStatus:WalletTenantContractingProcesses`

- **Regra obrigatória:** se o subject for explícito, o builder não deve requalificar o texto além da política de case configurada
- **Regra obrigatória adicional:** o builder deve poder ser sobrescrito via provider / callback, sem exigir trocar a implementação inteira do pacote.

### 4. Descoberta e registry

#### `PermissionDiscoveryResult`

- **Component:** `CoringaWc\FilamentAcl\Support\Discovery\PermissionDiscoveryResult`
- **Location:** `src/Support/Discovery/PermissionDiscoveryResult.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/02-panel-plugins.md
- **Responsabilidade:** transportar a lista final de entities descobertas, source da descoberta e problemas encontrados

#### `FilamentDirectoryDiscovery`

- **Component:** `CoringaWc\FilamentAcl\Support\Discovery\FilamentDirectoryDiscovery`
- **Location:** `src/Support/Discovery/FilamentDirectoryDiscovery.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/05-configurable-resources-and-pages.md
- **Responsabilidade:** descobrir `Resources` e `RelationManagers` geridos pelo pacote

#### Algoritmo obrigatório de descoberta

1. Resolver `Resources` registrados nos panels configurados, quando o panel estiver disponível
2. Complementar com scan dos diretórios configurados em `config('filament-acl.discovery.paths')`
3. Considerar apenas subclasses de `Resource` e `RelationManager`
4. Filtrar apenas classes explicitamente registradas, classes que adotem a trait principal do pacote ou classes marcadas por contrato equivalente
5. Nunca tentar descobrir `custom Actions` por introspecção de closures

#### Regras de genericidade da descoberta

- `discovery.paths` deve vir vazio por padrão
- o pacote não assume `app_path('Filament')`, `App\\Filament\\` ou convenção de namespace semelhante como contrato implícito
- se o projeto quiser filesystem scan, ele o habilita explicitamente

#### `PermissionRegistry`

- **Component:** `CoringaWc\FilamentAcl\Support\Registry\PermissionRegistry`
- **Location:** `src/Support/Registry/PermissionRegistry.php`
- **Docs:** https://github.com/laravel/docs/blob/13.x/authorization.md
- **Responsabilidade:** transformar descoberta + resolver + builder na lista final de permissions geridas

#### `CachedPermissionRegistry`

- **Component:** `CoringaWc\FilamentAcl\Support\Registry\CachedPermissionRegistry`
- **Location:** `src/Support/Registry/CachedPermissionRegistry.php`
- **Docs:** https://github.com/laravel/docs/blob/13.x/authorization.md
- **Responsabilidade:** evitar redescoberta repetida dentro do mesmo comando/request

#### Regra obrigatória de ciclo de vida

- `CachedPermissionRegistry` deve ser `scoped`, não `singleton`
- nenhum serviço pode capturar `request()`, `auth()` ou config mutável no construtor

---

## Fase 3 — Policy Layer e Gate de Permissão

### 1. `PermissionGate`

- **Component:** `CoringaWc\FilamentAcl\Support\PermissionGate`
- **Location:** `src/Support/PermissionGate.php`
- **Docs:** https://github.com/laravel/docs/blob/13.x/authorization.md
- **Responsabilidade:** centralizar `Gate::inspect()`, `Gate::authorize()`, `Gate::check()` com `PermissionAction`

### API obrigatória

```php
public function inspect(
    mixed $user,
    string $ability,
    string|Model $target,
    PermissionAction|string|null $action,
    array $arguments = [],
): Response

public function authorize(
    mixed $user,
    string $ability,
    string|Model $target,
    PermissionAction|string|null $action,
    array $arguments = [],
): Response
```

### Regra de montagem dos argumentos do Gate

- Para abilities sem record:

```php
Gate::forUser($user)->inspect($ability, [$modelClass, $action, ...$arguments]);
```

- Para abilities com record:

```php
Gate::forUser($user)->inspect($ability, [$record, $action, ...$arguments]);
```

### Regra obrigatória de compatibilidade com o Filament

`PermissionGate` não pode chamar `Gate::inspect()` cegamente o tempo todo. Ele deve reproduzir o comportamento do helper `Filament\get_authorization_response()`:

- se `shouldCheckPolicyExistence() === true`:
  - só chamar `Gate::inspect()` quando a policy existir e o método existir
  - se policy ou método não existir e `strictAuthorization()` estiver desligado, retornar `Response::allow()` ou o resultado de `Gate::before()`
  - se policy ou método não existir e `strictAuthorization()` estiver ligado, lançar a mesma classe de erro lógico usada pelo Filament

- se `shouldCheckPolicyExistence() === false`:
  - chamar `Gate::inspect()` diretamente
  - ainda respeitar `strictAuthorization()` quando nem ability nem policy/método existirem

### Testes obrigatórios desta fase

- policy ausente com strict mode desligado: permitir
- policy ausente com strict mode ligado: falhar
- método ausente com strict mode desligado: permitir
- método ausente com strict mode ligado: falhar
- `Gate::before()` continua sendo respeitado no fluxo contextual

### 2. Trait de policy

- **Component:** `CoringaWc\FilamentAcl\Policies\Concerns\ChecksPermission`
- **Location:** `src/Policies/Concerns/ChecksPermission.php`
- **Docs:** https://github.com/laravel/docs/blob/13.x/authorization.md
- **Config:** helper central para permission check antes do domínio
- **Responsabilidade adicional:** normalizar o argumento adicional recebido pela policy, aceitando `string` com `OwnerClass::class` ou um objeto `PermissionAction`.

### API obrigatória da trait

```php
protected function checkPermission(
    mixed $user,
    string $ability,
    PermissionAction|string|null $action = null,
): Response

protected function denyUnlessPermitted(
    mixed $user,
    string $ability,
    PermissionAction|string|null $action = null,
): ?Response
```

### Regra obrigatória de uso nas policies consumidoras

Toda policy que adotar o pacote deve seguir este padrão:

```php
public function update(User $user, Post $record, PermissionAction|string|null $permissionAction = null): Response
{
    if ($response = $this->denyUnlessPermitted($user, 'update', $permissionAction)) {
        return $response;
    }

    // regras de domínio
    if ($record->is_locked) {
        return Response::deny('Locked record.');
    }

    return Response::allow();
}
```

### Regra opcional, mas documentada

- Se o host app quiser um `super-admin`, isso deve ser feito via `Gate::before()` no projeto consumidor, não dentro do plugin.

---

## Fase 4 — Integração com `Resource`

### Estratégia

Usar uma trait para sobrescrever o ponto exato onde o Filament hoje usa `get_authorization_response()`, sem exigir troca de herança da classe consumidora.

### Componente principal

- **Component:** `CoringaWc\FilamentAcl\Resources\Concerns\HasResourcePermissions`
- **Location:** `src/Resources/Concerns/HasResourcePermissions.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/09-advanced/06-security.md
- **Docs adicionais:** https://github.com/filamentphp/filament/blob/5.x/docs/05-panel-configuration.md

### Requisitos obrigatórios

- Ser usado por uma classe que extenda `Filament\\Resources\\Resource`
- Sobrescrever via trait:
  - `getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response`
  - `can(string|UnitEnum $action, ?Model $record = null): bool`
  - `authorize(string|UnitEnum $action, ?Model $record = null): ?Response`
- Não depender de uma classe base do pacote para funcionar

### API da trait

```php
public static function getPermissionSubject(): ?string

public static function getPermissionCustomActions(): array

public static function getPermissionPanel(): ?string

public static function getPermissionActions(): array

protected static function getPermissionAction(?string $permissionAction = null, ?string $registrationKey = null): PermissionAction

protected static function getPermissionGateArgument(?string $permissionAction = null, ?string $registrationKey = null): string|PermissionAction
```

### Regra de comportamento

- Se `static::$shouldSkipAuthorization === true`, retornar `Response::allow()`
- Caso contrário:
  1. resolver usuário autenticado do Filament
  2. resolver o subject da classe
  3. construir `PermissionAction::forResource(static::class, subject: ..., permissionAction: $action, panelId: ..., registrationKey: ...)`
  4. chamar `PermissionGate` preservando `shouldCheckPolicyExistence()` e strict mode

### API desejada no app consumidor

- `visible()` continua usando o `can()` nativo do usuário:

```php
filament()->auth()->user()?->can('advanceStatus', [$record, static::class])
```

- a trait do resource existe para built-ins, sync e normalização interna do subject/permission action; ela não substitui o `can()` do Laravel no código do app

### Regra estrutural do V1

- A trait deve ser suficiente para a integração completa da `Resource`
- O pacote não deve publicar ou exigir uma `PermissionResource` abstrata

---

## Fase 5 — Integração com `RelationManager`

### Estratégia

Usar uma trait para sobrescrever os pontos de autorização do `InteractsWithRelationshipTable`, sem exigir troca de herança da classe consumidora, para que:

- `canViewForRecord()` use contexto do próprio relation manager
- built-in actions usem o contexto do relation manager
- exista fallback opcional para `relatedResource`

### Componente principal

- **Component:** `CoringaWc\FilamentAcl\RelationManagers\Concerns\HasRelationManagerPermissions`
- **Location:** `src/RelationManagers/Concerns/HasRelationManagerPermissions.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/03-resources/07-managing-relationships.md

### API da trait

```php
public static function getPermissionSubject(): ?string

public static function shouldUseRelatedResourcePermissions(): bool
```

### Métodos obrigatórios

```php
public static function getPermissionActions(): array

public static function getPermissionCustomActions(): array

protected function getPermissionAction(?string $permissionAction = null, ?string $registrationKey = null): PermissionAction

protected function getPermissionGateArgument(?string $permissionAction = null, ?string $registrationKey = null): string|PermissionAction

public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool

public function getAuthorizationResponse(string $action, ?Model $record = null): Response
```

### Regra estrutural do V1

- A trait deve ser suficiente para a integração completa do `RelationManager`
- O pacote não deve publicar ou exigir um `RelationManager` abstrato do pacote

### Regra de `canViewForRecord()`

- Se `shouldUseRelatedResourcePermissions() === true` e houver `relatedResource`, delegar a `relatedResource::canAccess()`
- Caso contrário:
  - resolver o model relacionado
  - resolver o subject do relation manager
  - montar `PermissionAction::forRelationManager(static::class, subject: ..., permissionAction: 'viewAny', relatedResource: static::getRelatedResource(), registrationKey: ...)`
  - autorizar `viewAny`

### Regra de `getAuthorizationResponse()`

- Se `shouldUseRelatedResourcePermissions() === true` e o `record` for do model do `relatedResource`, delegar ao `relatedResource`
- Caso contrário, usar `PermissionGate` com `PermissionAction` do relation manager

---

## Fase 6 — Integração com `Actions` Nativas

### Estratégia

Não criar uma `Action` custom do pacote. O V1 deve apoiar-se totalmente em `Filament\Actions\Action`, `->visible()` e `->authorize()`.

### API desejada no app consumidor

- Para UX, usar o `can()` nativo do usuário com a classe dona como argumento adicional:

```php
->visible(fn (ContractingProcess $record): bool =>
    filament()->auth()->user()?->can('advanceStatus', [$record, ContractingProcessResource::class]) === true
)
```

- Para proteção efetiva da action:

```php
->authorize('advanceStatus', ContractingProcessResource::class)
```

- Para header actions sem record:

```php
->authorize('invite', [User::class, TenantUsersRelationManager::class])
```

### Regra obrigatória

- `visible()` continua sendo apenas UX; proteção real exige `authorize()`.
- O pacote deve documentar a forma explícita com `OwnerClass::class` como contrato estável do V1, usando array apenas quando também for necessário passar model class ou argumentos extras.
- O pacote pode oferecer auto-resolução de owner para `->authorize('ability')` puro apenas nos hosts em que o Filament expõe hook suficiente na classe dona; isso deve ser tratado como conveniência opcional, nunca como contrato universal do V1.

---

## Fase 7 — Service Provider, Plugin Object e Configuração Pública

### 1. `FilamentPermissionPlugin`

- **Component:** `CoringaWc\FilamentAcl\FilamentPermissionPlugin`
- **Location:** `src/FilamentPermissionPlugin.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/02-panel-plugins.md
- **Config:** implementar `Filament\\Contracts\\Plugin`

### API obrigatória

```php
public function getId(): string
{
    return 'filament-acl';
}
```

### Opções configuráveis via plugin object

```php
public function strictMode(bool $condition = true): static

public function scopeRolesByPanel(bool $condition = true): static

public function scopePermissionsByPanel(bool $condition = true): static

public function scopesRolesByPanel(): bool

public function scopesPermissionsByPanel(): bool
```

### Regra do V1

- O plugin object não deve forçar `strictAuthorization()`
- Pode expor apenas flags globais realmente genéricas; registro de classes deve ser automático via trait/scan/config, não por listas manuais no `PanelProvider`
- Escopo por `panel` para `roles` e `permissions` deve ser configurável separadamente no plugin object, para permitir estes cenários:
  - projeto sem escopo por painel
  - `roles` escopadas por painel e `permissions` globais
  - `roles` e `permissions` escopadas por painel

### 2. `FilamentPermissionServiceProvider`

- **Component:** `CoringaWc\FilamentAcl\FilamentPermissionServiceProvider`
- **Location:** `src/FilamentPermissionServiceProvider.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/03-building-a-panel-plugin.md
- **Config:** provider baseado em `Spatie\\LaravelPackageTools\\PackageServiceProvider`

### Requisitos obrigatórios

- registrar config file
- registrar commands
- bindar interfaces para implementações padrão
- registrar facade singleton do manager
- registrar migrations publicáveis do pacote
- não registrar assets no V1
- não depender de base classes do pacote em nenhum binding ou fluxo interno

### 3. Config pública

- **Component:** `config/filament-acl.php`
- **Location:** `config/filament-acl.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/01-getting-started.md

### Shape mínimo da config

```php
return [
    'models' => [
        'permission' => Spatie\Permission\Models\Permission::class,
        'role' => Spatie\Permission\Models\Role::class,
    ],

    'subject_resolver' => CoringaWc\FilamentAcl\Support\ConfiguredPermissionSubjectResolver::class,
    'permission_key_builder' => CoringaWc\FilamentAcl\Support\DefaultPermissionKeyBuilder::class,
    'permission_store' => CoringaWc\FilamentAcl\Support\SpatiePermissionStore::class,

    'permissions' => [
        'separator' => ':',
        'ability_case' => 'studly',
        'subject_case' => 'preserve',
        'allow_fallback_subjects' => false,
    ],

    'database' => [
        'panel_scope' => [
            'column' => 'panel',
            'on_roles' => false,
            'on_permissions' => false,
            'type' => 'string',
            'length' => 50,
            'nullable' => false,
            'default' => 'global',
        ],
    ],

    'discovery' => [
        'panels' => [],
        'paths' => [],
    ],

    'policies' => [
        'path' => app_path('Policies'),
        'generate' => true,
        'merge' => true,
        'methods' => [
            'viewAny', 'view', 'create', 'update', 'delete', 'deleteAny',
            'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny',
            'replicate', 'reorder',
        ],
        'single_parameter_methods' => [
            'viewAny', 'create', 'deleteAny', 'forceDeleteAny', 'restoreAny', 'reorder',
        ],
    ],

    'stubs' => [
        'path' => base_path('stubs/filament-acl'),
    ],

    'subject_overrides' => [
        // class-string => explicit subject
    ],

    'callbacks' => [
        'resolve_permission_subject_using' => null,
        'build_permission_key_using' => null,
    ],

    'relation_managers' => [
        'delegate_to_related_resource_by_default' => false,
    ],

    'integration' => [
        'require_explicit_opt_in' => true,
    ],
];
```

### Regra de precedência

- os métodos fluentes do `plugin()` definem o comportamento por painel no runtime do painel atual
- a config publicada define os defaults globais quando o `plugin()` não sobrescrever esses flags
- a migration publicada continua sendo propriedade do app consumidor; se o projeto mudar a estratégia de `panel scope`, ele precisa publicar ou ajustar a migration coerentemente

### Exemplo esperado no `PanelProvider`

```php
use CoringaWc\FilamentAcl\FilamentPermissionPlugin;

->plugin(
    FilamentPermissionPlugin::make()
        ->scopeRolesByPanel()
        ->scopePermissionsByPanel(false)
)
```

### Customização estilo Shield via provider

O pacote deve expor hooks equivalentes aos do Shield, mas próprios:

```php
FilamentPermission::resolvePermissionSubjectUsing(Closure $callback);
FilamentPermission::buildPermissionKeyUsing(Closure $callback);
FilamentPermission::defaultPermissionKeyBuilder(...);
```

Esses hooks devem permitir:

- qualificar o `subject` por cluster/navigation/panel quando o projeto quiser
- mudar o formato da permission key sem trocar a implementação inteira do pacote
- manter a lógica centralizada em provider do host app, e não espalhada nas classes consumidoras

### 4. Migration própria do pacote

- **Arquivo base:** `database/migrations/create_permission_tables.php.stub`
- **Objetivo:** publicar um schema compatível com `spatie/laravel-permission`, mas pronto para uso em painéis múltiplos

### Regras obrigatórias da migration

- a migration deve criar `permissions`, `roles`, `model_has_permissions`, `model_has_roles` e `role_has_permissions`
- `roles` devem receber coluna de `panel` quando `database.panel_scope.on_roles === true`
- `permissions` devem receber coluna de `panel` quando `database.panel_scope.on_permissions === true`
- a coluna `panel` deve ser do tipo configurável, mas o V1 deve oferecer suporte oficial apenas ao tipo `string`
- o pacote não deve assumir enum de painel; o valor é do host app
- os índices/uniques devem incluir `panel` quando a coluna estiver habilitada na tabela correspondente
- o suporte a `teams` do Spatie deve ser preservado
- quando `permissions` usar coluna `panel`, o adapter padrão do pacote deve incluir `panel` no lookup e no upsert; não basta herdar o lookup padrão do Spatie por `name + guard_name`
- o pacote deve documentar que projetos com `panel` em `permissions` precisam usar o store/model configurado pelo plugin, ou um model custom equivalente no app consumidor

### Unicidade esperada

- quando `roles` forem escopadas por painel:
  - sem teams: unique `['panel', 'name', 'guard_name']`
  - com teams: unique `['team_foreign_key', 'panel', 'name', 'guard_name']`
- quando `roles` não forem escopadas por painel:
  - sem teams: unique `['name', 'guard_name']`
  - com teams: unique `['team_foreign_key', 'name', 'guard_name']`
- quando `permissions` forem escopadas por painel:
  - unique `['panel', 'name', 'guard_name']`
- quando `permissions` não forem escopadas por painel:
  - unique `['name', 'guard_name']`

### Regra de genericidade

- se um projeto usar painel único, ele pode manter sempre o mesmo valor de `panel`
- se quiser desabilitar o escopo por painel em uma das tabelas, pode desligar o respectivo flag na config antes de publicar a migration

---

## Fase 7.1 — UI Opcional de Gestão de Roles/Permissões

### Objetivo

Fornecer uma UI padrão, reutilizável e extensível para gestão de roles/permissões, inspirada no desenho já aplicado em [AdministrationRoleResource.php](/home/coringawc/siasgfacil-filament/app/Filament/Admin/Clusters/ManageUsers/Resources/AdministrationRoles/AdministrationRoleResource.php) e [ApplicationRoleResource.php](/home/coringawc/siasgfacil-filament/app/Filament/Admin/Clusters/ManageTenants/Resources/ApplicationRoles/ApplicationRoleResource.php), sem acoplar o núcleo do pacote a essa UI.

### Distinção arquitetural obrigatória

- o núcleo de ACL contextual continua sem `BaseResource` / `BaseRelationManager`
- a UI de roles/permissões pode ter um resource base próprio, porque ela é um produto opcional do pacote, não uma exigência estrutural para o host app inteiro

### Componentes previstos

#### `RoleManagementResource`

- **Component:** `CoringaWc\FilamentAcl\Resources\Roles\RoleManagementResource`
- **Location:** `src/Resources/Roles/RoleManagementResource.php`
- **Papel:** classe base opcional da UI, a ser estendida pelo projeto consumidor

#### Pages base

- `src/Resources/Roles/Pages/ListRoles.php`
- `src/Resources/Roles/Pages/CreateRole.php`
- `src/Resources/Roles/Pages/EditRole.php`

#### Trait de schema

- **Component:** `CoringaWc\FilamentAcl\Resources\Roles\Concerns\HasRoleManagementFormComponents`
- **Location:** `src/Resources/Roles/Concerns/HasRoleManagementFormComponents.php`
- **Papel:** montar a árvore de permissões, grupos, ações “selecionar tudo” e componentes de formulário/tabela

### O que pertence ao plugin

- schema padrão de formulário e tabela
- árvore visual de permissões baseada no discovery/registry do próprio pacote
- páginas base de listagem, criação e edição
- sincronização de permissões no `afterCreate()` e `afterSave()`
- hooks protegidos para labels, cluster, navegação, escopo de query e campos adicionais

### O que permanece no host app

- model concreto de `Role`
- filtro por painel/tenant e qualquer `where()` de domínio
- labels finais, ícones, cluster, grupo de navegação, badge e sort
- campos extras além de nome/team/permissions
- decisão de expor uma ou várias resources concretas, como “Administration Roles” e “Application Roles”

### API mínima da UI base

```php
abstract class RoleManagementResource extends Resource
{
    abstract public static function getRoleModel(): string;

    abstract protected static function scopeRoleQuery(Builder $query): Builder;

    protected static function getRolePanel(): ?string;

    protected static function getPermissionFormSchema(): array;

    protected static function getRoleTableColumns(): array;
}
```

### Regra obrigatória

- a UI base deve ser útil por extensão, não por cópia
- o projeto consumidor deve conseguir reproduzir o padrão de “Administration Roles” e “Application Roles” mudando só model, labels, query scope e navegação
- a UI não deve depender do Shield em runtime

### Comando opcional de scaffold

O pacote pode expor um comando adicional para criar uma resource concreta estendendo a UI base:

```php
filament-acl:make-role-resource
    {name}
    {--panel=}
    {--cluster=}
    {--model=}
```

Esse comando deve gerar arquivos do app a partir de stubs publicáveis, sem modificar `PanelProvider` automaticamente.

---

## Fase 8 — Geração, Stubs, Descoberta e Sync

### 1. `GenerateCommand`

- **Component:** `CoringaWc\FilamentAcl\Commands\GenerateCommand`
- **Location:** `src/Commands/GenerateCommand.php`
- **Inspirado por:** `shield:generate`
- **Signature proposta:**

```php
filament-acl:generate
    {--all}
    {--option=policies_and_permissions}
    {--resource=}
    {--relation-manager=}
    {--exclude}
    {--ignore-existing-policies}
    {--panel=}
    {--dry-run}
    {--json}
```

### Objetivo do comando

- Ser a entrada de DX principal do pacote, como o `shield:generate`
- Permitir gerar policies contextualizadas, sincronizar permissões, ou ambos
- Funcionar de forma interativa quando flags não forem fornecidas
- Funcionar de forma determinística e estável em CI quando todas as flags forem fornecidas

### Fluxo obrigatório

1. Resolver o painel alvo, quando aplicável
2. Descobrir entidades opt-in do pacote
3. Filtrar `Resource` e `RelationManager` por flags
4. Quando `--option` incluir `policies`, gerar ou atualizar policies por stub
5. Quando `--option` incluir `permissions`, delegar para o fluxo de `SyncPermissionsCommand`
6. Exibir resumo final por entidade processada, policies geradas e permissões geradas

### Regras obrigatórias

- `custom Actions` não são unidades descobertas pelo comando; elas entram apenas via abilities declaradas na entidade dona
- o comando deve falhar claramente se uma entidade usar fallback de subject quando `allow_fallback_subjects=false`
- `--ignore-existing-policies` deve pular arquivos existentes, nunca sobrescrevê-los silenciosamente
- o modo interativo deve usar prompts, mas nunca esconder capacidade equivalente por flags

### 2. `PublishStubsCommand`

- **Component:** `CoringaWc\FilamentAcl\Commands\PublishStubsCommand`
- **Location:** `src/Commands/PublishStubsCommand.php`
- **Signature proposta:**

```php
filament-acl:publish-stubs
    {--force}
```

### Objetivo do comando

- Publicar os stubs do pacote para `base_path('stubs/filament-acl')`
- Permitir que o projeto consumidor customize a estrutura das policies geradas
- Reproduzir a boa DX do Shield, mas com foco em policies contextualizadas

### Regras obrigatórias

- se o stub já existir e `--force` não for passado, o comando deve preservar o arquivo local
- o lookup de stub do pacote deve sempre priorizar `base_path('stubs/filament-acl')`
- o pacote deve documentar quais stubs podem ser sobrescritos e quais placeholders eles recebem

### 2.1. Publicação de migrations

- a migration compatível com Spatie + `panel` deve ser publicada por tag, no estilo padrão de package tools
- o pacote pode expor um comando dedicado de publish se isso simplificar a DX, mas não deve editar config/provider do host app automaticamente

### 3. Geração de policy por stub

- **Component:** `CoringaWc\FilamentAcl\Commands\Concerns\CanGeneratePolicy`
- **Location:** `src/Commands/Concerns/CanGeneratePolicy.php`
- **Inspirado por:** `BezhanSalleh\FilamentShield\Commands\Concerns\CanGeneratePolicy`

### Responsabilidades obrigatórias

- resolver o path final da policy a partir do model alvo e da config do pacote
- montar as variáveis de stub para namespace, model, policy e métodos
- distinguir métodos de um parâmetro, métodos com record e métodos customizáveis
- gerar uma classe de policy que use `ChecksPermission` e aceite `PermissionAction|string|null $action = null`

### Regra de genericidade

- o pacote só deve gerar automaticamente métodos cuja assinatura ele conheça com segurança
- para methods/abilities custom sem assinatura inferível, o gerador deve:
  - usar um stub customizável dedicado, ou
  - emitir bloco `TODO` explícito para implementação manual
- o pacote não deve fingir inferência de assinatura para casos como `transfer(User $actor, User $record, Tenant $destination, PermissionAction|string|null $permissionAction = null)`

### 4. Manipulação de arquivos e stubs

- **Component:** `CoringaWc\FilamentAcl\Commands\Concerns\CanManipulateFiles`
- **Location:** `src/Commands/Concerns/CanManipulateFiles.php`
- **Inspirado por:** `BezhanSalleh\FilamentShield\Commands\Concerns\CanManipulateFiles`

### Responsabilidades obrigatórias

- checagem de colisão
- escrita idempotente
- cópia de stub com fallback pacote -> app
- suporte a placeholders de classe e de método

### 5. `SyncPermissionsCommand`

- **Component:** `CoringaWc\FilamentAcl\Commands\SyncPermissionsCommand`
- **Location:** `src/Commands/SyncPermissionsCommand.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/11-plugins/02-panel-plugins.md
- **Signature proposta:**

```php
filament-acl:sync
    {--panel=*}
    {--only=resources,relation-managers}
    {--dry-run}
    {--delete-stale}
    {--json}
```

### Fluxo obrigatório

1. Resolver panels alvo
2. Rodar `FilamentDirectoryDiscovery`
3. Popular `PermissionRegistry`
4. Coletar abilities por entidade
5. Resolver subject por entidade
6. Gerar keys via `BuildsPermissionKey`
7. Upsert via `StoresPermissions`
8. Se `--delete-stale`, deletar somente permissões antes geridas pelo pacote e ausentes na nova coleta
9. Exibir resumo por entidade, subject e key

### Regra obrigatória do V1

- Abilities customizadas de action entram no sync porque foram declaradas na entidade dona.
- O comando não tenta descobrir actions como unidades independentes.
- Se uma entidade depender de fallback de subject e `allow_fallback_subjects=false`, o comando deve falhar com relatório claro em vez de sincronizar uma key implícita silenciosamente.

### 6. `InspectPermissionsCommand`

- **Component:** `CoringaWc\FilamentAcl\Commands\InspectPermissionsCommand`
- **Location:** `src/Commands/InspectPermissionsCommand.php`
- **Signature proposta:**

```php
filament-acl:inspect
    {class}
    {--type=}
    {--panel=}
    {--json}
```

### Saída obrigatória

- tipo da entidade
- subject resolvido
- abilities
- permission keys geradas
- `relatedResource`, se houver
- source da resolução do subject: explícito, config ou fallback

### 7. `CheckPermissionsReadinessCommand`

- **Component:** `CoringaWc\FilamentAcl\Commands\CheckPermissionsReadinessCommand`
- **Location:** `src/Commands/CheckPermissionsReadinessCommand.php`
- **Signature proposta:**

```php
filament-acl:check
```

### Verificações obrigatórias

- `spatie/laravel-permission` instalado
- config `permission.php` carregável
- tabela `permissions` existente
- coluna `panel` existente nas tabelas geridas, quando o feature flag estiver habilitado
- provider do pacote carregado
- ao menos uma entidade gerida descoberta

---

## Fase 9 — Bridge de Migração do Shield

### Objetivo

Fornecer uma ponte segura de migração para projetos que já usaram Shield, sem transformar o pacote em fork do Shield.

### Componente

- **Component:** `CoringaWc\FilamentAcl\Commands\BridgeShieldPermissionsCommand`
- **Location:** `src/Commands/BridgeShieldPermissionsCommand.php`
- **Docs:** https://github.com/filamentphp/filament/blob/5.x/docs/09-advanced/06-security.md

### Signature proposta

```php
filament-acl:bridge-shield
    {--panel=*}
    {--dry-run}
    {--json}
```

### Responsabilidade do V1

- Ler permissões existentes no backend do Spatie
- Detectar padrões conhecidos do Shield
- Gerar um relatório de mapeamento `old_permission -> new_permission`
- Nunca renomear ou deletar automaticamente no V1

### Fontes de reutilização permitidas do projeto atual

- Estratégia de resolução de subject inspirada em `app/FilamentShield/FilamentShield.php`
- Convenções de localized labels e qualificação por namespace

### Regras

- Não portar o core do Shield como vendor interno
- Não depender de `bezhansalleh/filament-shield` em `require` ou `require-dev`
- Não inferir abilities por `getPermissionPrefixes()` como contrato primário do pacote

---

## Fase 10 — Adoção Piloto no `siasgfacil-filament`

### Objetivo

Validar o plugin em problemas reais do sistema antes de expandir o uso.

### Slice piloto obrigatório

#### 0. UI base de roles/permissões

- reproduzir o padrão dos recursos [AdministrationRoleResource.php](/home/coringawc/siasgfacil-filament/app/Filament/Admin/Clusters/ManageUsers/Resources/AdministrationRoles/AdministrationRoleResource.php) e [ApplicationRoleResource.php](/home/coringawc/siasgfacil-filament/app/Filament/Admin/Clusters/ManageTenants/Resources/ApplicationRoles/ApplicationRoleResource.php)
- validar que duas resources concretas podem estender a mesma UI base mudando apenas:
  - model
  - labels
  - navigation/cluster
  - scope de query
- nenhum comportamento de sync de permissões deve ficar duplicado no app consumidor
- a trait/form schema do pacote deve absorver o que hoje está espalhado em [HasShieldFormComponents.php](/home/coringawc/siasgfacil-filament/app/FilamentShield/Traits/HasShieldFormComponents.php), exceto regras nitidamente específicas do app

#### 1. `TenantUsersRelationManager`

- subject explícito sugerido: `TenantUsers`
- abilities padrão + custom:
  - `transfer`
  - `invite`
  - `permissions`
- observação obrigatória: o `ToggleColumn` de `is_app_admin` não ganha proteção automática de policy no Filament; durante o piloto ele deve continuar usando `disabled()` / `visible()` com check contextual explícito ou ser refatorado para action/modal

#### 2. `AdvanceStatusAction`

- owner class: `ContractingProcessResource`
- owner type: `resource`
- ability: `advanceStatus`

#### 3. `ContractingProcessesRelationManager`

- subject explícito sugerido: `WalletContractingProcesses`
- delegação para `relatedResource`: `false`

#### 4. `MyWallet\ContractingProcessesRelationManager`

- subject explícito sugerido: `MyWalletContractingProcesses`
- delegação para `relatedResource`: `false`

### Resultado esperado do piloto

- uma UI base única consegue sustentar “Administration Roles” e “Application Roles” por extensão
- policies únicas por model onde for possível
- ausência de model estendido apenas para namespace de permissão
- custom actions usando `authorize()` nativo com a classe dona como argumento explícito quando necessário
- o `ToggleColumn` de administrador no `TenantUsersRelationManager` continua protegido explicitamente por `disabled()` / validação adicional, sem confiar em policy automática do Filament

---

## Fase 11 — Testes

### Estratégia

- converter o filament-acl para PHPUnit
- usar Orchestra Testbench no pacote
- cobrir permissões happy path, denial path e owner ausente

### Testes obrigatórios do pacote

#### `PluginRegistrationTest`

- provider registra config, commands e singleton do manager
- plugin object retorna `getId() === 'filament-acl'`

#### `PermissionActionTest`

- `forResource()`, `forRelationManager()` e `fromOwnerClass()` preenchem os campos corretos
- DTO é imutável e serializável

#### `ConfiguredPermissionSubjectResolverTest`

- usa subject explícito quando a classe declara `getPermissionSubject()`
- usa map de config quando presente
- usa fallback determinístico quando necessário

#### `DefaultPermissionKeyBuilderTest`

- gera key no formato `Ability:Subject`
- respeita `separator` e `case`

#### `SpatiePermissionStoreTest`

- quando `database.panel_scope.on_permissions === true`, lookup e upsert usam `panel` além de `name` e `guard_name`
- quando `database.panel_scope.on_permissions === false`, o adapter continua compatível com o comportamento padrão do Spatie
- teams + panel preservam as chaves de unicidade esperadas

#### `ResourceAuthorizationTest`

- uma `Resource` com `HasResourcePermissions` passa `PermissionAction` para a policy
- `authorize('update', $record)` propaga a mensagem de `Response::deny()`

#### `RelationManagerAuthorizationTest`

- `canViewForRecord()` usa contexto do relation manager
- `getAuthorizationResponse()` respeita `shouldUseRelatedResourcePermissions()`

#### `ActionAuthorizationTest`

- `->authorize('ability', OwnerClass::class)` propaga a classe dona corretamente
- record actions não duplicam o record nos argumentos
- header actions incluem model class + owner class
- quando o host suportar auto-resolução de owner, `->authorize('ability')` puro funciona; quando não suportar, o teste deve cobrir o fallback explícito

#### `GenerateCommandTest`

- `--option=policies` gera policy contextualizada a partir de stubs
- `--ignore-existing-policies` preserva arquivo existente
- métodos de um parâmetro usam o stub correto
- métodos com record usam o stub correto
- métodos custom sem assinatura inferível geram bloco `TODO` explícito ou usam stub custom publicado

#### `PublishStubsCommandTest`

- publica stubs em `stubs/filament-acl`
- sem `--force`, preserva customizações locais
- com `--force`, sobrescreve stubs publicados
- lookup do gerador prioriza o stub do host app sobre o stub interno do pacote

#### `PermissionSyncCommandTest`

- sync cria permissões esperadas
- `--dry-run` não persiste
- `--delete-stale` remove apenas permissões geridas pelo pacote
- discovery ignora classes fora do contrato do pacote
- abilities customizadas declaradas no owner entram no sync
- nenhuma action é descoberta isoladamente

#### `ShieldBridgeCommandTest`

- relatório de mapping é gerado
- `--dry-run` não altera dados

### Testes mínimos no projeto consumidor durante o piloto

- um teste de feature para `TenantUsersRelationManager` cobrindo `transfer` negado e permitido
- um teste de feature para o `ToggleColumn` de administrador garantindo que usuário sem ACL contextual não consegue efetivar a mudança
- um teste de feature para `AdvanceStatusAction` cobrindo `advanceStatus` negado e permitido
- um teste de feature cobrindo subjects diferentes para os dois `ContractingProcessesRelationManager`

---

## Riscos e Mitigações

### R1 — Fallback de subject baseado em FQCN gerar key feia ou instável

- **Mitigação:** subject explícito deve ser o caminho recomendado e o fallback deve ser apenas ponte de adoção

### R2 — Policies existentes quebrarem por assinatura incompatível

- **Mitigação:** `PermissionAction` sempre é parâmetro adicional e opcional, nunca substitui a assinatura padrão do Laravel

### R3 — Relation managers continuarem delegando sem querer ao `relatedResource`

- **Mitigação:** default do pacote é subject próprio; delegação é opt-in

### R4 — Actions continuarem usando apenas `visible()`

- **Mitigação:** o README e a UI base do pacote precisam documentar e incentivar `authorize()` nativo com `OwnerClass::class` como argumento

### R5 — Colunas interativas do Filament bypassarem policy contextual

- **Mitigação:** documentar explicitamente que `ToggleColumn`, `CheckboxColumn` e `SelectColumn` precisam de guards adicionais no host app; o pacote não promete cobrir isso implicitamente no V1

### R6 — Comando de sync apagar permissões não geridas pelo pacote

- **Mitigação:** manter namespace/manifest interno das permissões geridas e exigir `--delete-stale`

### R7 — Contaminação por estado global em ambiente Octane

- **Mitigação:** nada de static mutável ou singleton com request/user/config capturados; `PermissionAction` é criado por chamada

### R8 — Trait colidir com overrides locais do projeto consumidor

- **Mitigação:** documentar precedência de método, exigir alias explícito quando o host app já sobrescrever `getAuthorizationResponse()` ou `canViewForRecord()` e manter a integração por trait como contrato principal

### R9 — O pacote crescer para resolver problemas específicos do app piloto

- **Mitigação:** qualquer regra de tenancy, workflow, bridge legada, naming específico ou heurística não reutilizável deve ficar no projeto consumidor; o pacote recebe apenas os pontos de extensão necessários

### R10 — Uma mesma classe de `Resource` ser registrada mais de uma vez no panel

- **Mitigação:** manter `registrationKey` previsto no contexto e no subject resolver desde o V1, mesmo que o primeiro piloto não exercite esse caminho

### R11 — `->authorize('ability')` puro não ser universalmente inferível em actions customizadas

- **Mitigação:** tratar auto-resolução de owner como conveniência opt-in onde o host expõe hook suficiente; o contrato estável do V1 continua sendo `->authorize('ability', OwnerClass::class)` quando o alvo for implícito, ou array explícito quando houver argumentos adicionais

---

## Checklist de Revisão

- [ ] O pacote não depende arquiteturalmente do Shield
- [ ] Existe migration própria do pacote compatível com Spatie + coluna `panel`
- [ ] `PermissionAction` é explícita e opcional nas policies consumidoras
- [ ] `PermissionAction` não resolve subject sozinho; ele só carrega dados já resolvidos
- [ ] `HasResourcePermissions` sobrescreve o fluxo de autorização do Filament sem exigir troca de herança
- [ ] `HasRelationManagerPermissions` sobrescreve `canViewForRecord()` e `getAuthorizationResponse()` sem exigir troca de herança
- [ ] O pacote não publica nem exige `BaseResource` / `BaseRelationManager`
- [ ] O pacote não publica helper custom de `Action`; usa apenas `authorize()` / `visible()` nativos
- [ ] O fluxo contextual preserva a semântica do Filament para policy ausente / método ausente / strict mode
- [ ] `RelationManager` usa subject próprio por padrão
- [ ] actions de permissão customizadas dependem de declaração explícita na entidade dona
- [ ] Existe comando de geração de policies e permissões inspirado no Shield
- [ ] Existe comando para publicar stubs em `stubs/filament-acl`
- [ ] Existe estratégia documentada para customizar subject e permission key via provider/callback
- [ ] Há comando de sync com `--dry-run`
- [ ] Há comando de inspeção por classe
- [ ] A bridge do Shield é apenas relatório no V1
- [ ] A suíte do plugin está em PHPUnit
- [ ] O pacote é estritamente opt-in e não assume que todo `Resource` ou `RelationManager` do app será gerido por ele
- [ ] O desenho do contexto não fecha a porta para `configurable resources`
- [ ] Regras específicas do app piloto permanecem fora do pacote
- [ ] A UI base de roles/permissões é opcional, extensível e separada do núcleo contextual
- [ ] O piloto em `siasgfacil-filament` cobre `TenantUsersRelationManager` e `AdvanceStatusAction`
- [ ] O piloto cobre o risco adicional de `ToggleColumn`
- [ ] O README final não contém placeholders do filament-acl
