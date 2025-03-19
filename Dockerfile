FROM php:8.3-cli

# Atualiza pacotes e instala dependências básicas
RUN apt-get update && apt-get install -y \
    unzip \
    libaio1 \
    libpq-dev \
    wget \
    && docker-php-ext-install \
    pdo pdo_pgsql pgsql


RUN apt-get update && apt-get install -y inotify-tools    

# Instala o Oracle Instant Client
WORKDIR /tmp

# Copia os arquivos do Oracle Instant Client do host para o container
COPY ./instantclient-basic-linux.x64-19.26.0.0.0dbru.zip /tmp/
COPY ./instantclient-sdk-linux.x64-19.26.0.0.0dbru.zip /tmp/

# Descompacta e configura o Oracle Instant Client
RUN unzip -o /tmp/instantclient-basic-linux.x64-19.26.0.0.0dbru.zip -d /usr/local/ \
    && unzip -o /tmp/instantclient-sdk-linux.x64-19.26.0.0.0dbru.zip -d /usr/local/ \
    && ln -s /usr/local/instantclient_19_26 /usr/local/instantclient \
    && echo "/usr/local/instantclient" > /etc/ld.so.conf.d/oracle-instantclient.conf \
    && ldconfig

# Define variáveis de ambiente para OCI8
ENV ORACLE_HOME=/usr/local/instantclient
ENV LD_LIBRARY_PATH=/usr/local/instantclient
ENV PATH="${PATH}:/usr/local/instantclient"

# Instala OCI8 via PECL
RUN echo "instantclient,/usr/local/instantclient" | pecl install oci8 \
    && echo "extension=oci8.so" > /usr/local/etc/php/conf.d/oci8.ini

# Configuração da aplicação
WORKDIR /var/www

# Cria o diretório data/cache e define permissões
RUN mkdir -p /var/www/data/cache && chmod -R 777 /var/www/data/cache

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia o código da aplicação e instala dependências
COPY . /var/www
RUN composer install --no-dev --optimize-autoloader

COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Define o comando de execução do container
CMD ["sh", "-c", "/usr/local/bin/start.sh"]