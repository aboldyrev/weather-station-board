<?php

namespace App\Console\Commands;

use App\Models\Light;
use App\Models\Temperature;
use Illuminate\Console\Command;

class GetData extends Command
{
	protected $signature = 'get-data';

	protected $description = 'Чтение данных с Arduino';


	public function __construct() {
		parent::__construct();
	}


	public function handle() {
		$device_name = '';

		// имя девайса
		$devices = glob('/dev/ttyUSB*');

		if (count($devices) == 0) {
			$this->error('Device not found');
			exit;
		} elseif (count($devices) == 1) {
			$device_name = array_first($devices);
		} elseif (count($devices) > 1) {
			$this->warn('Found more than one device');
			$device_name = $this->choice('Select your device', $devices);
		}

		$device = fopen($device_name, "r+b");
		sleep(2);
		fwrite($device, 't');
		sleep(2);
		$temperature = (float)trim(fgets($device));
		sleep(1);
		fwrite($device, 'l');
		sleep(2);
		$light = (float)trim(fgets($device));
		fclose($device);

		$last_value_light = Light::orderBy('created_at', 'desc')->first()->value;

		$diff_light = $light - $last_value_light;

		$values = [
			'light' => $light,
			'temp'  => $temperature
		];

		if ($light > 0){
			if (abs($diff_light) >= 300) {
				// Общий свет
				if ($diff_light > 0) {
					$this->logging('Общий свет включен', $values);
				} else {
					$this->logging('Общий свет выключен', $values);
				}
			}

			Temperature::create([ 'value' => $temperature ]);
			Light::create([ 'value' => $light ]);
		}
	}
}
