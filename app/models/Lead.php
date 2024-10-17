<?php

namespace Esvlad\Bx24copytobox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

use Esvlad\Bx24copytobox\Models\Crm;
use Esvlad\Bx24copytobox\Models\User;

class Lead extends Model{
	protected $table = "leads";

	public static function setLeadsDB($start = 0){//413100
		print(date('d.m.Y H:i:s') . " Выполнено шагов - " . $start . "\r\n");
		$leads = Crm::bxBoxCall('crm.lead.list', [
			'select' => ['ID', 'UF_CRM_1720601579'],
			'filter' => [">DATE_CREATE" => "2024-10-13"],
			'start' => $start
		]);

		if(!empty($leads['next'])) $next = $leads['next'];

		$leads_data = [];
		foreach($leads['result'] as $lead){
			if(!empty($lead['UF_CRM_1720601579']))
			$leads_data[] = [
				'old_id' => $lead['UF_CRM_1720601579'],
				'new_id' => $lead['ID']
			];
		}

		//print_r($leads_data);
		foreach($leads_data as $lead){
			if(!self::where('old_id', $lead['old_id'])->where('new_id', $lead['new_id'])->exists()){
				self::insert($lead);
			}
		}

		//self::insert($leads_data);

		if(empty($next)){
			print("Сбор завершен!");
			return true;
		}

		self::setLeadsDB($next);
	}
}