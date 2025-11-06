<?php

use App\Helpers\SimpleAdditiveWeighting;

test('pick acar', function () {
    $saw = new SimpleAdditiveWeighting([
        "production_year" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 30.0,
            "transform" => function (string $criterion, $item): string {
                $year = $item[$criterion];
                if (2000 <= $year && $year <= 2004) {
                    return SimpleAdditiveWeighting::VERY_LOW;
                }
                if (2005 <= $year && $year <= 2009) {
                    return SimpleAdditiveWeighting::LOW;
                }
                if (2010 <= $year && $year <= 2013) {
                    return SimpleAdditiveWeighting::MODERATE;
                }
                if (2014 <= $year && $year <= 2015) {
                    return SimpleAdditiveWeighting::HIGH;
                }
                return SimpleAdditiveWeighting::VERY_HIGH;
            }
        ],
        "engine_capacity" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 20.0,
            "transform" => function (string $criterion, $item): string {
                $capacity = $item[$criterion];
                if ($capacity === 1200) {
                    return SimpleAdditiveWeighting::VERY_LOW;
                }
                if ($capacity === 1500) {
                    return SimpleAdditiveWeighting::LOW;
                }
                return SimpleAdditiveWeighting::HIGH;
            }
        ],
        "car_color" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 30.0,
            "transform" => function (string $criterion, $item): string {
                $color = $item[$criterion];
                if ($color === "white") {
                    return SimpleAdditiveWeighting::HIGH;
                }
                if ($color === "black") {
                    return SimpleAdditiveWeighting::VERY_HIGH;
                }
                return SimpleAdditiveWeighting::MODERATE;
            }
        ],
        "car_price" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 20.0,
            "transform" => function (string $criterion, $item): string {
                $price = $item[$criterion];
                if (50 <= $price && $price <= 100) {
                    return SimpleAdditiveWeighting::VERY_LOW;
                }
                if (101 <= $price && $price <= 200) {
                    return SimpleAdditiveWeighting::LOW;
                }
                if (201 <= $price && $price <= 300) {
                    return SimpleAdditiveWeighting::MODERATE;
                }
                if (301 <= $price && $price <= 400) {
                    return SimpleAdditiveWeighting::HIGH;
                }
                return SimpleAdditiveWeighting::VERY_HIGH;
            }
        ],
    ]);

    $scores = $saw->scoreAll(collect([
        ['car_model' => 'Toyota Agya', 'production_year' => 2014, 'engine_capacity' => 1200, 'car_color' => 'white', 'car_price' => 98],
        ['car_model' => 'Toyota Avanza', 'production_year' => 2015, 'engine_capacity' => 1200, 'car_color' => 'white', 'car_price' => 138],
        ['car_model' => 'Toyota Rush', 'production_year' => 2016, 'engine_capacity' => 1500, 'car_color' => 'black', 'car_price' => 190],
        ['car_model' => 'Toyota Kijang Innova', 'production_year' => 2018, 'engine_capacity' => 2400, 'car_color' => 'silver', 'car_price' => 300],
        ['car_model' => 'Toyota Fortuner', 'production_year' => 2019, 'engine_capacity' => 2400, 'car_color' => 'black', 'car_price' => 530]
    ]));

    expect($scores[0])->toEqual([
        'item' => [
            'car_model' => 'Toyota Fortuner',
            'production_year' => 2019,
            'engine_capacity' => 2400,
            'car_color' => 'black',
            'car_price' => 530,
        ],
        'score' => 100.0,
    ]);

    expect($scores[1])->toEqual([
        'item' => [
            'car_model' => 'Toyota Rush',
            'production_year' => 2016,
            'engine_capacity' => 1500,
            'car_color' => 'black',
            'car_price' => 190,
        ],
        'score' => 67.5,
    ]);

    expect($scores[2])->toEqual([
        'item' => [
            'car_model' => 'Toyota Kijang Innova',
            'production_year' => 2018,
            'engine_capacity' => 2400,
            'car_color' => 'silver',
            'car_price' => 300,
        ],
        'score' => 62.5,
    ]);

    expect($scores[3])->toEqual([
        'item' => [
            'car_model' => 'Toyota Avanza',
            'production_year' => 2015,
            'engine_capacity' => 1200,
            'car_color' => 'white',
            'car_price' => 138,
        ],
        'score' => 35.0,
    ]);

    expect($scores[4])->toEqual([
        'item' => [
            'car_model' => 'Toyota Agya',
            'production_year' => 2014,
            'engine_capacity' => 1200,
            'car_color' => 'white',
            'car_price' => 98,
        ],
        'score' => 33.75,
    ]);
});

test('supplier selection', function () {
    $saw = new SimpleAdditiveWeighting([
        "quality" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 30.0,
            "transform" => function (string $criterion, $item): string {
                return match ($item[$criterion]) {
                    1 => SimpleAdditiveWeighting::VERY_LOW,
                    2 => SimpleAdditiveWeighting::LOW,
                    3 => SimpleAdditiveWeighting::MODERATE,
                    4 => SimpleAdditiveWeighting::HIGH,
                    5 => SimpleAdditiveWeighting::VERY_HIGH,
                };
            }
        ],
        "price" => [
            "type" => SimpleAdditiveWeighting::COST,
            "weight" => 25.0,
            "transform" => function (string $criterion, $item): string {
                return match ($item[$criterion]) {
                    1 => SimpleAdditiveWeighting::VERY_LOW,
                    2 => SimpleAdditiveWeighting::LOW,
                    3 => SimpleAdditiveWeighting::MODERATE,
                    4 => SimpleAdditiveWeighting::HIGH,
                    5 => SimpleAdditiveWeighting::VERY_HIGH,
                };
            }
        ],
        "delivery" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 20.0,
            "transform" => function (string $criterion, $item): string {
                return match ($item[$criterion]) {
                    1 => SimpleAdditiveWeighting::VERY_LOW,
                    2 => SimpleAdditiveWeighting::LOW,
                    3 => SimpleAdditiveWeighting::MODERATE,
                    4 => SimpleAdditiveWeighting::HIGH,
                    5 => SimpleAdditiveWeighting::VERY_HIGH,
                };
            }
        ],
        "services" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 15.0,
            "transform" => function (string $criterion, $item): string {
                return match ($item[$criterion]) {
                    1 => SimpleAdditiveWeighting::VERY_LOW,
                    2 => SimpleAdditiveWeighting::LOW,
                    3 => SimpleAdditiveWeighting::MODERATE,
                    4 => SimpleAdditiveWeighting::HIGH,
                    5 => SimpleAdditiveWeighting::VERY_HIGH,
                };
            }
        ],
        "technical_capability" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 10.0,
            "transform" => function (string $criterion, $item): string {
                return match ($item[$criterion]) {
                    1 => SimpleAdditiveWeighting::VERY_LOW,
                    2 => SimpleAdditiveWeighting::LOW,
                    3 => SimpleAdditiveWeighting::MODERATE,
                    4 => SimpleAdditiveWeighting::HIGH,
                    5 => SimpleAdditiveWeighting::VERY_HIGH,
                };
            }
        ],
    ]);

    $scores = $saw->scoreAll(collect([
        ['quality' => 5, 'price' => 4, 'delivery' => 4, 'services' => 5, 'technical_capability' => 4],
        ['quality' => 4, 'price' => 5, 'delivery' => 5, 'services' => 3, 'technical_capability' => 5],
        ['quality' => 5, 'price' => 4, 'delivery' => 5, 'services' => 4, 'technical_capability' => 4],
        ['quality' => 3, 'price' => 3, 'delivery' => 5, 'services' => 4, 'technical_capability' => 3],
        ['quality' => 4, 'price' => 3, 'delivery' => 3, 'services' => 5, 'technical_capability' => 4],
    ]));

    expect($scores[0])->toEqual([
        'item' => [
            'quality' => 5,
            'price' => 4,
            'delivery' => 5,
            'services' => 4,
            'technical_capability' => 4,
        ],
        'score' => 75.0,
    ]);

    expect($scores[1])->toEqual([
        'item' => [
            'quality' => 5,
            'price' => 4,
            'delivery' => 4,
            'services' => 5,
            'technical_capability' => 4,
        ],
        'score' => 72.5,
    ]);

    expect($scores[2])->toEqual([
        'item' => [
            'quality' => 4,
            'price' => 3,
            'delivery' => 3,
            'services' => 5,
            'technical_capability' => 4,
        ],
        'score' => 65.0,
    ]);

    expect($scores[3])->toEqual([
        'item' => [
            'quality' => 3,
            'price' => 3,
            'delivery' => 5,
            'services' => 4,
            'technical_capability' => 3,
        ],
        'score' => 62.5,
    ]);

    expect($scores[4])->toEqual([
        'item' => [
            'quality' => 4,
            'price' => 5,
            'delivery' => 5,
            'services' => 3,
            'technical_capability' => 5,
        ],
        'score' => 55.0,
    ]);
});

test('weight cyber rules', function () {
    $saw = new SimpleAdditiveWeighting([
        "whoami" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 10,
            "transform" => function (string $criterion, $item): string {
                return $item[$criterion] >= 1 ? SimpleAdditiveWeighting::VERY_HIGH : SimpleAdditiveWeighting::VERY_LOW;
            }
        ],
        "netuser" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 10,
            "transform" => function (string $criterion, $item): string {
                return $item[$criterion] >= 1 ? SimpleAdditiveWeighting::VERY_HIGH : SimpleAdditiveWeighting::VERY_LOW;
            }
        ],
        "ipconfig_all" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 20,
            "transform" => function (string $criterion, $item): string {
                return $item[$criterion] >= 1 ? SimpleAdditiveWeighting::VERY_HIGH : SimpleAdditiveWeighting::VERY_LOW;
            }
        ],
        "memdump" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 30,
            "transform" => function (string $criterion, $item): string {
                return $item[$criterion] >= 1 ? SimpleAdditiveWeighting::VERY_HIGH : SimpleAdditiveWeighting::VERY_LOW;
            }
        ],
        "lateral_movement" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 20,
            "transform" => function (string $criterion, $item): string {
                return $item[$criterion] >= 1 ? SimpleAdditiveWeighting::VERY_HIGH : SimpleAdditiveWeighting::VERY_LOW;
            }
        ],
        "first_time_process_executed" => [
            "type" => SimpleAdditiveWeighting::BENEFIT,
            "weight" => 10,
            "transform" => function (string $criterion, $item): string {
                return $item[$criterion] >= 1 ? SimpleAdditiveWeighting::VERY_HIGH : SimpleAdditiveWeighting::VERY_LOW;
            }
        ],
    ]);

    // values represent the number of times a rule has been matched in a given timeframe
    $scores = $saw->scoreAll(collect([
        ['machine' => 'A', 'whoami' => 3, 'netuser' => 1, 'ipconfig_all' => 1, 'memdump' => 1, 'lateral_movement' => 1, 'first_time_process_executed' => 1],
        ['machine' => 'B', 'whoami' => 0, 'netuser' => 1, 'ipconfig_all' => 1, 'memdump' => 0, 'lateral_movement' => 0, 'first_time_process_executed' => 0],
        ['machine' => 'C', 'whoami' => 1, 'netuser' => 0, 'ipconfig_all' => 0, 'memdump' => 0, 'lateral_movement' => 0, 'first_time_process_executed' => 1],
    ]));

    expect($scores[0])->toEqual([
        'item' => [
            'machine' => 'A',
            'whoami' => 3,
            'netuser' => 1,
            'ipconfig_all' => 1,
            'memdump' => 1,
            'lateral_movement' => 1,
            'first_time_process_executed' => 1,
        ],
        'score' => 100.0,
    ]);

    expect($scores[1])->toEqual([
        'item' => [
            'machine' => 'B',
            'whoami' => 0,
            'netuser' => 1,
            'ipconfig_all' => 1,
            'memdump' => 0,
            'lateral_movement' => 0,
            'first_time_process_executed' => 0,
        ],
        'score' => 34.375,
    ]);

    expect($scores[2])->toEqual([
        'item' => [
            'machine' => 'C',
            'whoami' => 1,
            'netuser' => 0,
            'ipconfig_all' => 0,
            'memdump' => 0,
            'lateral_movement' => 0,
            'first_time_process_executed' => 1,
        ],
        'score' => 25.0,
    ]);
});
