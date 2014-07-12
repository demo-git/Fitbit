<?php

namespace Fitbit;

class FitBitFood
{

		private $name;
		private $unit;
		private $fitbit;
		private $serving;
		private $calories;
		private $nutrition = array(
			'caloriesFromFat' => array('val' => 0, 'unit' => 'kCal'),
			'totalFat' => array('val' => 0, 'unit' => 'g'),
			'transFat' => array('val' => 0, 'unit' => 'g'),
			'saturatedFat' => array('val' => 0, 'unit' => 'g'),
			'cholesterol' => array('val' => 0, 'unit' => 'mg'),
			'sodium' => array('val' => 0, 'unit' => 'mg'),
			'potassium' => array('val' => 0, 'unit' => 'mg'),
			'totalCarbohydrate' => array('val' => 0, 'unit' => 'g'),
			'dietaryFiber' => array('val' => 0, 'unit' => 'g'),
			'sugars' => array('val' => 0, 'unit' => 'g'),
			'protein' => array('val' => 0, 'unit' => 'g'),
			'vitaminA' => array('val' => 0, 'unit' => 'IU'),
			'vitaminB6' => array('val' => 0, 'unit' => 'mg'),
			'vitaminB12' => array('val' => 0, 'unit' => 'μg'),
			'vitaminC' => array('val' => 0, 'unit' => 'mg'),
			'vitaminD' => array('val' => 0, 'unit' => 'IU'),
			'vitaminE' => array('val' => 0, 'unit' => 'IU'),
			'biotin' => array('val' => 0, 'unit' => 'mg'),
			'folicAcid' => array('val' => 0, 'unit' => 'mg'),
			'niacin' => array('val' => 0, 'unit' => 'mg'),
			'pantothenicAcid' => array('val' => 0, 'unit' => 'mg'),
			'riboflavin' => array('val' => 0, 'unit' => 'mg'),
			'thiamin' => array('val' => 0, 'unit' => 'mg'),
			'calcium' => array('val' => 0, 'unit' => 'g'),
			'copper' => array('val' => 0, 'unit' => 'mg'),
			'iron' => array('val' => 0, 'unit' => 'mg'),
			'magnesium' => array('val' => 0, 'unit' => 'mg'),
			'phosphorus' => array('val' => 0, 'unit' => 'g'),
			'iodine' => array('val' => 0, 'unit' => 'μg'),
			'zinc' => array('val' => 0, 'unit' => 'mg')
			);
		private $units;

    public function __construct($fitbit, $name, $unit, $serving, $calories){
			$this->name = $name;
			$this->fitbit = $fitbit;
			$this->unit = $this->getUnit($unit);
			$this->serving = $serving;
			$this->calories = $calories;
			$this->units  = array(
				'μg' => 1/1000000000,
				'mg' => 1/1000000,
				'g' =>  1/1000,
				'kg' => 1
				);
    }

    private function getUnit($find){
			foreach($this->fitbit->getFoodUnits() as $unit){
				if($unit->name == $find) {
					return $unit->id;
				}
			}
			return false;
    }

		private function unitConvert($value, $from, $to){
			if($from == 'μg' && $to == 'IU') return $value*40;
			elseif($from == 'ATE' && $to == 'IU') return $value/0.67;
			elseif($from == 'RE' && $to == 'IU') return $value/0.33334;
			return $value*($this->units[$from]/$this->units[$to]);
		}

    public function addNutrition($name, $val){
    	$nutrid = $this->nutrition[$name];
    	$this->nutrition[$name]['val'] = $this->unitConvert($val['val'], $val['unit'], $nutrid['unit']);
    }

    public function nutricToFibit(){
    	$n = array();
    	foreach($this->nutrition as $key => $nutrition){
    		$n[$key] = $nutrition['val'];
    	}
    	return $n;
    }

    public function addToFitbit(){
    	return $this->fitbit->createFood($this->name, $this->unit, $this->serving, $this->calories, null, null, $this->nutricToFibit());
    }

    public function printNutrition(){
    	print_r($this->nutrition);
    }

}



