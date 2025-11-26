<?php
require __DIR__ . '/../config/redis.php';

$redis = redis_client();

// Guardar un valor
$redis->set('mensaje_bienvenida', 'Hola desde Redis 👋');

// Leer el valor
$valor = $redis->get('mensaje_bienvenida');

echo "Redis dice: " . $valor;
