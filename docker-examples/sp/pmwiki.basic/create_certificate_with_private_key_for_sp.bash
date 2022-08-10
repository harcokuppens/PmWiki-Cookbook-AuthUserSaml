# create certificate for idp
# src: https://serverfault.com/questions/649990/non-interactive-creation-of-ssl-certificate-requests
# .pem is private key and .crt is certificate

mkdir -p cert/
openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out cert/sp.crt -keyout cert/sp.pem -subj "/C=NL/ST=Netherlands/L=Nijmegen/O=Radboud University/OU=ICIS SWS/CN=cs.ru.nl"
