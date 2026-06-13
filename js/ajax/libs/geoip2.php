<?php
/**
 * GeoIP2 Wrapper for MaxMind DB
 */
require_once 'reader.php';

class GeoIP2 {
    private $reader;
    private $dbPath;

    public function __construct($dbPath) {
        $this->dbPath = $dbPath;
    }

    public function getCityByIP($ip) {
        if (!file_exists($this->dbPath)) {
            return null;
        }

        try {
            if (!$this->reader) {
                $this->reader = new MMDBReader($this->dbPath);
            }
            
            $record = $this->reader->get($ip);
            
            // Güvenlik: Kayıt bir dizi (Map) olmalı
            if (!$record || !is_array($record)) {
                return null;
            }
            
            if (isset($record['city']['names']['tr'])) {
                return $record['city']['names']['tr'];
            }
            
            if (isset($record['city']['names']['en'])) {
                return $record['city']['names']['en'];
            }
            
            // Eğer şehir yoksa region'a bak (Bazen şehir boş, bölge dolu olabilir)
            if (isset($record['subdivisions'][0]['names']['en'])) {
                return $record['subdivisions'][0]['names']['en'];
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getMetadata() {
        if (!$this->reader) {
            try {
                $this->reader = new MMDBReader($this->dbPath);
            } catch (Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }
        $reflection = new ReflectionClass($this->reader);
        $property = $reflection->getProperty('metadata');
        $property->setAccessible(true);
        return $property->getValue($this->reader);
    }
}
