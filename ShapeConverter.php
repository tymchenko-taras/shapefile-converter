<?php
/**
 * Created by PhpStorm.
 * User: Taras
 * Date: 31/10/18
 * Time: 11:59 AM
 */

class ShapeConverter
{
    protected function getResultFilePath($path) {
        $ext = '.csv';
        $result = 'result';
        $pathInfo = pathinfo($path);
        $path = $pathInfo['dirname'] . DIRECTORY_SEPARATOR;
        if(!empty($pathInfo['filename'])){
            $result = $pathInfo['filename'];
        }
        return $path . $result . '('. date('Y-m-d H:i:s') .')' . $ext;
    }

    protected function getShapeProjectParams($string) {
        //The central meridian and longitude of origin parameters are synonymous.
        $string = str_replace('"', '', $string);
        $string = str_replace("'", '', $string);
        $result = [
            'False_Easting' => null,
            'False_Northing' => null,
            'Central_Meridian' => null,
            'Standard_Parallel_1' => null,
            'Standard_Parallel_2' => null,
            'Latitude_Of_Origin' => null,
        ];
        foreach ($result as $key => $value) {
            if(preg_match("#{$key}[,\s]+(-?\d+(\.\d+)?)#", $string, $matches)){
                if(isset($matches[1])){
                    $result[ $key ] = $matches[1];
                } else {
                    throw new \Exception('Can not find required parameter ' . $key);
                }
            }
        }

        return $result;
    }

    public function convert($path, $nameKey, $additionalColumns = []) {
        $csvPath = $this->getResultFilePath($path);
        $csv = fopen($csvPath, 'w');
        $shp = new \ShapeFile\ShapeFile($path, ['noparts' => true]);
        if (!$shp) {
            throw new \Exception('Failed to parse shapefile');
        }
        $params = $this->getShapeProjectParams($shp->getPRJ());
        $converter = new \GpointConverter();
        $converter->configLambertProjection(
            $params['False_Easting'],
            $params['False_Northing'],
            $params['Central_Meridian'],
            $params['Latitude_Of_Origin'],
            $params['Standard_Parallel_1'],
            $params['Standard_Parallel_2']
        );
        foreach ($shp as $item) {
            $coordinates = [];
            if(!empty($item['shp']['parts'])) {
                foreach ($item['shp']['parts'] as $rings) {
                    foreach ($rings['rings'] as $ring) {
                        if(!empty($ring['points'])){
                            foreach ($ring['points'] as $point) {
                                $converter->setLambert($point['y'], $point['x']);
                                $converter->convertLCCtoLL();
                                $coordinates[] = $converter->long . ' ' . $converter->lat;
                            }
                        }
                    }
                }
            }

            if(reset($coordinates) != end($coordinates)){
                $coordinates[] = reset($coordinates);
            }
            $name = preg_replace('#[\x00-\x1F\x80-\xFF]#', ' ', $item['dbf'][ $nameKey ]);
            $name = preg_replace('#\s+#', ' ', $name);
            fputcsv($csv, array_merge([$name,  implode(',', $coordinates)], $additionalColumns));
        }
        fclose($csv);
        echo 'Created: ', $csvPath;
    }
}