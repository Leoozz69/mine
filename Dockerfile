# Utiliza a imagem oficial do PHP com Apache
FROM php:7.4-apache

# Copia os arquivos do projeto para o diretório padrão do Apache
COPY . /var/www/html/

# Expor a porta 80 para o servidor web Apache
EXPOSE 80
