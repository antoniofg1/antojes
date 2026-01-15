# 📦 Instalación de Dependencias - Antojes

## Paso 1: Instalar Bundles de Symfony

Cuando tengas conexión a Internet, ejecuta estos comandos:

```bash
# Security Bundle (para autenticación y autorización)
composer require symfony/security-bundle

# JWT Authentication Bundle (para tokens JWT)
composer require lexik/jwt-authentication-bundle

# Validator (para validación de datos)
composer require symfony/validator

# Opcional pero recomendado
composer require symfony/serializer
```

## Paso 2: Generar claves JWT

```bash
# Crear directorio para las claves
mkdir config/jwt

# Generar par de claves (pública y privada)
php bin/console lexik:jwt:generate-keypair
```

Esto creará:
- `config/jwt/private.pem` (clave privada)
- `config/jwt/public.pem` (clave pública)

## Paso 3: Configurar JWT

Crea el archivo `config/packages/lexik_jwt_authentication.yaml`:

```yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600
```

## Paso 4: Actualizar Security.yaml

Reemplaza el contenido de `config/packages/security.yaml` con:

```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
        App\Entity\User:
            algorithm: auto

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        login:
            pattern: ^/api/login
            stateless: true
            json_login:
                check_path: /api/login
                username_path: email
                password_path: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure

        api:
            pattern: ^/api
            stateless: true
            jwt: ~

    access_control:
        - { path: ^/api/login, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }

when@test:
    security:
        password_hashers:
            Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface:
                algorithm: auto
                cost: 4
                time_cost: 3
                memory_cost: 10
```

## Paso 5: Verificar instalación

```bash
# Limpiar cache
php bin/console cache:clear

# Verificar que no hay errores
php bin/console about

# Listar rutas
php bin/console debug:router
```

## Paso 6: Probar la API

### Crear un usuario
```bash
curl -X POST http://localhost:8000/api/usuarios \
  -H "X-API-KEY: antojes-api-key-2026" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"123456"}'
```

### Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "X-API-KEY: antojes-api-key-2026" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"123456"}'
```

Esto te devolverá un JWT token que usarás en los siguientes requests:

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### Usar el token
```bash
curl -X GET http://localhost:8000/api/home \
  -H "X-API-KEY: antojes-api-key-2026" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

## Troubleshooting

### Error: "There is no extension able to load the configuration for security"

**Solución:** Instala el security-bundle:
```bash
composer require symfony/security-bundle
```

### Error: "There is no extension able to load the configuration for lexik_jwt_authentication"

**Solución:** Instala el JWT bundle:
```bash
composer require lexik/jwt-authentication-bundle
```

### Error: "Unable to load pem key"

**Solución:** Genera las claves JWT:
```bash
php bin/console lexik:jwt:generate-keypair
```

### Error: "Cannot autowire UserPasswordHasherInterface"

**Solución:** Asegúrate de que security-bundle está instalado y configurado correctamente.

## Estado actual del proyecto

✅ Base de datos creada y con schema actualizado
✅ Chat general (id=1) creado
✅ Entidades y repositorios implementados
✅ Controladores API completos
✅ API Key listener configurado
✅ Servicio de geolocalización implementado
⏳ Pendiente: Instalar bundles de seguridad y JWT
⏳ Pendiente: Generar claves JWT
⏳ Pendiente: Actualizar security.yaml

## Comandos útiles

```bash
# Ver rutas disponibles
php bin/console debug:router

# Ver servicios
php bin/console debug:container

# Limpiar cache
php bin/console cache:clear

# Ver estructura de base de datos
php bin/console doctrine:schema:validate

# Ver SQL de schema
php bin/console doctrine:schema:update --dump-sql

# Ejecutar consultas SQL
php bin/console doctrine:query:sql "SELECT * FROM user"
```

## Próximos pasos

1. Ejecutar los comandos de instalación cuando tengas Internet
2. Probar el endpoint de login
3. Crear algunos usuarios de prueba
4. Actualizar ubicaciones de usuarios
5. Probar la funcionalidad de usuarios cercanos
6. Probar envío de mensajes en chat general
7. Crear chats privados entre usuarios

¡Listo para usar! 🚀
