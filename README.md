# 📊 Sistema de Gestão de Ajustes e Prestações de Contas (PDDE)

Sistema integrado para gestão, análise financeira e monitorização de prestações de contas de recursos federais (PDDE). A plataforma automatiza o fluxo de trabalho desde a receção dos processos até o encaminhamento para a Secretaria da Fazenda (SEFAZ).

## 🌟 Diferenciais do Sistema

- **Dashboard Executivo (`dash.php`):** Monitorização em tempo real através de gráficos (Google Charts) que mostram o status das prestações de contas e o balanço financeiro (Saldos, Repasses, Rentabilidade e Despesas).
- **Gestão de Cotas (`cota.php` / `cotafin.php`):** Emissão de documentos técnicos de análise para encaminhamento entre setores.
- **Fluxo de Notificações Profissional:** Geradores de e-mails formatados para comunicar pendências específicas de documentos ou termos de colaboração (TC).
- **Interface Dinâmica:** Sidebar responsiva com níveis de acesso diferenciados (Adm, Ofc, Ofp) e feedback visual via Toasts.

## 🚀 Funcionalidades Principais

### 1. Análise e Pareceres
- **Análise Financeira:** Módulo central para validação de despesas e conciliação bancária.
- **Emissão de Cotas:** Geração de despachos automáticos com dados da instituição, programa e exercício.

### 2. Controle de Pendências
- **Monitorização:** Registo de inconsistências com histórico de responsáveis.
- **Exportação:** Geração de ficheiros Excel (`.xls`) para relatórios de gestão e acompanhamento offline.

### 3. Painel de Indicadores
- Visualização consolidada de:
  - Processos Entregues vs. Pendentes.
  - Análise de Saldos (Inicial, Repasse, Rendimentos e Final).

## 🛠️ Stack Técnica

- **Linguagem:** PHP 8.x
- **Arquitetura:** MVC-lite com Autoload (PSR-4) e Conexão via PDO (Singleton).
- **Base de Dados:** MySQL / MariaDB.
- **Frontend:** Bootstrap 5, Google Charts API, LineIcons.
- **Integração:** PHPExcel (ou header nativo) para relatórios.

## 📂 Estrutura de Pastas

```text
├── source/               # Classes principais (Database, Modelos)
├── sql/                  # Scripts de migração do banco de dados
├── dash.php              # Painel de indicadores e gráficos
├── aFinanceira.php       # Interface de análise técnica
├── cota.php              # Gerador de parecer de análise
├── emailPendencias.php   # Sistema de notificação por e-mail
├── menu.php              # Navegação dinâmica por perfil de usuário
└── .gitignore            # Proteção de credenciais e backups
