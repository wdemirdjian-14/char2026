#!/usr/bin/env bash
# =========================================================
#  Bascule du site sur char2026.fr
#
#  À lancer EN ROOT SUR LE VPS, une fois que le DNS de
#  char2026.fr pointe bien sur 93.93.117.124 :
#      bash activer-char2026-fr.sh
#
#  Le script refuse de s'exécuter tant que le DNS n'est pas
#  en place, pour ne pas casser la configuration existante.
# =========================================================
set -euo pipefail

IP_ATTENDUE="93.93.117.124"
CONF=/etc/nginx/sites-available/char2026.walautao.fr
ATTENTE=/etc/nginx/sites-enabled/char2026.fr

echo "· Vérification du DNS…"
for h in char2026.fr www.char2026.fr; do
  ip=$(dig +short A "$h" | tail -1)
  if [ -z "$ip" ]; then
    echo "✖ $h ne résout pas encore. Rien n'a été modifié."
    echo "  Vérifiez chez votre registraire que le domaine est bien délégué"
    echo "  et qu'un enregistrement A pointe sur $IP_ATTENDUE."
    exit 1
  fi
  if [ "$ip" != "$IP_ATTENDUE" ]; then
    echo "✖ $h pointe sur $ip au lieu de $IP_ATTENDUE. Rien n'a été modifié."
    exit 1
  fi
  echo "  ✓ $h -> $ip"
done

echo "· Sauvegarde de la configuration…"
SAUVE=/root/nginx-sauvegarde-$(date +%Y%m%d-%H%M%S)
mkdir -p "$SAUVE"
cp "$CONF" "$SAUVE/"
[ -e "$ATTENTE" ] && cp "$(readlink -f "$ATTENTE")" "$SAUVE/" || true
echo "  $SAUVE"

echo "· Retrait du bloc d'attente…"
rm -f "$ATTENTE"

echo "· Ajout des noms au vhost…"
sed -i 's/^\(\s*server_name\) char2026\.walautao\.fr;/\1 char2026.walautao.fr char2026.fr www.char2026.fr;/' "$CONF"
grep -n "server_name" "$CONF"

echo "· Test nginx…"
if ! nginx -t; then
  echo "✖ Configuration invalide — restauration"
  cp "$SAUVE/$(basename "$CONF")" "$CONF"
  [ -f "$SAUVE/char2026.fr" ] && cp "$SAUVE/char2026.fr" /etc/nginx/sites-available/char2026.fr && ln -sf /etc/nginx/sites-available/char2026.fr "$ATTENTE"
  nginx -t && systemctl reload nginx
  exit 1
fi
systemctl reload nginx

echo "· Extension du certificat aux nouveaux noms…"
certbot --nginx --expand --non-interactive --agree-tos \
        --cert-name char2026.walautao.fr \
        -d char2026.walautao.fr -d char2026.fr -d www.char2026.fr

nginx -t && systemctl reload nginx

echo
echo "· Contrôle final…"
for u in https://char2026.fr/ https://www.char2026.fr/ https://char2026.walautao.fr/; do
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$u" || echo 000)
  printf "  %-34s %s\n" "$u" "$code"
done
echo
echo "✓ char2026.fr est actif. Les trois adresses servent le même site."
echo
echo "Pour faire de char2026.fr l'adresse canonique (et rediriger"
echo "char2026.walautao.fr vers elle), ajoutez dans le bloc 443 :"
echo '    if ($host = char2026.walautao.fr) { return 301 https://char2026.fr$request_uri; }'
