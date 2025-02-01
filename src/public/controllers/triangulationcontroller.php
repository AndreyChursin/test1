<?php
class TriangulationController
{
    /**
     * Метод для обработки запроса расчёта координат по методу триангуляции.
     * @return array
     */
    public function calculateAction(): array
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // Проверка наличия всех необходимых полей
        $requiredFields = [
            'distance1',
            'distance2',
            'distance3',
            'point1-lat',
            'point1-lon',
            'point2-lat',
            'point2-lon',
            'point3-lat',
            'point3-lon'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return [
                    'success' => false,
                    'error'   => "Отсутствует поле: $field"
                ];
            }
        }

        // Приведение к числовому типу и базовая проверка
        $d1 = floatval($data['distance1']);
        $d2 = floatval($data['distance2']);
        $d3 = floatval($data['distance3']);

        if ($d1 <= 0 || $d2 <= 0 || $d3 <= 0) {
            return [
                'success' => false,
                'error' => 'Расстояния должны быть положительными числами'
            ];
        }

        // Координаты опорных точек (широта, долгота)
        $p1 = [floatval($data['point1-lat']), floatval($data['point1-lon'])];
        $p2 = [floatval($data['point2-lat']), floatval($data['point2-lon'])];
        $p3 = [floatval($data['point3-lat']), floatval($data['point3-lon'])];

        // логика триангуляции.
        if ('simple') {
            // Пример: для упрощения вычисляем среднее значение координат.
            $latitude = ($p1[0] + $p2[0] + $p3[0]) / 3;
            $longitude = ($p1[1] + $p2[1] + $p3[1]) / 3;
        } else {
            // Выполняем трилатерацию
            $result = $this->trilaterate($p1, $p2, $p3, $d1, $d2, $d3);
            if ($result === false) {
                return [
                    'success' => false,
                    'error'   => 'Не удалось рассчитать координаты (возможно, опорные точки выровнены или неверные данные)'
                ];
            }
            $latitude = $result[0];
            $longitude = $result[1];
        }
        // Возвращаем результат
        return [
            'success'   => true,
            'latitude'  => $latitude,
            'longitude' => $longitude
        ];
    }

    /**
     * Функция трилатерации для плоской системы координат.
     *
     * @param array $p1 Координаты первой опорной точки [x, y].
     * @param array $p2 Координаты второй опорной точки [x, y].
     * @param array $p3 Координаты третьей опорной точки [x, y].
     * @param float $r1 Расстояние от искомой точки до первой точки.
     * @param float $r2 Расстояние от искомой точки до второй точки.
     * @param float $r3 Расстояние от искомой точки до третьей точки.
     *
     * @return array|false Массив с координатами искомой точки [x, y] или false при ошибке.
     */
    private function trilaterate(array $p1, array $p2, array $p3, float $r1, float $r2, float $r3): array|false
    {
        $d = sqrt(pow($p2[0] - $p1[0], 2) + pow($p2[1] - $p1[1], 2));
        if ($d == 0) {
            return false; // Первая и вторая точки совпадают.
        }
        $ex = [($p2[0] - $p1[0]) / $d, ($p2[1] - $p1[1]) / $d];

        $p1p3 = [$p3[0] - $p1[0], $p3[1] - $p1[1]];
        $i = $ex[0] * $p1p3[0] + $ex[1] * $p1p3[1];

        $temp = [$p1p3[0] - $i * $ex[0], $p1p3[1] - $i * $ex[1]];
        $tempNorm = sqrt(pow($temp[0], 2) + pow($temp[1], 2));
        if ($tempNorm == 0) {
            return false;
        }
        $ey = [$temp[0] / $tempNorm, $temp[1] / $tempNorm];

        $j = $ey[0] * $p1p3[0] + $ey[1] * $p1p3[1];

        // Решаем систему уравнений:
        $x = (pow($r1, 2) - pow($r2, 2) + pow($d, 2)) / (2 * $d);
        if ($j == 0) {
            return false;
        }
        $y = (pow($r1, 2) - pow($r3, 2) + pow($i, 2) + pow($j, 2)) / (2 * $j) - ($i / $j) * $x;

        $final_x = $p1[0] + $x * $ex[0] + $y * $ey[0];
        $final_y = $p1[1] + $x * $ex[1] + $y * $ey[1];

        return [$final_x, $final_y];
    }
}
