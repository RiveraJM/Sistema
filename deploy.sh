#!/usr/bin/env bash
set -euo pipefail

# RUTA: ejecuta desde la raíz del repo
echo "Iniciando deploy local con docker-compose..."

# Build/Rebuild images
docker compose build --pull --no-cache

# Levantar en background
docker compose up -d --remove-orphans

# Espera breve para que DB inicialice
echo "Esperando 10s para que la base de datos termine de arrancar..."
sleep 10

# Si hay archivo SQL en ./sql/init.sql lo importamos (opcional)
if [ -f ./sql/init.sql ]; then
  echo "Importando ./sql/init.sql a la base de datos..."
  docker exec -i sistema_db sh -c "exec mysql -u${MYSQL_USER:-sistema_user} -p${MYSQL_PASSWORD:-sistema_pass} ${MYSQL_DATABASE:-sistema_clinico}" < ./sql/init.sql || true
fi

echo "Deploy finalizado. Accede a: http://localhost:8080"
