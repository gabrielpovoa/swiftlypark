# Design System Specification

## 1. Objetivo

Este documento registra um sistema visual reutilizável e define uma referência para novas telas e refatorações.

O design é uma interface SaaS escura, com alto contraste, superfícies translúcidas, cantos arredondados, azul como cor principal e cores semânticas para sucesso, atenção, informação e erro.

Esta especificação cobre:

- infraestrutura Tailwind e CSS;
- fontes e hierarquia tipográfica;
- cores e backgrounds;
- espaçamento, dimensões e responsividade;
- componentes recorrentes;
- ícones, gráficos e animações;
- acessibilidade;
- inconsistências atuais e direção recomendada.

## 2. Infraestrutura visual atual

### 2.1. Tailwind CSS

O projeto carrega Tailwind CSS v4 diretamente pelo navegador:

```html
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
```

Estado atual:

- não existe `tailwind.config.js`;
- não existe processo local de build/purge do Tailwind;
- as classes podem ser escritas diretamente nos templates HTML e JavaScript;
- valores arbitrários são frequentes, como `bg-[#0b0e14]`, `rounded-[2.5rem]` e `tracking-[0.3em]`;
- pequenas regras CSS específicas aparecem dentro das próprias views.

Consequência: o produto possui uma linguagem visual reconhecível, mas os tokens ainda não estão centralizados tecnicamente.

### 2.2. CSS adicional

O CSS manual é usado principalmente para:

- animações específicas, como `shake` e pulso;
- transição da sidebar;
- botões flutuantes e tooltips;
- scrollbar da navegação;
- ajustes de impressão;
- substituição pontual da fonte na tela de perfil;
- templates de e-mail, que usam CSS inline por compatibilidade.

Novos componentes devem priorizar classes Tailwind. CSS manual deve ser reservado para comportamento que não seja expresso claramente pelas utilities existentes.

### 2.3. Bibliotecas visuais

| Biblioteca | Uso |
|---|---|
| Tailwind CSS v4 Browser | Layout, cores, responsividade e estados |
| Lucide Icons | Ícones de navegação, ações, status e indicadores |
| SweetAlert2 | Confirmações, erros e interações modais |
| Chart.js | Gráficos e visualização de dados |
| Google Fonts | Nunito global e Inter na tela de perfil |

## 3. Identidade visual

### 3.1. Personalidade

A interface deve transmitir:

- tecnologia e controle operacional;
- segurança e rastreabilidade;
- clareza para uso contínuo em sistemas administrativos;
- separação visível entre ações normais, críticas e privilegiadas;
- aparência premium sem comprometer a leitura.

Características predominantes:

- dark mode como tema principal;
- títulos pesados e compactos;
- labels pequenas, em caixa alta e com tracking amplo;
- cards com transparência baixa e bordas claras sutis;
- iluminação azul/índigo difusa no background;
- verde para sucesso e indicadores positivos;
- âmbar para atenção e contextos temporários;
- rose para erro, remoção e segurança crítica.

## 4. Tipografia

### 4.1. Fonte principal

`Nunito` é a fonte principal do sistema:

```css
font-family: "Nunito", sans-serif;
```

Pesos carregados: `200` a `1000`, normal e itálico.

Uso recomendado:

- interface geral;
- formulários;
- navegação;
- dashboards;
- cards e tabelas;
- títulos institucionais.

Fallback:

```css
font-family: "Nunito", ui-sans-serif, system-ui, sans-serif;
```

### 4.2. Fonte secundária existente

Uma implementação existente também utiliza `Inter` nos pesos `300` a `700` e substitui a fonte do `body`.

Isso é uma exceção atual, não um padrão global. Para consistência, novas telas devem usar Nunito. Inter deve ser removida da tela de perfil em uma futura consolidação, salvo decisão explícita de migrar todo o produto para Inter.

### 4.3. Fontes utilitárias

- `font-mono`: placas, horários, códigos e valores que precisam de alinhamento visual;
- `Courier New`: código de ticket em template de e-mail;
- Arial/Helvetica/Segoe UI: templates de e-mail e conteúdos externos, por compatibilidade.

### 4.4. Escala tipográfica recomendada

| Token | Classes recorrentes | Uso |
|---|---|---|
| Display XL | `text-6xl font-black tracking-tighter` | Hero e números operacionais importantes |
| Display | `text-4xl font-black tracking-tight` | Título principal da página |
| Heading 1 | `text-3xl font-black tracking-tight` | Títulos de tela compactos |
| Heading 2 | `text-xl font-black` ou `text-2xl font-bold` | Seções e cards destacados |
| Heading 3 | `text-base font-bold` | Subsessões e linhas principais |
| Body | `text-sm` ou base padrão | Conteúdo e descrições |
| Supporting | `text-xs text-slate-500` | Ajuda e metadados |
| Eyebrow/Label | `text-[10px] font-black uppercase tracking-widest` | Labels, categorias e KPIs |
| Micro | `text-[8px]` ou `text-[9px] font-bold uppercase` | Badges e metadados compactos |

Regras:

- `font-black` é o peso de identidade para títulos, botões e KPIs;
- `font-bold` é usado para textos de interface com importância média;
- descrições devem evitar caixa alta;
- caixa alta e tracking amplo devem ficar restritos a labels curtas;
- evitar textos essenciais abaixo de `10px`.

## 5. Cores

### 5.1. Paleta base

| Token conceitual | Valor/classe atual | Função |
|---|---|---|
| `surface-canvas` | `#0b0e14` | Background principal da aplicação |
| `surface-canvas-deep` | `#0a0c10` | Background mais escuro, usado no perfil |
| `surface-control` | `#131720` | Inputs, selects e controles |
| `surface-control-alt` | `#111827` / `#111722` | Selects e controles especiais |
| `surface-elevated` | `white/[0.02]` a `white/[0.05]` | Cards e painéis |
| `border-subtle` | `white/5` | Divisores e cards discretos |
| `border-default` | `white/10` | Cards, inputs e botões secundários |
| `text-primary` | `white` | Títulos, valores e ações importantes |
| `text-secondary` | `slate-300` / `slate-400` | Conteúdo secundário |
| `text-muted` | `slate-500` | Labels e descrições |
| `text-disabled` | `slate-600` | Estados fracos ou indisponíveis |

### 5.2. Cor de marca e ação principal

Azul é a cor primária do design system.

| Estado | Classe |
|---|---|
| Ação principal | `bg-blue-600 text-white` |
| Hover | `hover:bg-blue-500` |
| Texto/ícone | `text-blue-400` ou `text-blue-500` |
| Fundo discreto | `bg-blue-500/10` |
| Borda | `border-blue-500/20` |
| Focus ring | `focus:ring-2 focus:ring-blue-500/50` |
| Glow | `shadow-blue-600/20` |

Gradiente de marca recorrente:

```text
from-blue-500 to-indigo-600
from-blue-400 to-indigo-400
```

### 5.3. Cores semânticas

#### Sucesso e estado operacional positivo

```text
bg-emerald-500/10
border-emerald-500/20
text-emerald-400
```

Usos: confirmação, status ativo, disponibilidade e sucesso de formulário.

#### Indicadores positivos

Emerald também pode ser usado como accent de métricas positivas:

```text
bg-emerald-500
hover:bg-emerald-400
text-emerald-400
```

#### Erro e ação destrutiva

```text
bg-rose-500/10
border-rose-500/20
text-rose-400
hover:bg-rose-500
```

Usos: erro, acesso revogado, exclusão/inativação e saída operacional.

#### Atenção e contexto temporário

```text
bg-amber-500/10
border-amber-500/20
text-amber-300
bg-amber-400 text-black
```

Usos: sessão temporária, contexto alternativo, pendência e avisos que exigem atenção.

#### Informação e papéis especiais

- índigo: perfis, categorias e marca complementar;
- cyan/sky: indicadores informativos;
- violet/lime: diferenciação pontual de alertas e categorias.

Não introduzir nova cor sem um significado semântico distinto.

## 6. Backgrounds e profundidade

### 6.1. Canvas principal

O padrão das páginas autenticadas é:

```html
<section class="min-h-screen bg-[#0b0e14] text-slate-300 p-5 md:p-10">
```

Telas dentro do shell com sidebar usam margem esquerda de `5rem` (`ml-20`). A sidebar expande visualmente para `18rem` (`w-72`) no hover.

### 6.2. Luz ambiente

Telas de login e dashboard usam círculos desfocados posicionados no background:

```text
bg-blue-600/5 ou /10
bg-indigo-600/5 ou /10
blur-[80px] a blur-[120px]
rounded-full
```

Esses elementos são decorativos e devem ter `pointer-events-none`.

### 6.3. Superfícies

| Nível | Padrão |
|---|---|
| Card discreto | `bg-white/[0.02] border border-white/5` |
| Card padrão | `bg-white/[0.03] border border-white/10` |
| Card interativo | card padrão + `hover:bg-white/[0.05]` |
| Input | `bg-[#131720] border border-white/10` |
| Glass panel | `bg-white/[0.02] backdrop-blur-2xl border-white/5` |
| Overlay | `bg-[#0b0e14]/80 backdrop-blur-sm` |

### 6.4. Sombras

Padrões recorrentes:

- card elevado: `shadow-2xl`;
- ação primária: `shadow-lg shadow-blue-600/20`;
- painel/modal: `shadow-[0_20px_50px_rgba(0,0,0,0.5)]`;
- glow contextual: sombra com a mesma família cromática do componente.

Sombras não devem substituir bordas em dark mode. Cards elevados normalmente usam sombra e borda sutil em conjunto.

## 7. Layout, espaçamento e responsividade

### 7.1. Containers

| Contexto | Classe predominante |
|---|---|
| Conteúdo administrativo | `max-w-7xl mx-auto` |
| Perfil | `max-w-6xl mx-auto` |
| Autenticação | `max-w-md` |
| Conteúdo amplo legado | `.content-wrapper { max-width: 1600px; }` |

### 7.2. Padding de página

Padrão autenticado:

```text
p-5 md:p-10
```

Telas mais editoriais ou perfil usam:

```text
p-6 md:p-12
```

### 7.3. Espaçamentos recorrentes

- gap compacto: `gap-2` ou `gap-3`;
- gap de formulário: `gap-4`;
- gap entre cards: `gap-4` ou `gap-6`;
- separação de grandes seções: `mb-8`;
- padding de card: `p-5`, `p-6` ou `p-8`;
- campos grandes: `px-4 py-3` ou `pl-12 pr-4 py-4` quando possuem ícone.

### 7.4. Grids

O sistema segue mobile first:

```text
grid-cols-1
sm:grid-cols-2
md:grid-cols-2
lg:grid-cols-*
xl:grid-cols-3/4/6/12
```

Regras:

- formulários começam em uma coluna;
- KPIs podem passar para duas colunas em `sm`;
- dashboards complexos usam `xl` para composição final;
- headers mudam de coluna para linha em `md`, `lg` ou `xl`;
- ações devem ocupar a largura inteira no mobile quando isso melhorar o toque.

## 8. Formas, bordas e ícones

### 8.1. Border radius

| Elemento | Classe recomendada |
|---|---|
| Badge pequeno | `rounded-lg` ou `rounded-full` |
| Input e botão | `rounded-xl` ou `rounded-2xl` |
| Card padrão | `rounded-2xl` ou `rounded-3xl` |
| Modal/auth card | `rounded-[2.5rem]` |
| Avatar | `rounded-full` |

Para novas telas, preferir:

- `rounded-xl` para controles;
- `rounded-2xl` para cards;
- `rounded-3xl` apenas para painéis de destaque.

### 8.2. Ícones

Lucide é o sistema oficial de ícones.

Escala:

- micro: `w-3 h-3`;
- controle: `w-4 h-4`;
- navegação: `w-5 h-5`;
- destaque: `w-7 h-7` ou `w-8 h-8`;
- autenticação/empty state: `w-10 h-10` ou maior.

Regras:

- ícones devem acompanhar texto em ações que não sejam universalmente reconhecíveis;
- usar `currentColor` por meio das classes `text-*`;
- evitar misturar outras bibliotecas de ícones;
- chamar `lucide.createIcons()` após conteúdo dinâmico ser inserido.

## 9. Componentes

### 9.1. Cabeçalho de página

Estrutura recomendada:

```html
<header class="flex flex-col md:flex-row md:items-end justify-between gap-5 mb-8">
  <div>
    <span class="text-blue-500 text-xs font-black uppercase tracking-[0.3em]">Contexto</span>
    <h1 class="text-4xl font-black text-white mt-2">Título</h1>
    <p class="text-slate-500 mt-2">Descrição objetiva da tela.</p>
  </div>
</header>
```

O accent pode mudar conforme o contexto: azul para ações gerais, emerald para indicadores positivos e âmbar para atenção.

### 9.2. Cards

Card padrão:

```html
<article class="rounded-2xl bg-white/[0.03] border border-white/10 p-6">
```

Card de KPI:

```html
<article class="rounded-3xl bg-white/[0.03] border border-white/10 p-5">
  <i class="w-5 h-5 text-blue-400"></i>
  <p class="text-[10px] uppercase tracking-widest text-slate-500 font-black mt-5">Indicador</p>
  <strong class="block text-3xl text-white font-black mt-2">0</strong>
</article>
```

### 9.3. Inputs e selects

Campo padrão:

```text
w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-sm text-white
focus:outline-none focus:ring-2 focus:ring-blue-500/50
```

Campo com ícone:

```text
w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03]
border border-white/5 text-white placeholder-slate-600 text-sm
focus:ring-2 focus:ring-blue-500/50
```

Label:

```text
block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2
```

Estados necessários:

- focus visível;
- disabled com opacidade reduzida e cursor apropriado;
- erro com rose e mensagem textual;
- sucesso não deve ser indicado somente pela cor.

### 9.4. Botões

#### Primário

```text
rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black px-5 py-3
transition-all active:scale-[0.98]
```

#### Positivo/sucesso

```text
rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black font-black px-5 py-3
```

#### Secundário

```text
rounded-xl bg-white/[0.05] hover:bg-white/[0.08]
border border-white/10 text-slate-200 font-black px-5 py-3
```

#### Destrutivo

```text
rounded-xl bg-rose-500/10 hover:bg-rose-500
border border-rose-500/20 text-rose-400 hover:text-white
```

#### Atenção privilegiada

```text
rounded-lg bg-amber-400 hover:bg-amber-300 text-black font-black
```

### 9.5. Alertas

Padrão:

```text
rounded-2xl px-5 py-4 font-bold border
```

Variações:

- sucesso: emerald `/10`, borda `/20`, texto `400`;
- erro: rose `/10`, borda `/20`, texto `400`;
- atenção: amber `/10`, borda `/20`, texto `300`;
- informação: blue `/10`, borda `/20`, texto `300`.

Aplicar `role="alert"` para erros e `role="status"` para confirmações não críticas.

### 9.6. Badges

```text
inline-flex items-center gap-1
px-2 py-1 rounded-lg
text-[9px] font-black uppercase tracking-widest
```

O badge de papel usa azul. Status ativo usa emerald. Status revogado usa rose. Pendências usam amber.

### 9.7. Sidebar

Características atuais:

- fixa à esquerda;
- largura recolhida `w-20`;
- largura expandida `hover:w-72`;
- background `#0b0e14`;
- borda direita `white/5`;
- transição de `500ms` com curva customizada;
- labels aparecem por opacidade;
- ícones usam `w-5 h-5`;
- item ativo/hover recebe realce azul.

A sidebar também contém:

- logo e marca;
- navegação filtrada por permission;
- seletor de contexto;
- seletor de perfil ou visualização temporária;
- perfil do usuário;
- logout.

### 9.8. Contexto temporário

Contextos temporários ou privilegiados possuem linguagem visual própria em âmbar:

- banner fixo ou sticky no topo;
- `bg-amber-500/10`;
- `border-amber-500/20`;
- `text-amber-100/300`;
- seletor e ação contextual em `amber-400`;
- ícone `badge-alert` ou `badge-check`.

Esse estado deve permanecer visualmente evidente durante toda a sessão temporária.

### 9.9. Modais

Padrão atual:

- overlay cobrindo viewport;
- background escuro translúcido;
- blur de fundo;
- card central com `max-w-md`;
- `rounded-[2.5rem]`;
- sombra forte;
- ações explicitamente separadas.

SweetAlert2 pode ser usado para confirmações rápidas. Modais HTML são adequados para revisão detalhada de dados.

### 9.10. Tabelas, listas e histórico

- preferir cards/listas no mobile;
- títulos e valores importantes em branco;
- metadados em slate-500;
- divisores `border-white/5`;
- ações e tipos recebem cores semânticas;
- tabelas extensas devem usar container com overflow horizontal;
- históricos devem destacar ação, responsável, objeto e alterações sem depender somente de cor.

### 9.11. Footer

O footer segue o shell escuro, usa `max-w-7xl`, card translúcido, glow azul e grid responsivo. Em sessão autenticada, respeita a largura recolhida da sidebar por meio de `ml-20`.

### 9.12. Impressão e e-mail

As telas de impressão são claras e devem evitar o tema dark:

- background branco;
- texto slate escuro;
- bordas neutras;
- ações com `print:hidden`;
- conteúdo tabular e legível.

Templates de e-mail usam CSS inline e fontes seguras do sistema. Eles não devem depender de Tailwind, Lucide ou JavaScript.

## 10. Movimento e interação

Padrões atuais:

- transições rápidas: `duration-300`;
- expansão do shell/sidebar: `duration-500`;
- entrada de página: `duration-700`;
- pressionar botão: `active:scale-95` ou `active:scale-[0.98]`;
- hover de card/ícone: mudança de cor, background e pequena escala;
- loading: `animate-spin` ou `animate-pulse`;
- erro de autenticação: animação `shake` de `400ms`;
- status online: pulso contínuo discreto.

Regras:

- animações devem reforçar estado ou hierarquia;
- evitar animações contínuas em grandes áreas;
- respeitar `prefers-reduced-motion` em uma futura camada CSS global;
- não bloquear interação durante transições decorativas.

## 11. Acessibilidade

Requisitos para novas implementações:

- manter contraste suficiente entre texto e background;
- nunca usar apenas cor para comunicar erro, papel ou estado;
- associar todo `label` ao respectivo campo;
- manter foco de teclado claramente visível;
- usar `aria-live` em mensagens assíncronas;
- usar `role="alert"` para falhas críticas;
- fornecer `alt` significativo para logos e imagens de usuário;
- usar texto acessível ou `aria-label` em botões apenas com ícone;
- garantir área de toque mínima próxima de `44px`;
- não usar textos essenciais menores que `10px`;
- validar contraste das variações com opacidade, especialmente slate-600;
- preservar ordem lógica no mobile.

## 12. Padrões por contexto

| Contexto | Accent | Elementos principais |
|---|---|---|
| Navegação e ação principal | Blue | links, botões primários, foco e marca |
| Métricas positivas | Emerald | KPIs, exportação, gráficos e sucesso |
| Segurança e erro | Rose | bloqueios, exclusão e alertas críticos |
| Atenção e sessão temporária | Amber | banners, contexto alternativo e pendências |
| Perfis e categorias | Blue + Indigo | badges, grupos e permissões visuais |
| Estado positivo | Emerald | disponibilidade, confirmação e conclusão |
| Estado negativo | Rose | falha, indisponibilidade e encerramento |

## 13. Tokens canônicos recomendados

Enquanto não houver configuração Tailwind central, novas telas devem reutilizar estas combinações:

```text
Canvas:          bg-[#0b0e14]
Control:         bg-[#131720]
Card:            bg-white/[0.03] border border-white/10
Card subtle:     bg-white/[0.02] border border-white/5
Primary text:    text-white
Body text:       text-slate-300
Supporting text: text-slate-500
Primary action:  bg-blue-600 hover:bg-blue-500
Focus:           focus:ring-2 focus:ring-blue-500/50
Success:         emerald-500/emerald-400
Warning:         amber-500/amber-400
Danger:          rose-500/rose-400
Control radius:  rounded-xl
Card radius:     rounded-2xl
Page width:      max-w-7xl mx-auto
Page padding:    p-5 md:p-10
```

## 14. Inconsistências conhecidas

O inventário encontrou os seguintes pontos:

1. Nunito é global, mas a página de perfil substitui por Inter.
2. O canvas principal alterna entre `#0b0e14` e `#0a0c10`.
3. Inputs usam `#131720`, `#111827`, `#111722` e superfícies brancas translúcidas.
4. Cards variam entre `rounded-xl`, `rounded-2xl`, `rounded-3xl` e valores arbitrários.
5. Há CSS inline e blocos `<style>` distribuídos em várias views.
6. Alguns textos usam `8px` e podem apresentar baixa legibilidade.
7. O footer informa “Tailwind CSS”, mas a versão e o pipeline não estão centralizados.
8. Tailwind é carregado por CDN em runtime, inadequado como configuração final de produção.
9. Não há suporte global explícito a `prefers-reduced-motion`.
10. Não existe conjunto de componentes reutilizáveis para botões, inputs, alertas e cards.

Essas inconsistências não impedem o funcionamento atual, mas devem orientar a próxima refatoração visual.

## 15. Evolução recomendada

### Curto prazo

- usar os tokens canônicos deste documento em novas telas;
- padronizar Nunito como fonte única;
- consolidar canvas, input e card backgrounds;
- evitar novos valores arbitrários quando já existe equivalente;
- garantir foco e labels acessíveis.

### Médio prazo

- criar um CSS global com custom properties semânticas;
- extrair partials/componentes para botão, campo, alerta, badge, card e cabeçalho;
- mover animações e regras da sidebar para stylesheet compartilhado;
- adicionar estilos de reduced motion;
- revisar contraste e tamanhos abaixo de `10px`.

### Produção madura

- adotar Tailwind com build local;
- definir content paths e minificação;
- criar configuração ou tema Tailwind com os tokens do design system;
- eliminar dependência do compilador Tailwind no navegador;
- criar uma página interna de catálogo dos componentes;
- adicionar testes visuais e estados de acessibilidade.

## 16. Manutenção

Alterações relevantes na infraestrutura visual, nos tokens ou nos componentes devem atualizar este documento. Exemplos de implementação devem permanecer genéricos e reutilizáveis, sem referências a produtos, organizações ou regras de negócio específicas.
