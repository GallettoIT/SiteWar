#!/bin/bash

# Script per avviare l'ambiente Docker di Site War

echo "Avvio dell'ambiente Docker per Site War..."

# Crea le directory per i log se non esistono
mkdir -p docker/logs

# Costruisci e avvia il container
docker-compose up -d

# Verifica che il container sia avviato
if [ $? -eq 0 ]; then
  echo -e "\n✅ Ambiente Docker avviato con successo!"
  echo "📊 L'applicazione è disponibile all'indirizzo: http://localhost:8080"
  echo "📋 Logs disponibili in: docker/logs/"
  echo -e "\nPer fermare l'ambiente, eseguire: docker-compose down"
else
  echo -e "\n❌ Errore durante l'avvio dell'ambiente Docker."
  echo "Controlla i log per dettagli sull'errore."
fi