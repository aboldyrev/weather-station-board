<?php

namespace App\Console\Commands;

use App\Models\Light;
use App\Models\Temperature;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GetData extends Command
{
	protected $signature = 'get-data';

	protected $description = 'Чтение данных с Arduino';


	public function __construct() {
		parent::__construct();
	}


	protected function log(string $message) {
		$timestamp = Carbon::now();

		file_put_contents(
			storage_path('logs/get-data-' . $timestamp->format('Y-m-d') . '.log'),
			'[ ' . $timestamp->format('d.m.Y H:i:s') . ' ]' . $message . PHP_EOL,
			FILE_APPEND
		);
	}


	protected function logging($content, $value = NULL) {
		if (is_array($value)) {
			$context = 'Уровень света: ' . $value[ 'light' ] . '; Температура: ' . $value[ 'temp' ] . '°C';
		} elseif (is_string($value)) {
			$context = $value;
		} else {
			$context = false;
		}

		if ($context) {
			$this->log($content . json_encode([ 'data' => $context ], JSON_UNESCAPED_UNICODE));
		} else {
			$this->log($content);
		}

		if (file_exists(base_path('pc_boot.lock'))) {
			$this->log('Перезагрузка компьютера');
			unlink (base_path('pc_boot.lock'));
		} else {
			foreach (config('contacts.mails', []) as $mail) {
				\Mail::send(
					'emails.simple',
					[ 'content' => $content, 'context' => $context ],
					function($message) use ($content, $mail) {
						$subject = $content . ' - ' . Carbon::now()->format('H:i');
						$message
							->from(
								config('mail.from')['address'],
								isset($mail['from']) ? $mail['from'] : config('mail.from')['name']
							)
							->to($mail['address'], $mail['name'])
							->subject($subject);
					}
				);
			}
		}
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

		$last_value_light = Light::orderBy('created_at', 'desc')->first();

		if ($last_value_light) {
			$last_value_light = $last_value_light->value;
		} else {
			$last_value_light = 0;
		}

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
