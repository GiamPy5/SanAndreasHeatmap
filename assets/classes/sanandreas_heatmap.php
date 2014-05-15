<?php

ini_set('memory_limit', '-1');

require_once('vendor/gd_heatmap/gd_heatmap.php');

class CSanAndreasHeatmap {

    protected $database_config   =
        array(
            // By default, the type is 'mysql'. Types available are 'array' and 'mysql' — if another type is entered it will be reset to 'mysql'.
            'type'     => 'mysql',

            // These values are ignored and not used if the type is 'array'. It is suggested to use 'array' only for testing or debugging purposes.
            'hostname' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'heatmap',

            'table' => 'coordinates',
            'x_column' => 'x',
            'y_column' => 'y'
        );

    protected $database_handler  = null;

    /*
        This variable is populated by the database if the config 'mysql' was selected.
        Otherwise insert the coordinates manually here if you're using the 'array' type.
    */
    protected $map_coordinates   = 
        array(
            array(1482.7028,-1771.8403),
            array(384.3985,-2087.8699),
            array(384.3985,-2087.8699),
            array(1636.7345,-1875.1460),
            array(1691.1294,-1848.2501),
            array(1692.1553,-1703.5740),
            array(1699.6017,-1595.0657),
            array(1836.2867,-1615.0037),
            array(1938.9124,-1629.2429),
            array(1950.7043,-1755.0970),
            array(1958.7981,-1794.1204), 
            array(1970.9253,-1934.1924),
            array(2084.4036,-1919.6848), 
            array(2094.6614,-1897.4415),
            array(2215.8672,-1901.5297),
            array(2228.1917,-1974.9897),
            array(2415.6587,-1967.3401),
            array(2416.4314,-1820.6899),
            array(2397.0378,-1729.7274), 
            array(2262.1567,-1729.2256),
            array(2182.7190,-1740.3082),
            array(2076.4121,-1749.2941),
            array(1936.5626,-1749.2295),
            array(1820.2206,-1760.0153),
            array(1807.8940,-1830.6697),
            array(1704.3971,-1808.9795),
            array(1686.6853,-1840.3320),
            array(1637.5179,-1868.6910),
            array(1619.0934,-1888.8633),
            array(-1007.3392,-695.1489),
            array(2787.2681,-2456.4229),
            array(2787.4143,-2494.5986),
            array(2788.3472,-2418.0657),
            array(-1020.7845,-694.7393),
            array(601.0737,-1239.9606),
            array(219.4351,114.0401),
            array(2288.2930,576.3303),
            array(2097.0273,500.8596),
            array(2110.0378,579.2719),
            array(1178.1207,-1323.7904),
            array(1742.9888,-1861.6996),
            array(1706.6880,-1493.7015),
            array(2757.1770,-2577.1335),
            array(1938.8156,166.5612),
            array(1481.0662,-1770.9189),
            array(595.2648,-1249.7048),
            array(1809.2361,-1600.7588),
            array(1807.0909,-1619.1715),
            array(1796.0118,-1618.5099),
            array(2476.3320,-1655.7800),
            array(2430.5625,-1671.8623),
            array(2466.8682,-1743.6140),
            array(2466.9714,-1739.5807),
            array(2154.1563,-1729.4092),
            array(2153.9851,-1723.8635),
            array(2015.5629,-1716.9529),
            array(2011.5095,-1717.0439),
            array(2173.5679,-1444.4554),
            array(2168.3977,-1444.3779),
            array(2505.4006,-1678.7899),
            array(2500.0532,-1655.9648),
            array(2479.5403,-1654.1879),
            array(1554.5250,-1675.6558),
            array(254.3079,76.8163),
            array(246.7578,63.2360),
            array(1367.3322,-1279.8539),
            array(-347.9203,-1046.4130),
            array(264.2225,80.3875),
            array(264.1970,83.7838),
            array(270.0467,81.9640),
            array(268.1883,77.5770),
            array(242.7447,66.3662),
            array(1568.6444,-1690.6406),
            array(242.9832,66.3688),
        );
    protected $map_handler       = null;
    
    protected $heatmap_config    = 
        array(
            'debug'  => FALSE,
            'width'  => null,
            'height' => null,
            'noc'    => 32,
            'r'      => 25,
            'dither' => FALSE,
            'format' => 'jpeg'
        );

    protected $directory_config  =
        array(
            'assets_classes' => 'assets/classes/',
            'assets_images'  => 'assets/images/',
        );

    /*
        $database contains the database configuration.
        $directory contains the directory configuration.
        $heatmap contains the heatmap configuration.
    */
    public function __construct($database = array(), $directory = array(), $heatmap = array()) {
        foreach ($this->database_config as $key => $value) {
            if ($key == 'type') {
                $allowedTypes = array('mysql', 'array');
                if (! in_array($database[$key], $allowedTypes)) {
                    $this->database_config[$key] = 'mysql';
                    continue;
                }
                $this->database_config[$key] = $database[$key];
            } else if (isset($database[$key])) {
                $this->database_config[$key] = $database[$key];
            }
        }

        foreach ($this->directory_config as $key => $value) {
            if (isset($directory[$key])) {
                $this->directory_config[$key] = $directory[$key];
            }
        }

        foreach ($this->heatmap_config as $key => $value) {
            if (isset($heatmap[$key])) {
                if ($key === 'width' || $key === 'height') {
                    continue;
                }
                $this->heatmap_config[$key] = $heatmap[$key];
            }
        }
        return false;
    }

    public function connect() {
        $dsn = "mysql:dbname={$this->database_config['database']};host={$this->database_config['hostname']}";
        try {
            $this->database_handler = new PDO($dsn, $this->database_config['username'], $this->database_config['password']);
        } catch (PDOException $e) {
            echo "Connection failed: {$e->getMessage()}";
        }
    }
    
    public function create_map($file) {
        if (! $file) {
            return false;
        }

        $this->map_handler = $this->imagecreatefromany("{$this->get_assets_images_directory()}$file");
        return ($this->get_image()) ? true : false;
    }

    public function apply_heatmap() {
        if (! $this->get_image()) {
            return false;
        }

        if ($this->database_config['type'] === 'mysql') {
            $this->connect();
            $statement = $this->get_database()->prepare("SELECT {$this->database_config['x_column']}, {$this->database_config['y_column']} FROM {$this->database_config['table']}");
            $statement->execute();
            $result = $statement->fetchAll(PDO::FETCH_NUM);
            $this->map_coordinates = $result;
        }

        $coordinates = $this->sanitaze_coordinates();


        $heatmap = new gd_heatmap($coordinates, $this->get_config());
        
        imagecopymerge($this->get_image(), $heatmap->get_image(), 0, 0, 0, 0, $this->heatmap_config['width'], $this->heatmap_config['height'], 75);
        return false;
    }

    public function output_map() {
        if (! $this->get_image()) {
            return false;
        }

        header("Content-Type: image/{$this->heatmap_config['format']}");
        imagepng($this->get_image());
    }

    public function free_map_memory() {
        if (imagedestroy($this->get_image())) {
            $this->map_handler = null;
            return true;
        }
    }

    public function load() {
        // Load every coordinate inside the $hidta_coordinates Array.
    }
    
    protected function get_database() {
        return $this->database_handler;
    }

    protected function get_image() {
        return $this->map_handler;
    }
    
    protected function get_config() {
        return $this->heatmap_config;
    }

    protected function get_coordinates() {
        return $this->map_coordinates;
    }

    protected function get_assets_classes_directory() {
        return $this->directory_config['assets_classes'];
    }

    protected function get_assets_images_directory() {
        return $this->directory_config['assets_images'];
    }

    protected function get_vendor_directory() {
        return $this->directory_config['vendor'];
    }

    protected function imagecreatefromany($file) {
        $imageInfo = getimagesize($file);
        $allowedTypes = array( 
            'image/jpeg',
            'image/png'
        );

        if (!in_array($imageInfo["mime"], $allowedTypes)) { 
            return false; 
        }

        $this->heatmap_config['width'] = $imageInfo[0];
        $this->heatmap_config['height'] = $imageInfo[1];

        switch ($imageInfo["mime"]) {
            case "image/jpeg": 
                return imagecreatefromjpeg($file); 
            break; 
            case "image/png": 
                return imagecreatefrompng($file); 
            break; 
        }
        return false;
    }

    protected function sanitaze_coordinates() {
        if (! $this->get_coordinates()) {
            return false;
        }

        $sanizated_coordinates = array();
    
        /*
            Converts the GTA San Andreas coordinates in order to be used in the map picture.
        */
        for($index = 0, $count = count($this->get_coordinates()); $index < $count; ++$index) {
            $this->map_coordinates[$index][0] = $this->map_coordinates[$index][0] / (6000 / $this->heatmap_config['width']);
            $this->map_coordinates[$index][1] = $this->map_coordinates[$index][1] / (6000 / $this->heatmap_config['height']);
            
            $this->map_coordinates[$index][0] = $this->map_coordinates[$index][0] + ($this->heatmap_config['width'] / 2);
            $this->map_coordinates[$index][1] = -($this->map_coordinates[$index][1] - ($this->heatmap_config['height'] / 2));
        
            $this->map_coordinates[$index][0] = intval(floor($this->map_coordinates[$index][0]));
            $this->map_coordinates[$index][1] = intval(floor($this->map_coordinates[$index][1]));
        }
        
        $values = $this->get_coordinates();
        
        /*
            This is hacky however it's needed for the heatmap — basically it implodes every map coordinate array.
            Then it compares the values and finds if some coordinates are repeated.
            This is done in order to show the "heat" differences in the map.
        */
        for($index = 0, $count = count($values); $index < $count; ++$index) {
            $values[$index] = implode(" ", $values[$index]);
        }

        $count = array_count_values($values);
        
        foreach($count as $key => $value) {
            $coords = explode(" ", $key);
            $coords[] = $value;
            $sanizated_coordinates[] = $coords;
        }
        return $sanizated_coordinates;
    }
}