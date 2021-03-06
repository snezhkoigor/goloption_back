<?php

namespace App\Http\Controllers\Api\V1\Strike;

use App\Http\Controllers\Controller;
use App\OptionStrikes;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Exception\NotFoundException;

class StrikeController extends Controller
{
	public function fpOdr()
	{
		if (isset($_GET['symbol'], $_GET['fp'], $_GET['open0'], $_GET['open1'], $_GET['close1']))
		{
			$fp = (float)$_GET['fp'];
			$symbol = strtoupper($_GET['symbol']);
			$open_0 = (float)$_GET['open0'];
			$open_1 = (float)$_GET['open1'];
			$close_1 = (float)$_GET['close1'];

			$parse_date = DB::table('option_parse_dates')
				->select('id')
				->orderBy('parse_date', 'desc')
				->limit(1)
				->first();
			
			if ($fp === 0)
			{
				$strike_with_fp = DB::table('option_strikes')
					->select('fp')
					->where([
						['fp', '>', 0],
						['symbol', $symbol]
					])
					->orderBy('parse_date_id', 'desc')
					->limit(1)
					->first();
				
				if ($strike_with_fp)
				{
					$fp = $strike_with_fp->fp;
				}
			}

			if ($parse_date && $_GET['fp'] !== 0)
			{
				DB::table('option_strikes')
					->where([
						['parse_date_id', $parse_date->id],
						['symbol', $symbol]
					])
					->update([
						'fp' => $fp
					]);

				DB::table('option_strikes')
					->where([
						['parse_date_id', $parse_date->id],
						['symbol', $symbol]
					])
					->where(function ($query) use ($open_0, $open_1, $close_1) {
		                $query->whereBetween('strike', [$open_0, $close_1])
		                      ->orWhereBetween('strike', [$open_1, $close_1]);
		            })
					->update([
						'odr' => 1
					]);
			}
		}

		return response()->json([
            'status' => true,
            'message' => 'Данные изменены',
            'data' => null
        ], 200);
	}
	
    public function getBySymbol($symbol, $type)
    {
    	$parse_date = !empty($_GET['parse_date']) ? json_decode($_GET['parse_date'], true) : [];
    	$filters = !empty($_GET['filters']) ? json_decode($_GET['filters'], true) : [];
    	$fields = !empty($_GET['fields']) ? explode(',', $_GET['fields']) : OptionStrikes::getDefaultFields();
    	$result = [];
    	$strikes = [];
    	$query = DB::table('option_strikes')
		    ->select(['option_strikes.fp', 'option_strikes.strike', 'option_strikes.odr', 'option_strikes.expire', 'option_parse_dates.parse_date',
	              'option_strikes.id', 'option_strike_calls_puts.type', 'option_strike_calls_puts.open_interest',
                  'option_strike_calls_puts.volume', 'option_strike_calls_puts.premia', 'option_strike_calls_puts.spros_1',
                  'option_strike_calls_puts.spros_2', 'option_strike_calls_puts.predlojenie_1', 'option_strike_calls_puts.predlojenie_2',
                  'option_strike_calls_puts.prirost_tekushiy', 'option_strike_calls_puts.prirost_predydushiy',
                  'option_strike_calls_puts.money_obshiy', 'option_strike_calls_puts.money_tekushiy',
                  'option_strike_calls_puts.balance_of_day', 'option_strike_calls_puts.is_balance'])
		    ->join('option_strike_calls_puts', 'option_strike_calls_puts.strike_id', '=', 'option_strikes.id')
		    ->join('option_parse_dates', 'option_parse_dates.id', '=', 'option_strikes.parse_date_id')
		    ->where([
		    	['option_strikes.symbol', strtoupper($symbol)],
			    ['option_strikes.type', $type]
		    ]);

    	if (isset($filters['odr']))
	    {
	    	$query->where('option_strikes.odr', $filters['odr']);
	    }
    	if (isset($parse_date['from']))
	    {
	    	$query->where('option_parse_dates.parse_date', '>=', date('Y-m-d H:i:s', $parse_date['from']));
	    }
	    if (isset($parse_date['to']))
	    {
	    	$query->where('option_parse_dates.parse_date', '<=', date('Y-m-d H:i:s', $parse_date['to']));
	    }
	    if (!isset($parse_date['from']) && !isset($parse_date['to']))
	    {
	    	$max_date = DB::table('option_parse_dates')
				->select('parse_date')
				->orderBy('parse_date', 'desc')
				->limit(1)
				->first();

			if ($max_date)
			{
				$query->where('option_parse_dates.parse_date', '>=', $max_date->parse_date);
			}
			else
			{
				echo 'Нет данных';
				die;
			}
	    }
	    if (count($filters) !== 0)
	    {
	    	foreach ($filters as $filter)
		    {
		    	if ($filter['value'] !== 'max')
			    {
			    	$query->where(($filter['field'] === 'type' ? 'option_strike_calls_puts.type' : $filter['field']), $filter['operation'], $filter['value']);
			    }
		    }
	    }

    	$data = $query
		    ->get()
	        ->toArray();

    	if ($data)
	    {
	        foreach ($data as $strike)
	        {
	        	$parse_date = strtotime($strike->parse_date);
	        	$strikes[$parse_date][$strike->strike]['parse_date'] = $parse_date;
//	        	$strikes[$strike->parse_date][$strike->strike]['id'] = $strike->id;
	        	$strikes[$parse_date][$strike->strike]['strike'] = $strike->strike;
	        	$strikes[$parse_date][$strike->strike]['fp'] = $strike->fp;

	        	if (in_array('odr', $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['odr'] = $strike->odr;
		        }
		        if (in_array('expire', $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['expire'] = $strike->expire;
		        }
		        if (in_array('open_interest_' . $strike->type, $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['open_interest_' . $strike->type] = $strike->open_interest;
		        }
		        if (in_array('volume_' . $strike->type, $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['volume_' . $strike->type] = $strike->volume;
		        }
		        if (in_array('premia_' . $strike->type, $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['premia_' . $strike->type] = $strike->premia;
		        }
		        if (in_array('spros_1_' . $strike->type, $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['spros_1_' . $strike->type] = $strike->spros_1;
		        }
		        if (in_array('spros_2_' . $strike->type, $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['spros_2_' . $strike->type] = $strike->spros_2;
		        }
		        if (in_array('predlojenie_1_' . $strike->type, $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['predlojenie_1_' . $strike->type] = $strike->predlojenie_1;
		        }
		        if (in_array('predlojenie_2_' . $strike->type, $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['predlojenie_2_' . $strike->type] = $strike->predlojenie_2;
		        }
		        if (in_array('prirost_tekushiy_' . $strike->type, $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['prirost_tekushiy_' . $strike->type] = $strike->prirost_tekushiy;
		        }
		        if (in_array('prirost_predydushiy_' . $strike->type, $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['prirost_predydushiy_' . $strike->type] = $strike->prirost_predydushiy;
		        }
		        if (in_array('money_obshiy_' . $strike->type, $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['money_obshiy_' . $strike->type] = $strike->money_obshiy;
		        }
		        if (in_array('money_tekushiy_' . $strike->type, $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['money_tekushiy_' . $strike->type] = $strike->money_tekushiy;
		        }
		        if (in_array('balance_of_day_' . $strike->type, $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['balance_of_day_' . $strike->type] = $strike->balance_of_day;
		        }
		        if (in_array('is_balance_' . $strike->type, $fields))
		        {
		        	$strikes[$parse_date][$strike->strike]['is_balance_' . $strike->type] = $strike->is_balance;
		        }
	        }

            foreach ($strikes as $parsed => $items)
	        {
	            foreach ($items as $item)
		        {
		            $result[] = array_values($item);
		        }
	        }
	    }

	    return response()->csv($result, 200, [], [
	    	'encoding' => 'UTF-8',
		    'delimiter' => ';',
		    'quoted' => false,
		    'include_header' => true,
	    ]);
    }

    public function saveFpAndOdrFromIndicator($strike_id, $fp = null, $odr = null)
    {
    	$strike = OptionStrikes::query('id', $strike_id)->first();

    	if ($strike === null)
	    {
	    	throw new NotFoundException('Нет страйка');
	    }

	    if ($fp)
	    {
	        $strike->fp = $fp;
	    }
	    if ($odr)
	    {
	        $strike->odr = $odr;
	    }

    	$strike->save();

    	return response()->json([
            'status' => true,
            'message' => 'Данные изменены',
            'data' => null
        ], 200);
    }
}
