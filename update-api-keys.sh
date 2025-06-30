#!/bin/bash

# Script per aggiornare le API keys di Site War

echo "🔑 Strumento di aggiornamento API keys per Site War"
echo "===================================================="

# Verificare se il file di configurazione esiste
CONFIG_FILE="server/config/api_keys.php"
if [ ! -f "$CONFIG_FILE" ]; then
  echo "File di configurazione non trovato. Creiamo una copia da api_keys.example.php."
  cp server/config/api_keys.example.php $CONFIG_FILE
fi

# Funzione per aggiornare una chiave API
update_key() {
  local key_name=$1
  local description=$2
  
  echo -e "\n📝 $description"
  read -p "Inserisci la chiave API per $key_name (lascia vuoto per mantenere il valore attuale): " new_key
  
  if [ -n "$new_key" ]; then
    # Estrai il valore attuale per determinare il formato (stringa o array)
    current_value=$(grep -A5 "'$key_name'" $CONFIG_FILE)
    
    if [[ $current_value == *"=>"* ]] && [[ $current_value != *"=>"*"["* ]]; then
      # È una stringa semplice
      sed -i "" "s|'$key_name' => '.*'|'$key_name' => '$new_key'|g" $CONFIG_FILE
    else
      echo "Il formato della chiave è complesso (array). Modifica manualmente il file $CONFIG_FILE."
    fi
    
    echo "✅ Chiave $key_name aggiornata."
  else
    echo "⏩ Nessuna modifica alla chiave $key_name."
  fi
}

# Aggiorna le varie chiavi API
update_key "pagespeed" "Google PageSpeed Insights API"
update_key "securityheaders" "Security Headers API (lascia vuoto se non necessaria)"
update_key "whois" "WHOIS API"
update_key "openai" "OpenAI API"

echo -e "\n🔐 Moz API richiede due valori. Modifica manualmente il file $CONFIG_FILE se necessario."

echo -e "\n✅ Aggiornamento API keys completato!"
echo "Verifica le modifiche nel file $CONFIG_FILE"