# Contexto do projeto

## Objetivo

Construir um serviço de arbitragem de criptomoedas em Hyperf. O sistema observará preços em diferentes exchanges, identificará oportunidades considerando custos e, futuramente, coordenará ordens com segurança.

## Estado atual

- Framework: Hyperf 3.2
- Linguagem: PHP 8.2+
- Runtime concorrente: Swoole
- Primeira exchange: Binance Spot
- Mercado inicial: pares BTC/USDT, ETH/USDT e SOL/USDT
- Feed inicial: melhor preço e quantidade de compra/venda (`bookTicker`)

## Vocabulário

- **Exchange**: plataforma externa de negociação, como Binance.
- **Símbolo/par**: ativo base e ativo de cotação, como `BTCUSDT`.
- **Bid**: melhor oferta de compra.
- **Ask**: melhor oferta de venda.
- **Market data**: dados públicos de preço e livro de ofertas.
- **Oportunidade**: diferença negociável entre exchanges após taxas, slippage e demais custos.

## Restrições atuais

- Não enviar ordens reais sem solicitação explícita e salvaguardas próprias.
- Valores monetários não devem usar `float`; preserve decimais como strings ou use uma biblioteca decimal.
- Símbolos monitorados devem ser configuráveis e não espalhados pelo código.

