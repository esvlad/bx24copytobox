<?php

namespace Esvlad\Bx24copytobox\Models;

use Esvlad\Bx24copytobox\Models\CrestCloud;
use Esvlad\Bx24copytobox\Models\CrestBox;

class Crm{
	public static function bxCloudCallBatch($batch_list){
		$result = CrestCloud::callBatch($batch_list);
	    if (!empty($result['error']) && $result['error'] == 'QUERY_LIMIT_EXCEEDED'){
	        sleep(1);
	        self::bxCloudCallBatch($batch_list);
	    }

	    return $result;
	}

	public static function bxBoxCallBatch($batch_list){
		$result = CrestBox::callBatch($batch_list);
	    if (!empty($result['error']) && $result['error'] == 'QUERY_LIMIT_EXCEEDED'){
	        sleep(1);
	        self::bxBoxCallBatch($batch_list);
	    }

	    return $result;
	}

	public static function bxCloudCall($method, $data){
		$result = CrestCloud::call($method, $data);

		if(!empty($result['error']) && $result['error'] == 'QUERY_LIMIT_EXCEEDED'){
			sleep(1);
			self::bxCloudCall($method, $data);
		} else {
			return $result;
		}
	}

	public static function bxBoxCall($method, $data){
		$result = CrestBox::call($method, $data);

		if(!empty($result['error']) && $result['error'] == 'QUERY_LIMIT_EXCEEDED'){
			sleep(1);
			self::bxBoxCall($method, $data);
		} else {
			return $result;
		}
	}
}