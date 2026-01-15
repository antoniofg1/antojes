<?php

namespace App\Service;

/**
 * Servicio de Geolocalización
 * 
 * Proporciona métodos para calcular distancias entre coordenadas geográficas
 * usando la fórmula Haversine
 * 
 * La fórmula Haversine calcula la distancia más corta entre dos puntos
 * en la superficie de una esfera (en este caso, la Tierra)
 */
class GeolocationService
{
    /**
     * Radio de la Tierra en kilómetros
     */
    private const EARTH_RADIUS_KM = 6371;

    /**
     * Radio máximo de búsqueda por defecto (en km)
     */
    private const DEFAULT_RADIUS_KM = 5.0;

    /**
     * Calcula la distancia entre dos puntos geográficos usando la fórmula Haversine
     * 
     * Fórmula:
     * a = sin²(Δlat/2) + cos(lat1) * cos(lat2) * sin²(Δlon/2)
     * c = 2 * atan2(√a, √(1−a))
     * d = R * c
     * 
     * Donde:
     * - lat1, lon1: coordenadas del primer punto
     * - lat2, lon2: coordenadas del segundo punto
     * - R: radio de la Tierra (6371 km)
     * - d: distancia entre los puntos
     * 
     * @param float $lat1 Latitud del primer punto
     * @param float $lng1 Longitud del primer punto
     * @param float $lat2 Latitud del segundo punto
     * @param float $lng2 Longitud del segundo punto
     * @return float Distancia en kilómetros
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        // Convertir grados a radianes
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        // Diferencias
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLng = $lng2Rad - $lng1Rad;

        // Fórmula Haversine
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Distancia = Radio de la Tierra * c
        $distance = self::EARTH_RADIUS_KM * $c;

        return round($distance, 2);
    }

    /**
     * Verifica si dos puntos están dentro del radio especificado
     * 
     * @param float $lat1 Latitud del primer punto
     * @param float $lng1 Longitud del primer punto
     * @param float $lat2 Latitud del segundo punto
     * @param float $lng2 Longitud del segundo punto
     * @param float $radiusKm Radio máximo en kilómetros
     * @return bool True si los puntos están dentro del radio
     */
    public function isWithinRadius(
        float $lat1, 
        float $lng1, 
        float $lat2, 
        float $lng2, 
        float $radiusKm = self::DEFAULT_RADIUS_KM
    ): bool {
        $distance = $this->calculateDistance($lat1, $lng1, $lat2, $lng2);
        return $distance <= $radiusKm;
    }

    /**
     * Valida que las coordenadas sean válidas
     * 
     * @param float $lat Latitud (-90 a 90)
     * @param float $lng Longitud (-180 a 180)
     * @return bool True si las coordenadas son válidas
     */
    public function areValidCoordinates(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    /**
     * Obtiene el radio por defecto de búsqueda
     * 
     * @return float Radio en kilómetros
     */
    public function getDefaultRadius(): float
    {
        return self::DEFAULT_RADIUS_KM;
    }
}
