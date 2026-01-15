# 🗨️ Antojes - Chat Geolocalizado API

Aplicación de chat geolocalizado desarrollada con **Symfony 7**, **Doctrine ORM** y **MySQL**.

## 📋 Descripción

Sistema de chat que permite:
- Visualizar usuarios dentro de un radio de 5 km
- Chat GENERAL donde todos los usuarios online pueden comunicarse
- Chats PRIVADOS temporales entre dos usuarios
- Seguridad mediante API Key + JWT (cuando se instalen las dependencias)

## 🏗️ Arquitectura

### Entidades Principales

```
User (usuarios)
├── id, name, email, password
├── lat, lng (geolocalización)
├── online (estado)
└── lastActivity

Chat (chats)
├── id, type (GENERAL|PRIVATE)
├── isActive
└── createdAt

ChatMember (membresías)
├── chat, user
├── joinedAt
└── leftAt (null si está activo)

Message (mensajes)
├── id, chat, user
├── text
└── createdAt
```

### Fórmula Haversine

Para calcular distancias entre coordenadas geográficas:

```
a = sin²(Δlat/2) + cos(lat1) * cos(lat2) * sin²(Δlon/2)
c = 2 * atan2(√a, √(1−a))
d = R * c  (R = 6371 km)
```

Implementada en:
- `UserRepository::findUsersWithinRadius()`
- `GeolocationService::calculateDistance()`

## 🔧 Configuración

### 1. Variables de entorno (.env)

```env
APP_ENV=dev
APP_SECRET=your-secret-key-here
APP_API_KEY=antojes-api-key-2026

DATABASE_URL="mysql://root:@127.0.0.1:3306/chat?serverVersion=8.0.32&charset=utf8mb4"

# JWT (cuando se instale lexik/jwt-authentication-bundle)
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=antojes
```

### 2. Instalar dependencias (cuando haya conexión)

```bash
composer require symfony/security-bundle
composer require lexik/jwt-authentication-bundle
composer require symfony/validator
```

### 3. Generar claves JWT

```bash
mkdir config/jwt
php bin/console lexik:jwt:generate-keypair
```

### 4. Crear base de datos y tablas

```bash
# Crear base de datos
php bin/console doctrine:database:create

# Actualizar schema
php bin/console doctrine:schema:update --force

# Crear chat general (id=1)
php bin/console doctrine:query:sql "INSERT INTO chat (id, type, is_active, created_at) VALUES (1, 'GENERAL', 1, NOW())"
```

## 📡 Endpoints API

### Headers requeridos

Todos los endpoints `/api/*` requieren:

```
X-API-KEY: antojes-api-key-2026
Authorization: Bearer <JWT_TOKEN>  (excepto /api/login)
```

---

### 🔐 Autenticación

#### POST /api/login
Solo requiere `X-API-KEY`. Retorna JWT token.

**Request:**
```json
{
  "email": "juan@example.com",
  "password": "123456"
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "..."
}
```

---

#### POST /api/logout
Marca al usuario como offline.

**Response:**
```json
{
  "message": "Sesión cerrada"
}
```

---

### 👤 Gestión de Usuarios

#### GET /api/usuarios
Lista todos los usuarios.

**Response:**
```json
[
  {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "online": true,
    "lat": "40.4168000",
    "lng": "-3.7038000"
  }
]
```

---

#### GET /api/usuarios/{id}
Obtiene un usuario específico.

**Response:**
```json
{
  "id": 1,
  "name": "Juan Pérez",
  "email": "juan@example.com",
  "online": true,
  "lat": "40.4168000",
  "lng": "-3.7038000",
  "lastActivity": "2026-01-15 14:30:00"
}
```

---

#### POST /api/usuarios
Crea un nuevo usuario.

**Request:**
```json
{
  "name": "María García",
  "email": "maria@example.com",
  "password": "123456"
}
```

**Response:**
```json
{
  "id": 2,
  "name": "María García",
  "email": "maria@example.com",
  "message": "Usuario creado exitosamente"
}
```

---

#### PUT /api/usuarios/{id}
Actualiza un usuario.

**Request:**
```json
{
  "name": "María García López",
  "email": "maria.garcia@example.com"
}
```

---

#### DELETE /api/usuarios/{id}
Elimina un usuario.

**Response:**
```json
{
  "message": "Usuario eliminado exitosamente"
}
```

---

### 🏠 Home y Geolocalización

#### GET /api/home
Retorna datos del usuario actual y usuarios cercanos (< 5 km).

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "online": true,
    "lat": "40.4168000",
    "lng": "-3.7038000"
  },
  "nearbyUsers": [
    {
      "id": 2,
      "name": "María García",
      "email": "maria@example.com",
      "online": true,
      "distance": 2.45
    },
    {
      "id": 3,
      "name": "Pedro López",
      "email": "pedro@example.com",
      "online": true,
      "distance": 4.87
    }
  ],
  "nearbyCount": 2
}
```

---

#### POST /api/actualizar
Actualiza la ubicación del usuario (lat, lng).

**Request:**
```json
{
  "lat": 40.4168,
  "lng": -3.7038
}
```

**Response:**
```json
{
  "message": "Ubicación actualizada",
  "lat": "40.41680000",
  "lng": "-3.70380000"
}
```

---

### 💬 Chat General

#### GET /api/general
Obtiene información del chat general y últimos mensajes.

**Response:**
```json
{
  "chat": {
    "id": 1,
    "type": "GENERAL",
    "isActive": true
  },
  "messages": [
    {
      "id": 1,
      "text": "Hola a todos!",
      "user": {
        "id": 1,
        "name": "Juan Pérez"
      },
      "createdAt": "2026-01-15 14:25:00"
    },
    {
      "id": 2,
      "text": "Hola Juan!",
      "user": {
        "id": 2,
        "name": "María García"
      },
      "createdAt": "2026-01-15 14:26:00"
    }
  ]
}
```

---

### 🔒 Chats Privados

#### GET /api/privado
Lista todos los chats privados activos del usuario.

**Response:**
```json
{
  "chats": [
    {
      "id": 2,
      "type": "PRIVATE",
      "isActive": true,
      "otherUser": {
        "id": 3,
        "name": "Pedro López",
        "email": "pedro@example.com",
        "online": true
      },
      "lastMessage": {
        "text": "Nos vemos mañana",
        "createdAt": "2026-01-15 15:00:00"
      }
    }
  ]
}
```

---

#### POST /api/privado/invitar
Crea un chat privado entre el usuario actual y otro usuario.
Si ya existe, retorna el existente.

**Request:**
```json
{
  "userId": 3
}
```

**Response:**
```json
{
  "message": "Chat privado creado",
  "chat": {
    "id": 2,
    "type": "PRIVATE",
    "isActive": true,
    "otherUser": {
      "id": 3,
      "name": "Pedro López",
      "email": "pedro@example.com"
    }
  }
}
```

---

#### GET /api/privado/{id}
Obtiene los mensajes de un chat privado específico.

**Response:**
```json
{
  "chat": {
    "id": 2,
    "type": "PRIVATE",
    "isActive": true,
    "otherUser": {
      "id": 3,
      "name": "Pedro López",
      "online": true
    }
  },
  "messages": [
    {
      "id": 5,
      "text": "Hola Pedro",
      "user": {
        "id": 1,
        "name": "Juan Pérez"
      },
      "createdAt": "2026-01-15 14:30:00"
    },
    {
      "id": 6,
      "text": "Hola Juan, ¿cómo estás?",
      "user": {
        "id": 3,
        "name": "Pedro López"
      },
      "createdAt": "2026-01-15 14:31:00"
    }
  ]
}
```

---

#### POST /api/privado/salir
El usuario abandona un chat privado.
Si ambos usuarios abandonan, el chat se marca como inactivo.

**Request:**
```json
{
  "chatId": 2
}
```

**Response:**
```json
{
  "message": "Has salido del chat"
}
```

---

#### POST /api/privado/cambiar/chat
Cambia el chat activo (útil para frontend).

**Request:**
```json
{
  "chatId": 2
}
```

**Response:**
```json
{
  "message": "Chat cambiado",
  "chatId": 2
}
```

---

### 📨 Mensajes

#### GET /api/mensaje
Obtiene mensajes de un chat.

**Query Params:**
- `chatId`: ID del chat (requerido)
- `limit`: Número de mensajes (default: 50)

**Example:** `GET /api/mensaje?chatId=1&limit=30`

**Response:**
```json
{
  "messages": [
    {
      "id": 1,
      "text": "Hola!",
      "user": {
        "id": 1,
        "name": "Juan Pérez"
      },
      "createdAt": "2026-01-15 14:25:00"
    }
  ],
  "count": 1
}
```

---

#### POST /api/mensaje
Envía un mensaje a un chat.

**Request:**
```json
{
  "chat_id": 1,
  "text": "Hola mundo!"
}
```

**Response:**
```json
{
  "message": "Mensaje enviado",
  "data": {
    "id": 10,
    "text": "Hola mundo!",
    "user": {
      "id": 1,
      "name": "Juan Pérez"
    },
    "createdAt": "2026-01-15 16:45:00"
  }
}
```

---

### 👤 Perfil

#### GET /api/perfil
Obtiene el perfil del usuario actual.

**Response:**
```json
{
  "id": 1,
  "name": "Juan Pérez",
  "email": "juan@example.com",
  "online": true,
  "lat": "40.4168000",
  "lng": "-3.7038000",
  "lastActivity": "2026-01-15 16:45:00"
}
```

---

## 🧪 Pruebas con curl

### 1. Crear un usuario

```bash
curl -X POST http://localhost:8000/api/usuarios \
  -H "X-API-KEY: antojes-api-key-2026" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "password": "123456"
  }'
```

### 2. Login (una vez instalado JWT)

```bash
curl -X POST http://localhost:8000/api/login \
  -H "X-API-KEY: antojes-api-key-2026" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan@example.com",
    "password": "123456"
  }'
```

### 3. Actualizar ubicación

```bash
curl -X POST http://localhost:8000/api/actualizar \
  -H "X-API-KEY: antojes-api-key-2026" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "lat": 40.4168,
    "lng": -3.7038
  }'
```

### 4. Ver usuarios cercanos

```bash
curl -X GET http://localhost:8000/api/home \
  -H "X-API-KEY: antojes-api-key-2026" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### 5. Enviar mensaje al chat general

```bash
curl -X POST http://localhost:8000/api/mensaje \
  -H "X-API-KEY: antojes-api-key-2026" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "chat_id": 1,
    "text": "Hola a todos!"
  }'
```

---

## 📁 Estructura del Proyecto

```
src/
├── Controller/
│   ├── ChatController.php         # /api/home, /api/general, /api/actualizar
│   ├── MessageController.php      # /api/mensaje, /api/perfil
│   ├── PrivateChatController.php  # /api/privado, /api/invitar
│   └── UserController.php         # /api/usuarios (CRUD)
│
├── Entity/
│   ├── User.php                   # Usuario con geolocalización
│   ├── Chat.php                   # Chat (GENERAL o PRIVATE)
│   ├── ChatMember.php             # Relación usuario-chat
│   └── Message.php                # Mensajes
│
├── Repository/
│   ├── UserRepository.php         # Incluye búsqueda por distancia (Haversine)
│   ├── ChatRepository.php         # Gestión de chats
│   ├── ChatMemberRepository.php   # Gestión de membresías
│   └── MessageRepository.php      # Gestión de mensajes
│
├── Service/
│   └── GeolocationService.php     # Servicio de cálculo de distancias
│
├── EventListener/
│   └── ApiKeyListener.php         # Valida X-API-KEY en todas las peticiones
│
└── DTO/
    ├── LoginRequest.php
    ├── UpdateLocationRequest.php
    └── SendMessageRequest.php
```

---

## 🔒 Seguridad

### Capas de seguridad:

1. **API Key (X-API-KEY header)**
   - Validada por `ApiKeyListener`
   - Requerida en TODOS los endpoints `/api/*`
   - Configurada en `.env` como `APP_API_KEY`

2. **JWT Authentication** (cuando se instale)
   - Login retorna JWT token
   - Token requerido en todos los endpoints excepto `/api/login`
   - Token válido por 1 hora (configurable)

3. **Verificación de membresía**
   - Los usuarios solo pueden acceder a chats donde son miembros
   - Validado en cada endpoint de chat/mensaje

---

## 🚀 Iniciar el servidor

```bash
# Servidor de desarrollo de Symfony
symfony server:start

# O con PHP
php -S localhost:8000 -t public/
```

La API estará disponible en: `http://localhost:8000/api`

---

## 📚 Conceptos para explicar en clase

### 1. Fórmula Haversine
- Calcula la distancia más corta entre dos puntos en una esfera
- Usa coordenadas geográficas (latitud y longitud)
- Implementada en SQL y PHP para optimización

### 2. Doctrine ORM
- Mapeo objeto-relacional
- Relaciones: OneToMany, ManyToOne
- Repositorios personalizados con consultas SQL

### 3. API REST
- Métodos HTTP: GET, POST, PUT, DELETE
- Estructura JSON request/response
- Códigos HTTP: 200, 201, 400, 401, 403, 404

### 4. Seguridad en APIs
- API Key para autenticación de aplicación
- JWT para autenticación de usuario
- Event Listeners de Symfony

### 5. Arquitectura de Chats
- Chat general con ID fijo (1)
- Chats privados temporales
- Sistema de membresías con leftAt

---

## 📝 Notas Importantes

1. **Chat General (id=1)**: Siempre debe existir. Se crea con:
   ```sql
   INSERT INTO chat (id, type, is_active, created_at) 
   VALUES (1, 'GENERAL', 1, NOW());
   ```

2. **Radio de búsqueda**: Por defecto 5 km, modificable en `GeolocationService`

3. **Chats privados inactivos**: Se marcan como `isActive=false` cuando ambos usuarios abandonan

4. **Usuarios online**: Se marca con el campo `online` y se actualiza con `/api/logout`

---

## ✅ Checklist de Instalación

- [ ] Instalar dependencias con composer
- [ ] Configurar `.env` con credenciales de BD
- [ ] Crear base de datos
- [ ] Ejecutar `doctrine:schema:update`
- [ ] Crear chat general (id=1)
- [ ] Generar claves JWT
- [ ] Probar endpoint `/api/usuarios` (crear usuario)
- [ ] Probar endpoint `/api/login`
- [ ] Probar endpoint `/api/home`

---

## 📞 Soporte

Para dudas sobre el proyecto:
- Revisar los comentarios en el código (están muy detallados)
- Consultar este README
- Revisar la documentación de Symfony: https://symfony.com/doc

---

**Desarrollado por:** Antonio FG  
**Repositorio:** https://github.com/antoniofg1/antojes  
**Fecha:** Enero 2026
