# Crypto Arbitrage

Projeto pessoal e educacional para acompanhar preços de criptomoedas em diferentes corretoras e identificar possíveis oportunidades de arbitragem em tempo real.

> [!IMPORTANT]
> Este repositório tem finalidade exclusivamente informativa e de estudo. Ele não constitui recomendação financeira, não garante rentabilidade e não executa ordens de compra ou venda. Taxas, slippage, liquidez, latência, limites operacionais e riscos de transferência podem tornar uma diferença de preço inviável na prática.

## Introdução

Arbitragem é a estratégia de comprar um ativo onde ele está mais barato e vendê-lo onde está mais caro. No mercado de criptomoedas, uma mesma moeda pode apresentar pequenas diferenças de preço entre corretoras.

Este projeto observa o melhor preço disponível para compra (`ask`) e venda (`bid`) em diferentes exchanges. Quando o maior `bid` é superior ao menor `ask`, o sistema calcula o spread bruto e registra a oportunidade caso ela ultrapasse o limite configurado.

Atualmente são monitoradas:

- exchanges: Binance Spot, Bybit Spot e OKX Spot;
- moedas: Bitcoin, Ethereum, Solana, Dogecoin e Sui;
- pares: `BTCUSDT`, `ETHUSDT`, `SOLUSDT`, `DOGEUSDT` e `SUIUSDT`.

## O que o projeto faz

1. Abre conexões WebSocket públicas com as exchanges.
2. Recebe o melhor `bid`, o melhor `ask` e suas respectivas quantidades.
3. Converte as mensagens de cada corretora para um formato único de cotação.
4. Armazena temporariamente a cotação mais recente de cada par no Redis.
5. Compara apenas cotações recentes entre as exchanges.
6. Calcula o spread bruto com a fórmula:

```text
spread (%) = ((preço de venda - preço de compra) / preço de compra) × 100
```

7. Registra no console candidatos cujo spread seja maior que o limite configurado — `0,30%` por padrão.

O fluxo principal é:

```text
WebSockets das exchanges
          ↓
Normalização das cotações
          ↓
       Redis
          ↓
Comparação por criptomoeda
          ↓
Oportunidade registrada no console
```

O sistema usa somente market data público. Não há autenticação nas exchanges, uso de chaves de API, movimentação de saldo ou envio automático de ordens.

## Stack

- **PHP 8.2+** — linguagem principal;
- **Hyperf 3.2** — framework da aplicação e gerenciamento dos processos;
- **Swoole** — runtime assíncrono e concorrente usado pelas conexões e processos de longa duração;
- **WebSocket** — consumo de cotações em tempo real das exchanges;
- **Redis 7.4** — cache das últimas cotações e comunicação Pub/Sub entre coletores e analisadores;
- **BCMath** — cálculos decimais de preço e spread sem o uso de `float`;
- **Docker e Docker Compose** — ambiente local reproduzível;
- **PHPUnit/Hyperf Testing** — testes automatizados;
- **PHPStan** — análise estática;
- **PHP CS Fixer** — padronização do código.

## Arquitetura

O código separa as regras de negócio dos detalhes de infraestrutura:

- `app/Domain`: tipos e dados normalizados do domínio;
- `app/Exchange`: integrações WebSocket e tradução das respostas de cada exchange;
- `app/Infrastructure`: detalhes técnicos compartilhados, como o cache Redis;
- `app/Services`: análise e coordenação das regras de arbitragem;
- `app/Process/MarketData`: processos que coletam as cotações;
- `app/Process/CryptoAnalysis`: processos que analisam cada moeda;
- `config/autoload`: exchanges, pares e parâmetros operacionais;
- `test`: testes automatizados.

## Setup com Docker (recomendado)

### Pré-requisitos

- Git;
- Docker com o Docker Compose.

### Instalação

Clone o repositório e entre na pasta do projeto:

```bash
git clone <URL_DO_REPOSITORIO>
cd crypto-arbitrage
```

Crie o arquivo local de ambiente:

```bash
cp .env.example .env
```

No PowerShell, use:

```powershell
Copy-Item .env.example .env
```

Construa as imagens e inicie a aplicação e o Redis:

```bash
docker compose up --build
```

Os processos de coleta e análise começam junto com o Hyperf. As conexões, atualizações e oportunidades encontradas aparecem nos logs do terminal. A aplicação HTTP fica disponível em `http://localhost:9501` e o Redis em `localhost:6379`.

Para encerrar o ambiente, pressione `Ctrl+C` e execute:

```bash
docker compose down
```

## Setup sem Docker

Para executar diretamente no sistema, é necessário ter:

- PHP 8.2 ou superior;
- Composer;
- Swoole 5.0 ou superior, com `swoole.use_shortname=Off`;
- extensões PHP exigidas pelo Hyperf, incluindo Redis e BCMath;
- uma instância Redis acessível.

Depois, instale as dependências, prepare o ambiente e inicie o serviço:

```bash
composer install
cp .env.example .env
php bin/hyperf.php start
```

Se o Redis não estiver em `localhost:6379`, ajuste `REDIS_HOST`, `REDIS_PORT`, `REDIS_AUTH` e `REDIS_DB` no arquivo `.env`.

## Configuração

Os símbolos e endereços WebSocket ficam em `config/autoload/exchanges.php`. Os principais parâmetros da análise ficam em `config/autoload/arbitrage.php`:

- `quote_ttl_seconds`: tempo de vida de uma cotação no Redis;
- `max_quote_age_ms`: idade máxima aceita durante a comparação;
- `min_spread_percent`: spread bruto mínimo para registrar um candidato;
- `sleep_after_opportunity_seconds`: intervalo antes de repetir alertas para o mesmo par.

Hosts e portas das exchanges também podem ser sobrescritos por variáveis de ambiente. Valores sensíveis ou específicos de cada ambiente devem permanecer no `.env` e não devem ser versionados.

## Qualidade do código

Execute os testes automatizados:

```bash
composer test
```

Execute a análise estática:

```bash
composer analyse
```

Para aplicar o padrão de formatação configurado:

```bash
composer cs-fix
```

Se estiver usando apenas Docker, os mesmos comandos podem ser executados no container:

```bash
docker compose exec hyperf-skeleton composer test
docker compose exec hyperf-skeleton composer analyse
```

## Limitações atuais

- O spread calculado é bruto: taxas, slippage e outros custos ainda não são descontados.
- A quantidade realmente negociável entre os livros não é usada para dimensionar uma operação.
- O sistema apenas identifica e registra candidatos; nenhuma ordem é executada.
- Não há gestão de saldo, risco, transferência entre exchanges ou persistência histórica.
- Diferenças observadas podem desaparecer antes de uma operação real ser concluída.

## Licença

Distribuído sob a licença MIT. Consulte o arquivo `LICENSE` para mais detalhes.
