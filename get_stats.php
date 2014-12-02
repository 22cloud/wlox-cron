#!/usr/bin/php
<?php
echo "Beginning Get Status processing...".PHP_EOL;

include 'cfg.php';


// GET BITCOIN GLOBAL STATS
$data = file_get_contents('http://blockchain.info/charts/total-bitcoins?format=csv');
$data1 = explode("\n",$data);
$c = count($data1) - 1;
$data2 = explode(',',$data1[$c]);
$total_btc = $data2[1];

$data = file_get_contents('http://blockchain.info/charts/market-cap?format=csv');
$data1 = explode("\n",$data);
$c = count($data1) - 1;
$data2 = explode(',',$data1[$c]);
$market_cap = $data2[1];

$data = file_get_contents('http://blockchain.info/charts/trade-volume?format=csv');
$data1 = explode("\n",$data);
$c = count($data1) - 1;
$data2 = explode(',',$data1[$c]);
$trade_volume = $data2[1];
db_update('current_stats',1,array('trade_volume'=>$data2[1],'total_btc'=>$total_btc,'market_cap'=>$market_cap));

// GET EXCHANGE RATES
if ($CFG->currencies) {
	foreach ($CFG->currencies as $currency) {
		if ($currency['currency'] == 'BTC' || $currency == 'USD')
			continue;
		
		$currencies[] = $currency['currency'].'USD';
	}
	$currency_string = urlencode(implode(',',$currencies));
	$data = json_decode(file_get_contents('http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20yahoo.finance.xchange%20where%20pair%3D%22'.$currency_string.'%22&format=json&diagnostics=true&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys'),TRUE);
	
	if ($data['query']['results']['rate']) {
		foreach ($data['query']['results']['rate'] as $row) {
			$key = str_replace('USD','',$row['id']);
			$ask = $row['Ask'];
			$bid = $row['Bid'];
			
			if (strlen($key) < 3 || strstr($key,'='))
				continue;
			
			$sql = "SELECT id FROM currencies WHERE currency = '$key'";
			$result = db_query_array($sql);
			
			if ($result) {
				db_update('currencies',$result[0]['id'],array('usd_bid'=>$bid,'usd_ask'=>$ask));
			}
			else {
				db_insert('currencies',array('usd_bid'=>$bid,'usd_ask'=>$ask,'currency'=>$key));
			}
		}
	}
}

db_update('status',1,array('cron_get_stats'=>date('Y-m-d H:i:s')));
echo 'done'.PHP_EOL;


