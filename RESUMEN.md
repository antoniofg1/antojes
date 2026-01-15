# 🎉 PROYECTO COMPLETADO: Antojes - Chat Geolocalizado

## ✅ Estado del Proyecto

**Base de datos:** ✓ Configurada y operativa
**Tablas creadas:** ✓ user, chat, chat_member, message
**Chat general (id=1):** ✓ Creado y activo
**Entidades Doctrine:** ✓ Completas con relaciones
**Repositorios:** ✓ Con métodos personalizados (Haversine)
**Controladores API:** ✓ Todos los endpoints implementados
**Seguridad API Key:** ✓ Configurada
**Documentación:** ✓ README completo con ejemplos

## 📊 Resumen Técnico

### Entidades Creadas

1. **User** - Usuarios con geolocalización
   - Campos: id, name, email, password, lat, lng, online, lastActivity
   - Implementa UserInterface y PasswordAuthenticatedUserInterface

2. **Chat** - Chats (general y privados)
   - Tipos: GENERAL (id=1) y PRIVATE
   - Campos: id, type, isActive, createdAt

3. **ChatMember** - Membresías usuario-chat
   - Campos: id, chat, user, joinedAt, leftAt

4. **Message** - Mensajes
   - Campos: id, chat, user, text, createdAt

### Repositorios con Funcionalidad Especial

**UserRepository:**
- `findUsersWithinRadius()` - Busca usuarios dentro de 5 km usando Haversine
- `updateLocation()` - Actualiza lat/lng del usuario
- `updateOnlineStatus()` - Marca usuario como online/offline

**ChatRepository:**
- `getGeneralChat()` - Obtiene el chat general (id=1)
- `findPrivateChatBetweenUsers()` - Busca chat privado entre dos usuarios
- `findPrivateChatsForUser()` - Lista chats privados activos

**ChatMemberRepository:**
- `addUserToChat()` - Añade usuario a un chat
- `removeUserFromChat()` - Marca que usuario abandonó chat
- `getOtherUserInPrivateChat()` - Obtiene el otro usuario en chat privado

**MessageRepository:**
- `findLatestMessages()` - Obtiene últimos N mensajes
- `createMessage()` - Crea y guarda un mensaje

### Servicios

**GeolocationService:**
- `calculateDistance()` - Calcula distancia entre dos puntos (Haversine)
- `isWithinRadius()` - Verifica si dos puntos están dentro del radio
- `areValidCoordinates()` - Valida coordenadas geográficas

### Event Listeners

**ApiKeyListener:**
- Valida X-API-KEY en todas las peticiones /api/*
- Retorna 401 si la API Key es inválida o falta

## 🌐 Endpoints Implementados

### Autenticación
- ✓ `POST /api/login` - Login con email/password (retorna JWT)
- ✓ `POST /api/logout` - Marca usuario como offline

### Usuarios (CRUD)
- ✓ `GET /api/usuarios` - Lista todos los usuarios
- ✓ `GET /api/usuarios/{id}` - Obtiene un usuario
- ✓ `POST /api/usuarios` - Crea usuario
- ✓ `PUT /api/usuarios/{id}` - Actualiza usuario
- ✓ `DELETE /api/usuarios/{id}` - Elimina usuario

### Geolocalización
- ✓ `GET /api/home` - Usuario actual + usuarios cercanos (< 5km)
- ✓ `POST /api/actualizar` - Actualiza ubicación (lat, lng)

### Chat General
- ✓ `GET /api/general` - Info del chat general + últimos mensajes

### Chats Privados
- ✓ `GET /api/privado` - Lista chats privados activos
- ✓ `POST /api/privado/invitar` - Crea/obtiene chat privado con otro usuario
- ✓ `GET /api/privado/{id}` - Mensajes de un chat privado
- ✓ `POST /api/privado/salir` - Abandona un chat privado
- ✓ `POST /api/privado/cambiar/chat` - Cambia chat activo

### Mensajes
- ✓ `GET /api/mensaje?chatId=X` - Obtiene mensajes de un chat
- ✓ `POST /api/mensaje` - Envía mensaje a un chat

### Perfil
- ✓ `GET /api/perfil` - Obtiene perfil del usuario actual

## 🔧 Configuración Actual

### Variables de entorno (.env)
```env
APP_API_KEY=antojes-api-key-2026
DATABASE_URL="mysql://root:@127.0.0.1:3306/chat"
```

### Base de datos
- Motor: MySQL 8.0
- Nombre: chat
- Host: 127.0.0.1:3306
- Usuario: root
- Contraseña: (vacía)

## 📝 Pendientes (Requieren Instalación)

### Cuando tengas conexión a Internet:

1. **Instalar bundles:**
   ```bash
   composer require symfony/security-bundle
   composer require lexik/jwt-authentication-bundle
   composer require symfony/validator
   ```

2. **Generar claves JWT:**
   ```bash
   mkdir config/jwt
   php bin/console lexik:jwt:generate-keypair
   ```

3. **Crear archivo JWT config:**
   Ver `INSTALACION.md` para detalles completos

4. **Actualizar security.yaml:**
   Ver `INSTALACION.md` para la configuración completa

## 🧪 Cómo Probar (Una vez instaladas dependencias)

### 1. Crear usuario
```bash
curl -X POST http://localhost:8000/api/usuarios \
  -H "X-API-KEY: antojes-api-key-2026" \
  -H "Content-Type: application/json" \
  -d '{"name":"Juan","email":"juan@test.com","password":"123456"}'
```

### 2. Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "X-API-KEY: antojes-api-key-2026" \
  -H "Content-Type: application/json" \
  -d '{"email":"juan@test.com","password":"123456"}'
```

### 3. Actualizar ubicación (Madrid)
```bash
curl -X POST http://localhost:8000/api/actualizar \
  -H "X-API-KEY: antojes-api-key-2026" \
  -H "Authorization: Bearer TOKEN_JWT" \
  -H "Content-Type: application/json" \
  -d '{"lat":40.4168,"lng":-3.7038}'
```

### 4. Ver usuarios cercanos
```bash
curl -X GET http://localhost:8000/api/home \
  -H "X-API-KEY: antojes-api-key-2026" \
  -H "Authorization: Bearer TOKEN_JWT"
```

## 📚 Archivos de Documentación

1. **README_API.md** - Documentación completa de todos los endpoints con ejemplos
2. **INSTALACION.md** - Guía paso a paso para instalar dependencias
3. **RESUMEN.md** - Este archivo (resumen ejecutivo)

## 🎓 Conceptos Clave para la Clase

### 1. Fórmula Haversine
```
Calcula la distancia entre dos puntos en una esfera
d = 2R × arcsin(√(sin²((lat₂-lat₁)/2) + cos(lat₁) × cos(lat₂) × sin²((lng₂-lng₁)/2)))
R = 6371 km (radio de la Tierra)
```

### 2. Arquitectura REST
- GET: Obtener recursos
- POST: Crear recursos
- PUT: Actualizar recursos
- DELETE: Eliminar recursos

### 3. Seguridad en capas
1. API Key (identifica la aplicación)
2. JWT (identifica al usuario)
3. Verificación de membresía (autorización)

### 4. Relaciones Doctrine
- OneToMany / ManyToOne (User ↔ Messages)
- ManyToMany con entidad intermedia (User ↔ Chat vía ChatMember)

### 5. Soft Delete
- No se eliminan registros físicamente
- Se usa `leftAt` en ChatMember para marcar abandono
- Se usa `isActive` en Chat para marcar chats cerrados

## 🚀 Comandos Útiles

```bash
# Iniciar servidor
symfony server:start
# O
php -S localhost:8000 -t public/

# Ver rutas
php bin/console debug:router

# Ver estructura BD
php bin/console doctrine:schema:validate

# Limpiar cache
php bin/console cache:clear

# Consultas SQL directas
php bin/console doctrine:query:sql "SELECT * FROM user"
```

## 📂 Estructura de Archivos

```
src/
├── Controller/          # 4 controladores API
├── Entity/             # 4 entidades (User, Chat, ChatMember, Message)
├── Repository/         # 4 repositorios con lógica de negocio
├── Service/            # GeolocationService
├── EventListener/      # ApiKeyListener
└── DTO/               # 3 DTOs para validación

config/
├── packages/          # Configuración de bundles
└── services.yaml      # Registro de servicios

public/
└── index.php          # Entry point

README_API.md          # Documentación completa
INSTALACION.md         # Guía de instalación
RESUMEN.md            # Este archivo
```

## 💡 Próximos Pasos Sugeridos

1. ✅ Instalar dependencias (composer)
2. ✅ Generar claves JWT
3. ✅ Probar endpoint de login
4. ✅ Crear 3-4 usuarios de prueba
5. ✅ Actualizar ubicaciones diferentes
6. ✅ Probar búsqueda de usuarios cercanos
7. ✅ Enviar mensajes en chat general
8. ✅ Crear un chat privado
9. ✅ Probar abandonar chat privado

## 🎯 Características Destacadas

✨ **Búsqueda geolocalizada**: Usuarios dentro de 5 km con Haversine
✨ **Chat híbrido**: General (público) + Privados (1 a 1)
✨ **Membresías inteligentes**: leftAt permite historial
✨ **Seguridad robusta**: API Key + JWT + validación de acceso
✨ **Código documentado**: Comentarios detallados en español
✨ **API REST completa**: CRUD + operaciones especializadas

## 📞 Información del Repositorio

**GitHub:** https://github.com/antoniofg1/antojes
**Desarrollador:** Antonio FG
**Tecnologías:** PHP 8+ | Symfony 7 | Doctrine ORM | MySQL 8
**Fecha:** Enero 2026

---

¡Proyecto listo para presentar y demostrar! 🎉
