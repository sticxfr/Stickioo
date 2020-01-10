'use strict';

//  ---------------------------------------------------------------------------

const ccxt = require ('ccxt');
const { ExchangeError } = require ('ccxt/js/base/errors');

//  ---------------------------------------------------------------------------

module.exports = class gateio extends ccxt.gateio {
    describe () {
        return this.deepExtend (super.describe (), {
            'has': {
                'watchOrderBook': true,
                'watchTicker': true,
                'watchTrades': true,
                'watchOHLCV': true,
            },
            'urls': {
                'api': {
                    'ws': 'wss://ws.gate.io/v3',
                },
            },
        });
    }

    async watchOrderBook (symbol, limit = undefined, params = {}) {
        await this.loadMarkets ();
        const market = this.market (symbol);
        const marketId = market['id'].toUpperCase ();
        const requestId = this.nonce ();
        const url = this.urls['api']['ws'];
        if (!limit) {
            limit = 30;
        } else if (limit !== 1 && limit !== 5 && limit !== 10 && limit !== 20 && limit !== 30) {
            throw new ExchangeError (this.id + ' watchOrderBook limit argument must be undefined, 1, 5, 10, 20, or 30');
        }
        const interval = this.safeString (params, 'interval', '0.00000001');
        const precision = -1 * Math.log10 (interval);
        if (precision < 0 || precision > 8 || precision % 1 !== 0) {
            throw new ExchangeError (this.id + ' invalid interval');
        }
        const messageHash = 'depth.update' + ':' + marketId;
        const subscribeMessage = {
            'id': requestId,
            'method': 'depth.subscribe',
            'params': [marketId, limit, interval],
        };
        const future = this.watch (url, messageHash, subscribeMessage);
        return future;
    }

    signMessage (client, messageHash, message, params = {}) {
        // todo: implement gateio signMessage
        return message;
    }

    limitOrderBook (orderbook, symbol, limit = undefined, params = {}) {
        return orderbook.limit (limit);
    }

    handleOrderBook (client, message) {
        const params = message['params'];
        const clean = params[0];
        const book = params[1];
        const marketId = params[2];
        const methodType = message['method'];
        const messageHash = methodType + ':' + marketId;
        let orderBook = undefined;
        if (clean) {
            orderBook = this.orderBook ({});
            this.orderbooks[marketId] = orderBook;
        } else {
            orderBook = this.orderbooks[marketId];
        }
        const sides = ['bids', 'asks'];
        for (let j = 0; j < 2; j++) {
            const side = sides[j];
            if (side in book) {
                const bookSide = book[side];
                for (let i = 0; i < bookSide.length; i++) {
                    const order = bookSide[i];
                    orderBook[side].store (parseFloat (order[0]), parseFloat (order[1]));
                }
            }
        }
        client.resolve (orderBook, messageHash);
    }

    async watchTicker (symbol, params = {}) {
        await this.loadMarkets ();
        const market = this.market (symbol);
        const marketId = market['id'].toUpperCase ();
        const requestId = this.nonce ();
        const url = this.urls['api']['ws'];
        const subscribeMessage = {
            'id': requestId,
            'method': 'ticker.subscribe',
            'params': [marketId],
        };
        const messageHash = 'ticker.update' + ':' + marketId;
        return await this.watch (url, messageHash, subscribeMessage);
    }

    handleTicker (client, message) {
        const result = message['params'];
        const marketId = result[0];
        const normalMarketId = marketId.toLowerCase ();
        let market = undefined;
        if (normalMarketId in this.markets_by_id) {
            market = this.markets_by_id[normalMarketId];
        }
        const ticker = result[1];
        const parsed = this.parseTicker (ticker, market);
        const methodType = message['method'];
        const messageHash = methodType + ':' + marketId;
        client.resolve (parsed, messageHash);
    }

    async watchTrades (symbol, params = {}) {
        await this.loadMarkets ();
        const market = this.market (symbol);
        const marketId = market['id'].toUpperCase ();
        const requestId = this.nonce ();
        const url = this.urls['api']['ws'];
        const subscribeMessage = {
            'id': requestId,
            'method': 'trades.subscribe',
            'params': [marketId],
        };
        const messageHash = 'trades.update' + ':' + marketId;
        return await this.watch (url, messageHash, subscribeMessage);
    }

    handleTrades (client, messsage) {
        const result = messsage['params'];
        const marketId = result[0];
        const normalMarketId = marketId.toLowerCase ();
        let market = undefined;
        if (normalMarketId in this.markets_by_id) {
            market = this.markets_by_id[normalMarketId];
        }
        const trades = result[1];
        for (let i = 0; i < trades.length; i++) {
            const trade = trades[i];
            const parsed = this.parseTrade (trade, market);
            this.trades[marketId].push (parsed);
        }
        const methodType = messsage['method'];
        const messageHash = methodType + ':' + marketId;
        client.resolve (this.trades[marketId], messageHash);
    }

    async watchOHLCV (symbol, timeframe = '1m', since = undefined, limit = undefined, params = {}) {
        await this.loadMarkets ();
        const market = this.market (symbol);
        const marketId = market['id'].toUpperCase ();
        const requestId = this.nonce ();
        const url = this.urls['api']['ws'];
        const interval = parseInt (this.timeframes['10mins']);
        const subscribeMessage = {
            'id': requestId,
            'method': 'kline.subscribe',
            'params': [marketId, 1800],
        };
        const messageHash = 'kline.update' + ':' + marketId;
        return await this.watch (url, messageHash, subscribeMessage);
    }

    handleOHLCV (client, message) {
        const ohlcv = message['params'][0];
        const marketId = ohlcv[7];
        const normalMarketId = marketId.toLowerCase ();
        let market = undefined;
        if (normalMarketId in this.markets_by_id) {
            market = this.markets_by_id[normalMarketId];
        }
        const parsed = this.parseOHLCV (ohlcv, market);
        const methodType = message['method'];
        const messageHash = methodType + ':' + marketId;
        client.resolve (parsed, messageHash);
    }

    handleMessage (client, message) {
        const methods = {
            'depth.update': this.handleOrderBook,
            'ticker.update': this.handleTicker,
            'trades.update': this.handleTrades,
            'kline.update': this.handleOHLCV,
        };
        const methodType = this.safeString (message, 'method');
        const method = this.safeValue (methods, methodType);
        if (method) {
            method.apply (this, [client, message]);
        }
    }
};
