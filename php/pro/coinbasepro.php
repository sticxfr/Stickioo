<?php

namespace ccxt\pro;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import
use ccxt\BadSymbol;
use React\Async;

class coinbasepro extends \ccxt\async\coinbasepro {

    use ClientTrait;

    public function describe() {
        return $this->deep_extend(parent::describe(), array(
            'has' => array(
                'ws' => true,
                'watchOHLCV' => false, // missing on the exchange side
                'watchOrderBook' => true,
                'watchTicker' => true,
                'watchTickers' => false, // for now
                'watchTrades' => true,
                'watchBalance' => false,
                'watchStatus' => false, // for now
                'watchOrders' => true,
                'watchMyTrades' => true,
            ),
            'urls' => array(
                'api' => array(
                    'ws' => 'wss://ws-feed.pro.coinbase.com',
                ),
            ),
            'options' => array(
                'tradesLimit' => 1000,
                'ordersLimit' => 1000,
                'myTradesLimit' => 1000,
            ),
        ));
    }

    public function authenticate() {
        $this->check_required_credentials();
        $path = '/users/self/verify';
        $nonce = $this->nonce();
        $payload = (string) $nonce . 'GET' . $path;
        $signature = $this->hmac($this->encode($payload), base64_decode($this->secret), 'sha256', 'base64');
        return array(
            'timestamp' => $nonce,
            'key' => $this->apiKey,
            'signature' => $signature,
            'passphrase' => $this->password,
        );
    }

    public function subscribe($name, $symbol, $messageHashStart, $params = array ()) {
        return Async\async(function () use ($name, $symbol, $messageHashStart, $params) {
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $messageHash = $messageHashStart . ':' . $market['id'];
            $url = $this->urls['api']['ws'];
            if (is_array($params) && array_key_exists('signature', $params)) {
                // need to distinguish between public trades and user trades
                $url = $url . '?';
            }
            $subscribe = array(
                'type' => 'subscribe',
                'product_ids' => [
                    $market['id'],
                ],
                'channels' => array(
                    $name,
                ),
            );
            $request = array_merge($subscribe, $params);
            return Async\await($this->watch($url, $messageHash, $request, $messageHash));
        }) ();
    }

    public function watch_ticker($symbol, $params = array ()) {
        return Async\async(function () use ($symbol, $params) {
            /**
             * watches a price ticker, a statistical calculation with the information calculated over the past 24 hours for a specific market
             * @param {string} $symbol unified $symbol of the market to fetch the ticker for
             * @param {array} $params extra parameters specific to the coinbasepro api endpoint
             * @return {array} a {@link https://docs.ccxt.com/en/latest/manual.html#ticker-structure ticker structure}
             */
            $name = 'ticker';
            return Async\await($this->subscribe($name, $symbol, $name, $params));
        }) ();
    }

    public function watch_trades($symbol, $since = null, $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $since, $limit, $params) {
            /**
             * get the list of most recent $trades for a particular $symbol
             * @param {string} $symbol unified $symbol of the market to fetch $trades for
             * @param {int|null} $since timestamp in ms of the earliest trade to fetch
             * @param {int|null} $limit the maximum amount of $trades to fetch
             * @param {array} $params extra parameters specific to the coinbasepro api endpoint
             * @return {[array]} a list of ~@link https://docs.ccxt.com/en/latest/manual.html?#public-$trades trade structures~
             */
            Async\await($this->load_markets());
            $symbol = $this->symbol($symbol);
            $name = 'matches';
            $trades = Async\await($this->subscribe($name, $symbol, $name, $params));
            if ($this->newUpdates) {
                $limit = $trades->getLimit ($symbol, $limit);
            }
            return $this->filter_by_since_limit($trades, $since, $limit, 'timestamp', true);
        }) ();
    }

    public function watch_my_trades($symbol = null, $since = null, $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $since, $limit, $params) {
            /**
             * watches information on multiple $trades made by the user
             * @param {string} $symbol unified market $symbol of the market orders were made in
             * @param {int|null} $since the earliest time in ms to fetch orders for
             * @param {int|null} $limit the maximum number of  orde structures to retrieve
             * @param {array} $params extra parameters specific to the coinbasepro api endpoint
             * @return {[array]} a list of [order structures]{@link https://docs.ccxt.com/en/latest/manual.html#order-structure
             */
            if ($symbol === null) {
                throw new BadSymbol($this->id . ' watchMyTrades requires a symbol');
            }
            Async\await($this->load_markets());
            $symbol = $this->symbol($symbol);
            $name = 'user';
            $messageHash = 'myTrades';
            $authentication = $this->authenticate();
            $trades = Async\await($this->subscribe($name, $symbol, $messageHash, array_merge($params, $authentication)));
            if ($this->newUpdates) {
                $limit = $trades->getLimit ($symbol, $limit);
            }
            return $this->filter_by_since_limit($trades, $since, $limit, 'timestamp', true);
        }) ();
    }

    public function watch_orders($symbol = null, $since = null, $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $since, $limit, $params) {
            /**
             * watches information on multiple $orders made by the user
             * @param {string|null} $symbol unified market $symbol of the market $orders were made in
             * @param {int|null} $since the earliest time in ms to fetch $orders for
             * @param {int|null} $limit the maximum number of  orde structures to retrieve
             * @param {array} $params extra parameters specific to the coinbasepro api endpoint
             * @return {[array]} a list of {@link https://docs.ccxt.com/en/latest/manual.html#order-structure order structures}
             */
            if ($symbol === null) {
                throw new BadSymbol($this->id . ' watchMyTrades requires a symbol');
            }
            Async\await($this->load_markets());
            $symbol = $this->symbol($symbol);
            $name = 'user';
            $messageHash = 'orders';
            $authentication = $this->authenticate();
            $orders = Async\await($this->subscribe($name, $symbol, $messageHash, array_merge($params, $authentication)));
            if ($this->newUpdates) {
                $limit = $orders->getLimit ($symbol, $limit);
            }
            return $this->filter_by_since_limit($orders, $since, $limit, 'timestamp', true);
        }) ();
    }

    public function watch_order_book($symbol, $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $limit, $params) {
            /**
             * watches information on open orders with bid (buy) and ask (sell) prices, volumes and other data
             * @param {string} $symbol unified $symbol of the $market to fetch the order book for
             * @param {int|null} $limit the maximum amount of order book entries to return
             * @param {array} $params extra parameters specific to the coinbasepro api endpoint
             * @return {array} A dictionary of {@link https://docs.ccxt.com/en/latest/manual.html#order-book-structure order book structures} indexed by $market symbols
             */
            $name = 'level2';
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $symbol = $market['symbol'];
            $messageHash = $name . ':' . $market['id'];
            $url = $this->urls['api']['ws'];
            $subscribe = array(
                'type' => 'subscribe',
                'product_ids' => [
                    $market['id'],
                ],
                'channels' => array(
                    $name,
                ),
            );
            $request = array_merge($subscribe, $params);
            $subscription = array(
                'messageHash' => $messageHash,
                'symbol' => $symbol,
                'marketId' => $market['id'],
                'limit' => $limit,
            );
            $orderbook = Async\await($this->watch($url, $messageHash, $request, $messageHash, $subscription));
            return $orderbook->limit ();
        }) ();
    }

    public function handle_trade($client, $message) {
        //
        //     {
        //         $type => 'match',
        //         trade_id => 82047307,
        //         maker_order_id => '0f358725-2134-435e-be11-753912a326e0',
        //         taker_order_id => '252b7002-87a3-425c-ac73-f5b9e23f3caf',
        //         side => 'sell',
        //         size => '0.00513192',
        //         price => '9314.78',
        //         product_id => 'BTC-USD',
        //         sequence => 12038915443,
        //         time => '2020-01-31T20:03:41.158814Z'
        //     }
        //
        $marketId = $this->safe_string($message, 'product_id');
        if ($marketId !== null) {
            $trade = $this->parse_ws_trade($message);
            $symbol = $trade['symbol'];
            // the exchange sends $type = 'match'
            // but requires 'matches' upon subscribing
            // therefore we resolve 'matches' here instead of 'match'
            $type = 'matches';
            $messageHash = $type . ':' . $marketId;
            $tradesArray = $this->safe_value($this->trades, $symbol);
            if ($tradesArray === null) {
                $tradesLimit = $this->safe_integer($this->options, 'tradesLimit', 1000);
                $tradesArray = new ArrayCache ($tradesLimit);
                $this->trades[$symbol] = $tradesArray;
            }
            $tradesArray->append ($trade);
            $client->resolve ($tradesArray, $messageHash);
        }
        return $message;
    }

    public function handle_my_trade($client, $message) {
        $marketId = $this->safe_string($message, 'product_id');
        if ($marketId !== null) {
            $trade = $this->parse_ws_trade($message);
            $type = 'myTrades';
            $messageHash = $type . ':' . $marketId;
            $tradesArray = $this->myTrades;
            if ($tradesArray === null) {
                $limit = $this->safe_integer($this->options, 'myTradesLimit', 1000);
                $tradesArray = new ArrayCacheBySymbolById ($limit);
                $this->myTrades = $tradesArray;
            }
            $tradesArray->append ($trade);
            $client->resolve ($tradesArray, $messageHash);
        }
        return $message;
    }

    public function parse_ws_trade($trade) {
        //
        // private trades
        // {
        //     "type" => "match",
        //     "trade_id" => 10,
        //     "sequence" => 50,
        //     "maker_order_id" => "ac928c66-ca53-498f-9c13-a110027a60e8",
        //     "taker_order_id" => "132fb6ae-456b-4654-b4e0-d681ac05cea1",
        //     "time" => "2014-11-07T08:19:27.028459Z",
        //     "product_id" => "BTC-USD",
        //     "size" => "5.23512",
        //     "price" => "400.23",
        //     "side" => "sell",
        //     "taker_user_id => "5844eceecf7e803e259d0365",
        //     "user_id" => "5844eceecf7e803e259d0365",
        //     "taker_profile_id" => "765d1549-9660-4be2-97d4-fa2d65fa3352",
        //     "profile_id" => "765d1549-9660-4be2-97d4-fa2d65fa3352",
        //     "taker_fee_rate" => "0.005"
        // }
        //
        // {
        //     "type" => "match",
        //     "trade_id" => 10,
        //     "sequence" => 50,
        //     "maker_order_id" => "ac928c66-ca53-498f-9c13-a110027a60e8",
        //     "taker_order_id" => "132fb6ae-456b-4654-b4e0-d681ac05cea1",
        //     "time" => "2014-11-07T08:19:27.028459Z",
        //     "product_id" => "BTC-USD",
        //     "size" => "5.23512",
        //     "price" => "400.23",
        //     "side" => "sell",
        //     "maker_user_id => "5844eceecf7e803e259d0365",
        //     "maker_id" => "5844eceecf7e803e259d0365",
        //     "maker_profile_id" => "765d1549-9660-4be2-97d4-fa2d65fa3352",
        //     "profile_id" => "765d1549-9660-4be2-97d4-fa2d65fa3352",
        //     "maker_fee_rate" => "0.001"
        // }
        //
        // public trades
        // {
        //     "type" => "received",
        //     "time" => "2014-11-07T08:19:27.028459Z",
        //     "product_id" => "BTC-USD",
        //     "sequence" => 10,
        //     "order_id" => "d50ec984-77a8-460a-b958-66f114b0de9b",
        //     "size" => "1.34",
        //     "price" => "502.1",
        //     "side" => "buy",
        //     "order_type" => "limit"
        // }
        $parsed = parent::parse_trade($trade);
        $feeRate = null;
        if (is_array($trade) && array_key_exists('maker_fee_rate', $trade)) {
            $parsed['takerOrMaker'] = 'maker';
            $feeRate = $this->safe_number($trade, 'maker_fee_rate');
        } else {
            $parsed['takerOrMaker'] = 'taker';
            $feeRate = $this->safe_number($trade, 'taker_fee_rate');
        }
        $market = $this->market($parsed['symbol']);
        $feeCurrency = $market['quote'];
        $feeCost = null;
        if (($parsed['cost'] !== null) && ($feeRate !== null)) {
            $feeCost = $parsed['cost'] * $feeRate;
        }
        $parsed['fee'] = array(
            'rate' => $feeRate,
            'cost' => $feeCost,
            'currency' => $feeCurrency,
        );
        return $parsed;
    }

    public function parse_ws_order_status($status) {
        $statuses = array(
            'filled' => 'closed',
            'canceled' => 'canceled',
        );
        return $this->safe_string($statuses, $status, 'open');
    }

    public function handle_order($client, $message) {
        //
        // Order is created
        //
        //     {
        //         $type => 'received',
        //         side => 'sell',
        //         product_id => 'BTC-USDC',
        //         time => '2021-03-05T16:42:21.878177Z',
        //         $sequence => 5641953814,
        //         profile_id => '774ee0ce-fdda-405f-aa8d-47189a14ba0a',
        //         user_id => '54fc141576dcf32596000133',
        //         order_id => '11838707-bf9c-4d65-8cec-b57c9a7cab42',
        //         order_type => 'limit',
        //         size => '0.0001',
        //         price => '50000',
        //         client_oid => 'a317abb9-2b30-4370-ebfe-0deecb300180'
        //     }
        //
        //     {
        //         "type" => "received",
        //         "time" => "2014-11-09T08:19:27.028459Z",
        //         "product_id" => "BTC-USD",
        //         "sequence" => 12,
        //         "order_id" => "dddec984-77a8-460a-b958-66f114b0de9b",
        //         "funds" => "3000.234",
        //         "side" => "buy",
        //         "order_type" => "market"
        //     }
        //
        // Order is on the $order book
        //
        //     {
        //         $type => 'open',
        //         side => 'sell',
        //         product_id => 'BTC-USDC',
        //         time => '2021-03-05T16:42:21.878177Z',
        //         $sequence => 5641953815,
        //         profile_id => '774ee0ce-fdda-405f-aa8d-47189a14ba0a',
        //         user_id => '54fc141576dcf32596000133',
        //         price => '50000',
        //         order_id => '11838707-bf9c-4d65-8cec-b57c9a7cab42',
        //         remaining_size => '0.0001'
        //     }
        //
        // Order is partially or completely filled
        //
        //     {
        //         $type => 'match',
        //         side => 'sell',
        //         product_id => 'BTC-USDC',
        //         time => '2021-03-05T16:37:13.396107Z',
        //         $sequence => 5641897876,
        //         profile_id => '774ee0ce-fdda-405f-aa8d-47189a14ba0a',
        //         user_id => '54fc141576dcf32596000133',
        //         trade_id => 5455505,
        //         maker_order_id => 'e5f5754d-70a3-4346-95a6-209bcb503629',
        //         taker_order_id => '88bf7086-7b15-40ff-8b19-ab4e08516d69',
        //         size => '0.00021019',
        //         price => '47338.46',
        //         taker_profile_id => '774ee0ce-fdda-405f-aa8d-47189a14ba0a',
        //         taker_user_id => '54fc141576dcf32596000133',
        //         taker_fee_rate => '0.005'
        //     }
        //
        // Order is canceled / closed
        //
        //     {
        //         $type => 'done',
        //         side => 'buy',
        //         product_id => 'BTC-USDC',
        //         time => '2021-03-05T16:37:13.396107Z',
        //         $sequence => 5641897877,
        //         profile_id => '774ee0ce-fdda-405f-aa8d-47189a14ba0a',
        //         user_id => '54fc141576dcf32596000133',
        //         order_id => '88bf7086-7b15-40ff-8b19-ab4e08516d69',
        //         reason => 'filled'
        //     }
        //
        $orders = $this->orders;
        if ($orders === null) {
            $limit = $this->safe_integer($this->options, 'ordersLimit', 1000);
            $orders = new ArrayCacheBySymbolById ($limit);
            $this->orders = $orders;
        }
        $type = $this->safe_string($message, 'type');
        $marketId = $this->safe_string($message, 'product_id');
        if ($marketId !== null) {
            $messageHash = 'orders:' . $marketId;
            $symbol = $this->safe_symbol($marketId);
            $orderId = $this->safe_string($message, 'order_id');
            $makerOrderId = $this->safe_string($message, 'maker_order_id');
            $takerOrderId = $this->safe_string($message, 'taker_order_id');
            $orders = $this->orders;
            $previousOrders = $this->safe_value($orders->hashmap, $symbol, array());
            $previousOrder = $this->safe_value($previousOrders, $orderId);
            if ($previousOrder === null) {
                $previousOrder = $this->safe_value_2($previousOrders, $makerOrderId, $takerOrderId);
            }
            if ($previousOrder === null) {
                $parsed = $this->parse_ws_order($message);
                $orders->append ($parsed);
                $client->resolve ($orders, $messageHash);
            } else {
                $sequence = $this->safe_integer($message, 'sequence');
                $previousInfo = $this->safe_value($previousOrder, 'info', array());
                $previousSequence = $this->safe_integer($previousInfo, 'sequence');
                if (($previousSequence === null) || ($sequence > $previousSequence)) {
                    if ($type === 'match') {
                        $trade = $this->parse_ws_trade($message);
                        if ($previousOrder['trades'] === null) {
                            $previousOrder['trades'] = array();
                        }
                        $previousOrder['trades'][] = $trade;
                        $previousOrder['lastTradeTimestamp'] = $trade['timestamp'];
                        $totalCost = 0;
                        $totalAmount = 0;
                        $trades = $previousOrder['trades'];
                        for ($i = 0; $i < count($trades); $i++) {
                            $trade = $trades[$i];
                            $totalCost = $this->sum($totalCost, $trade['cost']);
                            $totalAmount = $this->sum($totalAmount, $trade['amount']);
                        }
                        if ($totalAmount > 0) {
                            $previousOrder['average'] = $totalCost / $totalAmount;
                        }
                        $previousOrder['cost'] = $totalCost;
                        if ($previousOrder['filled'] !== null) {
                            $previousOrder['filled'] .= $trade['amount'];
                            if ($previousOrder['amount'] !== null) {
                                $previousOrder['remaining'] = $previousOrder['amount'] - $previousOrder['filled'];
                            }
                        }
                        if ($previousOrder['fee'] === null) {
                            $previousOrder['fee'] = array(
                                'cost' => 0,
                                'currency' => $trade['fee']['currency'],
                            );
                        }
                        if (($previousOrder['fee']['cost'] !== null) && ($trade['fee']['cost'] !== null)) {
                            $previousOrder['fee']['cost'] = $this->sum($previousOrder['fee']['cost'], $trade['fee']['cost']);
                        }
                        // update the newUpdates count
                        $orders->append ($previousOrder);
                        $client->resolve ($orders, $messageHash);
                    } elseif (($type === 'received') || ($type === 'done')) {
                        $info = array_merge($previousOrder['info'], $message);
                        $order = $this->parse_ws_order($info);
                        $keys = is_array($order) ? array_keys($order) : array();
                        // update the reference
                        for ($i = 0; $i < count($keys); $i++) {
                            $key = $keys[$i];
                            if ($order[$key] !== null) {
                                $previousOrder[$key] = $order[$key];
                            }
                        }
                        // update the newUpdates count
                        $orders->append ($previousOrder);
                        $client->resolve ($orders, $messageHash);
                    }
                }
            }
        }
    }

    public function parse_ws_order($order) {
        $id = $this->safe_string($order, 'order_id');
        $clientOrderId = $this->safe_string($order, 'client_oid');
        $marketId = $this->safe_string($order, 'product_id');
        $symbol = $this->safe_symbol($marketId);
        $side = $this->safe_string($order, 'side');
        $price = $this->safe_number($order, 'price');
        $amount = $this->safe_number_2($order, 'size', 'funds');
        $time = $this->safe_string($order, 'time');
        $timestamp = $this->parse8601($time);
        $reason = $this->safe_string($order, 'reason');
        $status = $this->parse_ws_order_status($reason);
        $orderType = $this->safe_string($order, 'order_type');
        $remaining = $this->safe_number($order, 'remaining_size');
        $type = $this->safe_string($order, 'type');
        $filled = null;
        if (($amount !== null) && ($remaining !== null)) {
            $filled = $amount - $remaining;
        } elseif ($type === 'received') {
            $filled = 0;
            if ($amount !== null) {
                $remaining = $amount - $filled;
            }
        }
        $cost = null;
        if (($price !== null) && ($amount !== null)) {
            $cost = $price * $amount;
        }
        return array(
            'info' => $order,
            'symbol' => $symbol,
            'id' => $id,
            'clientOrderId' => $clientOrderId,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601($timestamp),
            'lastTradeTimestamp' => null,
            'type' => $orderType,
            'timeInForce' => null,
            'postOnly' => null,
            'side' => $side,
            'price' => $price,
            'stopPrice' => null,
            'triggerPrice' => null,
            'amount' => $amount,
            'cost' => $cost,
            'average' => null,
            'filled' => $filled,
            'remaining' => $remaining,
            'status' => $status,
            'fee' => null,
            'trades' => null,
        );
    }

    public function handle_ticker($client, $message) {
        //
        //     {
        //         $type => 'ticker',
        //         sequence => 12042642428,
        //         product_id => 'BTC-USD',
        //         price => '9380.55',
        //         open_24h => '9450.81000000',
        //         volume_24h => '9611.79166047',
        //         low_24h => '9195.49000000',
        //         high_24h => '9475.19000000',
        //         volume_30d => '327812.00311873',
        //         best_bid => '9380.54',
        //         best_ask => '9380.55',
        //         side => 'buy',
        //         time => '2020-02-01T01:40:16.253563Z',
        //         trade_id => 82062566,
        //         last_size => '0.41969131'
        //     }
        //
        $marketId = $this->safe_string($message, 'product_id');
        if ($marketId !== null) {
            $ticker = $this->parse_ticker($message);
            $symbol = $ticker['symbol'];
            $this->tickers[$symbol] = $ticker;
            $type = $this->safe_string($message, 'type');
            $messageHash = $type . ':' . $marketId;
            $client->resolve ($ticker, $messageHash);
        }
        return $message;
    }

    public function parse_ticker($ticker, $market = null) {
        //
        //     {
        //         $type => 'ticker',
        //         sequence => 7388547310,
        //         product_id => 'BTC-USDT',
        //         price => '22345.67',
        //         open_24h => '22308.13',
        //         volume_24h => '470.21123644',
        //         low_24h => '22150',
        //         high_24h => '22495.15',
        //         volume_30d => '25713.98401605',
        //         best_bid => '22345.67',
        //         best_bid_size => '0.10647825',
        //         best_ask => '22349.68',
        //         best_ask_size => '0.03131702',
        //         side => 'sell',
        //         time => '2023-03-04T03:37:20.799258Z',
        //         trade_id => 11586478,
        //         last_size => '0.00352175'
        //     }
        //
        $type = $this->safe_string($ticker, 'type');
        if ($type === null) {
            return parent::parse_ticker($ticker, $market);
        }
        $marketId = $this->safe_string($ticker, 'product_id');
        $symbol = $this->safe_symbol($marketId, $market, '-');
        $timestamp = $this->parse8601($this->safe_string($ticker, 'time'));
        $last = $this->safe_number($ticker, 'price');
        return array(
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601($timestamp),
            'high' => $this->safe_number($ticker, 'high_24h'),
            'low' => $this->safe_number($ticker, 'low_24h'),
            'bid' => $this->safe_number($ticker, 'best_bid'),
            'bidVolume' => $this->safe_number($ticker, 'best_bid_size'),
            'ask' => $this->safe_number($ticker, 'best_ask'),
            'askVolume' => $this->safe_number($ticker, 'best_ask_size'),
            'vwap' => null,
            'open' => $this->safe_number($ticker, 'open_24h'),
            'close' => $last,
            'last' => $last,
            'previousClose' => null,
            'change' => null,
            'percentage' => null,
            'average' => null,
            'baseVolume' => $this->safe_number($ticker, 'volume_24h'),
            'quoteVolume' => null,
            'info' => $ticker,
        );
    }

    public function handle_delta($bookside, $delta) {
        $price = $this->safe_number($delta, 0);
        $amount = $this->safe_number($delta, 1);
        $bookside->store ($price, $amount);
    }

    public function handle_deltas($bookside, $deltas) {
        for ($i = 0; $i < count($deltas); $i++) {
            $this->handle_delta($bookside, $deltas[$i]);
        }
    }

    public function handle_order_book($client, $message) {
        //
        // first $message (snapshot)
        //
        //     {
        //         "type" => "snapshot",
        //         "product_id" => "BTC-USD",
        //         "bids" => [
        //             ["10101.10", "0.45054140"]
        //         ],
        //         "asks" => [
        //             ["10102.55", "0.57753524"]
        //         ]
        //     }
        //
        // subsequent updates
        //
        //     {
        //         "type" => "l2update",
        //         "product_id" => "BTC-USD",
        //         "time" => "2019-08-14T20:42:27.265Z",
        //         "changes" => array(
        //             array( "buy", "10101.80000000", "0.162567" )
        //         )
        //     }
        //
        $type = $this->safe_string($message, 'type');
        $marketId = $this->safe_string($message, 'product_id');
        $market = $this->safe_market($marketId, null, '-');
        $symbol = $market['symbol'];
        $name = 'level2';
        $messageHash = $name . ':' . $marketId;
        $subscription = $this->safe_value($client->subscriptions, $messageHash, array());
        $limit = $this->safe_integer($subscription, 'limit');
        if ($type === 'snapshot') {
            $this->orderbooks[$symbol] = $this->order_book(array(), $limit);
            $orderbook = $this->orderbooks[$symbol];
            $this->handle_deltas($orderbook['asks'], $this->safe_value($message, 'asks', array()));
            $this->handle_deltas($orderbook['bids'], $this->safe_value($message, 'bids', array()));
            $orderbook['timestamp'] = null;
            $orderbook['datetime'] = null;
            $client->resolve ($orderbook, $messageHash);
        } elseif ($type === 'l2update') {
            $orderbook = $this->orderbooks[$symbol];
            $timestamp = $this->parse8601($this->safe_string($message, 'time'));
            $changes = $this->safe_value($message, 'changes', array());
            $sides = array(
                'sell' => 'asks',
                'buy' => 'bids',
            );
            for ($i = 0; $i < count($changes); $i++) {
                $change = $changes[$i];
                $key = $this->safe_string($change, 0);
                $side = $this->safe_string($sides, $key);
                $price = $this->safe_number($change, 1);
                $amount = $this->safe_number($change, 2);
                $bookside = $orderbook[$side];
                $bookside->store ($price, $amount);
            }
            $orderbook['timestamp'] = $timestamp;
            $orderbook['datetime'] = $this->iso8601($timestamp);
            $client->resolve ($orderbook, $messageHash);
        }
    }

    public function handle_subscription_status($client, $message) {
        //
        //     {
        //         type => 'subscriptions',
        //         channels => array(
        //             {
        //                 name => 'level2',
        //                 product_ids => array( 'ETH-BTC' )
        //             }
        //         )
        //     }
        //
        return $message;
    }

    public function handle_message($client, $message) {
        $type = $this->safe_string($message, 'type');
        $methods = array(
            'snapshot' => array($this, 'handle_order_book'),
            'l2update' => array($this, 'handle_order_book'),
            'subscribe' => array($this, 'handle_subscription_status'),
            'ticker' => array($this, 'handle_ticker'),
            'received' => array($this, 'handle_order'),
            'open' => array($this, 'handle_order'),
            'change' => array($this, 'handle_order'),
            'done' => array($this, 'handle_order'),
        );
        $length = strlen($client->url) - 0;
        $authenticated = $client->url[$length - 1] === '?';
        $method = $this->safe_value($methods, $type);
        if ($method === null) {
            if ($type === 'match') {
                if ($authenticated) {
                    $this->handle_my_trade($client, $message);
                    $this->handle_order($client, $message);
                } else {
                    $this->handle_trade($client, $message);
                }
            }
        } else {
            return $method($client, $message);
        }
    }
}
