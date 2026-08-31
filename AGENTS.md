# Crypto Arbitrage — instruções para agentes de IA

Antes de alterar código, leia todos os arquivos de `.ai/`. Eles são a fonte de verdade sobre o contexto, a arquitetura e as convenções deste projeto.

Regras essenciais:

- Preserve a arquitetura definida em `.ai/architecture.md`.
- Implemente somente o necessário para a tarefa atual; evite abstrações especulativas.
- Use PHP 8.2+, `strict_types`, classes pequenas, `final` quando não houver extensão prevista e namespaces PSR-4 coerentes com as pastas.
- Mantenha regras de negócio independentes de Hyperf, Swoole, banco de dados e APIs externas.
- Integrações com exchanges pertencem a `App\Exchange\<Exchange>` e devem traduzir dados externos antes de entregá-los ao domínio.
- Configurações operacionais pertencem a `config/autoload`; credenciais e valores dependentes do ambiente pertencem ao `.env`.
- Antes de concluir, execute os testes e a análise estática disponíveis, ou informe claramente por que não foi possível.

