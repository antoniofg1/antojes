<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository de User
 * Incluye método para buscar usuarios cercanos usando la fórmula Haversine
 * 
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Actualiza (rehashes) la contraseña del usuario automáticamente
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Encuentra usuarios dentro de un radio específico (en kilómetros)
     * usando la fórmula Haversine para calcular distancias en la Tierra
     * 
     * Fórmula Haversine:
     * a = sin²(Δlat/2) + cos(lat1) * cos(lat2) * sin²(Δlon/2)
     * c = 2 * atan2(√a, √(1−a))
     * d = R * c
     * 
     * Donde R es el radio de la Tierra (6371 km)
     * 
     * @param float $lat Latitud del punto de referencia
     * @param float $lng Longitud del punto de referencia
     * @param float $radiusKm Radio de búsqueda en kilómetros (default 5)
     * @param User|null $excludeUser Usuario a excluir de los resultados (usualmente el usuario actual)
     * @return User[] Array de usuarios dentro del radio especificado
     */
    public function findUsersWithinRadius(float $lat, float $lng, float $radiusKm = 5.0, ?User $excludeUser = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // Radio de la Tierra en kilómetros
        $earthRadius = 6371;
        
        $sql = "
            SELECT u.*, 
                   ( :earth_radius * acos(
                       cos(radians(:lat)) * 
                       cos(radians(u.lat)) * 
                       cos(radians(u.lng) - radians(:lng)) + 
                       sin(radians(:lat)) * 
                       sin(radians(u.lat))
                   )) AS distance
            FROM user u
            WHERE u.lat IS NOT NULL 
              AND u.lng IS NOT NULL
              AND u.online = 1
        ";
        
        $params = [
            'earth_radius' => $earthRadius,
            'lat' => $lat,
            'lng' => $lng,
        ];
        
        if ($excludeUser) {
            $sql .= " AND u.id != :exclude_id";
            $params['exclude_id'] = $excludeUser->getId();
        }
        
        $sql .= "
            HAVING distance <= :radius
            ORDER BY distance ASC
        ";
        
        $params['radius'] = $radiusKm;
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery($params);
        
        $users = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $user = $this->find($row['id']);
            if ($user) {
                // Agregamos la distancia como propiedad temporal
                $user->distance = round($row['distance'], 2);
                $users[] = $user;
            }
        }
        
        return $users;
    }

    /**
     * Encuentra un usuario por email
     */
    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Busca usuarios online
     */
    public function findOnlineUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.online = :online')
            ->setParameter('online', true)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Actualiza la ubicación de un usuario
     */
    public function updateLocation(User $user, float $lat, float $lng): void
    {
        $user->setLat((string)$lat);
        $user->setLng((string)$lng);
        $user->setLastActivity(new \DateTime());
        
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Marca un usuario como online/offline
     */
    public function updateOnlineStatus(User $user, bool $online): void
    {
        $user->setOnline($online);
        $user->setLastActivity(new \DateTime());
        
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
