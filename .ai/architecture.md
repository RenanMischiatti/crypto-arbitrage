# Arquitetura

Adote uma arquitetura pragmática orientada a domínio, crescendo apenas quando houver comportamento real.

## Limites

- `app/Domain`: regras de negócio e tipos independentes de framework ou fornecedor.
- `app/Services`: serviços que coordenam casos de uso e regras, como a análise de cotações.
- `app/Infrastructure`: detalhes técnicos compartilhados, como o cache de cotações no Redis.
- `app/Exchange`: adaptadores de APIs externas, separados por exchange.
- `app/Process`: pontos de entrada long-running do Hyperf. Coletores das exchanges ficam em `app/Process/MarketData`; comparadores por moeda ficam em `app/Process/CryptoAnalysis`. Esses processos devem apenas iniciar/delegar o trabalho.
- `config/autoload`: configuração operacional por assunto.

## Fluxo de market data

`Processo Hyperf -> adaptador WebSocket da exchange -> tradução da mensagem -> consumidor/caso de uso`

Detalhes do protocolo da Binance não devem vazar para regras de arbitragem. Quando existir uma segunda exchange, extraia um contrato comum baseado nas necessidades reais do caso de uso.

Cada exchange deve traduzir sua resposta para `App\Domain\MarketData\DTO\Quote`, garantindo os mesmos nomes de campos independentemente da origem.

Cada cotação normalizada passa por `Infrastructure\Redis\QuoteCache`, sobrescreve `quote:latest:<exchange>:<symbol>` e publica uma notificação por símbolo. Os processos delegam a comparação para `Services\Arbitrage\QuoteAnalyzerService`, que carrega somente cotações recentes em uma operação `MGET`.

## Princípios de decisão

- Prefira composição a herança.
- Uma classe deve ter um motivo claro para mudar.
- Crie interfaces nas fronteiras que realmente precisem de substituição, não para cada classe.
- DTOs externos são traduzidos antes de entrar no domínio.
- Processos de rede devem tolerar desconexão, reconectar com espera e encerrar junto ao Hyperf.
- Nunca misture decisão de arbitragem com transporte WebSocket ou logging.
