# Convenções de código

- Todo arquivo PHP começa com `declare(strict_types=1);`.
- Siga PSR-12 e PSR-4 (`App\` aponta para `app/`).
- Use nomes em inglês no código; documentação e mensagens podem usar português.
- Prefira dependências explícitas por construtor.
- Use `readonly` para estado imutável e `final` para implementações sem ponto de extensão planejado.
- Evite métodos longos, comentários que repetem o código, helpers globais e arrays sem formato nas regras de negócio.
- Valide dados recebidos em fronteiras externas.
- Capture exceções somente onde seja possível acrescentar contexto, recuperar ou encerrar corretamente.
- Não registre segredos. Logs de mercado devem ser estruturados.
- Teste transformações, validações e regras de negócio sem depender da rede.
- Não adicione dependências quando os recursos do PHP, Hyperf ou Swoole atenderem de forma clara.

