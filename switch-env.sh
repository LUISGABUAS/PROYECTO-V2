#!/bin/bash
# Uso: ./switch-env.sh production | simulation

ENV=$1
BASE_DIR="$(cd "$(dirname "$0")" && pwd)"

if [ -z "$ENV" ]; then
    echo "Uso: ./switch-env.sh production | simulation"
    echo ""
    # Mostrar entorno actual
    if [ -f "$BASE_DIR/.env" ]; then
        CURRENT=$(grep "^APP_ENV=" "$BASE_DIR/.env" | cut -d= -f2)
        echo "Entorno actual: $CURRENT"
    fi
    exit 1
fi

SOURCE="$BASE_DIR/.env.$ENV"

if [ ! -f "$SOURCE" ]; then
    echo "Error: no existe el archivo .env.$ENV"
    exit 1
fi

cp "$SOURCE" "$BASE_DIR/.env"
echo "Entorno cambiado a: $ENV"
echo "Recuerda reiniciar Apache si es necesario."
