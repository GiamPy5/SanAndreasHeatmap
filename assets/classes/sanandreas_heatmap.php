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
    protected $map_coordinates   = array();
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

    public function apply_heatmap($coordinates = array()) {
        if (! $this->get_image()) {
            return false;
        }

        if ($this->database_config['type'] === 'mysql') {
            $this->connect();
            $statement = $this->get_database()->prepare("SELECT {$this->database_config['x_column']}, {$this->database_config['y_column']} FROM {$this->database_config['table']}");
            $statement->execute();
            $result = $statement->fetchAll(PDO::FETCH_NUM);
            $this->map_coordinates = $result;
        } else if ($this->database_config['type'] === 'array') {
            if (! $coordinates) {
                return false;
            } else {
                $this->map_coordinates = $coordinates;
            }
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